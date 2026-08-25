<?php

namespace App\Http\Controllers;

use App\Models\RegionalReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegionalReportController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(RegionalReport::orderByDesc('created_at')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'institution_id' => 'nullable|integer|exists:institutions,id',
            'region' => 'required|string|max:255',
            'city' => 'nullable|string|max:255',
            'report_type' => 'nullable|string|max:255',
            'period_start' => 'nullable|date',
            'period_end' => 'nullable|date',
            'total_production_mt' => 'nullable|numeric|min:0',
            'warehouse_stock_mt' => 'nullable|numeric|min:0',
            'financing_volume_fcfa' => 'nullable|numeric|min:0',
            'active_farmers' => 'nullable|integer|min:0',
            'metrics' => 'nullable|array',
        ]);

        return response()->json(RegionalReport::create($data), 201);
    }
}
