<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Buyer;
use App\Models\Institution;
use App\Models\LoanApplication;
use App\Models\MarketListing;
use App\Models\MarketPrice;
use App\Models\Notification;
use App\Models\PurchaseOrder;
use App\Models\RegionalReport;
use App\Models\User;
use App\Models\WarehouseReceipt;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application''s database with realistic Cameroon demo data.
     */
    public function run(): void
    {
        $user = User::firstOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
            'password' => 'password',
        ]);

        $institutions = Institution::factory()->count(4)->create(['user_id' => $user->id]);
        $buyers = Buyer::factory()->count(5)->create(['user_id' => $user->id]);
        $receipts = WarehouseReceipt::factory()->count(18)->create();
        $listings = MarketListing::factory()->count(25)->create();

        MarketPrice::factory()->count(6)->create();

        LoanApplication::factory()->count(25)->create([
            'buyer_id' => function () use ($buyers) {
                return $buyers->random()->id;
            },
            'institution_id' => function () use ($institutions) {
                return $institutions->random()->id;
            },
            'warehouse_receipt_id' => function () use ($receipts) {
                return $receipts->random()->id;
            },
        ]);

        PurchaseOrder::factory()->count(18)->create();

        AuditLog::factory()->count(20)->create(['user_id' => $user->id]);
        Notification::factory()->count(12)->create(['user_id' => $user->id]);
        RegionalReport::factory()->count(6)->create(['institution_id' => function () use ($institutions) {
            return $institutions->random()->id;
        }]);
    }
}
