<?php

namespace App\Services\Accounting;

use App\Enums\CashMovementType;
use App\Enums\PaymentMethod;
use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\CashRegisterSession;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CashRegisterService
{
    public function resolveRegisterForUser(User $user): CashRegister
    {
        $register = CashRegister::query()
            ->where('branch_id', $user->branch_id)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        if ($register === null) {
            throw new RuntimeException('لا يوجد صندوق نشط لهذا الفرع.');
        }

        return $register;
    }

    public function getOrCreateDailySession(
        CashRegister $register,
        User $user,
        ?Carbon $date = null,
    ): CashRegisterSession {
        $businessDate = ($date ?? now())->toDateString();

        return DB::transaction(function () use ($register, $user, $businessDate) {
            $session = CashRegisterSession::query()
                ->where('cash_register_id', $register->id)
                ->whereDate('session_date', $businessDate)
                ->first();

            if ($session !== null) {
                if ($session->status === 'closed') {
                    $session->update([
                        'status' => 'open',
                        'closed_at' => null,
                        'actual_cash' => null,
                        'difference' => null,
                    ]);
                }

                return $session->fresh();
            }

            return CashRegisterSession::create([
                'cash_register_id' => $register->id,
                'user_id' => $user->id,
                'session_date' => $businessDate,
                'status' => 'open',
                'opening_balance' => 0,
                'expected_cash' => 0,
                'opened_at' => now(),
                'notes' => 'سجل يومي تلقائي',
            ]);
        });
    }

    public function resolveTodaySession(User $user, bool $createIfMissing = true): CashRegisterSession
    {
        $register = $this->resolveRegisterForUser($user);

        if ($createIfMissing) {
            return $this->getOrCreateDailySession($register, $user);
        }

        $session = CashRegisterSession::query()
            ->where('cash_register_id', $register->id)
            ->whereDate('session_date', now()->toDateString())
            ->first();

        if ($session === null) {
            throw new RuntimeException('لا يوجد جرد لهذا اليوم.');
        }

        return $session;
    }

    /**
     * @return array<string, mixed>
     */
    public function dailySummary(CashRegisterSession $session): array
    {
        $session->loadMissing('cashRegister', 'payments.invoice');

        $payments = $session->payments;
        $invoiceIds = $payments->pluck('invoice_id')->filter()->unique();

        return [
            'id' => $session->id,
            'session_date' => $session->session_date?->format('Y-m-d') ?? $session->opened_at?->format('Y-m-d'),
            'status' => $session->status,
            'register_name' => $session->cashRegister?->name,
            'opening_balance' => (float) $session->opening_balance,
            'expected_cash' => (float) $session->expected_cash,
            'actual_cash' => $session->actual_cash !== null ? (float) $session->actual_cash : null,
            'difference' => $session->difference !== null ? (float) $session->difference : null,
            'invoice_count' => $invoiceIds->count(),
            'total_sales' => round((float) $payments->sum('amount'), 2),
            'cash_sales' => round((float) $payments->where('method', PaymentMethod::Cash->value)->sum('amount'), 2),
            'card_sales' => round((float) $payments->where('method', '!=', PaymentMethod::Cash->value)->sum('amount'), 2),
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, Invoice>
     */
    public function dailyInvoices(CashRegisterSession $session)
    {
        return Invoice::query()
            ->whereHas('payments', fn ($query) => $query->where('cash_register_session_id', $session->id))
            ->with([
                'payments' => fn ($query) => $query->where('cash_register_session_id', $session->id),
                'user:id,name',
            ])
            ->withCount('items')
            ->latest()
            ->get();
    }

    public function openSession(
        CashRegister $register,
        float $openingBalance,
        User $user,
        ?string $notes = null,
    ): CashRegisterSession {
        $session = $this->getOrCreateDailySession($register, $user);

        if ($openingBalance <= 0) {
            return $session;
        }

        return DB::transaction(function () use ($session, $openingBalance, $notes) {
            if ((float) $session->opening_balance > 0) {
                return $session;
            }

            $session->update([
                'opening_balance' => $openingBalance,
                'notes' => trim(($session->notes ?? '').($notes ? "\n{$notes}" : '')),
            ]);

            $this->recordMovement(
                $session->fresh(),
                CashMovementType::Deposit,
                $openingBalance,
                notes: 'Opening balance',
            );

            return $session->fresh();
        });
    }

    public function closeSession(
        CashRegisterSession $session,
        float $actualCash,
        ?string $notes = null,
    ): CashRegisterSession {
        return DB::transaction(function () use ($session, $actualCash, $notes) {
            if ($session->status !== 'open') {
                throw new RuntimeException('يمكن إغلاق الجلسات المفتوحة فقط.');
            }

            $expectedCash = (float) $session->expected_cash;
            $difference = $actualCash - $expectedCash;

            if ($difference != 0.0) {
                $this->recordMovement(
                    $session,
                    CashMovementType::Adjustment,
                    abs($difference),
                    notes: $difference > 0 ? 'Cash over at close' : 'Cash short at close',
                );
            }

            $session->update([
                'status' => 'closed',
                'actual_cash' => $actualCash,
                'difference' => $difference,
                'closed_at' => now(),
                'notes' => trim(($session->notes ?? '').($notes ? "\n{$notes}" : '')),
            ]);

            return $session->fresh();
        });
    }

    public function recordPayment(
        CashRegisterSession $session,
        Invoice $invoice,
        PaymentMethod|string $method,
        float $amount,
        ?User $user = null,
        ?string $reference = null,
    ): Payment {
        return DB::transaction(function () use ($session, $invoice, $method, $amount, $user, $reference) {
            $methodValue = $method instanceof PaymentMethod ? $method->value : $method;

            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'cash_register_session_id' => $session->id,
                'method' => $methodValue,
                'amount' => $amount,
                'reference' => $reference,
                'user_id' => $user?->id ?? auth()->id(),
                'paid_at' => now(),
            ]);

            $invoice->increment('paid', $amount);
            $invoice->decrement('balance', $amount);

            if ($methodValue === PaymentMethod::Cash->value) {
                $this->recordMovement(
                    $session,
                    CashMovementType::Sale,
                    $amount,
                    $payment,
                );
            }

            return $payment;
        });
    }

    public function recordMovement(
        CashRegisterSession $session,
        CashMovementType|string $type,
        float $amount,
        ?Model $reference = null,
        ?string $notes = null,
    ): CashMovement {
        return DB::transaction(function () use ($session, $type, $amount, $reference, $notes) {
            $typeValue = $type instanceof CashMovementType ? $type->value : $type;

            $movement = CashMovement::create([
                'session_id' => $session->id,
                'type' => $typeValue,
                'amount' => $amount,
                'reference_type' => $reference ? $reference->getMorphClass() : null,
                'reference_id' => $reference?->getKey(),
                'notes' => $notes,
            ]);

            $delta = match ($typeValue) {
                CashMovementType::Sale->value,
                CashMovementType::Deposit->value => $amount,
                CashMovementType::Refund->value,
                CashMovementType::Expense->value,
                CashMovementType::Withdrawal->value => -$amount,
                default => 0,
            };

            if ($delta !== 0.0) {
                $session->increment('expected_cash', $delta);
            }

            return $movement;
        });
    }
}
