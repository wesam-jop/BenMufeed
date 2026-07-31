<?php

namespace App\Models;

use App\Enums\KitchenTicketKind;
use App\Enums\KitchenTicketStatus;
use App\Enums\OrderSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class KitchenTicket extends Model
{
    protected $fillable = [
        'order_id',
        'batch_token',
        'kind',
        'status',
        'table_number',
        'service_label',
        'source',
        'printed_at',
        'ready_at',
    ];

    protected function casts(): array
    {
        return [
            'kind' => KitchenTicketKind::class,
            'status' => KitchenTicketStatus::class,
            'source' => OrderSource::class,
            'table_number' => 'integer',
            'printed_at' => 'datetime',
            'ready_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItems(): BelongsToMany
    {
        return $this->belongsToMany(OrderItem::class, 'kitchen_ticket_items');
    }
}
