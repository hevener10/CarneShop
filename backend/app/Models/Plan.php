<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'limit_products',
        'limit_categories',
        'has_domain',
        'has_api',
        'is_active',
        'order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'has_domain' => 'boolean',
        'has_api' => 'boolean',
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    // Planos padrão
    public const FREE = 1;
    public const BASIC = 2;
    public const PREMIUM = 3;

    public function isFree(): bool
    {
        return $this->id === self::FREE;
    }

    public function isBasic(): bool
    {
        return $this->id === self::BASIC;
    }

    public function isPremium(): bool
    {
        return $this->id === self::PREMIUM;
    }

    public function stores()
    {
        return $this->hasMany(Store::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
