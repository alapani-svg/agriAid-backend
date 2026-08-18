<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_applications', function (Blueprint $table): void {
            $table->id();
            $table->string('buyer_name');
            $table->string('institution_name');
            $table->unsignedBigInteger('warehouse_receipt_id')->nullable();
            $table->unsignedInteger('requested_amount_fcfa');
            $table->decimal('requested_amount_usd', 15, 2)->unsigned();
            $table->unsignedSmallInteger('term_months');
            $table->unsignedTinyInteger('score')->nullable();
            $table->string('status')->default('pending');
            $table->json('repayment_schedule')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('buyer_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_applications');
    }
};
