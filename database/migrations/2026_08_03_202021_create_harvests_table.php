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
        Schema::create('harvests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->constrained('farmers')->onDelete('cascade');
            $table->unsignedBigInteger('warehouse_id')->nullable(); // FK added later when warehouses table exists
            $table->string('crop_type');
            $table->decimal('quantity_kg', 12, 2);
            $table->decimal('quality_grade', 3, 2)->nullable(); // 1.0 to 5.0
            $table->date('harvest_date');
            $table->date('storage_date')->nullable();
            $table->enum('status', ['harvested', 'in_transit', 'stored', 'sold'])->default('harvested');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('farmer_id');
            $table->index('warehouse_id');
            $table->index('crop_type');
            $table->index('status');
            $table->index('harvest_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('harvests');
    }
};
