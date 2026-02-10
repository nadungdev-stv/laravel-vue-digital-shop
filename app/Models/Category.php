<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'icon',
        'status',
        'sort_order',
        'meta_title',
        'meta_description'
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
    public function getImageAttribute($value)
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

    public function getIconAttribute($value)
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
