<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\Farmer;
use App\Models\Stock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $farmer = Farmer::query()->where('user_id', $user->id)->first();

        if (! $farmer) {
            return response()->json([
                'stocks' => [],
                'totals' => ['quantity_kg' => 0, 'lines' => 0],
            ]);
        }

        $stocks = Stock::query()
            ->where('farmer_id', $farmer->id)
            ->orderBy('crop')
            ->get()
            ->map(fn (Stock $s) => [
                'id' => $s->id,
                'crop' => $s->crop,
                'quantity_kg' => (float) $s->quantity_kg,
                'unit' => $s->unit,
                'location' => $s->location,
                'updated_at' => $s->updated_at?->toIso8601String(),
            ]);

        return response()->json([
            'stocks' => $stocks,
            'totals' => [
                'quantity_kg' => $stocks->sum('quantity_kg'),
                'lines' => $stocks->count(),
            ],
        ]);
    }
}
