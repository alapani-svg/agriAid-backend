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
        Schema::create('warehouse_receipts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('receipt_number')->unique();
            $table->foreignUuid('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->foreignUuid('stock_id')->nullable()->constrained('stocks')->onDelete('set null');
            $table->foreignUuid('farmer_id')->constrained('farmers')->onDelete('cascade');
            $table->string('crop_type');
            $table->decimal('quantity_kg', 12, 2);
            $table->date('issue_date');
            $table->text('qr_code_data')->nullable();
            $table->enum('status', ['active', 'redeemed', 'cancelled'])->default('active');
            $table->timestamps();

            $table->index('warehouse_id');
            $table->index('farmer_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_receipts');
    }
};
