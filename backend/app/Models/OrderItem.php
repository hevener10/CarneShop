<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'variation_name',
        'quantity',
        'gramage',
        'unit_price',
        'subtotal',
        'observations',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'gramage' => 'integer',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getFormattedSubtotalAttribute(): string
    {
        return 'R$ ' . number_format($this->subtotal, 2, ',', '.');
    }
}
