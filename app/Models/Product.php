<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'content',
        'image',
        'price',
        'sale_price',
        'status',
        'featured',
        'views',
        'sort_order',
        'meta_title',
        'meta_description',
        'delivery_type',
        'stock_quantity',
        'tags',
        'features',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'featured' => 'boolean',
        'views' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'product_categories');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function gallery(): HasMany
    {
        return $this->hasMany(ProductGallery::class)->orderBy('sort_order');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function getImageAttribute($value)
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

    public function getFirstGalleryImageAttribute($value)
    {
        if ($value) {
            $value = str_replace('/public/', '/', $value);
            if (str_starts_with($value, 'public/')) {
                $value = '/' . substr($value, 7);
            }
            return $value;
        }
        return $value;
    }
}
