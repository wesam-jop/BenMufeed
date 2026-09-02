<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CustomerFavorite extends Pivot
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'customer_favorites';

    protected $fillable = [
        'customer_id',
        'product_id',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
