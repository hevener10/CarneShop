<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'discount_price',
        'discount_percent',
        'image',
        'images',
        'is_active',
        'is_featured',
        'stock',
        'min_gramage',
        'max_gramage',
        'gramage_step',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'discount_percent' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'stock' => 'integer',
        'min_gramage' => 'integer',
        'max_gramage' => 'integer',
        'gramage_step' => 'integer',
        'images' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    // Accessors
    public function getFinalPriceAttribute(): float
    {
        return $this->discount_price ?? $this->price;
    }

    public function getHasDiscountAttribute(): bool
    {
        return $this->discount_price !== null && $this->discount_price < $this->price;
    }

    public function getDiscountPercentageAttribute(): ?int
    {
        if (!$this->has_discount) {
            return null;
        }
        
        return round((($this->price - $this->discount_price) / $this->price) * 100);
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'R$ ' . number_format($this->price, 2, ',', '.');
    }

    public function getFormattedDiscountPriceAttribute(): ?string
    {
        if (!$this->discount_price) {
            return null;
        }
        
        return 'R$ ' . number_format($this->discount_price, 2, ',', '.');
    }

    // Relações
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function variations()
    {
        return $this->hasMany(ProductVariation::class)->active();
    }

    public function allVariations()
    {
        return $this->hasMany(ProductVariation::class);
    }

    public function observations()
    {
        return $this->store->productObservations();
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Métodos
    public function calculatePrice(int $gramage): float
    {
        $pricePerGram = $this->final_price / 1000;
        return $pricePerGram * $gramage;
    }

    public function getGramages(): array
    {
        $gramages = [];
        
        for ($g = $this->min_gramage; $g <= $this->max_gramage; $g += $this->gramage_step) {
            $gramages[] = $g;
        }
        
        return $gramages;
    }
}
