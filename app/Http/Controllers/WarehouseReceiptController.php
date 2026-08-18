<?php

namespace App\Http\Controllers;

use App\Models\WarehouseReceipt;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WarehouseReceiptController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(WarehouseReceipt::orderByDesc('verified_at')->get());
    }

    public function show(WarehouseReceipt $warehouseReceipt): JsonResponse
    {
        return response()->json($warehouseReceipt);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'farmer_name' => 'required|string|max:255',
            'crop_type' => 'required|string|max:255',
            'quantity_mt' => 'required|integer|min:1',
            'location' => 'required|string|max:255',
            'verified_at' => 'nullable|date',
            'qr_code' => 'nullable|string|max:1024',
        ]);

        return response()->json(WarehouseReceipt::create($data), 201);
    }

    public function update(Request $request, WarehouseReceipt $warehouseReceipt): JsonResponse
    {
        $data = $request->validate([
            'farmer_name' => 'sometimes|string|max:255',
            'crop_type' => 'sometimes|string|max:255',
            'quantity_mt' => 'sometimes|integer|min:1',
            'location' => 'sometimes|string|max:255',
            'verified_at' => 'nullable|date',
            'qr_code' => 'nullable|string|max:1024',
        ]);

        $warehouseReceipt->update($data);

        return response()->json($warehouseReceipt);
    }

    public function destroy(WarehouseReceipt $warehouseReceipt): JsonResponse
    {
        $warehouseReceipt->delete();

        return response()->json(null, 204);
    }
}
