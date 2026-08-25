<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Gate logistics manifest: incoming/outgoing vehicle movements recorded at a
     * warehouse gate. Manually logged by warehouse managers.
     */
    public function up(): void
    {
        Schema::create('warehouse_gate_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->enum('direction', ['in', 'out']);
            $table->string('vehicle_id');
            $table->string('commodity');
            $table->decimal('weight_kg', 12, 2);
            $table->string('gate')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('occurred_at');
            $table->timestamps();

            $table->index('warehouse_id');
            $table->index('direction');
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_gate_logs');
    }
};
