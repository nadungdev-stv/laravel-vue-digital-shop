<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'variant_id',
        'product_name',
        'image',
        'quantity',
        'price',
        'account_delivered',
        'customer_account_info',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'account_delivered' => 'array',
        'customer_account_info' => 'array',
    ];

    public $timestamps = false; // migration created_at is useCurrent, but no updated_at

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
