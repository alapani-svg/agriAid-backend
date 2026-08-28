<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farmer_access_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('farmer_id'); // UUID from farmers table
            $table->foreignId('lender_id')->constrained('users'); // the lender user requesting access
            $table->string('lender_name');
            $table->string('lender_email');
            $table->string('lender_institution')->nullable(); // optional institution name
            $table->text('reason'); // why the lender wants access
            $table->string('status')->default('pending'); // pending, approved, denied, expired, revoked
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // 24-72h after approval
            $table->foreignId('approved_by')->nullable()->constrained('users'); // the farmer user who approved
            $table->text('farmer_note')->nullable(); // optional note from farmer on approval/denial
            $table->timestamps();

            $table->index('farmer_id');
            $table->index('lender_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farmer_access_requests');
    }
};
