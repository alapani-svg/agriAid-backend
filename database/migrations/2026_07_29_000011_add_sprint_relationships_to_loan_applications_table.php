<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_applications', function (Blueprint $table): void {
            $table->foreignId('buyer_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('institution_id')->nullable()->after('buyer_id')->constrained()->nullOnDelete();
            $table->foreign('warehouse_receipt_id')->references('id')->on('warehouse_receipts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('loan_applications', function (Blueprint $table): void {
            $table->dropForeign(['warehouse_receipt_id']);
            $table->dropConstrainedForeignId('institution_id');
            $table->dropConstrainedForeignId('buyer_id');
        });
    }
};
