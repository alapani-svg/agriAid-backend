<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'manager_user_id',
        'farmer_id',
        'name',
        'region',
        'village',
        'address',
        'capacity_total_kg',
        'status',
        'notes',
        'aeration_active',
        'aeration_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'capacity_total_kg' => 'decimal:2',
            'aeration_active' => 'boolean',
            'aeration_updated_at' => 'datetime',
        ];
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'farmer_id');
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function sensorReadings(): HasMany
    {
        return $this->hasMany(WarehouseSensorReading::class);
    }

    public function gateLogs(): HasMany
    {
        return $this->hasMany(WarehouseGateLog::class);
    }
}
