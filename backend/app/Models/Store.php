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

    /**
     * Restringe a consulta a lojas ativas e nao suspensas.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('is_suspended', false);
    }

    /**
     * Restringe a consulta ao slug informado.
     */
    public function scopeBySlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }

    /**
     * Informa se a loja esta ativa para operacao.
     */
    public function isActive(): bool
    {
        return $this->is_active && !$this->is_suspended;
    }

    /**
     * Informa se a loja ainda esta no periodo de teste.
     */
    public function isOnTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Retorna o usuario dono da loja.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Retorna o plano contratado pela loja.
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Retorna as categorias cadastradas pela loja.
     */
    public function categories()
    {
        return $this->hasMany(Category::class)->orderBy('order');
    }

    /**
     * Retorna os produtos cadastrados pela loja.
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Retorna os kits cadastrados pela loja.
     */
    public function kits()
    {
        return $this->hasMany(Kit::class);
    }

    /**
     * Retorna os pedidos recebidos pela loja.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Retorna os banners configurados para a vitrine.
     */
    public function banners()
    {
        return $this->hasMany(Banner::class)->orderBy('order');
    }

    /**
     * Retorna os bairros atendidos pela loja.
     */
    public function neighborhoods()
    {
        return $this->hasMany(Neighborhood::class);
    }

    /**
     * Retorna as observacoes de produto configuradas para a loja.
     */
    public function productObservations()
    {
        return $this->hasMany(ProductObservation::class);
    }

    /**
     * Retorna o historico de assinaturas da loja.
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Retorna a assinatura mais recente da loja.
     */
    public function currentSubscription()
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    /**
     * Monta o link do WhatsApp da loja com DDI brasileiro quando necessario.
     */
    public function getWhatsappLink(): string
    {
        if (!$this->whatsapp) {
            return '';
        }

        // Remove caracteres nao numericos.
        $phone = preg_replace('/[^0-9]/', '', $this->whatsapp);

        // Adiciona pais se nao tiver.
        if ($phone === '') {
            return '';
        }

        if (!str_starts_with($phone, '55') && in_array(strlen($phone), [10, 11], true)) {
            $phone = '55' . $phone;
        }

        return "https://wa.me/{$phone}";
    }

    /**
     * Verifica se a loja ainda pode cadastrar produtos no plano atual.
     */
    public function canAddProduct(): bool
    {
        $limit = $this->plan?->limit_products;

        // Limite ilimitado.
        if ($limit === 0 || $limit === null) {
            return true;
        }

        return $this->products()->count() < $limit;
    }

    /**
     * Verifica se a loja ainda pode cadastrar categorias no plano atual.
     */
    public function canAddCategory(): bool
    {
        $limit = $this->plan?->limit_categories;

        // Limite ilimitado.
        if ($limit === 0 || $limit === null) {
            return true;
        }

        return $this->categories()->count() < $limit;
    }
}
