<?php

declare(strict_types=1);

namespace App\Actions\Employees;

use App\Exceptions\InvalidAssignmentPeriodException;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class TransferEmployeeAction
{
    /**
     * 従業員の部署・役職・上司を異動させる。
     */
    public function execute(
        Employee $employee,
        int $departmentId,
        int $positionId,
        ?int $managerId,
        Carbon $startedAt,
    ): EmployeeAssignment {
        if ($managerId !== null && $this->wouldCreateCycle($employee, $managerId)) {
            throw new InvalidAssignmentPeriodException(
                '指定された上司は、この従業員の部下です。循環した指揮系統は作成できません。'
            );
        }

        return DB::transaction(function () use ($employee, $departmentId, $positionId, $managerId, $startedAt) {
            $current = $employee->currentAssignment()->lockForUpdate()->first();

            if ($current !== null && $startedAt->lt($current->started_at)) {
                throw new InvalidAssignmentPeriodException(
                    '新しい異動日は、現在の割り当て開始日より前に設定できません。'
                );
            }

            if ($current !== null) {
                $current->update(['ended_at' => $startedAt->copy()->subDay()]);
            }

            return $employee->assignments()->create([
                'department_id' => $departmentId,
                'position_id' => $positionId,
                'manager_id' => $managerId,
                'started_at' => $startedAt,
                'ended_at' => null,
            ]);
        });
    }

    /**
     * $managerId を上司として設定すると、指揮系統が循環してしまうかどうか判定。
     */
    private function wouldCreateCycle(Employee $employee, int $managerId): bool
    {
        return $managerId === $employee->id || $employee->hierarchySubordinateIds()->contains($managerId);
    }
}
