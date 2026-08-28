<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Buyer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'company_name',
        'city',
        'region',
        'contact_email',
        'contact_phone',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
