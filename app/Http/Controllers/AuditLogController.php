<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    /**
     * List audit logs with pagination, filtering and formatted output.
     */
    public function index(Request $request): JsonResponse
    {
        if ($request->user()?->role !== 'admin') {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $query = AuditLog::query()->orderByDesc('created_at');

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        if ($action = $request->query('action')) {
            $query->where('action', 'like', "%{$action}%");
        }

        if ($userId = $request->query('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($fromDate = $request->query('from_date')) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate = $request->query('to_date')) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        $perPage = max(1, min(100, (int) $request->query('per_page', 20)));
        $result = $query->paginate($perPage);

        return response()->json([
            'data' => array_map(fn (AuditLog $log) => $this->format($log), $result->items()),
            'current_page' => $result->currentPage(),
            'last_page' => $result->lastPage(),
            'total' => $result->total(),
            'per_page' => $result->perPage(),
        ]);
    }

    /**
     * Create a new audit log entry.
     */
    public function store(Request $request): JsonResponse
    {
        if ($request->user()?->role !== 'admin') {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'actor_name' => ['nullable', 'string', 'max:255'],
            'action' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'auditable_type' => ['nullable', 'string', 'max:255'],
            'auditable_id' => ['nullable', 'integer'],
            'metadata' => ['nullable', 'array'],
            'ip_address' => ['nullable', 'string', 'max:45'],
        ]);

        $log = AuditLog::create($data);

        return response()->json($this->format($log), 201);
    }

    /**
     * Delete a single audit log entry (admin only).
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        if ($request->user()?->role !== 'admin') {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $log = AuditLog::find($id);

        if (! $log) {
            return response()->json(['error' => 'Audit log not found'], 404);
        }

        $log->delete();

        return response()->json(['message' => 'Audit log deleted.']);
    }

    /**
     * Delete all audit logs, optionally filtered by category (admin only).
     */
    public function clearAll(Request $request): JsonResponse
    {
        if ($request->user()?->role !== 'admin') {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $query = AuditLog::query();

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        $deleted = $query->delete();

        return response()->json(['message' => 'Audit logs cleared.', 'deleted' => $deleted]);
    }

    /**
     * Export audit logs as a branded CSV (same pattern as the credibility export).
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        if ($request->user()?->role !== 'admin') {
            abort(403, 'Forbidden');
        }

        $query = AuditLog::query()->orderByDesc('created_at');

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        if ($action = $request->query('action')) {
            $query->where('action', 'like', "%{$action}%");
        }

        if ($userId = $request->query('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($fromDate = $request->query('from_date')) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate = $request->query('to_date')) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        $logs = $query->get();

        $fileName = 'agriaid-audit-logs-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        $callback = function () use ($logs) {
            $handle = fopen('php://output', 'w');
            // BOM for Excel UTF-8
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // agriAid branding comment lines
            fputcsv($handle, ['# agriAid Platform — Audit Log Export']);
            fputcsv($handle, ['# Empowering Cameroon\'s Agricultural Future']);
            fputcsv($handle, ['# Generated: ' . now()->format('Y-m-d H:i:s')]);
            fputcsv($handle, ['# Total records: ' . $logs->count()]);
            fputcsv($handle, []);

            fputcsv($handle, ['Date', 'Actor', 'Action', 'Category', 'Auditable Type', 'Auditable ID', 'IP Address', 'Metadata']);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->created_at?->toDateTimeString(),
                    $log->actor_name ?? 'System',
                    $log->action,
                    $log->category ?? 'system',
                    $log->auditable_type ?? '',
                    $log->auditable_id ?? '',
                    $log->ip_address ?? '',
                    $log->metadata ? json_encode($log->metadata) : '',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export audit logs as a branded PDF with agriAid logo.
     * GET /api/audit-logs/export-pdf
     */
    public function exportPdf(Request $request)
    {
        if ($request->user()?->role !== 'admin') {
            abort(403, 'Forbidden');
        }

        $query = AuditLog::query()->orderByDesc('created_at');

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        if ($action = $request->query('action')) {
            $query->where('action', 'like', "%{$action}%");
        }

        if ($userId = $request->query('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($fromDate = $request->query('from_date')) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate = $request->query('to_date')) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        $logs = $query->limit(500)->get();
        $generatedAt = now()->format('Y-m-d H:i');

        $pdf = Pdf::loadView('exports.audit-log', [
            'logs' => $logs,
            'generatedAt' => $generatedAt,
            'filters' => [
                'category' => $request->query('category'),
                'action' => $request->query('action'),
                'from_date' => $request->query('from_date'),
                'to_date' => $request->query('to_date'),
            ],
        ]);

        $pdf->setPaper('A4', 'landscape');

        return $pdf->download('agriaid-audit-logs-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Format an audit log entry for API output.
     *
     * @return array<string, mixed>
     */
    private function format(AuditLog $log): array
    {
        return [
            'id' => (string) $log->id,
            'user_id' => $log->user_id,
            'actor_name' => $log->actor_name,
            'action' => $log->action,
            'category' => $log->category,
            'auditable_type' => $log->auditable_type,
            'auditable_id' => $log->auditable_id,
            'metadata' => $log->metadata,
            'ip_address' => $log->ip_address,
            'created_at' => $log->created_at?->toIso8601String(),
            'formatted_date' => $log->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
