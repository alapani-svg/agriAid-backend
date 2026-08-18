<?php

namespace Database\Factories;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        $titles = [
            'Loan review pending' => 'A receipt-backed loan application is ready for financial institution review.',
            'Price alert: Maize up' => 'Yellow Maize prices in Garoua Hub increased by 2.4% today.',
            'New warehouse receipt' => 'A new warehouse receipt has been verified and is available as collateral.',
            'Purchase order accepted' => 'Your purchase order has been accepted by the seller.',
            'Repayment due soon' => 'A monthly loan repayment is due within the next 7 days.',
        ];
        $title = $this->faker->randomElement(array_keys($titles));

        return [
            'user_id' => null,
            'title' => $title,
            'message' => $titles[$title],
            'type' => $this->faker->randomElement(['info', 'success', 'warning', 'alert']),
            'channel' => 'in_app',
            'status' => $this->faker->randomElement(['unread', 'read']),
            'read_at' => null,
        ];
    }
}
