<?php

namespace Database\Factories;

use App\Models\MarketPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

class MarketPriceFactory extends Factory
{
    protected $model = MarketPrice::class;

    public function definition(): array
    {
        $crops = ['Maize', 'Sorghum', 'Millet', 'Rice', 'Cotton', 'Coffee', 'Cocoa', 'Soybeans', 'Groundnut'];
        $cities = ['Garoua', 'Maroua', 'Ngaoundéré', 'Yaoundé', 'Douala', 'Bafoussam', 'Bertoua', 'Bamenda', 'Ebolowa', 'Buea'];
        $crop = $this->faker->randomElement($crops);
        $city = $this->faker->randomElement($cities);
        $fcfaPerMt = $this->faker->numberBetween(60_000, 600_000);

        return [
            'commodity' => $crop,
            'symbol' => strtoupper($crop),
            'city' => $city,
            'hub' => $city . ' Hub',
            'price_fcfa_per_kg' => round($fcfaPerMt / 1000, 2),
            'price_usd_per_kg' => round($fcfaPerMt / 655.957 / 1000, 4),
            'price_fcfa_per_mt' => $fcfaPerMt,
            'price_usd_per_mt' => round($fcfaPerMt / 655.957, 2),
            'trend' => $this->faker->randomElement(['up', 'down']),
            'change_percent' => $this->faker->randomFloat(2, -5, 5),
        ];
    }
}
