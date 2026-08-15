<?php

declare(strict_types=1);

use App\Actions\Employees\CreateDelegationAction;
use App\Exceptions\InvalidDelegationException;
use App\Models\Employee;
use Illuminate\Support\Carbon;

it('creates a delegation for the given period', function () {
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

it('rejects delegating to oneself', function () {
    $employee = Employee::factory()->create();

    expect(fn () => (new CreateDelegationAction)->execute(
        delegator: $employee,
        delegate: $employee,
        startedAt: Carbon::now(),
        endedAt: null,
    ))->toThrow(InvalidDelegationException::class);
});

it('rejects an end date before the start date', function () {
    $delegator = Employee::factory()->create();
    $delegate = Employee::factory()->create();

    expect(fn () => (new CreateDelegationAction)->execute(
        delegator: $delegator,
        delegate: $delegate,
        startedAt: Carbon::parse('2024-06-01'),
        endedAt: Carbon::parse('2024-05-01'),
    ))->toThrow(InvalidDelegationException::class);
});

it('rejects a delegation that overlaps an existing one for the same pair', function () {
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
