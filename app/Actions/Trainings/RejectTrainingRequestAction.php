<?php

declare(strict_types=1);

namespace App\Actions\Trainings;

use App\Actions\Trainings\Concerns\LocksTrainingRequestForDecision;
use App\Enums\TrainingRequestApprovalStageStatus;
use App\Enums\TrainingRequestStatus;
use App\Models\Employee;
use App\Models\TrainingRequest;
use Illuminate\Support\Facades\DB;

final class RejectTrainingRequestAction
{
    use LocksTrainingRequestForDecision;

    /** 多段階承認の途中どの段階であっても、却下されれば申請全体が即座に却下扱いになる（残りの段階は不要）。 */
    public function execute(TrainingRequest $trainingRequest, Employee $decidedBy, ?string $comment = null): TrainingRequest
    {
        return DB::transaction(function () use ($trainingRequest, $decidedBy, $comment) {
            $locked = $this->lockPendingTrainingRequest($trainingRequest);

            $locked->approvalHistory()->create([
                'stage_number' => $locked->current_approval_stage,
                'decided_by_employee_id' => $decidedBy->id,
                'status' => TrainingRequestApprovalStageStatus::Rejected,
                'decided_at' => now(),
                'comment' => $comment,
            ]);

            $locked->update([
                'status' => TrainingRequestStatus::Rejected,
                'decided_by_employee_id' => $decidedBy->id,
                'decided_at' => now(),
                'decision_comment' => $comment,
            ]);

            return $locked->fresh();
        });
    }
}
