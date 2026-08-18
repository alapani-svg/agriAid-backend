<?php

namespace Database\Factories;

use App\Models\Institution;
use App\Models\RegionalReport;
use Illuminate\Database\Eloquent\Factories\Factory;

class RegionalReportFactory extends Factory
{
    protected $model = RegionalReport::class;

    public function definition(): array
    {
        $regions = [
            'Far North' => 'Maroua',
            'North' => 'Garoua',
            'Adamawa' => 'Ngaoundéré',
            'Centre' => 'Yaoundé',
            'Littoral' => 'Douala',
        ];
        $region = $this->faker->randomElement(array_keys($regions));
        $city = $regions[$region];

        return [
            'institution_id' => Institution::factory(),
            'region' => $region,
            'city' => $city,
            'report_type' => $this->faker->randomElement(['food_security', 'credit_risk', 'production', 'warehouse_capacity']),
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'total_production_mt' => $this->faker->numberBetween(1_000, 100_000),
            'warehouse_stock_mt' => $this->faker->numberBetween(500, 20_000),
            'financing_volume_fcfa' => $this->faker->numberBetween(10_000_000, 1_000_000_000),
            'active_farmers' => $this->faker->numberBetween(50, 5_000),
            'metrics' => [
                'risk_level' => $this->faker->randomElement(['stable', 'moderate', 'elevated']),
                'priority_crop' => $this->faker->randomElement(['Maize', 'Sorghum', 'Cotton', 'Rice']),
            ],
        ];
    }
}
