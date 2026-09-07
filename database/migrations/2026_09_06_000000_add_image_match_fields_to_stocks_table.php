<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stocks', function (Blueprint $table): void {
            $table->tinyInteger('image_match_score')->unsigned()->nullable()->after('photo_path');
            $table->string('image_match_status')->nullable()->after('image_match_score'); // matches, mismatched, uncertain, unavailable
            $table->text('image_match_reasoning')->nullable()->after('image_match_status');
            $table->string('image_match_confidence')->nullable()->after('image_match_reasoning'); // high, medium, low
            $table->index('image_match_status');
        });
    }

    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table): void {
            $table->dropColumn([
                'image_match_score',
                'image_match_status',
                'image_match_reasoning',
                'image_match_confidence',
            ]);
        });
    }
};
