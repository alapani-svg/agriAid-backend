<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BuyerRequestFactory extends Factory
{
    protected $model = \App\Models\BuyerRequest::class;

    public function definition(): array
    {
        return [
            'crop' => $this->faker->randomElement(['Yellow Maize', 'White Sorghum', 'Paddy Rice', 'Red Sorghum', 'Cocoa Beans']),
            'quantity_kg' => $this->faker->randomFloat(2, 1000, 50000),
            'location' => $this->faker->randomElement(['Garoua', 'Maroua', 'Bafoussam', 'Douala', 'Bamenda']),
            'delivery_deadline' => $this->faker->dateTimeBetween('+3 days', '+30 days')->format('Y-m-d'),
            'buyer_message' => $this->faker->sentence(),
            'status' => 'PENDING',
        ];
    }
}
