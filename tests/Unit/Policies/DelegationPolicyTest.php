<?php

declare(strict_types=1);

use App\Models\Delegation;
use App\Models\Employee;
use App\Models\User;

it('人事はどの委任も閲覧できる', function () {
    $hr = Employee::factory()->hr()->create();
    $delegation = Delegation::factory()->create();

    expect($hr->user->can('view', $delegation))->toBeTrue();
});

it('委任元本人は自分の委任を閲覧できる', function () {
    $delegator = Employee::factory()->create();
    $delegation = Delegation::factory()->create(['delegator_id' => $delegator->id]);

    expect($delegator->user->can('view', $delegation))->toBeTrue();
});

it('委任先本人は自分宛の委任を閲覧できる', function () {
    $delegate = Employee::factory()->create();
    $delegation = Delegation::factory()->create(['delegate_id' => $delegate->id]);

    expect($delegate->user->can('view', $delegation))->toBeTrue();
});

it('無関係な従業員は委任を閲覧できない', function () {
    $bystander = Employee::factory()->create();
    $delegation = Delegation::factory()->create();

    expect($bystander->user->can('view', $delegation))->toBeFalse();
});

it('従業員レコードのないユーザーは委任を閲覧できない', function () {
    $user = User::factory()->create();
    $delegation = Delegation::factory()->create();

    expect($user->can('view', $delegation))->toBeFalse();
});

it('人事は委任を作成できる', function () {
    $hr = Employee::factory()->hr()->create();

    expect($hr->user->can('create', Delegation::class))->toBeTrue();
});

it('一般社員は委任を作成できない', function () {
    $employee = Employee::factory()->create();

    expect($employee->user->can('create', Delegation::class))->toBeFalse();
});

it('人事は委任を取り消せる', function () {
    $hr = Employee::factory()->hr()->create();
    $delegation = Delegation::factory()->create();

    expect($hr->user->can('delete', $delegation))->toBeTrue();
});

it('一般社員は委任元本人であっても委任を取り消せない', function () {
    $delegator = Employee::factory()->create();
    $delegation = Delegation::factory()->create(['delegator_id' => $delegator->id]);

    expect($delegator->user->can('delete', $delegation))->toBeFalse();
});
