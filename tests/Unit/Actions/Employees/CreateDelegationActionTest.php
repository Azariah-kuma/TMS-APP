<?php

declare(strict_types=1);

use App\Actions\Employees\CreateDelegationAction;
use App\Exceptions\EmployeeRetiredException;
use App\Exceptions\InvalidDelegationException;
use App\Models\Employee;
use Illuminate\Support\Carbon;

it('指定した期間で委任を作成できる', function () {
    $delegator = Employee::factory()->create();
    $delegate = Employee::factory()->create();

    $delegation = (new CreateDelegationAction)->execute(
        delegator: $delegator,
        delegate: $delegate,
        startedAt: Carbon::parse('2024-01-10'),
        endedAt: Carbon::parse('2024-01-20'),
    );

    expect($delegation->delegator_id)->toBe($delegator->id)
        ->and($delegation->delegate_id)->toBe($delegate->id)
        ->and($delegation->started_at->toDateString())->toBe('2024-01-10')
        ->and($delegation->ended_at->toDateString())->toBe('2024-01-20');
});

it('委任元が退職済みの場合は拒否される', function () {
    $delegator = Employee::factory()->create(['retired_at' => now()->subDay()]);
    $delegate = Employee::factory()->create();

    expect(fn () => (new CreateDelegationAction)->execute(
        delegator: $delegator,
        delegate: $delegate,
        startedAt: Carbon::now(),
        endedAt: null,
    ))->toThrow(EmployeeRetiredException::class);
});

it('委任先が退職済みの場合は拒否される', function () {
    $delegator = Employee::factory()->create();
    $delegate = Employee::factory()->create(['retired_at' => now()->subDay()]);

    expect(fn () => (new CreateDelegationAction)->execute(
        delegator: $delegator,
        delegate: $delegate,
        startedAt: Carbon::now(),
        endedAt: null,
    ))->toThrow(EmployeeRetiredException::class);
});

it('自分自身への委任は拒否される', function () {
    $employee = Employee::factory()->create();

    expect(fn () => (new CreateDelegationAction)->execute(
        delegator: $employee,
        delegate: $employee,
        startedAt: Carbon::now(),
        endedAt: null,
    ))->toThrow(InvalidDelegationException::class);
});

it('終了日が開始日より前の場合は拒否される', function () {
    $delegator = Employee::factory()->create();
    $delegate = Employee::factory()->create();

    expect(fn () => (new CreateDelegationAction)->execute(
        delegator: $delegator,
        delegate: $delegate,
        startedAt: Carbon::parse('2024-06-01'),
        endedAt: Carbon::parse('2024-05-01'),
    ))->toThrow(InvalidDelegationException::class);
});

it('同じ組み合わせで期間が重複する委任は拒否される', function () {
    $delegator = Employee::factory()->create();
    $delegate = Employee::factory()->create();

    $action = new CreateDelegationAction;

    $action->execute(
        delegator: $delegator,
        delegate: $delegate,
        startedAt: Carbon::parse('2024-01-01'),
        endedAt: Carbon::parse('2024-01-31'),
    );

    expect(fn () => $action->execute(
        delegator: $delegator,
        delegate: $delegate,
        startedAt: Carbon::parse('2024-01-15'),
        endedAt: Carbon::parse('2024-02-15'),
    ))->toThrow(InvalidDelegationException::class);
});
