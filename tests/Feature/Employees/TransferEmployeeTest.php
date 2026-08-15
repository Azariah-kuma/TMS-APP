<?php

declare(strict_types=1);

use App\Enums\EmployeeRole;
use App\Models\Department;
use App\Models\Position;
use Laravel\Sanctum\Sanctum;

it('lets hr transfer an employee to a new department and position, keeping history', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $employee = createEmployeeWithAssignment(assignmentAttributes: ['started_at' => '2024-01-01']);

    $newDepartment = Department::factory()->create();
    $newPosition = Position::factory()->create();

    Sanctum::actingAs($hr->user);

    $this->postJson("/api/employees/{$employee->id}/assignments", [
        'department_id' => $newDepartment->id,
        'position_id' => $newPosition->id,
        'manager_id' => null,
        'started_at' => '2024-06-01',
    ])->assertCreated();

    expect($employee->assignments()->count())->toBe(2)
        ->and($employee->currentAssignment()->first()->department_id)->toBe($newDepartment->id);
});

it('forbids a general employee from transferring themselves', function () {
    $employee = createEmployeeWithAssignment();

    Sanctum::actingAs($employee->user);

    $this->postJson("/api/employees/{$employee->id}/assignments", [
        'department_id' => Department::factory()->create()->id,
        'position_id' => Position::factory()->create()->id,
        'started_at' => now()->toDateString(),
    ])->assertForbidden();
});

it('forbids a manager from transferring their own subordinate', function () {
    $manager = createEmployeeWithAssignment();
    $subordinate = createEmployeeWithAssignment(assignmentAttributes: ['manager_id' => $manager->id]);

    Sanctum::actingAs($manager->user);

    $this->postJson("/api/employees/{$subordinate->id}/assignments", [
        'department_id' => Department::factory()->create()->id,
        'position_id' => Position::factory()->create()->id,
        'started_at' => now()->toDateString(),
    ])->assertForbidden();
});
