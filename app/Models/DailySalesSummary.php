<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailySalesSummary extends Model
{
    protected $primaryKey = 'sale_date';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'sale_date',
        'pos_orders_count',
        'takeaway_count',
        'table_count',
        'takeaway_total',
        'table_total',
        'pos_total',
        'collected_total',
        'invoice_count',
        'invoice_total',
        'grand_total',
        'open_tables_count',
        'open_tables_total',
        'computed_at',
    ];

    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
            'takeaway_total' => 'decimal:2',
            'table_total' => 'decimal:2',
            'pos_total' => 'decimal:2',
            'collected_total' => 'decimal:2',
            'invoice_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'open_tables_total' => 'decimal:2',
            'computed_at' => 'datetime',
        ];
    }
}
