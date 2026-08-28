<?php

namespace App\Http\Controllers;

use App\Models\MarketPrice;
use App\Services\MarketPriceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketPriceController extends Controller
{
    public function __construct(private readonly MarketPriceService $marketPriceService)
    {
    }

    /**
     * GET /api/market-prices
     *
     * Returns current Cameroon agricultural market prices (FCFA per kg),
     * sourced live from the WFP/HDX food prices feed (cached 6h) with a
     * curated fallback. Falls back to admin-curated DB rows when present.
     */
    public function index(): JsonResponse
    {
        $storedPrices = MarketPrice::orderBy('commodity')->get();

        if ($storedPrices->isNotEmpty()) {
            $data = $storedPrices->map(fn (MarketPrice $item): array => [
                'id' => $item->id,
                'commodity' => $item->commodity,
                'market' => $item->hub ?? $item->city,
                'price_fcfa_per_kg' => (float) $item->price_fcfa_per_kg,
                'price_usd_per_kg' => (float) $item->price_usd_per_kg,
                'unit' => 'kg',
                'trend' => $item->trend ?? 'stable',
                'change_percent' => (float) $item->change_percent,
                'last_updated' => $item->updated_at?->toDateString() ?? now()->toDateString(),
            ])->values();

            return response()->json([
                'data' => $data,
                'source' => 'admin-curated',
                'last_updated' => now()->toDateString(),
                'currency' => 'FCFA',
            ]);
        }

        return response()->json($this->marketPriceService->getMarketPrices());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'commodity' => 'required|string|max:255',
            'symbol' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'hub' => 'nullable|string|max:255',
            'price_fcfa_per_kg' => 'required|numeric|min:0',
            'price_usd_per_kg' => 'required|numeric|min:0',
            'price_fcfa_per_mt' => 'required|numeric|min:0',
            'price_usd_per_mt' => 'required|numeric|min:0',
            'trend' => 'nullable|string|in:up,down,stable',
            'change_percent' => 'nullable|numeric',
        ]);

        return response()->json(MarketPrice::create($data), 201);
    }
}
