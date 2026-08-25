<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_listings', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('subtitle')->nullable();
            $table->string('location');
            $table->unsignedInteger('qty_mt');
            $table->decimal('price_fcfa_per_mt', 15, 2)->unsigned();
            $table->decimal('price_usd_per_mt', 15, 2)->unsigned();
            $table->boolean('estate_reserve')->default(false);
            $table->string('image_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_listings');
    }
};
