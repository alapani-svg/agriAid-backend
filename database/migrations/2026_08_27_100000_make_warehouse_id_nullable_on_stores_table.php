<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the foreign key constraint first, then the unique index, then make nullable
        Schema::table('stores', function (Blueprint $table) {
            $driver = \DB::getDriverName();

            // SQLite (used in tests) doesn't support information_schema or dropForeign
            if ($driver !== 'sqlite') {
                $foreignKeys = collect(\DB::select(
                    "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stores' AND COLUMN_NAME = 'warehouse_id' AND REFERENCED_TABLE_NAME IS NOT NULL"
                ));
                foreach ($foreignKeys as $fk) {
                    $table->dropForeign($fk->CONSTRAINT_NAME);
                }
            }

            // Drop the unique constraint (guard for SQLite where it may not exist)
            try {
                $table->dropUnique(['warehouse_id']);
            } catch (\Throwable $e) {
                // Index may not exist in SQLite — safe to ignore.
            }
        });

        Schema::table('stores', function (Blueprint $table) {
            // Make warehouse_id nullable so farmers can create a store without a warehouse.
            $table->foreignUuid('warehouse_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->foreignUuid('warehouse_id')->nullable(false)->change();
            $table->unique('warehouse_id');
        });
    }
};
