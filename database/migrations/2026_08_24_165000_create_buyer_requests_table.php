<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buyer_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('farmer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('crop');
            $table->decimal('quantity_kg', 12, 2);
            $table->string('location')->nullable();
            $table->date('delivery_deadline')->nullable();
            $table->text('buyer_message')->nullable();
            $table->decimal('proposed_price_per_kg', 12, 2)->nullable();
            $table->text('farmer_message')->nullable();
            $table->string('status', 50)->default('PENDING');
            $table->enum('rejected_by', ['farmer', 'buyer'])->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buyer_requests');
    }
};
