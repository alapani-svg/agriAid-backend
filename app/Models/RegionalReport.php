<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegionalReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'region',
        'city',
        'report_type',
        'period_start',
        'period_end',
        'total_production_mt',
        'warehouse_stock_mt',
        'financing_volume_fcfa',
        'active_farmers',
        'metrics',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_production_mt' => 'decimal:2',
        'warehouse_stock_mt' => 'decimal:2',
        'financing_volume_fcfa' => 'decimal:2',
        'active_farmers' => 'integer',
        'metrics' => 'array',
    ];
}
