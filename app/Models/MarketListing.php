<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketListing extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'subtitle',
        'location',
        'qty_mt',
        'price_fcfa_per_mt',
        'price_usd_per_mt',
        'estate_reserve',
        'verified',
        'image_url',
    ];

    protected $casts = [
        'estate_reserve' => 'boolean',
        'verified' => 'boolean',
    ];
}
