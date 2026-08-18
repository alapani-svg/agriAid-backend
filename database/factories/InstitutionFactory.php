<?php

namespace Database\Factories;

use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

class InstitutionFactory extends Factory
{
    protected $model = Institution::class;

    public function definition(): array
    {
        $cities = ['Yaoundé', 'Douala', 'Garoua', 'Maroua', 'Bafoussam', 'Bertoua', 'Bamenda', 'Ebolowa', 'Buea', 'Ngaoundéré'];
        $regions = ['Centre', 'Littoral', 'North', 'Far North', 'West', 'East', 'North West', 'South', 'South West', 'Adamawa'];
        $city = $this->faker->randomElement($cities);
        $region = $regions[array_search($city, $cities)];

        return [
            'user_id' => null,
            'name' => $this->faker->company . ' Agricultural Credit Desk',
            'registration_number' => 'BEAC-' . strtoupper($this->faker->bothify('????-####')),
            'type' => $this->faker->randomElement(['financial', 'cooperative', 'government', 'ngo']),
            'city' => $city,
            'region' => $region,
            'contact_email' => $this->faker->unique()->safeEmail,
            'contact_phone' => '+2376' . $this->faker->numberBetween(50_000_000, 99_999_999),
            'status' => $this->faker->randomElement(['active', 'active', 'active', 'pending']),
        ];
    }
}
