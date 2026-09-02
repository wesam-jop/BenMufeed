<?php

namespace App\Services\Shared;

use App\Enums\ApprovalType;
use App\Models\ApprovalRequest;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ApprovalService
{
    public function requiresApproval(ApprovalType $type, float $value, ?Branch $branch = null): bool
    {
        $branch ??= auth()->user()?->branch;

        $thresholds = $branch?->settings['approval_thresholds'] ?? [];

        if (! array_key_exists($type->value, $thresholds)) {
            return match ($type) {
                ApprovalType::VoidInvoice,
                ApprovalType::Refund,
                ApprovalType::StockAdjustment => true,
                default => false,
            };
        }

        $threshold = (float) $thresholds[$type->value];

        return $value >= $threshold;
    }

    public function createRequest(
        ApprovalType $type,
        Model $requestable,
        User $requestedBy,
        ?string $reason = null,
        ?float $thresholdValue = null,
    ): ApprovalRequest {
        return ApprovalRequest::create([
            'type' => $type->value,
            'requestable_type' => $requestable->getMorphClass(),
            'requestable_id' => $requestable->getKey(),
            'requested_by' => $requestedBy->id,
            'status' => 'pending',
            'threshold_value' => $thresholdValue,
            'reason' => $reason,
        ]);
    }

    public function approve(ApprovalRequest $request, User $approver): ApprovalRequest
    {
        return DB::transaction(function () use ($request, $approver) {
            if ($request->status !== 'pending') {
                throw new RuntimeException('Only pending approval requests can be approved.');
            }

            if ($request->requested_by === $approver->id) {
                throw new RuntimeException('You cannot approve your own request.');
            }

            $request->update([
                'status' => 'approved',
                'approved_by' => $approver->id,
            ]);

            return $request->fresh();
        });
    }
}
