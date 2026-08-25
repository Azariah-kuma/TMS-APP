<?php

declare(strict_types=1);

use App\Actions\Reports\BuildTrainingSummaryReportAction;
use App\Enums\TrainingEnrollmentStatus;
use App\Models\Department;
use App\Models\EmployeeAssignment;
use App\Models\Training;
use App\Models\TrainingEnrollment;

it('研修別に受講ステータスの内訳を集計する', function () {
    $training = Training::factory()->create(['title' => '情報セキュリティ研修']);
    TrainingEnrollment::factory()->create(['training_id' => $training->id, 'status' => TrainingEnrollmentStatus::NotStarted]);
    TrainingEnrollment::factory()->create(['training_id' => $training->id, 'status' => TrainingEnrollmentStatus::InProgress]);
    TrainingEnrollment::factory()->create(['training_id' => $training->id, 'status' => TrainingEnrollmentStatus::Completed]);
    TrainingEnrollment::factory()->create(['training_id' => $training->id, 'status' => TrainingEnrollmentStatus::Completed]);

    $report = app(BuildTrainingSummaryReportAction::class)->execute();

    expect($report['by_training'])->toBe([
        ['id' => $training->id, 'name' => '情報セキュリティ研修', 'not_started' => 1, 'in_progress' => 1, 'completed' => 2],
    ]);
});

it('受講記録が1件もない研修はレポートに含まれない', function () {
    Training::factory()->create();

    $report = app(BuildTrainingSummaryReportAction::class)->execute();

    expect($report['by_training'])->toBe([]);
});

it('部署別に、現在の配属先department（在籍中の割り当て）で受講ステータスの内訳を集計する', function () {
    $department = Department::factory()->create(['name' => '開発部']);
    $employee = createEmployeeWithAssignment([], ['department_id' => $department->id]);

    TrainingEnrollment::factory()->create(['employee_id' => $employee->id, 'status' => TrainingEnrollmentStatus::Completed]);
    TrainingEnrollment::factory()->create(['employee_id' => $employee->id, 'status' => TrainingEnrollmentStatus::NotStarted]);

    $report = app(BuildTrainingSummaryReportAction::class)->execute();

    expect($report['by_department'])->toBe([
        ['id' => $department->id, 'name' => '開発部', 'not_started' => 1, 'in_progress' => 0, 'completed' => 1],
    ]);
});

it('終了済みの配属は部署別集計に使わない（現在の配属先で集計する）', function () {
    $oldDepartment = Department::factory()->create();
    $newDepartment = Department::factory()->create(['name' => '営業部']);
    $employee = createEmployeeWithAssignment([], ['department_id' => $newDepartment->id]);

    EmployeeAssignment::factory()->ended()->create([
        'employee_id' => $employee->id,
        'department_id' => $oldDepartment->id,
    ]);

    TrainingEnrollment::factory()->create(['employee_id' => $employee->id, 'status' => TrainingEnrollmentStatus::Completed]);

    $report = app(BuildTrainingSummaryReportAction::class)->execute();

    expect($report['by_department'])->toBe([
        ['id' => $newDepartment->id, 'name' => '営業部', 'not_started' => 0, 'in_progress' => 0, 'completed' => 1],
    ]);
});
