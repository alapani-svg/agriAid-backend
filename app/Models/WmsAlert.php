<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WmsAlert extends Model
{
    protected $table = 'wms_alerts';

    protected $fillable = [
        'stock_id',
        'warehouse_id',
        'farmer_id',
        'crop_type',
        'crop_display_name',
        'lot_id',
        'quantity_kg',
        'quality_grade',
        'shelf_life_hours',
        'status',
        'alert_level',
        'recommended_action',
        'alert_reasons',
        'current_temperature_c',
        'current_humidity_pct',
        'acknowledged',
        'acknowledged_at',
        'acknowledged_by',
    ];

    protected $casts = [
        'alert_reasons' => 'array',
        'acknowledged' => 'boolean',
        'acknowledged_at' => 'datetime',
        'current_temperature_c' => 'float',
        'current_humidity_pct' => 'float',
        'quantity_kg' => 'float',
        'shelf_life_hours' => 'integer',
        'alert_level' => 'integer',
    ];

    protected $keyType = 'int';

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class, 'stock_id', 'id');
    }
}
