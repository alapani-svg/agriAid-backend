<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('buyer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('market_listing_id')->nullable()->constrained()->nullOnDelete();
            $table->string('commodity');
            $table->unsignedInteger('quantity_mt');
            $table->decimal('price_fcfa_per_mt', 15, 2)->unsigned();
            $table->decimal('price_usd_per_mt', 15, 2)->unsigned();
            $table->decimal('total_fcfa', 15, 2)->unsigned();
            $table->decimal('total_usd', 15, 2)->unsigned();
            $table->string('delivery_city')->nullable();
            $table->string('delivery_status')->default('pending');
            $table->string('payment_status')->default('pending');
            $table->timestamps();

            $table->index(['delivery_status', 'payment_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
