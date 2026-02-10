<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = [
        'title',
        'image',
        'link',
        'category_id',
        'description',
        'sort_order',
        'status',
    ];
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
}

