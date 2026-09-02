<?php

namespace App\Models;

use App\Enums\ApprovalType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ApprovalRequest extends Model
{
    protected $fillable = [
        'type',
        'requestable_type',
        'requestable_id',
        'requested_by',
        'approved_by',
        'status',
        'threshold_value',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'type' => ApprovalType::class,
            'threshold_value' => 'decimal:2',
        ];
    }

    public function requestable(): MorphTo
    {
        return $this->morphTo();
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
