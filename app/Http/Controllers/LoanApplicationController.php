<?php

namespace App\Http\Controllers;

use App\Models\LoanApplication;
use App\Http\Resources\LoanApplicationResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LoanApplicationController extends Controller
{
    private const FCFA_PER_USD = 610;

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
