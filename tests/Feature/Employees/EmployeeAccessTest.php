<?php

declare(strict_types=1);

use App\Enums\EmployeeRole;
use Laravel\Sanctum\Sanctum;

it('allows hr to view any employee', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $someone = createEmployeeWithAssignment();

    Sanctum::actingAs($hr->user);

    $this->getJson("/api/employees/{$someone->id}")
        ->assertOk()
        ->assertJsonPath('id', $someone->id);
});

it('allows an employee to view their own record', function () {
    $employee = createEmployeeWithAssignment();

    Sanctum::actingAs($employee->user);

    $this->getJson("/api/employees/{$employee->id}")->assertOk();
});

it('allows a manager to view a subordinates record', function () {
    $manager = createEmployeeWithAssignment();
    $subordinate = createEmployeeWithAssignment(assignmentAttributes: ['manager_id' => $manager->id]);

    Sanctum::actingAs($manager->user);

    $this->getJson("/api/employees/{$subordinate->id}")->assertOk();
});

it('forbids viewing an unrelated employees record', function () {
    $employee = createEmployeeWithAssignment();
    $other = createEmployeeWithAssignment();

    Sanctum::actingAs($employee->user);

    $this->getJson("/api/employees/{$other->id}")->assertForbidden();
});

it('forbids a general employee from listing all employees', function () {
    $employee = createEmployeeWithAssignment();

    Sanctum::actingAs($employee->user);

    $this->getJson('/api/employees')->assertForbidden();
});

it('allows hr to list all employees', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    createEmployeeWithAssignment();

    Sanctum::actingAs($hr->user);

    $this->getJson('/api/employees')->assertOk();
});
