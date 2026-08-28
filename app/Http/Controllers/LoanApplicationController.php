<?php

namespace App\Http\Controllers;

use App\Models\LoanApplication;
use App\Models\LoanReminder;
use App\Models\Buyer;
use App\Models\Notification;
use App\Models\User;
use App\Http\Resources\LoanApplicationResource;
use App\Mail\BrandedNotification;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class LoanApplicationController extends Controller
{
    private const FCFA_PER_USD = 610;

    /**
     * Canonical loan lifecycle statuses.
     */
    private const STATUSES = [
        'pending',
        'under_review',
        'approved',
        'rejected',
        'disbursed',
        'repaid',
        'defaulted',
    ];

    /**
     * Allowed forward status transitions.
     */
    private const TRANSITIONS = [
        'pending' => ['under_review', 'approved', 'rejected'],
        'under_review' => ['approved', 'rejected', 'pending'],
        'approved' => ['disbursed', 'rejected'],
        'rejected' => ['pending'],
        'disbursed' => ['repaid', 'defaulted'],
        'repaid' => [],
        'defaulted' => ['repaid'],
    ];

    public function index(): JsonResponse
    {
        return response()->json(LoanApplicationResource::collection(
            LoanApplication::orderByDesc('created_at')->get()
        ));
    }

    public function show(LoanApplication $loanApplication): JsonResponse
    {
        return response()->json(new LoanApplicationResource($loanApplication));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'buyer_id' => 'nullable|integer|exists:buyers,id',
            'institution_id' => 'nullable|integer|exists:institutions,id',
            'buyer_name' => 'required|string|max:255',
            'institution_name' => 'required|string|max:255',
            'warehouse_receipt_id' => 'nullable|string|exists:warehouse_receipts,id',
            'requested_amount_fcfa' => 'required|integer|min:1',
            'requested_amount_usd' => 'nullable|numeric|min:0',
            'term_months' => 'required|integer|min:1|max:240',
            'score' => 'nullable|integer|min:0|max:100',
            'status' => 'nullable|string|in:pending,approved,rejected,Active,Pending Review,Repaid,Rejected',
            'repayment_schedule' => 'nullable|array',
        ]);

        $data['requested_amount_usd'] = $data['requested_amount_usd'] ?? round($data['requested_amount_fcfa'] / self::FCFA_PER_USD, 2);
        $data['principal_usd'] = $data['principal_usd'] ?? $data['requested_amount_usd'];
        $data['repayment_schedule'] = $data['repayment_schedule'] ?? $this->buildSchedule($data['requested_amount_fcfa'], $data['term_months']);

        $loanApplication = LoanApplication::create($data);

        return response()->json(new LoanApplicationResource($loanApplication), 201);
    }

    public function update(Request $request, LoanApplication $loanApplication): JsonResponse
    {
        $data = $request->validate([
            'buyer_id' => 'nullable|integer|exists:buyers,id',
            'institution_id' => 'nullable|integer|exists:institutions,id',
            'buyer_name' => 'sometimes|string|max:255',
            'institution_name' => 'sometimes|string|max:255',
            'warehouse_receipt_id' => 'nullable|string|exists:warehouse_receipts,id',
            'requested_amount_fcfa' => 'sometimes|integer|min:1',
            'requested_amount_usd' => 'nullable|numeric|min:0',
            'term_months' => 'sometimes|integer|min:1|max:240',
            'score' => 'nullable|integer|min:0|max:100',
            'status' => 'nullable|string|in:pending,approved,rejected,Active,Pending Review,Repaid,Rejected',
            'repayment_schedule' => 'nullable|array',
        ]);

        if (array_key_exists('requested_amount_fcfa', $data) && !array_key_exists('requested_amount_usd', $data)) {
            $data['requested_amount_usd'] = round($data['requested_amount_fcfa'] / self::FCFA_PER_USD, 2);
        }

        if (!array_key_exists('requested_amount_fcfa', $data) && array_key_exists('requested_amount_usd', $data)) {
            $data['requested_amount_fcfa'] = (int) round($data['requested_amount_usd'] * self::FCFA_PER_USD);
        }

        if (array_key_exists('requested_amount_fcfa', $data) || array_key_exists('term_months', $data)) {
            $amount = $data['requested_amount_fcfa'] ?? $loanApplication->requested_amount_fcfa;
            $months = $data['term_months'] ?? $loanApplication->term_months;
            $data['repayment_schedule'] = $data['repayment_schedule'] ?? $this->buildSchedule($amount, $months);
        }

        $loanApplication->update($data);
        $loanApplication->principal_usd = $loanApplication->principal_usd ?? $loanApplication->requested_amount_usd;
        $loanApplication->save();

        return response()->json(new LoanApplicationResource($loanApplication));
    }

    public function destroy(LoanApplication $loanApplication): JsonResponse
    {
        $loanApplication->delete();

        return response()->json(null, 204);
    }

    /**
     * Admin: list all loan applications with pagination, filtering and search.
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $query = LoanApplication::query()->with(['buyer:id,user_id,name,contact_email', 'buyer.user:id,name,email']);

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('buyer_name', 'like', "%{$search}%")
                    ->orWhere('institution_name', 'like', "%{$search}%")
                    ->orWhere('purpose', 'like', "%{$search}%")
                    ->orWhereHas('buyer', fn ($b) => $b
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('contact_email', 'like', "%{$search}%"))
                    ->orWhereHas('buyer.user', fn ($u) => $u
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->query('status')) {
            // Match both the canonical status and any legacy equivalents stored in the DB.
            $variants = $this->statusVariants($status);
            $query->whereIn('status', $variants);
        }

        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));
        $loans = $query->orderByDesc('created_at')->paginate($perPage);

        // Status summary counts (across the full dataset, not just the current page).
        // Legacy status values are normalised into the canonical lifecycle set.
        $rawCounts = LoanApplication::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $summary = collect(self::STATUSES)
            ->mapWithKeys(fn (string $status) => [$status => 0])
            ->toArray();

        foreach ($rawCounts as $rawStatus => $total) {
            $canonical = $this->canonicalStatus($rawStatus);
            $summary[$canonical] = ($summary[$canonical] ?? 0) + (int) $total;
        }

        return response()->json([
            'data' => $loans->getCollection()->map(fn (LoanApplication $loan) => $this->adminPayload($loan))->values(),
            'summary' => $summary,
            'meta' => [
                'current_page' => $loans->currentPage(),
                'last_page' => $loans->lastPage(),
                'per_page' => $loans->perPage(),
                'total' => $loans->total(),
            ],
        ]);
    }

    /**
     * Admin: show a single loan application with full details.
     */
    public function adminShow(string $id): JsonResponse
    {
        $loan = LoanApplication::with(['buyer.user:id,name,email', 'reminders.admin:id,name'])
            ->find($id);

        if (! $loan) {
            return response()->json(['error' => 'Loan application not found'], 404);
        }

        return response()->json($this->adminPayload($loan, includeReminders: true));
    }

    /**
     * Admin: update the loan status with transition validation and audit logging.
     */
    public function adminUpdateStatus(Request $request, string $id): JsonResponse
    {
        $loan = LoanApplication::find($id);

        if (! $loan) {
            return response()->json(['error' => 'Loan application not found'], 404);
        }

        $data = $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', self::STATUSES)],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $current = $this->canonicalStatus($loan->status);
        $target = $data['status'];

        if ($current === $target) {
            return response()->json(['error' => 'Loan is already in that status'], 422);
        }

        $allowed = self::TRANSITIONS[$current] ?? [];
        if (! in_array($target, $allowed, true)) {
            return response()->json([
                'error' => "Invalid status transition from {$current} to {$target}",
                'allowed' => $allowed,
            ], 422);
        }

        $previous = $loan->status;
        $loan->status = $target;
        $loan->save();

        AuditLogger::log(
            action: 'loan.status_updated',
            category: 'loan',
            metadata: [
                'loan_id' => $loan->id,
                'from' => $previous,
                'to' => $target,
                'note' => $data['note'] ?? null,
            ],
            auditableType: LoanApplication::class,
            auditableId: (int) $loan->id,
        );

        $fresh = LoanApplication::with(['buyer.user:id,name,email'])->find($loan->id);

        return response()->json($this->adminPayload($fresh));
    }

    /**
     * Admin: add a loan reminder — creates an in-app notification for the
     * borrower and sends a branded e-mail. The reminder is also persisted.
     */
    public function adminAddReminder(Request $request, string $id): JsonResponse
    {
        $loan = LoanApplication::with(['buyer.user:id,name,email'])->find($id);

        if (! $loan) {
            return response()->json(['error' => 'Loan application not found'], 404);
        }

        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'type' => ['nullable', 'string', 'in:payment_reminder,status_update,general'],
        ]);

        $type = $data['type'] ?? 'payment_reminder';
        $message = $data['message'];
        $title = match ($type) {
            'status_update' => 'Loan status update',
            'general' => 'Loan notification',
            default => 'Loan payment reminder',
        };

        $recipient = $this->resolveBorrowerUser($loan);
        $recipientName = $loan->buyer_name ?? ($recipient?->name ?? 'Borrower');
        $recipientEmail = $recipient?->email ?? $loan->buyer?->contact_email ?? null;

        // Persist the reminder record.
        $reminder = LoanReminder::create([
            'loan_application_id' => $loan->id,
            'admin_id' => $request->user()->id,
            'message' => $message,
            'type' => $type,
            'sent_at' => now(),
        ]);

        // Create an in-app notification when we can resolve a user account.
        if ($recipient) {
            Notification::create([
                'id' => (string) Str::uuid(),
                'user_id' => $recipient->id,
                'type' => 'loan.reminder',
                'title' => $title,
                'message' => $message,
                'deep_link' => '/dashboard/buyer',
                'idempotency_key' => 'loan-reminder-' . $reminder->id,
                'status' => 'sent',
                'delivered_at' => now(),
            ]);
        }

        // Send a branded e-mail when an address is available.
        if ($recipientEmail) {
            try {
                Mail::to($recipientEmail)->send(
                    new BrandedNotification(
                        title: $title,
                        body: $message,
                        recipientName: $recipientName,
                    )
                );
            } catch (\Throwable $e) {
                Log::error('Failed to send loan reminder e-mail', [
                    'loan_id' => $loan->id,
                    'email' => $recipientEmail,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        AuditLogger::log(
            action: 'loan.reminder_sent',
            category: 'loan',
            metadata: [
                'loan_id' => $loan->id,
                'reminder_id' => $reminder->id,
                'type' => $type,
                'recipient' => $recipientEmail ?? $recipientName,
            ],
            auditableType: LoanApplication::class,
            auditableId: (int) $loan->id,
        );

        return response()->json([
            'message' => 'Reminder sent successfully.',
            'data' => [
                'id' => (string) $reminder->id,
                'loan_application_id' => (string) $loan->id,
                'message' => $reminder->message,
                'type' => $reminder->type,
                'sent_at' => $reminder->sent_at?->toIso8601String(),
                'recipient' => $recipientEmail ?? $recipientName,
            ],
        ], 201);
    }

    /**
     * Resolve the borrower's User account for a loan application (if any).
     */
    private function resolveBorrowerUser(LoanApplication $loan): ?User
    {
        if ($loan->buyer_id) {
            $buyer = $loan->buyer ?? Buyer::find($loan->buyer_id);
            if ($buyer && $buyer->user_id) {
                return User::find($buyer->user_id);
            }
        }

        // Fall back to matching by buyer name.
        if ($loan->buyer_name) {
            return User::where('name', $loan->buyer_name)->first();
        }

        return null;
    }

    /**
     * Normalise legacy status values into the canonical lifecycle set.
     */
    private function canonicalStatus(string $status): string
    {
        return match (strtolower(trim($status))) {
            'pending review', 'active' => 'pending',
            'pending' => 'pending',
            'under_review' => 'under_review',
            'approved' => 'approved',
            'rejected' => 'rejected',
            'disbursed' => 'disbursed',
            'repaid' => 'repaid',
            'defaulted' => 'defaulted',
            default => 'pending',
        };
    }

    /**
     * Return all DB-stored status variants that map to a canonical status,
     * so filtering works for both legacy and canonical values.
     *
     * @return string[]
     */
    private function statusVariants(string $canonical): array
    {
        return match ($canonical) {
            'pending' => ['pending', 'Pending Review', 'Active', 'Pending review'],
            'under_review' => ['under_review', 'Under Review', 'Under review'],
            'approved' => ['approved', 'Approved'],
            'rejected' => ['rejected', 'Rejected'],
            'disbursed' => ['disbursed', 'Disbursed'],
            'repaid' => ['repaid', 'Repaid'],
            'defaulted' => ['defaulted', 'Defaulted'],
            default => [$canonical],
        };
    }

    /**
     * Admin payload shape for loan applications.
     *
     * @return array<string, mixed>
     */
    private function adminPayload(LoanApplication $loan, bool $includeReminders = false): array
    {
        $buyer = $loan->relationLoaded('buyer') ? $loan->buyer : null;
        $user = $buyer && $buyer->relationLoaded('user') ? $buyer->user : null;

        $payload = [
            'id' => (string) $loan->id,
            'applicant_name' => $loan->buyer_name ?? ($user?->name ?? $buyer?->name),
            'applicant_email' => $user?->email ?? ($buyer?->contact_email ?? null),
            'amount' => (float) ($loan->requested_amount_fcfa ?? round(($loan->principal_usd ?? $loan->requested_amount_usd ?? 0) * 655.957)),
            'currency' => 'FCFA',
            'amount_usd' => (float) ($loan->requested_amount_usd ?? $loan->principal_usd ?? 0),
            'status' => $this->canonicalStatus($loan->status ?? 'pending'),
            'purpose' => $loan->purpose,
            'term_months' => (int) ($loan->term_months ?? 0),
            'term_years' => (int) ($loan->term_years ?? (int) floor(($loan->term_months ?? 0) / 12) ?: 0),
            'interest_rate' => (float) ($loan->interest_rate_apr ?? 0),
            'institution_name' => $loan->institution_name,
            'cig_affiliation' => $loan->cig_affiliation,
            'collateral_cert_no' => $loan->collateral_cert_no,
            'score' => $loan->score,
            'monthly_repayment_fcfa' => (float) round(($loan->monthly_repayment_usd ?? 0) * 655.957),
            'amount_paid_fcfa' => (float) round(($loan->amount_paid_usd ?? 0) * 655.957),
            'next_due_date' => $loan->next_due_date,
            'repayment_schedule' => $loan->repayment_schedule,
            'created_at' => $loan->created_at?->toIso8601String(),
            'updated_at' => $loan->updated_at?->toIso8601String(),
        ];

        if ($includeReminders) {
            $payload['reminders'] = $loan->relationLoaded('reminders')
                ? $loan->reminders->map(fn (LoanReminder $r) => [
                    'id' => (string) $r->id,
                    'message' => $r->message,
                    'type' => $r->type,
                    'sent_at' => $r->sent_at?->toIso8601String(),
                    'admin' => $r->relationLoaded('admin') && $r->admin ? ['id' => (string) $r->admin->id, 'name' => $r->admin->name] : null,
                ])->values()
                : [];
        }

        return $payload;
    }

    private function buildSchedule(int $amountFcfa, int $months): array
    {
        $monthly = (int) round($amountFcfa / $months);
        return array_map(fn ($index) => [
            'month' => $index + 1,
            'due_fcfa' => $monthly,
            'paid' => false,
        ], range(0, $months - 1));
    }
}
