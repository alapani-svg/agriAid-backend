<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('harvest_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('crop', 255);
            $table->decimal('mass_kg', 12, 2);
            $table->decimal('quality_pct', 5, 2);
            $table->decimal('price_per_kg', 12, 2)->nullable();
            $table->boolean('sell_on_market')->default(false);
            $table->text('crop_image')->nullable();
            $table->string('market_location', 255)->nullable();
            $table->unsignedBigInteger('asking_price_per_mt')->nullable();
            $table->string('status', 50)->default('VERIFIED');
            $table->timestamp('harvest_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('harvest_records');
    }
};
