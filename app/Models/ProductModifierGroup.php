<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ProductModifierGroup extends Pivot
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'product_modifier_groups';

    protected $fillable = [
        'product_id',
        'modifier_group_id',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function modifierGroup(): BelongsTo
    {
        return $this->belongsTo(ModifierGroup::class);
    }
}
