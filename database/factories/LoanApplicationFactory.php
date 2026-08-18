<?php

namespace Database\Factories;

use App\Models\Buyer;
use App\Models\Institution;
use App\Models\LoanApplication;
use App\Models\WarehouseReceipt;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoanApplicationFactory extends Factory
{
    protected $model = LoanApplication::class;

    public function definition(): array
    {
        $crops = ['Maize', 'Sorghum', 'Millet', 'Rice', 'Cotton', 'Coffee', 'Cocoa', 'Soybeans', 'Groundnut'];
        $amountFcfa = $this->faker->numberBetween(500_000, 50_000_000);
        $termMonths = $this->faker->randomElement([6, 12, 24, 36, 60, 120, 180, 240]);
        $rate = $this->faker->randomFloat(2, 3.0, 7.5);

        $repaymentSchedule = [];
        $monthly = (int) round($amountFcfa / $termMonths);
        for ($m = 1; $m <= min($termMonths, 24); $m++) {
            $repaymentSchedule[] = [
                'month' => $m,
                'due_fcfa' => $monthly,
                'paid' => $this->faker->boolean(25),
            ];
        }

        return [
            'buyer_id' => Buyer::factory(),
            'institution_id' => Institution::factory(),
            'buyer_name' => $this->faker->name,
            'institution_name' => $this->faker->company . ' Credit Desk',
            'cig_affiliation' => $this->faker->company . ' CIG',
            'purpose' => 'Seasonal ' . $this->faker->randomElement($crops) . ' expansion and storage',
            'warehouse_receipt_id' => WarehouseReceipt::factory(),
            'requested_amount_fcfa' => $amountFcfa,
            'requested_amount_usd' => round($amountFcfa / 655.957, 2),
            'principal_usd' => round($amountFcfa / 655.957, 2),
            'term_months' => $termMonths,
            'term_years' => (int) ceil($termMonths / 12),
            'interest_rate_apr' => $rate,
            'monthly_repayment_usd' => round($amountFcfa / $termMonths / 655.957, 2),
            'collateral_cert_no' => 'CERT-' . strtoupper($this->faker->bothify('???-####')),
            'score' => $this->faker->numberBetween(55, 99),
            'status' => $this->faker->randomElement(['Pending Review', 'Active', 'Repaid', 'Rejected']),
            'amount_paid_usd' => round($this->faker->numberBetween(0, $amountFcfa) / 655.957, 2),
            'next_due_date' => $this->faker->dateTimeBetween('now', '+3 months')->format('Y-m-d'),
            'repayment_schedule' => $repaymentSchedule,
        ];
    }
}
