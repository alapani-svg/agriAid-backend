<?php

namespace Database\Factories;

use App\Models\Buyer;
use App\Models\MarketListing;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition(): array
    {
        $crops = ['Maize', 'Sorghum', 'Millet', 'Rice', 'Cotton', 'Coffee', 'Cocoa', 'Soybeans', 'Groundnut'];
        $cities = ['Douala', 'Yaoundé', 'Garoua', 'Maroua', 'Bafoussam', 'Bertoua', 'Bamenda', 'Ebolowa', 'Buea'];
        $crop = $this->faker->randomElement($crops);
        $qty = $this->faker->numberBetween(5, 500);
        $priceFcfa = $this->faker->numberBetween(60_000, 600_000);

        return [
            'order_number' => 'PO-' . strtoupper($this->faker->bothify('???-####')),
            'buyer_id' => Buyer::factory(),
            'market_listing_id' => MarketListing::factory(),
            'commodity' => $crop,
            'quantity_mt' => $qty,
            'price_fcfa_per_mt' => $priceFcfa,
            'price_usd_per_mt' => round($priceFcfa / 655.957, 2),
            'total_fcfa' => $qty * $priceFcfa,
            'total_usd' => round($qty * $priceFcfa / 655.957, 2),
            'delivery_city' => $this->faker->randomElement($cities),
            'delivery_status' => $this->faker->randomElement(['pending', 'shipped', 'delivered']),
            'payment_status' => $this->faker->randomElement(['pending', 'paid', 'escrow']),
            'payment_method' => $this->faker->randomElement(['MoMo', 'Orange Money', 'Bank Transfer', 'Cash on Delivery']),
            'status' => $this->faker->randomElement(['PENDING', 'ACCEPTED', 'REJECTED', 'COMPLETED']),
        ];
    }
}
