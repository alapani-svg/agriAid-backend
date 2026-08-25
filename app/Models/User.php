<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLES = [
        'farmer',
        'lender',
        'warehouse',
        'buyer',
        'government',
        'admin',
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'notification_preference',
        'role',
        'region',
        'organization',
        'status',
        'avatar_path',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function farmer(): HasOne
    {
        return $this->hasOne(Farmer::class);
    }

    public function routeNotificationForMail($notification)
    {
        return $this->email;
    }

    public function routeNotificationForVonage($notification)
    {
        return $this->phone;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function avatarUrl(): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->avatar_path);
    }

    /**
     * Whether this user satisfies the given role for authorization purposes.
     * Admins implicitly satisfy any role check (consistent with EnsureUserRole).
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role || $this->role === 'admin';
    }
}
