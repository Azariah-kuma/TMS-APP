<?php

declare(strict_types=1);

namespace App\Actions\Trainings;

use App\Actions\Trainings\Concerns\LocksTrainingRequestForDecision;
use App\Enums\TrainingRequestStatus;
use App\Exceptions\AlreadyEnrolledException;
use App\Models\Employee;
use App\Models\TrainingRequest;
use Illuminate\Support\Facades\DB;

final class ApproveTrainingRequestAction
{
    use LocksTrainingRequestForDecision;

    public function __construct(
        private readonly EnrollEmployeeInTrainingAction $enrollEmployeeInTrainingAction,
    ) {}

    /**
     * 申請を承認し、実際の受講記録（TrainingEnrollment）を作成する。
     *
     * 別ルート（人事の直接登録・一括登録）で既に同じ研修に受講登録済みだった場合は、
     * 「受講させたい」という承認者の意図は既に達成されているとみなし、
     * エラーにはせず既存の受講記録に紐付けて承認扱いにする。
     */
    public function execute(TrainingRequest $trainingRequest, Employee $decidedBy): TrainingRequest
    {
        return DB::transaction(function () use ($trainingRequest, $decidedBy) {
            $locked = $this->lockPendingTrainingRequest($trainingRequest);

            $locked->update([
                'status' => TrainingRequestStatus::Approved,
                'decided_by_employee_id' => $decidedBy->id,
                'decided_at' => now(),
            ]);

            try {
                $enrollment = $this->enrollEmployeeInTrainingAction->execute(
                    $locked->employee,
                    $locked->training,
                    $locked->due_at,
                );
            } catch (AlreadyEnrolledException) {
                $enrollment = $locked->employee->trainingEnrollments()
                    ->where('training_id', $locked->training_id)
                    ->firstOrFail();
            }

            $locked->update(['training_enrollment_id' => $enrollment->id]);

            return $locked->fresh();
        });
    }
}
