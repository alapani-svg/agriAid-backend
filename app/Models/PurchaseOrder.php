<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'buyer_id',
        'market_listing_id',
        'commodity',
        'quantity_mt',
        'price_fcfa_per_mt',
        'price_usd_per_mt',
        'total_fcfa',
        'total_usd',
        'delivery_city',
        'delivery_status',
        'payment_status',
        'payment_method',
        'status',
    ];

    protected $casts = [
        'quantity_mt' => 'integer',
        'price_fcfa_per_mt' => 'decimal:2',
        'price_usd_per_mt' => 'decimal:2',
        'total_fcfa' => 'decimal:2',
        'total_usd' => 'decimal:2',
    ];
}
