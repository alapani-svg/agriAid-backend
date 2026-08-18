<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regional_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('institution_id')->nullable()->constrained()->nullOnDelete();
            $table->string('region');
            $table->string('city')->nullable();
            $table->string('report_type')->default('food_security');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->decimal('total_production_mt', 15, 2)->default(0);
            $table->decimal('warehouse_stock_mt', 15, 2)->default(0);
            $table->decimal('financing_volume_fcfa', 15, 2)->default(0);
            $table->unsignedInteger('active_farmers')->default(0);
            $table->json('metrics')->nullable();
            $table->timestamps();

            $table->index(['region', 'report_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regional_reports');
    }
};
