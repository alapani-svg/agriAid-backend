<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institutions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('registration_number')->nullable()->unique();
            $table->string('type')->default('financial');
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index(['region', 'city']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institutions');
    }
};
