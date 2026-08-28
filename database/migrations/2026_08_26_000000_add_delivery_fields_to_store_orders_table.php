<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_orders', function (Blueprint $table) {
            // Add shipped and delivered to the status enum
            $table->enum('status', ['pending', 'confirmed', 'shipped', 'delivered', 'completed', 'cancelled'])->default('pending')->change();

            // Delivery information
            $table->string('delivery_method')->nullable()->after('notes');
            $table->string('delivery_address')->nullable()->after('delivery_method');
            $table->string('delivery_city')->nullable()->after('delivery_address');
            $table->string('delivery_phone')->nullable()->after('delivery_city');
            $table->text('delivery_notes')->nullable()->after('delivery_phone');
        });
    }

    public function down(): void
    {
        Schema::table('store_orders', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_method',
                'delivery_address',
                'delivery_city',
                'delivery_phone',
                'delivery_notes',
            ]);
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending')->change();
        });
    }
};
