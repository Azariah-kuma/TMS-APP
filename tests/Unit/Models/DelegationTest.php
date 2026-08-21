<?php

declare(strict_types=1);

use App\Models\Delegation;
use App\Models\Employee;
use Illuminate\Support\Carbon;

it('委任を与えた委任元に属する', function () {
    $delegator = Employee::factory()->create();
    $delegation = Delegation::factory()->create(['delegator_id' => $delegator->id]);

    expect($delegation->delegator->is($delegator))->toBeTrue();
});

it('委任を受けた委任先に属する', function () {
    $delegate = Employee::factory()->create();
    $delegation = Delegation::factory()->create(['delegate_id' => $delegate->id]);

    expect($delegation->delegate->is($delegate))->toBeTrue();
});

it('今日が開始日と終了日の範囲内であれば有効', function () {
    $delegation = Delegation::factory()->create([
        'started_at' => Carbon::yesterday(),
        'ended_at' => Carbon::tomorrow(),
    ]);

    expect($delegation->isActive())->toBeTrue();
});

it('終了日が無くても、既に開始していれば有効', function () {
    $delegation = Delegation::factory()->create([
        'started_at' => Carbon::yesterday(),
        'ended_at' => null,
    ]);

    expect($delegation->isActive())->toBeTrue();
});

it('開始前は無効', function () {
    $delegation = Delegation::factory()->create([
        'started_at' => Carbon::tomorrow(),
        'ended_at' => null,
    ]);

    expect($delegation->isActive())->toBeFalse();
});

it('終了後は無効', function () {
    $delegation = Delegation::factory()->create([
        'started_at' => Carbon::today()->subDays(10),
        'ended_at' => Carbon::yesterday(),
    ]);

    expect($delegation->isActive())->toBeFalse();
});
