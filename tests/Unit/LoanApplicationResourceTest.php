<?php

namespace Tests\Unit;

use App\Http\Resources\LoanApplicationResource;
use App\Models\LoanApplication;
use PHPUnit\Framework\TestCase;

class LoanApplicationResourceTest extends TestCase
{
    public function test_loan_resource_exposes_expected_fields_and_conversions(): void
    {
        $loan = new LoanApplication();
        $loan->forceFill([
            'id' => 42,
            'buyer_name' => 'Amadou Souley',
            'cig_affiliation' => 'Mayo-Sava Agricultural CIG',
            'purpose' => 'Tractor and irrigation expansion',
            'requested_amount_fcfa' => 5000000,
            'requested_amount_usd' => 7622.05,
            'term_years' => 10,
            'term_months' => 120,
            'interest_rate_apr' => 4.1,
            'monthly_repayment_usd' => 78.23,
            'collateral_cert_no' => 'CERT-992-X',
            'status' => 'Pending Review',
            'amount_paid_usd' => 5.0,
            'next_due_date' => '2026-12-01',
            'institution_name' => 'Afriland First Bank',
        ]);

        $resource = (new LoanApplicationResource($loan))->toArray(new \Illuminate\Http\Request());

        $fcfaPerUsd = 655.957;

        $this->assertEquals('42', $resource['id']);
        $this->assertEquals('Amadou Souley', $resource['borrowerName']);
        $this->assertEquals('Mayo-Sava Agricultural CIG', $resource['cigAffiliation']);
        $this->assertEquals(5000000.0, $resource['principalFcfa']);
        $this->assertEquals(10, $resource['termYears']);
        $this->assertEquals(4.1, $resource['interestRateApr']);
        $this->assertEquals((float) round(78.23 * $fcfaPerUsd), $resource['monthlyRepaymentFcfa']);
        $this->assertEquals('CERT-992-X', $resource['collateralCertNo']);
        $this->assertEquals('Pending Review', $resource['status']);
        $this->assertEquals((float) round(5.0 * $fcfaPerUsd), $resource['amountPaidFcfa']);
        $this->assertEquals('2026-12-01', $resource['nextDueDate']);
        $this->assertEquals('Afriland First Bank', $resource['institutionName']);
    }
}
