<?php

namespace App\Services\Sales;

use App\Enums\OrderStatus;
use App\Enums\TableStatus;
use App\Models\Order;
use App\Models\RestaurantTable;
use App\Models\SiteSetting;
use App\Models\TableSession;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TableGuestSessionService
{
    public const COOKIE_NAME = 'table_guest_session';

    public const SESSION_HOURS = 1;

    public function activate(RestaurantTable $table, string $accessCode, ?int $guestCount = null): TableSession
    {
        $expected = SiteSetting::tableAccessCode();

        if (! hash_equals($expected, trim($accessCode))) {
            throw ValidationException::withMessages([
                'access_code' => 'رمز التفعيل غير صحيح. صوّر الباركود الموحد مرة أخرى.',
            ]);
        }

        if ($this->tableIsOccupied($table)) {
            throw ValidationException::withMessages([
                'table_id' => 'هذه الطاولة مشغولة حالياً (يوجد طلب في المطبخ). اختر طاولة أخرى أو راجع الكاشير.',
            ]);
        }

        $session = TableSession::query()
            ->where('table_id', $table->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        if ($session && $session->isExpired()) {
            $this->closeSession($session, freeTable: ! $this->tableHasKitchenOrders($table));
            $session = null;
        }

        // Browse-only session (no kitchen order yet) can be replaced by a new guest
        if ($session && ! $this->tableHasKitchenOrders($table)) {
            $this->closeSession($session, freeTable: false);
            $session = null;
        }

        if ($session) {
            throw ValidationException::withMessages([
                'table_id' => 'هذه الطاولة مشغولة حالياً. اختر طاولة أخرى أو راجع الكاشير.',
            ]);
        }

        return TableSession::create([
            'table_id' => $table->id,
            'session_token' => Str::random(48),
            'status' => 'open',
            'guest_count' => $guestCount,
            'opened_at' => now(),
            'expires_at' => now()->addHours(self::SESSION_HOURS),
            'activated_via' => 'qr_unified',
        ])->fresh(['table']);
    }

    /**
     * Floor / guest availability: occupied only after an order reaches the kitchen.
     */
    public function tableIsOccupied(RestaurantTable $table): bool
    {
        if ($table->status === TableStatus::Occupied) {
            return true;
        }

        return $this->tableHasKitchenOrders($table);
    }

    public function tableHasKitchenOrders(RestaurantTable $table): bool
    {
        return Order::query()
            ->whereHas('tableSession', fn ($q) => $q->where('table_id', $table->id))
            ->whereIn('status', [
                OrderStatus::Confirmed->value,
                OrderStatus::Preparing->value,
                OrderStatus::Ready->value,
            ])
            ->exists();
    }

    public function markOccupied(RestaurantTable $table): void
    {
        if ($table->status !== TableStatus::Occupied) {
            $table->update(['status' => TableStatus::Occupied]);
        }
    }

    public function markFree(RestaurantTable $table): void
    {
        if ($table->status !== TableStatus::Free) {
            $table->update(['status' => TableStatus::Free]);
        }
    }

    /**
     * @deprecated Use tableIsOccupied() for guest availability.
     */
    public function tableHasActiveSession(RestaurantTable $table): bool
    {
        return $this->tableIsOccupied($table);
    }

    public function findActiveByToken(?string $token): ?TableSession
    {
        if (blank($token)) {
            return null;
        }

        $session = TableSession::query()
            ->where('session_token', $token)
            ->where('status', 'open')
            ->with(['table.branch'])
            ->first();

        if (! $session) {
            return null;
        }

        if ($session->isExpired()) {
            $this->closeSession($session, freeTable: ! $this->tableHasKitchenOrders($session->table));

            return null;
        }

        return $session;
    }

    public function ensureActive(TableSession $session): TableSession
    {
        if ($session->isExpired()) {
            $this->closeSession($session, freeTable: ! $this->tableHasKitchenOrders($session->table));

            throw ValidationException::withMessages([
                'session' => 'انتهت مدة الجلسة. يرجى إعادة تفعيل الطاولة.',
            ]);
        }

        return $session;
    }

    public function closeSession(TableSession $session, bool $freeTable = true): void
    {
        if ($session->status === 'open') {
            $session->update([
                'status' => 'closed',
                'closed_at' => now(),
            ]);
        }

        if ($freeTable && $session->table) {
            $this->markFree($session->table);
        } elseif ($freeTable) {
            $table = RestaurantTable::query()->find($session->table_id);
            if ($table) {
                $this->markFree($table);
            }
        }
    }
}
