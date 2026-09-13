<?php

declare(strict_types=1);

use App\Actions\Reports\BuildDepartmentBudgetUsageReportAction;
use App\Models\Department;
use App\Models\DepartmentBudget;
use App\Models\Training;
use App\Models\TrainingEnrollment;
use App\Support\FiscalYear;
use Illuminate\Support\Carbon;

afterEach(function () {
    Carbon::setTestNow();
});

it('単価が設定された研修の受講登録数から消費額を算出し、予算との差分を返す', function () {
    Carbon::setTestNow(Carbon::create(2026, 6, 1));

    $department = Department::factory()->create();
    $budget = DepartmentBudget::factory()->create([
        'department_id' => $department->id,
        'fiscal_year' => 2026,
        'budget_amount' => 100000,
    ]);

    $training = Training::factory()->create(['unit_cost' => 30000]);
    $employee = createEmployeeWithAssignment([], ['department_id' => $department->id]);
    TrainingEnrollment::factory()->create([
        'employee_id' => $employee->id,
        'training_id' => $training->id,
    ]);

    $report = (new BuildDepartmentBudgetUsageReportAction)->execute(2026);
    $row = collect($report)->firstWhere('department_id', $department->id);

    expect($row['budget_id'])->toBe($budget->id)
        ->and($row['budget_amount'])->toBe(100000.0)
        ->and($row['consumed_amount'])->toBe(30000.0)
        ->and($row['remaining_amount'])->toBe(70000.0)
        ->and($row['is_over_budget'])->toBeFalse();
});

it('消費額が予算を超えるとis_over_budgetがtrueになる', function () {
    $department = Department::factory()->create();
    DepartmentBudget::factory()->create([
        'department_id' => $department->id,
        'fiscal_year' => FiscalYear::current(),
        'budget_amount' => 10000,
    ]);

    $training = Training::factory()->create(['unit_cost' => 30000]);
    $employee = createEmployeeWithAssignment([], ['department_id' => $department->id]);
    TrainingEnrollment::factory()->create([
        'employee_id' => $employee->id,
        'training_id' => $training->id,
    ]);

    $report = (new BuildDepartmentBudgetUsageReportAction)->execute();
    $row = collect($report)->firstWhere('department_id', $department->id);

    expect($row['is_over_budget'])->toBeTrue();
});

it('予算未設定の部署はbudget_amount・remaining_amountがnullになる', function () {
    $department = Department::factory()->create();

    $report = (new BuildDepartmentBudgetUsageReportAction)->execute();
    $row = collect($report)->firstWhere('department_id', $department->id);

    expect($row['budget_id'])->toBeNull()
        ->and($row['budget_amount'])->toBeNull()
        ->and($row['remaining_amount'])->toBeNull()
        ->and($row['consumed_amount'])->toBe(0.0)
        ->and($row['is_over_budget'])->toBeFalse();
});

it('単価未設定の研修や年度外の受講登録は消費額に含めない', function () {
    Carbon::setTestNow(Carbon::create(2026, 6, 1));

    $department = Department::factory()->create();
    $employee = createEmployeeWithAssignment([], ['department_id' => $department->id]);

    // 単価未設定
    $freeTraining = Training::factory()->create(['unit_cost' => null]);
    TrainingEnrollment::factory()->create([
        'employee_id' => $employee->id,
        'training_id' => $freeTraining->id,
    ]);

    // 年度外（前年度）
    $costedTraining = Training::factory()->create(['unit_cost' => 50000]);
    TrainingEnrollment::factory()->create([
        'employee_id' => $employee->id,
        'training_id' => $costedTraining->id,
        'created_at' => Carbon::create(2025, 6, 1),
    ]);

    $report = (new BuildDepartmentBudgetUsageReportAction)->execute(2026);
    $row = collect($report)->firstWhere('department_id', $department->id);

    expect($row['consumed_amount'])->toBe(0.0);
});
