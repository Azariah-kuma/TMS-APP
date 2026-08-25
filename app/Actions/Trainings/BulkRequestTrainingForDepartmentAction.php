<?php

declare(strict_types=1);

namespace App\Actions\Trainings;

use App\Exceptions\AlreadyEnrolledException;
use App\Exceptions\AlreadyRequestedException;
use App\Models\Employee;
use App\Models\Training;
use Illuminate\Database\Eloquent\Builder;

final class BulkRequestTrainingForDepartmentAction
{
    public function __construct(
        private readonly RequestTrainingAction $requestTrainingAction,
    ) {}

    /**
     * 指定した部署の従業員をまとめて研修申請する。
     * 上司が実行する場合は自分の部下（間接・委任経由を含む）に限定し、HRが実行する場合は
     * 部署内の在籍者全員が対象になる。既に受講登録済み・申請済みの従業員はスキップし、
     * バッチ全体は失敗させない。
     *
     * @return array{requested: int, skipped: int}
     */
    public function execute(Training $training, Employee $requestedBy, int $departmentId): array
    {
        $employees = Employee::query()
            ->whereNull('retired_at')
            ->whereHas(
                'currentAssignment',
                fn (Builder $assignment) => $assignment->where('department_id', $departmentId),
            )
            ->when(
                ! $requestedBy->isHr(),
                fn (Builder $query) => $query->whereIn('id', $requestedBy->subordinateIds()),
            )
            ->get();

        $requested = 0;
        $skipped = 0;

        foreach ($employees as $employee) {
            try {
                $this->requestTrainingAction->execute($employee, $requestedBy, $training);
                $requested++;
            } catch (AlreadyEnrolledException|AlreadyRequestedException) {
                $skipped++;
            }
        }

        return ['requested' => $requested, 'skipped' => $skipped];
    }
}
