<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    // Roles
    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_STORE_OWNER = 'store_owner';
    public const ROLE_CUSTOMER = 'customer';

    // Verificações
    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isStoreOwner(): bool
    {
        return $this->role === self::ROLE_STORE_OWNER;
    }

    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    // Relações
    public function stores()
    {
        return $this->hasMany(Store::class);
    }

    public function store()
    {
        return $this->hasOne(Store::class);
    }
}
