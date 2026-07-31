<?php

namespace App\Services;

use App\Models\RestaurantTable;
use App\Models\TableScanSession;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TableScanSessionService
{
    protected function maxHours(): int
    {
        return max(1, (int) config('store.table_scan_max_hours', 4));
    }

    public function issueFresh(RestaurantTable $table): TableScanSession
    {
        $this->revokeForTable($table->getKey());

        $now = now();

        return TableScanSession::query()->create([
            'restaurant_table_id' => $table->getKey(),
            'session_token' => Str::random(64),
            'expires_at' => $now->copy()->addHours($this->maxHours()),
            'last_seen_at' => $now,
        ]);
    }

    /**
     * جلسة مسح موحّدة (باركود واحد) — بدون طاولة حتى يختارها الزبون.
     * صالحة لمدة ثابتة (4 ساعات افتراضياً) من لحظة المسح.
     */
    public function issueVenueSession(): TableScanSession
    {
        $now = now();

        return TableScanSession::query()->create([
            'restaurant_table_id' => null,
            'session_token' => Str::random(64),
            'expires_at' => $now->copy()->addHours($this->maxHours()),
            'last_seen_at' => $now,
        ]);
    }

    /**
     * يجلب جلسة صالحة من التوكن (كوكي) — أو null إن انتهت/غير موجودة.
     */
    public function findValidByToken(string $scanToken): ?TableScanSession
    {
        if ($scanToken === '') {
            return null;
        }

        $session = TableScanSession::query()
            ->where('session_token', $scanToken)
            ->first();

        if ($session === null) {
            return null;
        }

        if (! $session->isValid()) {
            $session->delete();

            return null;
        }

        return $session;
    }

    /**
     * يربط طاولة بجلسة المسح (بعد اختيار الزبون لرقم الطاولة).
     */
    public function attachTable(TableScanSession $session, RestaurantTable $table): TableScanSession
    {
        $session->forceFill([
            'restaurant_table_id' => $table->getKey(),
            'last_seen_at' => now(),
        ])->save();

        return $session->fresh();
    }

    public function touch(TableScanSession $session): void
    {
        $session->forceFill(['last_seen_at' => now()])->save();
    }

    public function validateAndTouch(string $scanToken, RestaurantTable $table): TableScanSession
    {
        $session = TableScanSession::query()
            ->where('session_token', $scanToken)
            ->where('restaurant_table_id', $table->getKey())
            ->first();

        if ($session === null) {
            throw ValidationException::withMessages([
                'table_scan_session' => 'انتهت جلسة الطاولة — امسح رمز QR من جديد.',
            ]);
        }

        if ($session->isExpired() || $session->isIdleExpired()) {
            $session->delete();

            throw ValidationException::withMessages([
                'table_scan_session' => 'انتهت جلسة الطاولة — امسح رمز QR من جديد.',
            ]);
        }

        $session->forceFill(['last_seen_at' => now()])->save();

        return $session->fresh();
    }

    public function findValid(string $scanToken, RestaurantTable $table): ?TableScanSession
    {
        try {
            return $this->validateAndTouch($scanToken, $table);
        } catch (ValidationException) {
            return null;
        }
    }

    public function revoke(string $scanToken): void
    {
        TableScanSession::query()
            ->where('session_token', $scanToken)
            ->delete();
    }

    public function revokeForTable(int $restaurantTableId): void
    {
        TableScanSession::query()
            ->where('restaurant_table_id', $restaurantTableId)
            ->delete();
    }
}
