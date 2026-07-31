<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TableScanSession extends Model
{
    protected $fillable = [
        'restaurant_table_id',
        'session_token',
        'expires_at',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function restaurantTable(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isIdleExpired(): bool
    {
        $idleMinutes = (int) config('store.table_scan_idle_minutes', 0);

        if ($idleMinutes <= 0) {
            return false;
        }

        return $this->last_seen_at->addMinutes($idleMinutes)->isPast();
    }

    public function isValid(): bool
    {
        return ! $this->isExpired() && ! $this->isIdleExpired();
    }

    public function hasTable(): bool
    {
        return $this->restaurant_table_id !== null;
    }
}
