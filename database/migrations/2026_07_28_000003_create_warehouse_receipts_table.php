<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_receipts', function (Blueprint $table): void {
            $table->id();
            $table->string('farmer_name');
            $table->string('crop_type');
            $table->unsignedInteger('quantity_mt');
            $table->string('location');
            $table->timestamp('verified_at')->nullable();
            $table->string('qr_code')->nullable();
            $table->timestamps();

            $table->index('farmer_name');
            $table->index('crop_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_receipts');
    }
};
