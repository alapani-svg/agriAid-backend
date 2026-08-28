<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_reminders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('loan_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admin_id')->constrained('users');
            $table->string('message');
            $table->string('type')->default('payment_reminder'); // payment_reminder, status_update, general
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->index('loan_application_id');
            $table->index('admin_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_reminders');
    }
};
