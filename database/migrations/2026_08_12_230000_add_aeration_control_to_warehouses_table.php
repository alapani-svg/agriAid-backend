<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->boolean('aeration_active')->default(false)->after('status');
            $table->timestamp('aeration_updated_at')->nullable()->after('aeration_active');
        });
    }

    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropColumn(['aeration_active', 'aeration_updated_at']);
        });
    }
};
