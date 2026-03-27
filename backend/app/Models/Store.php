<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'plan_id',
        'name',
        'slug',
        'description',
        'logo',
        'primary_color',
        'whatsapp',
        'address',
        'city',
        'state',
        'latitude',
        'longitude',
        'minimum_order',
        'delivery_fee',
        'free_delivery',
        'is_active',
        'is_suspended',
        'suspension_reason',
        'trial_ends_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'minimum_order' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'free_delivery' => 'boolean',
        'is_active' => 'boolean',
        'is_suspended' => 'boolean',
        'trial_ends_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($store) {
            if (empty($store->uuid)) {
                $store->uuid = (string) Str::uuid();
            }
            if (empty($store->slug)) {
                $store->slug = Str::slug($store->name);
            }
        });
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('is_suspended', false);
    }

    public function scopeBySlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }

    // Verificações
    public function isActive(): bool
    {
        return $this->is_active && !$this->is_suspended;
    }

    public function isOnTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    // Relações
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class)->orderBy('order');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function kits()
    {
        return $this->hasMany(Kit::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function banners()
    {
        return $this->hasMany(Banner::class)->orderBy('order');
    }

    public function neighborhoods()
    {
        return $this->hasMany(Neighborhood::class);
    }

    public function productObservations()
    {
        return $this->hasMany(ProductObservation::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function currentSubscription()
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    // Métodos auxiliares
    public function getWhatsappLink(): string
    {
        if (!$this->whatsapp) {
            return '';
        }
        
        // Remove caracteres não numéricos
        $phone = preg_replace('/[^0-9]/', '', $this->whatsapp);
        
        // Adiciona país se não tiver
        if ($phone === '') {
            return '';
        }

        if (!str_starts_with($phone, '55') && in_array(strlen($phone), [10, 11], true)) {
            $phone = '55' . $phone;
        }
        
        return "https://wa.me/{$phone}";
    }

    public function canAddProduct(): bool
    {
        $limit = $this->plan?->limit_products;
        
        // Limite ilimitado
        if ($limit === 0 || $limit === null) {
            return true;
        }
        
        return $this->products()->count() < $limit;
    }

    public function canAddCategory(): bool
    {
        $limit = $this->plan?->limit_categories;
        
        // Limite ilimitado
        if ($limit === 0 || $limit === null) {
            return true;
        }
        
        return $this->categories()->count() < $limit;
    }
}
