<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuyerRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'buyer_id',
        'farmer_user_id',
        'crop',
        'quantity_kg',
        'location',
        'delivery_deadline',
        'buyer_message',
        'proposed_price_per_kg',
        'farmer_message',
        'status',
        'rejected_by',
        'rejection_reason',
    ];

    protected $casts = [
        'quantity_kg' => 'decimal:2',
        'proposed_price_per_kg' => 'decimal:2',
        'delivery_deadline' => 'date',
    ];
}
