<?php

declare(strict_types=1);

use App\Actions\Trainings\BulkRequestTrainingForDepartmentAction;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\Training;
use App\Models\TrainingEnrollment;
use App\Models\TrainingRequest;

it('上司は自部署の部下だけをまとめて研修申請できる', function () {
    $department = Department::factory()->create();
    $manager = Employee::factory()->create();
    EmployeeAssignment::factory()->create(['employee_id' => $manager->id, 'department_id' => $department->id]);

    $subordinateA = Employee::factory()->create();
    EmployeeAssignment::factory()->create([
        'employee_id' => $subordinateA->id, 'department_id' => $department->id, 'manager_id' => $manager->id,
    ]);
    $subordinateB = Employee::factory()->create();
    EmployeeAssignment::factory()->create([
        'employee_id' => $subordinateB->id, 'department_id' => $department->id, 'manager_id' => $manager->id,
    ]);

    // 同じ部署だが、この上司の部下ではない従業員は対象外。
    $sameDepartmentButNotSubordinate = Employee::factory()->create();
    EmployeeAssignment::factory()->create([
        'employee_id' => $sameDepartmentButNotSubordinate->id, 'department_id' => $department->id,
    ]);

    $training = Training::factory()->create();

    $result = app(BulkRequestTrainingForDepartmentAction::class)->execute($training, $manager, $department->id);

    expect($result)->toBe(['requested' => 2, 'skipped' => 0]);

    expect(TrainingRequest::where('employee_id', $subordinateA->id)->where('requested_by_employee_id', $manager->id)->exists())->toBeTrue()
        ->and(TrainingRequest::where('employee_id', $subordinateB->id)->exists())->toBeTrue()
        ->and(TrainingRequest::where('employee_id', $sameDepartmentButNotSubordinate->id)->exists())->toBeFalse();
});

it('人事が実行する場合は部署内の在籍者全員が対象になる', function () {
    $department = Department::factory()->create();
    $hr = Employee::factory()->hr()->create();

    $employeeA = Employee::factory()->create();
    EmployeeAssignment::factory()->create(['employee_id' => $employeeA->id, 'department_id' => $department->id]);
    $employeeB = Employee::factory()->create();
    EmployeeAssignment::factory()->create(['employee_id' => $employeeB->id, 'department_id' => $department->id]);

    $training = Training::factory()->create();

    $result = app(BulkRequestTrainingForDepartmentAction::class)->execute($training, $hr, $department->id);

    expect($result)->toBe(['requested' => 2, 'skipped' => 0]);
});

it('退職済みの部下は一括申請の対象から除外される', function () {
    $department = Department::factory()->create();
    $manager = Employee::factory()->create();

    $retired = Employee::factory()->create(['retired_at' => now()->subDay()]);
    EmployeeAssignment::factory()->create([
        'employee_id' => $retired->id, 'department_id' => $department->id, 'manager_id' => $manager->id,
    ]);
    $active = Employee::factory()->create();
    EmployeeAssignment::factory()->create([
        'employee_id' => $active->id, 'department_id' => $department->id, 'manager_id' => $manager->id,
    ]);

    $training = Training::factory()->create();

    $result = app(BulkRequestTrainingForDepartmentAction::class)->execute($training, $manager, $department->id);

    expect($result)->toBe(['requested' => 1, 'skipped' => 0])
        ->and(TrainingRequest::where('employee_id', $retired->id)->exists())->toBeFalse();
});

it('既に受講登録済み・申請済みの部下はスキップし、バッチ全体は失敗させない', function () {
    $department = Department::factory()->create();
    $manager = Employee::factory()->create();

    $alreadyEnrolled = Employee::factory()->create();
    EmployeeAssignment::factory()->create([
        'employee_id' => $alreadyEnrolled->id, 'department_id' => $department->id, 'manager_id' => $manager->id,
    ]);
    $alreadyRequested = Employee::factory()->create();
    EmployeeAssignment::factory()->create([
        'employee_id' => $alreadyRequested->id, 'department_id' => $department->id, 'manager_id' => $manager->id,
    ]);
    $notYetRequested = Employee::factory()->create();
    EmployeeAssignment::factory()->create([
        'employee_id' => $notYetRequested->id, 'department_id' => $department->id, 'manager_id' => $manager->id,
    ]);

    $training = Training::factory()->create();
    TrainingEnrollment::factory()->create(['employee_id' => $alreadyEnrolled->id, 'training_id' => $training->id]);
    TrainingRequest::factory()->create(['employee_id' => $alreadyRequested->id, 'training_id' => $training->id]);

    $result = app(BulkRequestTrainingForDepartmentAction::class)->execute($training, $manager, $department->id);

    expect($result)->toBe(['requested' => 1, 'skipped' => 2]);
});
