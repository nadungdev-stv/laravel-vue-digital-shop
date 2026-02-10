<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountStock extends Model
{
    protected $table = 'accounts_stock';

    protected $fillable = [
        'product_id',
        'account_username',
        'account_password',
        'account_note',
        'status',
        'sold_at',
    ];

    protected $casts = [
        'sold_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
