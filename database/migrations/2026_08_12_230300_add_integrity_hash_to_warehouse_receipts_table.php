<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A real, deterministic SHA-256 integrity hash computed from the receipt's
     * canonical fields at issuance time. This is a genuine cryptographic
     * verification seal — not a blockchain — used to detect tampering.
     */
    public function up(): void
    {
        Schema::table('warehouse_receipts', function (Blueprint $table) {
            $table->string('integrity_hash', 64)->nullable()->after('qr_code_data');
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_receipts', function (Blueprint $table) {
            $table->dropColumn('integrity_hash');
        });
    }
};
