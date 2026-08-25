<?php

declare(strict_types=1);

namespace App\Actions\Reports;

use App\Models\Training;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class BuildTrainingSummaryReportAction
{
    /**
     * 研修別・部署別に、受講記録のステータス内訳（未着手・受講中・完了）を集計する。
     *
     * @return array{
     *     by_training: list<array{id: int, name: string, not_started: int, in_progress: int, completed: int}>,
     *     by_department: list<array{id: int, name: string, not_started: int, in_progress: int, completed: int}>,
     * }
     */
    public function execute(): array
    {
        return [
            'by_training' => $this->byTraining(),
            'by_department' => $this->byDepartment(),
        ];
    }

    /** @return list<array{id: int, name: string, not_started: int, in_progress: int, completed: int}> */
    private function byTraining(): array
    {
        $rows = DB::table('training_enrollments')
            ->select('training_id', 'status', DB::raw('count(*) as count'))
            ->groupBy('training_id', 'status')
            ->get()
            ->groupBy('training_id');

        $titles = Training::query()->whereIn('id', $rows->keys())->pluck('title', 'id');

        return $rows
            ->map(fn (Collection $group, int $trainingId) => $this->summarize(
                $trainingId,
                $titles->get($trainingId, ''),
                $group,
            ))
            ->values()
            ->all();
    }

    /** @return list<array{id: int, name: string, not_started: int, in_progress: int, completed: int}> */
    private function byDepartment(): array
    {
        $rows = DB::table('training_enrollments')
            ->join('employees', 'employees.id', '=', 'training_enrollments.employee_id')
            ->join('employee_assignments', function ($join) {
                $join->on('employee_assignments.employee_id', '=', 'employees.id')
                    ->whereNull('employee_assignments.ended_at');
            })
            ->join('departments', 'departments.id', '=', 'employee_assignments.department_id')
            ->select(
                'departments.id as department_id',
                'departments.name as department_name',
                'training_enrollments.status',
                DB::raw('count(*) as count'),
            )
            ->groupBy('departments.id', 'departments.name', 'training_enrollments.status')
            ->get()
            ->groupBy('department_id');

        return $rows
            ->map(fn (Collection $group, int $departmentId) => $this->summarize(
                $departmentId,
                $group->first()->department_name,
                $group,
            ))
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, object{status: string, count: int}>  $rows
     * @return array{id: int, name: string, not_started: int, in_progress: int, completed: int}
     */
    private function summarize(int $id, string $name, Collection $rows): array
    {
        $byStatus = $rows->pluck('count', 'status');

        return [
            'id' => $id,
            'name' => $name,
            'not_started' => (int) ($byStatus['not_started'] ?? 0),
            'in_progress' => (int) ($byStatus['in_progress'] ?? 0),
            'completed' => (int) ($byStatus['completed'] ?? 0),
        ];
    }
}
