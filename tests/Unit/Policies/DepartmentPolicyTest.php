<?php

declare(strict_types=1);

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;

it('従業員なら誰でも部署一覧を閲覧できる', function () {
    $employee = Employee::factory()->create();

    expect($employee->user->can('viewAny', Department::class))->toBeTrue();
});

it('従業員レコードのないユーザーは部署一覧を閲覧できない', function () {
    $user = User::factory()->create();

    expect($user->can('viewAny', Department::class))->toBeFalse();
});

it('人事は部署を新規作成できる', function () {
    $hr = Employee::factory()->hr()->create();

    expect($hr->user->can('create', Department::class))->toBeTrue();
});

it('一般社員は部署を新規作成できない', function () {
    $employee = Employee::factory()->create();

    expect($employee->user->can('create', Department::class))->toBeFalse();
});

it('従業員レコードのないユーザーは部署を新規作成できない', function () {
    $user = User::factory()->create();

    expect($user->can('create', Department::class))->toBeFalse();
});
