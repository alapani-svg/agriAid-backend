<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_prices', function (Blueprint $table): void {
            $table->id();
            $table->string('commodity');
            $table->string('symbol');
            $table->string('city');
            $table->string('hub')->nullable();
            $table->decimal('price_fcfa_per_kg', 12, 2)->unsigned();
            $table->decimal('price_usd_per_kg', 12, 4)->unsigned();
            $table->decimal('price_fcfa_per_mt', 15, 2)->unsigned();
            $table->decimal('price_usd_per_mt', 15, 2)->unsigned();
            $table->string('trend')->default('stable');
            $table->decimal('change_percent', 6, 2)->default(0);
            $table->timestamps();

            $table->index(['commodity', 'city']);
            $table->index('symbol');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_prices');
    }
};
