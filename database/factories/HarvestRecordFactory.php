<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class HarvestRecordFactory extends Factory
{
    protected $model = \App\Models\HarvestRecord::class;

    public function definition(): array
    {
        return [
            'crop' => $this->faker->randomElement(['Yellow Maize (Grade A)', 'White Sorghum Bulk #1', 'Red Sorghum', 'Paddy Rice', 'Cocoa Beans']),
            'mass_kg' => $this->faker->randomFloat(2, 500, 10000),
            'quality_pct' => $this->faker->randomFloat(2, 85, 99),
            'price_per_kg' => $this->faker->randomFloat(2, 80, 250),
            'sell_on_market' => $this->faker->boolean(30),
            'crop_image' => $this->faker->imageUrl(640, 480, 'nature'),
            'market_location' => $this->faker->randomElement(['Garoua Central Silo', 'Maroua Silo', 'Bafoussam Market', 'Douala Port']),
            'asking_price_per_mt' => $this->faker->numberBetween(140000, 220000),
            'status' => $this->faker->randomElement(['VERIFIED', 'IN TRANSIT', 'PROCESSING']),
            'harvest_date' => $this->faker->dateTimeBetween('-60 days', 'now'),
        ];
    }
}
