<?php

declare(strict_types=1);

use App\Models\Employee;
use App\Models\Position;
use App\Models\User;

it('従業員なら誰でも役職一覧を閲覧できる', function () {
    $employee = Employee::factory()->create();

    expect($employee->user->can('viewAny', Position::class))->toBeTrue();
});

it('従業員レコードのないユーザーは役職一覧を閲覧できない', function () {
    $user = User::factory()->create();

    expect($user->can('viewAny', Position::class))->toBeFalse();
});

it('人事は役職を新規作成できる', function () {
    $hr = Employee::factory()->hr()->create();

    expect($hr->user->can('create', Position::class))->toBeTrue();
});

it('一般社員は役職を新規作成できない', function () {
    $employee = Employee::factory()->create();

    expect($employee->user->can('create', Position::class))->toBeFalse();
});

it('従業員レコードのないユーザーは役職を新規作成できない', function () {
    $user = User::factory()->create();

    expect($user->can('create', Position::class))->toBeFalse();
});

it('人事は役職を更新・削除できる', function () {
    $hr = Employee::factory()->hr()->create();
    $position = Position::factory()->create();

    expect($hr->user->can('update', $position))->toBeTrue()
        ->and($hr->user->can('delete', $position))->toBeTrue();
});

it('一般社員は役職を更新・削除できない', function () {
    $employee = Employee::factory()->create();
    $position = Position::factory()->create();

    expect($employee->user->can('update', $position))->toBeFalse()
        ->and($employee->user->can('delete', $position))->toBeFalse();
});
