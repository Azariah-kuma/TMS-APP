<?php

declare(strict_types=1);

use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\TrainingEnrollment;
use App\Models\User;

it('従業員なら誰でも受講記録一覧を要求できる', function () {
    $employee = Employee::factory()->create();

    expect($employee->user->can('viewAny', TrainingEnrollment::class))->toBeTrue();
});

it('従業員レコードのないユーザーは受講記録一覧を要求できない', function () {
    $user = User::factory()->create();

    expect($user->can('viewAny', TrainingEnrollment::class))->toBeFalse();
});

it('人事はどの受講記録も閲覧できる', function () {
    $hr = Employee::factory()->hr()->create();
    $enrollment = TrainingEnrollment::factory()->create();

    expect($hr->user->can('view', $enrollment))->toBeTrue();
});

it('本人は自分の受講記録を閲覧できる', function () {
    $employee = Employee::factory()->create();
    $enrollment = TrainingEnrollment::factory()->create(['employee_id' => $employee->id]);

    expect($employee->user->can('view', $enrollment))->toBeTrue();
});

it('上司は部下の受講記録を閲覧できる', function () {
    $manager = Employee::factory()->create();
    $subordinate = Employee::factory()->create();
    EmployeeAssignment::factory()->create(['employee_id' => $subordinate->id, 'manager_id' => $manager->id]);
    $enrollment = TrainingEnrollment::factory()->create(['employee_id' => $subordinate->id]);

    expect($manager->user->can('view', $enrollment))->toBeTrue();
});

it('無関係な従業員は受講記録を閲覧できない', function () {
    $bystander = Employee::factory()->create();
    $enrollment = TrainingEnrollment::factory()->create();

    expect($bystander->user->can('view', $enrollment))->toBeFalse();
});

it('従業員レコードのないユーザーは受講記録を閲覧できない', function () {
    $user = User::factory()->create();
    $enrollment = TrainingEnrollment::factory()->create();

    expect($user->can('view', $enrollment))->toBeFalse();
});

it('人事は従業員を研修に割り当てられる', function () {
    $hr = Employee::factory()->hr()->create();

    expect($hr->user->can('create', TrainingEnrollment::class))->toBeTrue();
});

it('一般社員は誰も研修に割り当てられない', function () {
    $employee = Employee::factory()->create();

    expect($employee->user->can('create', TrainingEnrollment::class))->toBeFalse();
});

it('本人は自分の進捗を更新できる', function () {
    $employee = Employee::factory()->create();
    $enrollment = TrainingEnrollment::factory()->create(['employee_id' => $employee->id]);

    expect($employee->user->can('update', $enrollment))->toBeTrue();
});

it('人事はどの受講記録の進捗も更新できる', function () {
    $hr = Employee::factory()->hr()->create();
    $enrollment = TrainingEnrollment::factory()->create();

    expect($hr->user->can('update', $enrollment))->toBeTrue();
});

it('上司は部下の進捗を代理更新できない', function () {
    $manager = Employee::factory()->create();
    $subordinate = Employee::factory()->create();
    EmployeeAssignment::factory()->create(['employee_id' => $subordinate->id, 'manager_id' => $manager->id]);
    $enrollment = TrainingEnrollment::factory()->create(['employee_id' => $subordinate->id]);

    expect($manager->user->can('update', $enrollment))->toBeFalse();
});

it('従業員レコードのないユーザーは進捗を更新できない', function () {
    $user = User::factory()->create();
    $enrollment = TrainingEnrollment::factory()->create();

    expect($user->can('update', $enrollment))->toBeFalse();
});

it('人事は受講登録を取り消せる', function () {
    $hr = Employee::factory()->hr()->create();
    $enrollment = TrainingEnrollment::factory()->create();

    expect($hr->user->can('delete', $enrollment))->toBeTrue();
});

it('受講登録された本人であっても、自分の受講登録は取り消せない', function () {
    $employee = Employee::factory()->create();
    $enrollment = TrainingEnrollment::factory()->create(['employee_id' => $employee->id]);

    expect($employee->user->can('delete', $enrollment))->toBeFalse();
});
