<?php

namespace App\Models;

use App\Enums\PrintJobKind;
use App\Enums\PrintJobStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrintJob extends Model
{
    protected $fillable = [
        'kind',
        'status',
        'kitchen_ticket_id',
        'order_id',
        'printer_name',
        'source',
        'attempts',
        'error_message',
        'last_attempt_at',
        'succeeded_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'kind' => PrintJobKind::class,
            'status' => PrintJobStatus::class,
            'attempts' => 'integer',
            'last_attempt_at' => 'datetime',
            'succeeded_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function kitchenTicket(): BelongsTo
    {
        return $this->belongsTo(KitchenTicket::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function printUrl(): string
    {
        return match ($this->kind) {
            PrintJobKind::KitchenTicket => route('print.kitchen-ticket', $this->kitchen_ticket_id),
            PrintJobKind::CashierReceipt => route('print.cashier-receipt', $this->order_id),
        };
    }

    public function referenceLabel(): string
    {
        return match ($this->kind) {
            PrintJobKind::KitchenTicket => 'تذكرة #'.$this->kitchen_ticket_id,
            PrintJobKind::CashierReceipt => 'فاتورة #'.$this->order_id,
        };
    }
}
