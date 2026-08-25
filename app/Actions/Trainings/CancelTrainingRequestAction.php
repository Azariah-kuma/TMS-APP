<?php

declare(strict_types=1);

namespace App\Actions\Trainings;

use App\Actions\Trainings\Concerns\LocksTrainingRequestForDecision;
use App\Enums\TrainingRequestStatus;
use App\Models\TrainingRequest;
use Illuminate\Support\Facades\DB;

final class CancelTrainingRequestAction
{
    use LocksTrainingRequestForDecision;

    /** 申請者本人が、承認待ちの申請を取り消す。 */
    public function execute(TrainingRequest $trainingRequest): TrainingRequest
    {
        return DB::transaction(function () use ($trainingRequest) {
            $locked = $this->lockPendingTrainingRequest($trainingRequest);

            $locked->update(['status' => TrainingRequestStatus::Cancelled]);

            return $locked->fresh();
        });
    }
}
