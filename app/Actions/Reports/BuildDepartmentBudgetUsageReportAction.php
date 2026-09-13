<?php

declare(strict_types=1);

namespace App\Actions\Reports;

use App\Models\Department;
use App\Support\FiscalYear;
use Illuminate\Support\Facades\DB;

final class BuildDepartmentBudgetUsageReportAction
{
    /**
     * 部署ごとに、指定した年度の研修予算と消費額（単価が設定された研修の受講登録数×単価）を集計する。
     *
     * 消費額は「現在の配属部署」を基準に集計する（BuildTrainingSummaryReportActionの部署別集計と同じ考え方）。
     *
     * @return list<array{
     *     department_id: int,
     *     department_name: string,
     *     fiscal_year: int,
     *     budget_id: int|null,
     *     budget_amount: float|null,
     *     consumed_amount: float,
     *     remaining_amount: float|null,
     *     is_over_budget: bool,
     * }>
     */
    public function execute(?int $fiscalYear = null): array
    {
        $fiscalYear = $fiscalYear ?? FiscalYear::current();
        $start = FiscalYear::startOf($fiscalYear);
        $end = FiscalYear::endOf($fiscalYear);

        $budgetIds = DB::table('department_budgets')
            ->where('fiscal_year', $fiscalYear)
            ->pluck('id', 'department_id');

        $budgets = DB::table('department_budgets')
            ->where('fiscal_year', $fiscalYear)
            ->pluck('budget_amount', 'department_id');

        $consumed = DB::table('training_enrollments')
            ->join('employees', 'employees.id', '=', 'training_enrollments.employee_id')
            ->join('employee_assignments', function ($join) {
                $join->on('employee_assignments.employee_id', '=', 'employees.id')
                    ->whereNull('employee_assignments.ended_at');
            })
            ->join('trainings', 'trainings.id', '=', 'training_enrollments.training_id')
            ->whereNotNull('trainings.unit_cost')
            ->whereBetween('training_enrollments.created_at', [$start, $end])
            ->groupBy('employee_assignments.department_id')
            ->select('employee_assignments.department_id', DB::raw('sum(trainings.unit_cost) as total'))
            ->pluck('total', 'department_id');

        return Department::query()
            ->orderBy('name')
            ->get()
            ->map(function (Department $department) use ($fiscalYear, $budgetIds, $budgets, $consumed) {
                $budgetAmount = isset($budgets[$department->id]) ? (float) $budgets[$department->id] : null;
                $consumedAmount = (float) ($consumed[$department->id] ?? 0);

                return [
                    'department_id' => $department->id,
                    'department_name' => $department->name,
                    'fiscal_year' => $fiscalYear,
                    'budget_id' => isset($budgetIds[$department->id]) ? (int) $budgetIds[$department->id] : null,
                    'budget_amount' => $budgetAmount,
                    'consumed_amount' => $consumedAmount,
                    'remaining_amount' => $budgetAmount === null ? null : $budgetAmount - $consumedAmount,
                    'is_over_budget' => $budgetAmount !== null && $consumedAmount > $budgetAmount,
                ];
            })
            ->values()
            ->all();
    }
}
