<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('warehouse_id')->unique()->constrained('warehouses')->onDelete('cascade');
            $table->string('farmer_id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('banner_path')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('status')->default('active');
            $table->string('theme_color')->default('#026e00');
            $table->timestamps();

            $table->index('farmer_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
