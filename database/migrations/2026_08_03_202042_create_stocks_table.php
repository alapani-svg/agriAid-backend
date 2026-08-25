<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('warehouse_id')->nullable(); // FK added later when warehouses table exists
            $table->foreignUuid('harvest_id')->nullable()->constrained('harvests')->onDelete('set null');
            $table->string('crop_type');
            $table->decimal('quantity_kg', 12, 2);
            $table->decimal('capacity_used', 12, 2); // current capacity used
            $table->decimal('capacity_total', 12, 2); // total warehouse capacity
            $table->date('entry_date');
            $table->date('exit_date')->nullable();
            $table->enum('status', ['in_stock', 'reserved', 'withdrawn', 'sold'])->default('in_stock');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('warehouse_id');
            $table->index('harvest_id');
            $table->index('crop_type');
            $table->index('status');
            $table->index('entry_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
