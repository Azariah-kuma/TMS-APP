<?php

declare(strict_types=1);

use App\Models\Delegation;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use Illuminate\Support\Collection;

it('lets a delegate temporarily see the delegators subordinates', function () {
    $manager = Employee::factory()->create();
    $subordinate = Employee::factory()->create();
    $delegate = Employee::factory()->create();

    EmployeeAssignment::factory()->create(['employee_id' => $subordinate->id, 'manager_id' => $manager->id]);

    expect($delegate->isManagerOf($subordinate))->toBeFalse();

    Delegation::factory()->create([
        'delegator_id' => $manager->id,
        'delegate_id' => $delegate->id,
        'started_at' => now()->subDay(),
        'ended_at' => now()->addDay(),
    ]);

    expect($delegate->isManagerOf($subordinate))->toBeTrue();
});

it('does not grant visibility once the delegation period has ended', function () {
    $manager = Employee::factory()->create();
    $subordinate = Employee::factory()->create();
    $delegate = Employee::factory()->create();

    EmployeeAssignment::factory()->create(['employee_id' => $subordinate->id, 'manager_id' => $manager->id]);

    Delegation::factory()->create([
        'delegator_id' => $manager->id,
        'delegate_id' => $delegate->id,
        'started_at' => now()->subDays(10),
        'ended_at' => now()->subDay(),
    ]);

    expect($delegate->isManagerOf($subordinate))->toBeFalse();
});

it('does not let delegation chains create a cycle in hierarchySubordinateIds', function () {
    $a = Employee::factory()->create();
    $b = Employee::factory()->create();

    Delegation::factory()->create([
        'delegator_id' => $a->id,
        'delegate_id' => $b->id,
        'started_at' => now()->subDay(),
        'ended_at' => null,
    ]);

    Delegation::factory()->create([
        'delegator_id' => $b->id,
        'delegate_id' => $a->id,
        'started_at' => now()->subDay(),
        'ended_at' => null,
    ]);

    // 委任は「委任の委任」を連鎖させない設計のため、循環していても無限ループしない。
    expect($a->subordinateIds())->toBeInstanceOf(Collection::class);
    expect($b->subordinateIds())->toBeInstanceOf(Collection::class);
});
