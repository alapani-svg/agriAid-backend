<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseGateLog extends Model
{
    public const DIRECTIONS = ['in', 'out'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'warehouse_id',
        'direction',
        'vehicle_id',
        'commodity',
        'weight_kg',
        'gate',
        'notes',
        'recorded_by_user_id',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'weight_kg' => 'decimal:2',
            'occurred_at' => 'datetime',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
