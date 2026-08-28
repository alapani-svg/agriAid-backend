<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreOrder extends Model
{
    public const STATUSES = ['pending', 'confirmed', 'shipped', 'delivered', 'completed', 'cancelled'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'stock_id',
        'buyer_id',
        'quantity_kg',
        'price_per_kg',
        'total_amount',
        'status',
        'notes',
        'delivery_method',
        'delivery_address',
        'delivery_city',
        'delivery_phone',
        'delivery_notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_kg' => 'decimal:2',
            'price_per_kg' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }
}
