<?php

declare(strict_types=1);

use App\Actions\Trainings\BulkEnrollEmployeesInTrainingAction;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\Training;
use App\Models\TrainingEnrollment;

it('指定した部署の全従業員を受講登録する', function () {
    $dev = Department::factory()->create();
    $sales = Department::factory()->create();

    $devEmployeeA = Employee::factory()->create();
    EmployeeAssignment::factory()->create(['employee_id' => $devEmployeeA->id, 'department_id' => $dev->id]);
    $devEmployeeB = Employee::factory()->create();
    EmployeeAssignment::factory()->create(['employee_id' => $devEmployeeB->id, 'department_id' => $dev->id]);
    $salesEmployee = Employee::factory()->create();
    EmployeeAssignment::factory()->create(['employee_id' => $salesEmployee->id, 'department_id' => $sales->id]);

    $training = Training::factory()->create();

    $result = app(BulkEnrollEmployeesInTrainingAction::class)->execute($training, $dev->id, null);

    expect($result)->toBe(['enrolled' => 2, 'skipped' => 0])
        ->and(TrainingEnrollment::where('employee_id', $devEmployeeA->id)->exists())->toBeTrue()
        ->and(TrainingEnrollment::where('employee_id', $devEmployeeB->id)->exists())->toBeTrue()
        ->and(TrainingEnrollment::where('employee_id', $salesEmployee->id)->exists())->toBeFalse();
});

it('部署を指定しない場合は全社員を受講登録する', function () {
    Employee::factory()->count(3)->create()->each(
        fn (Employee $employee) => EmployeeAssignment::factory()->create(['employee_id' => $employee->id]),
    );

    $training = Training::factory()->create();

    $result = app(BulkEnrollEmployeesInTrainingAction::class)->execute($training, null, null);

    expect($result)->toBe(['enrolled' => 3, 'skipped' => 0])
        ->and(TrainingEnrollment::count())->toBe(3);
});

it('既に受講登録済みの従業員はスキップし、バッチ全体を失敗させない', function () {
    $department = Department::factory()->create();
    $alreadyEnrolled = Employee::factory()->create();
    EmployeeAssignment::factory()->create(['employee_id' => $alreadyEnrolled->id, 'department_id' => $department->id]);
    $notYetEnrolled = Employee::factory()->create();
    EmployeeAssignment::factory()->create(['employee_id' => $notYetEnrolled->id, 'department_id' => $department->id]);

    $training = Training::factory()->create();
    TrainingEnrollment::factory()->create(['employee_id' => $alreadyEnrolled->id, 'training_id' => $training->id]);

    $result = app(BulkEnrollEmployeesInTrainingAction::class)->execute($training, $department->id, null);

    expect($result)->toBe(['enrolled' => 1, 'skipped' => 1]);
});

it('退職済みの従業員は一括受講登録の対象から除外される', function () {
    $department = Department::factory()->create();
    $retired = Employee::factory()->create(['retired_at' => now()->subDay()]);
    EmployeeAssignment::factory()->create(['employee_id' => $retired->id, 'department_id' => $department->id]);
    $active = Employee::factory()->create();
    EmployeeAssignment::factory()->create(['employee_id' => $active->id, 'department_id' => $department->id]);

    $training = Training::factory()->create();

    $result = app(BulkEnrollEmployeesInTrainingAction::class)->execute($training, $department->id, null);

    expect($result)->toBe(['enrolled' => 1, 'skipped' => 0])
        ->and(TrainingEnrollment::where('employee_id', $retired->id)->exists())->toBeFalse();
});
