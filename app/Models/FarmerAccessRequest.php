<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FarmerAccessRequest extends Model
{
    protected $fillable = [
        'farmer_id',
        'lender_id',
        'lender_name',
        'lender_email',
        'lender_institution',
        'reason',
        'status',
        'approved_at',
        'expires_at',
        'approved_by',
        'farmer_note',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function lender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lender_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Check if the access request is currently active (approved and not expired).
     */
    public function isActive(): bool
    {
        return $this->status === 'approved'
            && $this->expires_at !== null
            && $this->expires_at->isFuture();
    }
}
