<?php

namespace Database\Factories;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        $actions = [
            'loan_application_created' => 'loan',
            'warehouse_receipt_verified' => 'security',
            'purchase_order_placed' => 'node_sync',
            'price_update_synced' => 'system',
            'user_authenticated' => 'auth_event',
            'collateral_pledged' => 'underwriting',
            'block_generated' => 'block_gen',
        ];
        $action = $this->faker->randomElement(array_keys($actions));
        $category = $actions[$action];

        return [
            'user_id' => null,
            'actor_name' => $this->faker->name,
            'action' => $action,
            'category' => $category,
            'auditable_type' => null,
            'auditable_id' => null,
            'metadata' => [
                'source' => 'factory_seeder',
                'ip_region' => $this->faker->randomElement(['North', 'Far North', 'Centre']),
            ],
            'ip_address' => $this->faker->ipv4,
        ];
    }
}
