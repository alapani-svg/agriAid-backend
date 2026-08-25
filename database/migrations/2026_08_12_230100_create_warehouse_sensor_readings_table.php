<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Environmental telemetry log for a warehouse (temperature / moisture).
     * Manually logged by warehouse managers — not connected to physical IoT hardware.
     */
    public function up(): void
    {
        Schema::create('warehouse_sensor_readings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->decimal('temperature_celsius', 5, 2)->nullable();
            $table->decimal('moisture_pct', 5, 2)->nullable();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('recorded_at');
            $table->timestamps();

            $table->index('warehouse_id');
            $table->index('recorded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_sensor_readings');
    }
};
