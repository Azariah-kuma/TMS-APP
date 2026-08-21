<?php

declare(strict_types=1);

use App\Models\Employee;
use App\Models\User;

it('人事は全従業員の一覧を閲覧できる', function () {
    $hr = Employee::factory()->hr()->create();

    expect($hr->user->can('viewAny', Employee::class))->toBeTrue();
});

it('一般社員は全従業員の一覧を閲覧できない', function () {
    $employee = Employee::factory()->create();

    expect($employee->user->can('viewAny', Employee::class))->toBeFalse();
});

it('従業員レコードのないユーザーは従業員を閲覧できない', function () {
    $user = User::factory()->create();
    $someone = Employee::factory()->create();

    expect($user->can('view', $someone))->toBeFalse();
});

it('人事は新規従業員を登録できる', function () {
    $hr = Employee::factory()->hr()->create();

    expect($hr->user->can('create', Employee::class))->toBeTrue();
});

it('一般社員は新規従業員を登録できない', function () {
    $employee = Employee::factory()->create();

    expect($employee->user->can('create', Employee::class))->toBeFalse();
});

it('人事は従業員を異動させられる', function () {
    $hr = Employee::factory()->hr()->create();
    $someone = Employee::factory()->create();

    expect($hr->user->can('transfer', $someone))->toBeTrue();
});

it('一般社員は他の従業員を異動させられない', function () {
    $employee = Employee::factory()->create();
    $someone = Employee::factory()->create();

    expect($employee->user->can('transfer', $someone))->toBeFalse();
});

it('人事は招待メールを再送信できる', function () {
    $hr = Employee::factory()->hr()->create();
    $someone = Employee::factory()->create();

    expect($hr->user->can('resendInvite', $someone))->toBeTrue();
});

it('一般社員は他人の招待メールを再送信できない', function () {
    $employee = Employee::factory()->create();
    $someone = Employee::factory()->create();

    expect($employee->user->can('resendInvite', $someone))->toBeFalse();
});
