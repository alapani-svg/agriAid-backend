<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoanApplication extends Model
{
    use HasFactory;

    protected $attributes = [
        'status' => 'Pending Review',
    ];

    protected $fillable = [
        'buyer_id',
        'institution_id',
        'buyer_name',
        'institution_name',
        'cig_affiliation',
        'purpose',
        'warehouse_receipt_id',
        'requested_amount_fcfa',
        'requested_amount_usd',
        'principal_usd',
        'term_months',
        'term_years',
        'interest_rate_apr',
        'monthly_repayment_usd',
        'collateral_cert_no',
        'score',
        'status',
        'amount_paid_usd',
        'next_due_date',
        'repayment_schedule',
    ];

    protected $casts = [
        'repayment_schedule' => 'array',
        'requested_amount_fcfa' => 'integer',
        'requested_amount_usd' => 'decimal:2',
        'principal_usd' => 'decimal:2',
        'term_months' => 'integer',
        'term_years' => 'integer',
        'interest_rate_apr' => 'decimal:2',
        'monthly_repayment_usd' => 'decimal:2',
        'amount_paid_usd' => 'decimal:2',
        'score' => 'integer',
    ];

    public function reminders(): HasMany
    {
        return $this->hasMany(LoanReminder::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }
}
