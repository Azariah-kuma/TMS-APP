<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\TrainingRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/*
 * TrainingRequest(研修受講申請)のリソースクラス
 */

/** @mixin TrainingRequest */
final class TrainingRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'employee_name' => $this->whenLoaded('employee', fn () => $this->employee->user->name),
            'requested_by_employee_id' => $this->requested_by_employee_id,
            'requested_by_name' => $this->whenLoaded('requestedBy', fn () => $this->requestedBy?->user->name),
            'is_self_requested' => $this->isSelfRequested(),
            'training' => new TrainingResource($this->whenLoaded('training')),
            'status' => $this->status->value,
            'reason' => $this->reason,
            'due_at' => $this->due_at?->toDateString(),
            'required_approval_stages' => $this->required_approval_stages,
            'current_approval_stage' => $this->current_approval_stage,
            'approval_history' => TrainingRequestApprovalStageResource::collection($this->whenLoaded('approvalHistory')),
            'decided_by_employee_id' => $this->decided_by_employee_id,
            'decided_by_name' => $this->whenLoaded('decidedBy', fn () => $this->decidedBy?->user->name),
            'decided_at' => $this->decided_at?->toISOString(),
            'decision_comment' => $this->decision_comment,
            'requested_at' => $this->created_at?->toISOString(),
            'can_decide' => $request->user()->can('approve', $this->resource),
            'can_cancel' => $request->user()->can('cancel', $this->resource),
            'already_enrolled' => $this->whenLoaded(
                'employee',
                fn () => $this->employee->trainingEnrollments()->where('training_id', $this->training_id)->exists(),
            ),
        ];
    }
}
