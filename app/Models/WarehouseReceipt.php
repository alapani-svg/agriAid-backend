<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'farmer_name',
        'crop_type',
        'quantity_mt',
        'location',
        'verified_at',
        'qr_code',
    ];

    protected $casts = [
        'quantity_mt' => 'integer',
        'verified_at' => 'datetime',
    ];
}
