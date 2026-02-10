<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductGallery extends Model
{
    use HasFactory;

    protected $table = 'product_gallery';
    public $timestamps = false; // Table doesn't have created_at/updated_at

    protected $fillable = ['product_id', 'image_path', 'sort_order'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getImagePathAttribute($value)
    {
        if ($value) {
            // Remove /public/ or public/ prefix
            $value = str_replace('/public/', '/', $value);
            if (str_starts_with($value, 'public/')) {
                $value = '/' . substr($value, 7);
            }
            return $value;
        }
        return $value;
    }
}
