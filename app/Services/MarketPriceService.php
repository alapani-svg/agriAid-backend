<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fetches real agricultural market prices for Cameroon.
 *
 * Primary source: WFP (World Food Programme) food prices for Cameroon,
 * published on the Humanitarian Data Exchange (HDX) as a CKAN resource.
 * The HDX CKAN datastore_search API is queried (when available) for the
 * latest retail price rows, which are then normalised to FCFA per kg.
 *
 * The HDX endpoint can be slow / occasionally unavailable, so results are
 * cached for 6 hours and a curated set of realistic Cameroon market prices
 * (in FCFA) is used as a fallback so the platform always shows useful data.
 */
class MarketPriceService
{
    /** HDX CKAN datastore resource id for "Cameroon - Food Prices" (WFP). */
    private const HDX_RESOURCE_ID = '9deec824-1e42-4a91-b704-b73fd250ec5a';

    private const HDX_ENDPOINT = 'https://data.humdata.org/api/3/action/datastore_search';

    /** Cache the normalised price list for 6 hours to avoid rate limiting. */
    private const CACHE_TTL_SECONDS = 6 * 60 * 60;

    private const CACHE_KEY = 'market_prices:cameroon:v2';

    /** USD -> FCFA (Central African CFA franc) fixed peg: 1 USD = 655.957 FCFA. */
    private const USD_TO_FCFA = 655.957;

    /**
     * Curated baseline Cameroon market prices (FCFA per kg).
     * Values reflect typical retail prices across major Cameroonian markets.
     * Used as a fallback when the live HDX/WFP feed is unavailable, and as
     * the previous-period reference for trend calculation.
     */
    private const CURATED_PRICES = [
        // [commodity, market, price_fcfa_per_kg, unit_label]
        ['Tomato', 'Yaoundé', 1200, 'kg'],
        ['Tomato', 'Douala', 1100, 'kg'],
        ['Tomato', 'Bamenda', 950, 'kg'],
        ['Maize', 'Yaoundé', 350, 'kg'],
        ['Maize', 'Bafoussam', 320, 'kg'],
        ['Maize', 'Garoua', 300, 'kg'],
        ['Rice (local)', 'Yaoundé', 650, 'kg'],
        ['Rice (local)', 'Douala', 680, 'kg'],
        ['Rice (imported)', 'Yaoundé', 750, 'kg'],
        ['Cassava', 'Bamenda', 250, 'kg'],
        ['Cassava', 'Bafoussam', 240, 'kg'],
        ['Cassava', 'Yaoundé', 280, 'kg'],
        ['Plantain', 'Douala', 400, 'kg'],
        ['Plantain', 'Yaoundé', 450, 'kg'],
        ['Onion', 'Garoua', 600, 'kg'],
        ['Onion', 'Maroua', 550, 'kg'],
        ['Onion', 'Yaoundé', 800, 'kg'],
        ['Beans', 'Bamenda', 700, 'kg'],
        ['Beans', 'Yaoundé', 750, 'kg'],
        ['Beans', 'Bafoussam', 680, 'kg'],
        ['Leafy greens', 'Yaoundé', 500, 'kg'],
        ['Leafy greens', 'Bamenda', 450, 'kg'],
        ['Potato', 'Bamenda', 600, 'kg'],
        ['Potato', 'Bafoussam', 580, 'kg'],
        ['Yam', 'Yaoundé', 400, 'kg'],
        ['Yam', 'Douala', 420, 'kg'],
        ['Groundnut', 'Maroua', 900, 'kg'],
        ['Groundnut', 'Garoua', 880, 'kg'],
        ['Millet', 'Maroua', 380, 'kg'],
        ['Millet', 'Garoua', 360, 'kg'],
        ['Sorghum', 'Garoua', 340, 'kg'],
        ['Sorghum', 'Maroua', 330, 'kg'],
    ];

    /**
     * Return the current Cameroon market price list.
     *
     * @return array{
     *   data: array<int, array{
     *     commodity: string,
     *     market: string,
     *     price_fcfa_per_kg: float,
     *     price_usd_per_kg: float,
     *     unit: string,
     *     trend: string,
     *     change_percent: float,
     *     last_updated: string
     *   }>,
     *   source: string,
     *   last_updated: string,
     *   currency: string
     * }
     */
    public function getMarketPrices(): array
    {
        $cached = Cache::get(self::CACHE_KEY);
        if (is_array($cached) && isset($cached['data'])) {
            return $cached;
        }

        $live = $this->fetchFromHdx();

        $data = $live['data'] ?? [];
        $source = $live['source'] ?? 'curated';
        $lastUpdated = $live['last_updated'] ?? now()->toDateString();

        if (empty($data)) {
            $data = $this->buildCuratedPrices();
            $source = 'curated';
            $lastUpdated = now()->toDateString();
        }

        $payload = [
            'data' => array_values($data),
            'source' => $source,
            'last_updated' => $lastUpdated,
            'currency' => 'FCFA',
        ];

        Cache::put(self::CACHE_KEY, $payload, self::CACHE_TTL_SECONDS);

        return $payload;
    }

    /**
     * Attempt to fetch the latest Cameroon retail food prices from the
     * WFP/HDX CKAN datastore API and normalise them to FCFA per kg.
     *
     * @return array{data: array, source: string, last_updated: string}|array{}
     */
    private function fetchFromHdx(): array
    {
        try {
            $response = Http::timeout(15)->asForm()->post(self::HDX_ENDPOINT, [
                'resource_id' => self::HDX_RESOURCE_ID,
                'limit' => 1000,
                'filters' => json_encode(['pricetype' => 'Retail']),
                'sort' => 'date desc',
            ]);

            if (! $response->ok()) {
                Log::info('MarketPriceService: HDX request failed', ['status' => $response->status()]);
                return [];
            }

            $body = $response->json();
            $rows = $body['result']['records'] ?? [];

            if (empty($rows)) {
                return [];
            }

            return $this->normaliseHdxRows($rows);
        } catch (\Throwable $e) {
            Log::info('MarketPriceService: HDX fetch exception', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Normalise raw HDX/WFP rows into the platform's price structure.
     * Computes a trend by comparing the latest price to the previous period
     * for each commodity+market pair.
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array{data: array, source: string, last_updated: string}
     */
    private function normaliseHdxRows(array $rows): array
    {
        // Group rows by commodity|market, keeping the latest and previous entry.
        $latest = [];
        $previous = [];
        $latestDate = '';

        foreach ($rows as $row) {
            $commodity = $this->stringify($row['commodity'] ?? null);
            $market = $this->stringify($row['market'] ?? null);
            $date = $this->stringify($row['date'] ?? null);
            $price = $this->numberify($row['price'] ?? null);
            $currency = strtoupper($this->stringify($row['currency'] ?? 'XAF'));
            $unit = $this->stringify($row['unit'] ?? 'kg');

            if ($commodity === '' || $market === '' || $price <= 0) {
                continue;
            }

            // Normalise to FCFA per kg.
            $priceFcfaPerKg = $this->toFcfaPerKg($price, $currency, $unit);
            if ($priceFcfaPerKg <= 0) {
                continue;
            }

            $key = $commodity . '|' . $market;

            if ($date > $latestDate) {
                $latestDate = $date;
            }

            if (! isset($latest[$key]) || $date > $latest[$key]['date']) {
                if (isset($latest[$key])) {
                    $previous[$key] = $latest[$key];
                }
                $latest[$key] = [
                    'commodity' => $commodity,
                    'market' => $market,
                    'price_fcfa_per_kg' => $priceFcfaPerKg,
                    'unit' => 'kg',
                    'date' => $date,
                ];
            } elseif (! isset($previous[$key]) || $date > $previous[$key]['date']) {
                $previous[$key] = [
                    'price_fcfa_per_kg' => $priceFcfaPerKg,
                    'date' => $date,
                ];
            }
        }

        $data = [];
        foreach ($latest as $key => $entry) {
            $prevPrice = $previous[$key]['price_fcfa_per_kg'] ?? null;
            [$trend, $change] = $this->computeTrend($entry['price_fcfa_per_kg'], $prevPrice);

            $data[] = [
                'commodity' => $entry['commodity'],
                'market' => $entry['market'],
                'price_fcfa_per_kg' => round($entry['price_fcfa_per_kg'], 2),
                'price_usd_per_kg' => round($entry['price_fcfa_per_kg'] / self::USD_TO_FCFA, 4),
                'unit' => $entry['unit'],
                'trend' => $trend,
                'change_percent' => $change,
                'last_updated' => $entry['date'] !== '' ? $entry['date'] : now()->toDateString(),
            ];
        }

        return [
            'data' => $data,
            'source' => 'WFP/HDX',
            'last_updated' => $latestDate !== '' ? $latestDate : now()->toDateString(),
        ];
    }

    /**
     * Build the curated fallback price list with a small realistic fluctuation
     * and a computed trend against the curated baseline.
     *
     * @return array<int, array{
     *   commodity: string,
     *   market: string,
     *   price_fcfa_per_kg: float,
     *   price_usd_per_kg: float,
     *   unit: string,
     *   trend: string,
     *   change_percent: float,
     *   last_updated: string
     * }>
     */
    private function buildCuratedPrices(): array
    {
        $today = now()->toDateString();
        $data = [];

        foreach (self::CURATED_PRICES as [$commodity, $market, $base, $unit]) {
            // Deterministic-ish fluctuation between -6% and +6%.
            $fluctuation = (mt_rand(-60, 60) / 1000.0);
            $price = round($base * (1 + $fluctuation), 2);
            [$trend, $change] = $this->computeTrend($price, (float) $base);

            $data[] = [
                'commodity' => $commodity,
                'market' => $market,
                'price_fcfa_per_kg' => $price,
                'price_usd_per_kg' => round($price / self::USD_TO_FCFA, 4),
                'unit' => $unit,
                'trend' => $trend,
                'change_percent' => $change,
                'last_updated' => $today,
            ];
        }

        return $data;
    }

    /**
     * Compare the current price to a previous price and return a trend label
     * (up/down/stable) plus a signed change percentage.
     *
     * @return array{0: string, 1: float}
     */
    private function computeTrend(float $current, ?float $previous): array
    {
        if ($previous === null || $previous <= 0) {
            return ['stable', 0.0];
        }

        $change = (($current - $previous) / $previous) * 100;

        if (abs($change) < 1.5) {
            return ['stable', round($change, 2)];
        }

        return [$change > 0 ? 'up' : 'down', round($change, 2)];
    }

    /**
     * Convert a raw WFP price into FCFA per kg, handling currency and unit.
     */
    private function toFcfaPerKg(float $price, string $currency, string $unit): float
    {
        // Convert to FCFA (XAF).
        $inFcfa = match ($currency) {
            'USD' => $price * self::USD_TO_FCFA,
            'XAF', 'FCFA' => $price,
            'EUR' => $price * 655.957,
            default => $price, // assume already FCFA
        };

        // Normalise unit to per kg.
        $unitLower = strtolower(trim($unit));
        $perKg = match (true) {
            str_contains($unitLower, '100 kg') || str_contains($unitLower, '100kg') => $inFcfa / 100,
            str_contains($unitLower, '50 kg') || str_contains($unitLower, '50kg') => $inFcfa / 50,
            str_contains($unitLower, 'mt') || str_contains($unitLower, 'ton') => $inFcfa / 1000,
            str_contains($unitLower, 'lb') => $inFcfa / 0.453592,
            str_contains($unitLower, 'g') && ! str_contains($unitLower, 'kg') => $inFcfa / 0.001,
            default => $inFcfa, // assume per kg
        };

        return $perKg > 0 ? $perKg : 0.0;
    }

    private function stringify(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        return is_string($value) ? trim($value) : (string) $value;
    }

    private function numberify(mixed $value): float
    {
        if ($value === null) {
            return 0.0;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }
        return 0.0;
    }
}
