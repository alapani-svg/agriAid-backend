<?php

namespace App\Http\Controllers;

use App\Models\MarketPrice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketPriceController extends Controller
{
    private const BASE_MARKET_PRICES = [
        [
            'symbol' => 'SORGHUM',
            'hub' => 'Garoua',
            'price_usd_per_mt' => 238.00,
            'price_fcfa_per_mt' => 145180,
        ],
        [
            'symbol' => 'COFFEE',
            'hub' => 'Ngaoundere',
            'price_usd_per_mt' => 415.00,
            'price_fcfa_per_mt' => 253150,
        ],
        [
            'symbol' => 'COTTON',
            'hub' => 'Douala',
            'price_usd_per_mt' => 187.00,
            'price_fcfa_per_mt' => 114070,
        ],
        [
            'symbol' => 'MILLET',
            'hub' => 'Maroua',
            'price_usd_per_mt' => 156.00,
            'price_fcfa_per_mt' => 95160,
        ],
        [
            'symbol' => 'RICE',
            'hub' => 'Yaounde',
            'price_usd_per_mt' => 242.00,
            'price_fcfa_per_mt' => 147620,
        ],
    ];

    public function index(Request $request): JsonResponse
    {
        $commodity = $request->query('commodity');
        $city = $request->query('city');

        $query = MarketPrice::query()->orderBy('commodity');
        if ($commodity) {
            $query->where('commodity', 'like', "%{$commodity}%");
        }
        if ($city) {
            $query->where('city', 'like', "%{$city}%");
        }

        $storedPrices = $query->get();

        if ($storedPrices->isNotEmpty()) {
            return response()->json($storedPrices->map(fn (MarketPrice $item): array => [
                'id' => $item->id,
                'symbol' => $item->symbol,
                'price' => '$' . number_format((float) $item->price_usd_per_mt, 2),
                'trend' => $item->trend === 'down' ? 'down' : 'up',
                'change' => sprintf('%s%s%%', ((float) $item->change_percent) >= 0 ? '+' : '-', number_format(abs((float) $item->change_percent), 1)),
                'hub' => $item->hub ?? $item->city,
                'commodity' => $item->commodity,
                'city' => $item->city,
                'price_usd_per_mt' => (float) $item->price_usd_per_mt,
                'price_fcfa_per_mt' => (float) $item->price_fcfa_per_mt,
                'price_usd_per_kg' => (float) $item->price_usd_per_kg,
                'price_fcfa_per_kg' => (float) $item->price_fcfa_per_kg,
            ]));
        }

        $base = self::BASE_MARKET_PRICES;
        if ($commodity) {
            $base = array_filter($base, fn ($item) => stripos($item['symbol'], $commodity) !== false);
        }
        if ($city) {
            $base = array_filter($base, fn ($item) => stripos($item['hub'], $city) !== false);
        }

        $items = array_map(function (array $item): array {
            $fluctuation = rand(-40, 40) / 1000.0;
            $trend = $fluctuation >= 0 ? 'up' : 'down';
            $priceUsd = round($item['price_usd_per_mt'] * (1 + $fluctuation), 2);
            $priceFcfa = (int) round($item['price_fcfa_per_mt'] * (1 + $fluctuation));
            $change = sprintf('%s%s%%', $trend === 'up' ? '+' : '-', number_format(abs($fluctuation * 100), 1));

            return [
                'symbol' => $item['symbol'],
                'price' => '$' . number_format($priceUsd, 2),
                'trend' => $trend,
                'change' => $change,
                'hub' => $item['hub'],
                'price_usd_per_mt' => $priceUsd,
                'price_fcfa_per_mt' => $priceFcfa,
            ];
        }, array_values($base));

        return response()->json($items);
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
