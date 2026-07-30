<?php

namespace App\Identity\Infrastructure\Persistence\EloquentModels;

use Illuminate\Database\Eloquent\Model;

class EloquentOTP extends Model
{
    protected $table = 'otps';

    protected $fillable = [
        'id',
        'user_id',
        'code',
        'purpose',
        'expires_at',
        'verified',
        'verified_at',
        'created_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'verified' => 'boolean',
        'created_at' => 'datetime',
    ];

    public $timestamps = false;

    protected $keyType = 'string';
    public $incrementing = false;
}
