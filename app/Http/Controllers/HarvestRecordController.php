<?php

namespace App\Http\Controllers;

use App\Models\HarvestRecord;
use App\Http\Resources\HarvestRecordResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HarvestRecordController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(HarvestRecordResource::collection(
            HarvestRecord::orderByDesc('created_at')->get()
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
            'crop' => 'required|string|max:255',
            'mass_kg' => 'required|numeric|min:0',
            'quality_pct' => 'required|numeric|min:0|max:100',
            'price_per_kg' => 'nullable|numeric|min:0',
            'sell_on_market' => 'nullable|boolean',
            'crop_image' => 'nullable|string',
            'market_location' => 'nullable|string|max:255',
            'asking_price_per_mt' => 'nullable|integer|min:0',
            'status' => 'nullable|string|in:VERIFIED,IN TRANSIT,PROCESSING',
            'harvest_date' => 'nullable|date',
        ]);

        $data['status'] = $data['status'] ?? 'VERIFIED';
        $data['harvest_date'] = $data['harvest_date'] ?? now();

        return response()->json(new HarvestRecordResource(HarvestRecord::create($data)), 201);
    }
}
