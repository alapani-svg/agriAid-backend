<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('market_listings', function (Blueprint $table): void {
            $table->boolean('verified')->default(true)->after('estate_reserve');
        });
    }

    public function down(): void
    {
        Schema::table('market_listings', function (Blueprint $table): void {
            $table->dropColumn('verified');
        });
    }
};
