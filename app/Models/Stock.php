<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    public const STATUSES = ['in_stock', 'reserved', 'withdrawn', 'sold'];

    protected $fillable = [
        'warehouse_id',
        'harvest_id',
        'crop_type',
        'quantity_kg',
        'capacity_used',
        'capacity_total',
        'entry_date',
        'exit_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_kg' => 'decimal:2',
            'capacity_used' => 'decimal:2',
            'capacity_total' => 'decimal:2',
            'entry_date' => 'date',
            'exit_date' => 'date',
        ];
    }

    public function harvest(): BelongsTo
    {
        return $this->belongsTo(Harvest::class);
    }
}
