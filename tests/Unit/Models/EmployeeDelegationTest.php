<?php

declare(strict_types=1);

use App\Models\Delegation;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use Illuminate\Support\Collection;

it('委任先は一時的に委任元の部下を閲覧できるようになる', function () {
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

it('委任期間が終了すると閲覧権限は付与されなくなる', function () {
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

it('委任の連鎖があってもhierarchySubordinateIdsで循環が発生しない', function () {
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
