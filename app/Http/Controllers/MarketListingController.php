<?php

namespace App\Http\Controllers;

use App\Models\MarketListing;
use App\Http\Resources\MarketplaceListingResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MarketListingController extends Controller
{
    private const FCFA_PER_USD = 610;

    public function index(): JsonResponse
    {
        return response()->json(MarketplaceListingResource::collection(
            MarketListing::orderByDesc('created_at')->get()
        ));
    }

    public function show(MarketListing $marketListing): JsonResponse
    {
        return response()->json(new MarketplaceListingResource($marketListing));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string',
            'location' => 'required|string|max:255',
            'qty_mt' => 'required|integer|min:1',
            'price_fcfa_per_mt' => 'required|numeric|min:1',
            'price_usd_per_mt' => 'nullable|numeric|min:0',
            'estate_reserve' => 'sometimes|boolean',
            'verified' => 'sometimes|boolean',
            'image_url' => 'nullable|url|max:1024',
        ]);

        $data['price_usd_per_mt'] = $data['price_usd_per_mt'] ?? round($data['price_fcfa_per_mt'] / self::FCFA_PER_USD, 2);

        return response()->json(new MarketplaceListingResource(MarketListing::create($data)), 201);
    }

    public function update(Request $request, MarketListing $marketListing): JsonResponse
    {
        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'subtitle' => 'nullable|string',
            'location' => 'sometimes|string|max:255',
            'qty_mt' => 'sometimes|integer|min:1',
            'price_fcfa_per_mt' => 'sometimes|numeric|min:1',
            'price_usd_per_mt' => 'nullable|numeric|min:0',
            'estate_reserve' => 'sometimes|boolean',
            'verified' => 'sometimes|boolean',
            'image_url' => 'nullable|url|max:1024',
        ]);

        if (array_key_exists('price_fcfa_per_mt', $data) && !array_key_exists('price_usd_per_mt', $data)) {
            $data['price_usd_per_mt'] = round($data['price_fcfa_per_mt'] / self::FCFA_PER_USD, 2);
        }

        if (!array_key_exists('price_fcfa_per_mt', $data) && array_key_exists('price_usd_per_mt', $data)) {
            $data['price_fcfa_per_mt'] = round($data['price_usd_per_mt'] * self::FCFA_PER_USD);
        }

        $marketListing->update($data);

        return response()->json(new MarketplaceListingResource($marketListing));
    }

    public function destroy(MarketListing $marketListing): JsonResponse
    {
        $marketListing->delete();

        return response()->json(null, 204);
    }
}
