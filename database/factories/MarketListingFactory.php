<?php

namespace Database\Factories;

use App\Models\MarketListing;
use Illuminate\Database\Eloquent\Factories\Factory;

class MarketListingFactory extends Factory
{
    protected $model = MarketListing::class;

    public function definition(): array
    {
        $crops = ['Maize', 'Sorghum', 'Millet', 'Rice', 'Cotton', 'Coffee', 'Cocoa', 'Soybeans', 'Groundnut'];
        $cities = ['Garoua', 'Maroua', 'Ngaoundéré', 'Yaoundé', 'Douala', 'Bafoussam', 'Bertoua', 'Bamenda', 'Ebolowa', 'Buea'];
        $crop = $this->faker->randomElement($crops);
        $city = $this->faker->randomElement($cities);
        $fcfaPerMt = $this->faker->numberBetween(60_000, 600_000);

        return [
            'title' => "Premium {$crop} - {$city} Silo",
            'subtitle' => "Verified warehouse-backed {$crop} batch ready for buyer negotiation.",
            'location' => $city,
            'qty_mt' => $this->faker->numberBetween(10, 2_000),
            'price_fcfa_per_mt' => $fcfaPerMt,
            'price_usd_per_mt' => round($fcfaPerMt / 655.957, 2),
            'estate_reserve' => $this->faker->boolean(20),
            'verified' => $this->faker->boolean(80),
            'image_url' => null,
        ];
    }
}
