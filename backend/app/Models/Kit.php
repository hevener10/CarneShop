<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kit extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'name',
        'slug',
        'description',
        'items_list',
        'price',
        'price_per_person',
        'min_people',
        'max_people',
        'estimated_weight',
        'image',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'price_per_person' => 'decimal:2',
        'estimated_weight' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function items()
    {
        return $this->hasMany(KitItem::class);
    }
}
