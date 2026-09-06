<?php

declare(strict_types=1);

namespace App\Actions\Trainings;

use App\Actions\Trainings\Concerns\LocksTrainingRequestForDecision;
use App\Enums\TrainingRequestApprovalStageStatus;
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
     * 現在の段階を承認する。
     *
     * 多段階承認（required_approval_stages > 1）で、まだ次の段階が残っている場合は
     * 段階だけ進めて申請全体は引き続き承認待ち（pending）のままにする。
     * 最終段階の承認であれば、従来通り申請全体を承認済みにし、実際の受講記録
     * （TrainingEnrollment）を作成する。
     *
     * 別ルート（人事の直接登録・一括登録）で既に同じ研修に受講登録済みだった場合は、
     * 「受講させたい」という承認者の意図は既に達成されているとみなし、
     * エラーにはせず既存の受講記録に紐付けて承認扱いにする。
     */
    public function execute(TrainingRequest $trainingRequest, Employee $decidedBy): TrainingRequest
    {
        return DB::transaction(function () use ($trainingRequest, $decidedBy) {
            $locked = $this->lockPendingTrainingRequest($trainingRequest);

            $stageNumber = $locked->current_approval_stage;

            $locked->approvalHistory()->create([
                'stage_number' => $stageNumber,
                'decided_by_employee_id' => $decidedBy->id,
                'status' => TrainingRequestApprovalStageStatus::Approved,
                'decided_at' => now(),
            ]);

            if ($stageNumber < $locked->required_approval_stages) {
                $locked->update(['current_approval_stage' => $stageNumber + 1]);

                return $locked->fresh();
            }

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
