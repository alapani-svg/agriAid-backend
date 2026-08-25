<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_applications', function (Blueprint $table): void {
            $table->string('cig_affiliation')->nullable()->after('buyer_name');
            $table->string('purpose')->nullable()->after('cig_affiliation');
            $table->decimal('principal_usd', 15, 2)->unsigned()->nullable()->after('requested_amount_usd');
            $table->unsignedSmallInteger('term_years')->nullable()->after('term_months');
            $table->decimal('interest_rate_apr', 5, 2)->unsigned()->nullable()->after('term_years');
            $table->decimal('monthly_repayment_usd', 15, 2)->unsigned()->nullable()->after('interest_rate_apr');
            $table->string('collateral_cert_no')->nullable()->after('monthly_repayment_usd');
            $table->decimal('amount_paid_usd', 15, 2)->unsigned()->nullable()->default(0)->after('status');
            $table->string('next_due_date')->nullable()->after('amount_paid_usd');
        });
    }

    public function down(): void
    {
        Schema::table('loan_applications', function (Blueprint $table): void {
            $table->dropColumn([
                'cig_affiliation',
                'purpose',
                'principal_usd',
                'term_years',
                'interest_rate_apr',
                'monthly_repayment_usd',
                'collateral_cert_no',
                'amount_paid_usd',
                'next_due_date',
            ]);
        });
    }
};
