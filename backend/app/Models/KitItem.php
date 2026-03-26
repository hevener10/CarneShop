<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KitItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'kit_id',
        'product_id',
        'product_name',
        'quantity',
        'unit',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function kit()
    {
        return $this->belongsTo(Kit::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
