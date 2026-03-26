<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Neighborhood extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'name',
        'delivery_fee',
        'minimum_order',
        'is_active',
    ];

    protected $casts = [
        'delivery_fee' => 'decimal:2',
        'minimum_order' => 'decimal:2',
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
}
