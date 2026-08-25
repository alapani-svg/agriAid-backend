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
        Schema::table('stocks', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('notes');
            $table->decimal('ai_estimated_quantity_kg', 12, 2)->nullable()->after('photo_path');
            $table->text('ai_analysis_notes')->nullable()->after('ai_estimated_quantity_kg');
            // unavailable: no photo / AI not configured or call failed.
            // pending: photo uploaded, analysis not yet run.
            // verified: AI estimate is within tolerance of the declared quantity.
            // flagged: AI estimate deviates significantly — needs manual review.
            $table->enum('verification_status', ['unavailable', 'pending', 'verified', 'flagged'])
                ->default('unavailable')
                ->after('ai_analysis_notes');

            $table->index('verification_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropIndex(['verification_status']);
            $table->dropColumn(['photo_path', 'ai_estimated_quantity_kg', 'ai_analysis_notes', 'verification_status']);
        });
    }
};
