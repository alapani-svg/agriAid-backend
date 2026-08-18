<?php

namespace Database\Factories;

use App\Models\Buyer;
use Illuminate\Database\Eloquent\Factories\Factory;

class BuyerFactory extends Factory
{
    protected $model = Buyer::class;

    public function definition(): array
    {
        $cities = ['Douala', 'Yaoundé', 'Garoua', 'Maroua', 'Bafoussam', 'Bertoua', 'Bamenda', 'Ebolowa', 'Buea', 'Ngaoundéré'];
        $regions = ['Littoral', 'Centre', 'North', 'Far North', 'West', 'East', 'North West', 'South', 'South West', 'Adamawa'];
        $city = $this->faker->randomElement($cities);
        $region = $regions[array_search($city, $cities)];

        return [
            'user_id' => null,
            'name' => $this->faker->company,
            'company_name' => $this->faker->company . ' Commodities',
            'city' => $city,
            'region' => $region,
            'contact_email' => $this->faker->unique()->safeEmail,
            'contact_phone' => '+2376' . $this->faker->numberBetween(50_000_000, 99_999_999),
            'status' => $this->faker->randomElement(['active', 'active', 'active', 'inactive']),
        ];
    }
}
