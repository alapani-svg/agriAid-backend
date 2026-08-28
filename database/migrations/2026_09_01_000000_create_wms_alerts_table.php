<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_alerts', function (Blueprint $table) {
            $table->id();
            $table->uuid('stock_id');
            $table->string('warehouse_id');
            $table->string('farmer_id')->nullable();
            $table->string('crop_type');
            $table->string('crop_display_name')->nullable();
            $table->string('lot_id');
            $table->float('quantity_kg');
            $table->string('quality_grade')->nullable();
            $table->integer('shelf_life_hours');
            $table->string('status'); // good, warning, critical, expired
            $table->integer('alert_level'); // 0-3
            $table->string('recommended_action'); // hold, watch, discount, liquidate
            $table->json('alert_reasons')->nullable();
            $table->float('current_temperature_c')->nullable();
            $table->float('current_humidity_pct')->nullable();
            $table->boolean('acknowledged')->default(false);
            $table->timestamp('acknowledged_at')->nullable();
            $table->unsignedBigInteger('acknowledged_by')->nullable();
            $table->timestamps();

            $table->index(['warehouse_id', 'acknowledged']);
            $table->index(['farmer_id', 'acknowledged']);
            $table->index('alert_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_alerts');
    }
};
