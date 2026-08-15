<?php

declare(strict_types=1);

use App\Models\Employee;
use App\Models\EmployeeAssignment;

it('resolves direct and indirect subordinates through the reporting chain', function () {
    $director = Employee::factory()->create();
    $manager = Employee::factory()->create();
    $staffA = Employee::factory()->create();
    $staffB = Employee::factory()->create();
    $unrelated = Employee::factory()->create();

    EmployeeAssignment::factory()->create(['employee_id' => $manager->id, 'manager_id' => $director->id]);
    EmployeeAssignment::factory()->create(['employee_id' => $staffA->id, 'manager_id' => $manager->id]);
    EmployeeAssignment::factory()->create(['employee_id' => $staffB->id, 'manager_id' => $manager->id]);
    EmployeeAssignment::factory()->create(['employee_id' => $unrelated->id, 'manager_id' => null]);

    $subordinateIds = $director->subordinateIds();

    expect($subordinateIds)->toHaveCount(3)
        ->and($subordinateIds->contains($manager->id))->toBeTrue()
        ->and($subordinateIds->contains($staffA->id))->toBeTrue()
        ->and($subordinateIds->contains($staffB->id))->toBeTrue()
        ->and($subordinateIds->contains($unrelated->id))->toBeFalse();

    expect($director->isManagerOf($staffA))->toBeTrue()
        ->and($manager->isManagerOf($director))->toBeFalse()
        ->and($manager->isManagerOf($unrelated))->toBeFalse();
});

it('ignores an ended assignment when determining subordinates', function () {
    $manager = Employee::factory()->create();
    $formerReport = Employee::factory()->create();

    EmployeeAssignment::factory()->create([
        'employee_id' => $formerReport->id,
        'manager_id' => $manager->id,
        'started_at' => '2023-01-01',
        'ended_at' => '2023-06-01',
    ]);

    expect($manager->isManagerOf($formerReport))->toBeFalse();
});
