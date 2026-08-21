<?php

declare(strict_types=1);

namespace App\Actions\Employees;

use App\Actions\Employees\Concerns\GuardsAgainstRetiredEmployees;
use App\Exceptions\InvalidAssignmentPeriodException;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class TransferEmployeeAction
{
    use GuardsAgainstRetiredEmployees;

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
        return DB::transaction(function () use ($employee, $departmentId, $positionId, $managerId, $startedAt) {
            // 対象従業員と上司候補、双方の「現在の割り当て」行をID昇順でロックする。
            // 常に同じ順序でロックすることでデッドロックを避けつつ、「AをBの部下に」
            // 「BをAの部下に」という2つの異動が同時に実行された場合でも、
            // 片方がもう片方の完了を待つように直列化し、循環の作成を防ぐ。
            $this->lockCurrentAssignments($employee->id, $managerId);

            $this->assertNotRetired($employee, '退職済みの従業員を異動させることはできません。');

            if ($managerId !== null) {
                $this->assertNotRetired(
                    Employee::find($managerId),
                    '退職済みの従業員を上司に指定することはできません。',
                );
            }

            if ($managerId !== null && $this->wouldCreateCycle($employee, $managerId)) {
                throw new InvalidAssignmentPeriodException(
                    '指定された上司は、この従業員の部下です。循環した指揮系統は作成できません。'
                );
            }

            $current = $employee->currentAssignment()->first();

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
     * 対象従業員と上司候補、双方の「現在の割り当て」行をID昇順でロックする。
     *
     * 常に同じ順序でロックが取得される保証を得るには、ID昇順で1件ずつ別クエリとしてロックを取得する必要あり。
     * 3者以上が絡む循環異動（A→B→C→A）が同時に実行された場合でも、最初にどれか1つの行をロックできた異動だけが先に進み、
     * 残りは待たされて、最新の状態に基づいて循環チェックをやり直すことになる。
     */
    private function lockCurrentAssignments(int $employeeId, ?int $managerId): void
    {
        $employeeIds = collect([$employeeId, $managerId])->filter()->unique()->sort()->values();

        foreach ($employeeIds as $id) {
            EmployeeAssignment::query()
                ->where('employee_id', $id)
                ->whereNull('ended_at')
                ->lockForUpdate()
                ->get();
        }
    }

    /**
     * $managerId を上司として設定すると、指揮系統が循環してしまうかどうか判定。
     */
    private function wouldCreateCycle(Employee $employee, int $managerId): bool
    {
        return $managerId === $employee->id || $employee->hierarchySubordinateIds()->contains($managerId);
    }
}
