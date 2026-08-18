<?php

namespace Database\Factories;

use App\Models\WarehouseReceipt;
use Illuminate\Database\Eloquent\Factories\Factory;

class WarehouseReceiptFactory extends Factory
{
    protected $model = WarehouseReceipt::class;

    public function definition(): array
    {
        $crops = ['Maize', 'Sorghum', 'Millet', 'Rice', 'Cotton', 'Coffee', 'Cocoa', 'Soybeans', 'Groundnut'];
        $cities = ['Garoua', 'Maroua', 'Ngaoundéré', 'Yaoundé', 'Douala', 'Bafoussam', 'Bertoua', 'Bamenda', 'Ebolowa', 'Buea'];
        $crop = $this->faker->randomElement($crops);

        return [
            'farmer_name' => $this->faker->name,
            'crop_type' => $crop,
            'quantity_mt' => $this->faker->numberBetween(5, 1_000),
            'location' => $this->faker->randomElement($cities),
            'verified_at' => $this->faker->optional(80)->dateTimeBetween('-1 year', 'now'),
            'qr_code' => 'DWR-' . strtoupper($this->faker->bothify('???-####')),
        ];
    }
}
