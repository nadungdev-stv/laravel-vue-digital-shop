<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'slug',
        'variant_title',
        'variant_image',
        'price',
        'sale_price',
        'stock_quantity',
        'is_main',
        'status',
        'sort_order',
        'delivery_type',
        'duration',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'is_main' => 'boolean',
        'stock_quantity' => 'integer',
        'sort_order' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getVariantImageAttribute($value)
    {
        if ($value) {
            // Fix path: remove /public/ prefix if present
            $value = str_replace('/public/', '/', $value);
            if (str_starts_with($value, 'public/')) {
                $value = '/' . substr($value, 7);
            }
            return $value;
        }
        return $value;
    }
}
