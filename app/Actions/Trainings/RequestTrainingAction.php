<?php

declare(strict_types=1);

namespace App\Actions\Trainings;

use App\Actions\Employees\Concerns\GuardsAgainstRetiredEmployees;
use App\Enums\TrainingRequestStatus;
use App\Exceptions\AlreadyEnrolledException;
use App\Exceptions\AlreadyRequestedException;
use App\Models\Employee;
use App\Models\Training;
use App\Models\TrainingRequest;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;

final class RequestTrainingAction
{
    use GuardsAgainstRetiredEmployees;

    /**
     * $employee の研修受講を申請する。$requestedBy が $employee 自身なら本人申請、
     * それ以外（上司）なら代理申請になる（承認可否はPolicy側で区別する）。
     */
    public function execute(
        Employee $employee,
        Employee $requestedBy,
        Training $training,
        ?string $reason = null,
        ?Carbon $dueAt = null,
    ): TrainingRequest {
        $this->assertNotRetired($employee, '退職済みの従業員は研修を申請できません。');

        $alreadyEnrolled = $employee->trainingEnrollments()
            ->where('training_id', $training->id)
            ->exists();

        if ($alreadyEnrolled) {
            throw new AlreadyEnrolledException('この研修には既に受講登録されています。');
        }

        $alreadyPending = $employee->trainingRequests()
            ->where('training_id', $training->id)
            ->where('status', TrainingRequestStatus::Pending)
            ->exists();

        if ($alreadyPending) {
            throw new AlreadyRequestedException('この研修は既に申請済みで、承認待ちです。');
        }

        // 必要な承認段階数は申請時点のTraining設定をスナップショットする
        // （申請作成後にTraining側の設定が変わっても、進行中の申請には影響させないため）。
        $requiredApprovalStages = $training->requires_multistage_approval
            ? $training->approval_stage_count
            : 1;

        // 最終的な一意性は training_requests テーブルの部分ユニークインデックスが保証する。
        // 制約に違反した場合はここで捕捉して同じドメイン例外に変換する。
        try {
            return $employee->trainingRequests()->create([
                'requested_by_employee_id' => $requestedBy->id,
                'training_id' => $training->id,
                'status' => TrainingRequestStatus::Pending,
                'reason' => $reason,
                'due_at' => $dueAt,
                'required_approval_stages' => $requiredApprovalStages,
                'current_approval_stage' => 1,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw new AlreadyRequestedException('この研修は既に申請済みで、承認待ちです。');
        }
    }
}
