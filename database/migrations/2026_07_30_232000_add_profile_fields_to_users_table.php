<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 30)->nullable()->after('email');
            }
            if (! Schema::hasColumn('users', 'notification_preference')) {
                $table->string('notification_preference', 20)->default('email')->after('phone');
            }
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role', 40)->default('farmer')->after('notification_preference');
            }
            if (! Schema::hasColumn('users', 'region')) {
                $table->string('region', 60)->nullable()->after('role');
            }
            if (! Schema::hasColumn('users', 'organization')) {
                $table->string('organization')->nullable()->after('region');
            }
            if (! Schema::hasColumn('users', 'status')) {
                $table->string('status', 20)->default('pending')->after('organization');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['status', 'organization', 'region', 'role'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
