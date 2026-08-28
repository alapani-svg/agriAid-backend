<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstitutionController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Institution::orderBy('name')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
            'name' => 'required|string|max:255',
            'registration_number' => 'nullable|string|max:255|unique:institutions,registration_number',
            'type' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:255',
        ]);

        $institution = Institution::create($data);

        AuditLogger::log(
            action: 'institution.created',
            category: 'system',
            metadata: ['institution_id' => $institution->id, 'name' => $institution->name],
        );

        return response()->json($institution, 201);
    }

    public function show(string $id): JsonResponse
    {
        $institution = Institution::findOrFail($id);

        return response()->json($institution);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $institution = Institution::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'registration_number' => ['nullable', 'string', 'max:255', 'unique:institutions,registration_number,' . $id],
            'type' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:255',
        ]);

        $institution->update($data);

        AuditLogger::log(
            action: 'institution.updated',
            category: 'system',
            metadata: ['institution_id' => $institution->id, 'name' => $institution->name, 'changes' => $data],
        );

        return response()->json($institution);
    }

    public function destroy(string $id): JsonResponse
    {
        $institution = Institution::find($id);
        if ($institution === null) {
            return response()->json(['error' => 'Institution not found'], 404);
        }

        $name = $institution->name;
        $institution->delete();

        AuditLogger::log(
            action: 'institution.deleted',
            category: 'system',
            metadata: ['institution_id' => $id, 'name' => $name],
        );

        return response()->json(['message' => 'Institution deleted']);
    }

    /**
     * Simulate a loan approval for an institution.
     * Returns a simple-interest calculation without persisting any record.
     */
    public function simulateLoan(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'amount_fcfa' => ['required', 'numeric', 'min:1000'],
            'farmer_name' => ['nullable', 'string', 'max:255'],
            'term_months' => ['nullable', 'integer', 'min:1', 'max:240'],
        ]);

        $institution = Institution::findOrFail($id);
        $amount = (float) $data['amount_fcfa'];
        $termMonths = (int) ($data['term_months'] ?? 12);

        // Simulate a simple interest calculation
        $annualRate = 0.08; // 8% annual
        $monthlyRate = $annualRate / 12;
        $totalInterest = $amount * $monthlyRate * $termMonths;
        $totalRepayable = $amount + $totalInterest;
        $monthlyPayment = $totalRepayable / $termMonths;

        AuditLogger::log(
            action: 'institution.loan_simulated',
            category: 'loan',
            metadata: [
                'institution_id' => $institution->id,
                'institution_name' => $institution->name,
                'amount_fcfa' => $amount,
                'term_months' => $termMonths,
                'farmer_name' => $data['farmer_name'] ?? null,
            ],
        );

        return response()->json([
            'institution' => $institution->name,
            'institution_type' => $institution->type,
            'principal_fcfa' => $amount,
            'annual_rate' => $annualRate,
            'term_months' => $termMonths,
            'total_interest_fcfa' => round($totalInterest),
            'total_repayable_fcfa' => round($totalRepayable),
            'monthly_payment_fcfa' => round($monthlyPayment),
            'simulated_at' => now()->toIso8601String(),
            'status' => 'SIMULATED APPROVAL',
        ]);
    }
}
