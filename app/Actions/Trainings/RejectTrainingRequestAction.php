<?php

declare(strict_types=1);

namespace App\Actions\Trainings;

use App\Actions\Trainings\Concerns\LocksTrainingRequestForDecision;
use App\Enums\TrainingRequestStatus;
use App\Models\Employee;
use App\Models\TrainingRequest;
use Illuminate\Support\Facades\DB;

final class RejectTrainingRequestAction
{
    use LocksTrainingRequestForDecision;

    public function execute(TrainingRequest $trainingRequest, Employee $decidedBy, ?string $comment = null): TrainingRequest
    {
        return DB::transaction(function () use ($trainingRequest, $decidedBy, $comment) {
            $locked = $this->lockPendingTrainingRequest($trainingRequest);

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
