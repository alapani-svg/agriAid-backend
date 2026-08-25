<?php

namespace App\Http\Resources;

use App\Services\CredibilityScoreService;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanApplicationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => (string) $this->id,
            'borrowerName' => $this->borrower_name ?? $this->buyer_name,
            'cigAffiliation' => $this->cig_affiliation,
            'purpose' => $this->purpose,
            'principalFcfa' => (float) ($this->requested_amount_fcfa ?? round(($this->principal_usd ?? $this->requested_amount_usd ?? 0) * 655.957)),
            'termYears' => (int) ($this->term_years ?? (int) floor(($this->term_months ?? 0) / 12) ?: 1),
            'interestRateApr' => (float) ($this->interest_rate_apr ?? 0),
            'monthlyRepaymentFcfa' => (float) round(($this->monthly_repayment_usd ?? 0) * 655.957),
            'collateralCertNo' => $this->collateral_cert_no,
            'status' => $this->status ?? 'Pending Review',
            'amountPaidFcfa' => (float) round(($this->amount_paid_usd ?? 0) * 655.957),
            'nextDueDate' => $this->next_due_date,
            'institutionName' => $this->institution_name,
            'repaymentSchedule' => $this->repayment_schedule ?? [],
            'score' => (int) ($this->score ?? 0),
            'breakdown' => CredibilityScoreService::breakdown($this->toArray()),
        ];
    }
}
