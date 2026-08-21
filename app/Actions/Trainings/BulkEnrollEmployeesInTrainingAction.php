<?php

declare(strict_types=1);

namespace App\Actions\Trainings;

use App\Exceptions\AlreadyEnrolledException;
use App\Models\Employee;
use App\Models\Training;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

final class BulkEnrollEmployeesInTrainingAction
{
    public function __construct(
        private readonly EnrollEmployeeInTrainingAction $enrollEmployeeInTrainingAction,
    ) {}

    /**
     * 指定した部署の全従業員（$departmentId が null の場合は在籍中の全従業員）を、
     * まとめて研修に受講登録する。既に登録済みの従業員はスキップする
     * （一括操作のため、1件の重複でバッチ全体を失敗させない）。
     * 退職済みの従業員は対象から除外する。
     *
     * @return array{enrolled: int, skipped: int}
     */
    public function execute(Training $training, ?int $departmentId, ?Carbon $dueAt): array
    {
        $employees = Employee::query()
            ->whereNull('retired_at')
            ->when(
                $departmentId !== null,
                fn (Builder $query) => $query->whereHas(
                    'currentAssignment',
                    fn (Builder $assignment) => $assignment->where('department_id', $departmentId),
                ),
            )
            ->get();

        $enrolled = 0;
        $skipped = 0;

        foreach ($employees as $employee) {
            try {
                $this->enrollEmployeeInTrainingAction->execute($employee, $training, $dueAt);
                $enrolled++;
            } catch (AlreadyEnrolledException) {
                $skipped++;
            }
        }

        return ['enrolled' => $enrolled, 'skipped' => $skipped];
    }
}
