<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('farmers')) {
            Schema::create('farmers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
                $table->string('village')->nullable();
                $table->string('region', 60)->nullable();
                $table->decimal('farm_size_hectares', 10, 2)->default(0);
                $table->json('crop_types')->nullable();
                $table->string('cig_group')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('farmers', function (Blueprint $table) {
                if (! Schema::hasColumn('farmers', 'village')) {
                    $table->string('village')->nullable();
                }
                if (! Schema::hasColumn('farmers', 'region')) {
                    $table->string('region', 60)->nullable();
                }
                if (! Schema::hasColumn('farmers', 'farm_size_hectares')) {
                    $table->decimal('farm_size_hectares', 10, 2)->default(0);
                }
                if (! Schema::hasColumn('farmers', 'crop_types')) {
                    $table->json('crop_types')->nullable();
                }
                if (! Schema::hasColumn('farmers', 'cig_group')) {
                    $table->string('cig_group')->nullable();
                }
                if (! Schema::hasColumn('farmers', 'notes')) {
                    $table->text('notes')->nullable();
                }
            });
        }

        if (! Schema::hasTable('stocks')) {
            Schema::create('stocks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('farmer_id')->constrained()->cascadeOnDelete();
                $table->string('crop', 80);
                $table->decimal('quantity_kg', 14, 2)->default(0);
                $table->string('unit', 20)->default('kg');
                $table->string('location')->nullable();
                $table->timestamps();
                $table->unique(['farmer_id', 'crop']);
            });
        }

        if (! Schema::hasTable('harvests')) {
            Schema::create('harvests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('farmer_id')->constrained()->cascadeOnDelete();
                $table->string('crop', 80);
                $table->decimal('mass_kg', 14, 2);
                $table->unsignedTinyInteger('quality_pct')->default(80);
                $table->decimal('price_per_kg', 12, 2)->nullable();
                $table->string('status', 30)->default('recorded');
                $table->string('village')->nullable();
                $table->string('region', 60)->nullable();
                $table->date('harvested_on')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['farmer_id', 'crop']);
                $table->index('status');
            });
        } else {
            Schema::table('harvests', function (Blueprint $table) {
                if (! Schema::hasColumn('harvests', 'quality_pct')) {
                    $table->unsignedTinyInteger('quality_pct')->default(80);
                }
                if (! Schema::hasColumn('harvests', 'price_per_kg')) {
                    $table->decimal('price_per_kg', 12, 2)->nullable();
                }
                if (! Schema::hasColumn('harvests', 'status')) {
                    $table->string('status', 30)->default('recorded');
                }
                if (! Schema::hasColumn('harvests', 'village')) {
                    $table->string('village')->nullable();
                }
                if (! Schema::hasColumn('harvests', 'region')) {
                    $table->string('region', 60)->nullable();
                }
                if (! Schema::hasColumn('harvests', 'harvested_on')) {
                    $table->date('harvested_on')->nullable();
                }
                if (! Schema::hasColumn('harvests', 'notes')) {
                    $table->text('notes')->nullable();
                }
                if (! Schema::hasColumn('harvests', 'mass_kg') && Schema::hasColumn('harvests', 'quantity_kg')) {
                    // local schema may use a different mass column name — leave as-is
                }
            });
        }
    }

    public function down(): void
    {
        // Intentionally not dropping tables that may have been created by earlier local migrations.
    }
};
