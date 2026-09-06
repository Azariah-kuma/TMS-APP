<?php

declare(strict_types=1);

use App\Enums\EmployeeRole;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Training;
use App\Models\User;

it('従業員なら誰でも研修カタログを閲覧できる', function () {
    $employee = Employee::factory()->create();

    expect($employee->user->can('viewAny', Training::class))->toBeTrue();
});

it('従業員レコードのないユーザーは研修カタログを閲覧できない', function () {
    $user = User::factory()->create();

    expect($user->can('viewAny', Training::class))->toBeFalse();
});

it('従業員なら誰でも対象者の制限がない研修の詳細を閲覧できる', function () {
    $employee = Employee::factory()->create();
    $training = Training::factory()->create();

    expect($employee->user->can('view', $training))->toBeTrue();
});

it('対象者の制限に合致しない従業員は研修の詳細を閲覧できない', function () {
    $department = Department::factory()->create();
    $employee = createEmployeeWithAssignment();
    $training = Training::factory()->create(['audience_department_id' => $department->id]);

    expect($employee->user->can('view', $training))->toBeFalse();
});

it('人事は対象者の制限に関わらず研修の詳細を閲覧できる', function () {
    $department = Department::factory()->create();
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $training = Training::factory()->create(['audience_department_id' => $department->id]);

    expect($hr->user->can('view', $training))->toBeTrue();
});

it('従業員レコードのないユーザーは研修の詳細を閲覧できない', function () {
    $user = User::factory()->create();
    $training = Training::factory()->create();

    expect($user->can('view', $training))->toBeFalse();
});

it('人事は研修を新規作成できる', function () {
    $hr = Employee::factory()->hr()->create();

    expect($hr->user->can('create', Training::class))->toBeTrue();
});

it('一般社員は研修を新規作成できない', function () {
    $employee = Employee::factory()->create();

    expect($employee->user->can('create', Training::class))->toBeFalse();
});

it('人事は研修情報を更新できる', function () {
    $hr = Employee::factory()->hr()->create();
    $training = Training::factory()->create();

    expect($hr->user->can('update', $training))->toBeTrue();
});

it('一般社員は研修情報を更新できない', function () {
    $employee = Employee::factory()->create();
    $training = Training::factory()->create();

    expect($employee->user->can('update', $training))->toBeFalse();
});

it('人事は研修を削除できる', function () {
    $hr = Employee::factory()->hr()->create();
    $training = Training::factory()->create();

    expect($hr->user->can('delete', $training))->toBeTrue();
});

it('一般社員は研修を削除できない', function () {
    $employee = Employee::factory()->create();
    $training = Training::factory()->create();

    expect($employee->user->can('delete', $training))->toBeFalse();
});
