<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            // validation_status: pending (default, farmer posted), approved (warehouse manager validated), rejected
            $table->string('validation_status')->default('pending')->after('verification_status');
            $table->string('validated_by')->nullable()->after('validation_status');
            $table->timestamp('validated_at')->nullable()->after('validated_by');
            $table->text('validation_notes')->nullable()->after('validated_at');
        });
    }

    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropColumn(['validation_status', 'validated_by', 'validated_at', 'validation_notes']);
        });
    }
};
