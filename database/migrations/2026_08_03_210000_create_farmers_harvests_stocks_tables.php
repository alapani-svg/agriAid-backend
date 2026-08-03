<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farmers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('village')->nullable();
            $table->string('region', 60)->nullable();
            $table->decimal('farm_size_hectares', 10, 2)->default(0);
            $table->json('crop_types')->nullable();
            $table->string('cig_group')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->constrained()->cascadeOnDelete();
            $table->string('crop', 80);
            $table->decimal('quantity_kg', 14, 2)->default(0);
            $table->string('unit', 20)->default('kg');
            $table->string('location')->nullable();
            $table->timestamps();

            $table->unique(['farmer_id', 'crop']);
        });

        Schema::create('harvests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->constrained()->cascadeOnDelete();
            $table->string('crop', 80);
            $table->decimal('mass_kg', 14, 2);
            $table->unsignedTinyInteger('quality_pct')->default(80);
            $table->decimal('price_per_kg', 12, 2)->nullable();
            $table->string('status', 30)->default('recorded'); // recorded | verified | in_transit
            $table->string('village')->nullable();
            $table->string('region', 60)->nullable();
            $table->date('harvested_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['farmer_id', 'crop']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('harvests');
        Schema::dropIfExists('stocks');
        Schema::dropIfExists('farmers');
    }
};
