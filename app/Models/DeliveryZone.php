<?php

namespace App\Models;

use App\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;

class DeliveryZone extends Model
{
    use BelongsToBranch;

    protected $fillable = [
        'branch_id',
        'name',
        'fee',
        'min_order',
        'polygon',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'fee' => 'decimal:2',
            'min_order' => 'decimal:2',
            'polygon' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
