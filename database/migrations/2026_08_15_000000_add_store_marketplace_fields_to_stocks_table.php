<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->decimal('price_per_kg', 14, 2)->nullable()->after('quantity_kg');
            $table->string('currency', 10)->default('FCFA')->after('price_per_kg');
            $table->string('unit', 30)->default('kg')->after('currency');
            $table->decimal('unit_weight_kg', 10, 2)->nullable()->after('unit');
            $table->json('price_tiers')->nullable()->after('unit_weight_kg');
            $table->string('quality_grade', 60)->nullable()->after('price_tiers');
            $table->string('origin', 120)->nullable()->after('quality_grade');
            $table->string('seller_id')->nullable()->after('origin');
            $table->string('variety', 120)->nullable()->after('crop_type');
            $table->boolean('is_urgent_sale')->default(false)->after('seller_id');
            $table->decimal('flash_discount_percent', 5, 2)->default(0)->after('is_urgent_sale');
            $table->timestamp('flash_discount_expires_at')->nullable()->after('flash_discount_percent');
            $table->index(['status', 'quantity_kg']);
            $table->index('seller_id');
        });
    }

    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropColumn([
                'price_per_kg',
                'currency',
                'unit',
                'unit_weight_kg',
                'price_tiers',
                'quality_grade',
                'origin',
                'seller_id',
                'variety',
                'is_urgent_sale',
                'flash_discount_percent',
                'flash_discount_expires_at',
            ]);
        });
    }
};
