<?php

declare(strict_types=1);

use App\Enums\EmployeeRole;
use App\Models\Delegation;
use App\Models\TrainingEnrollment;
use Laravel\Sanctum\Sanctum;

it('lets hr create a delegation so the delegate can view the delegators subordinates', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $manager = createEmployeeWithAssignment();
    $subordinate = createEmployeeWithAssignment(assignmentAttributes: ['manager_id' => $manager->id]);
    $delegate = createEmployeeWithAssignment();

    $enrollment = TrainingEnrollment::factory()->create(['employee_id' => $subordinate->id]);

    Sanctum::actingAs($delegate->user);
    $this->getJson("/api/training-enrollments/{$enrollment->id}")->assertForbidden();

    Sanctum::actingAs($hr->user);
    $this->postJson("/api/employees/{$manager->id}/delegations", [
        'delegate_id' => $delegate->id,
        'started_at' => now()->toDateString(),
    ])->assertCreated();

    Sanctum::actingAs($delegate->user);
    $this->getJson("/api/training-enrollments/{$enrollment->id}")->assertOk();
});

it('forbids a general employee from creating a delegation', function () {
    $manager = createEmployeeWithAssignment();
    $delegate = createEmployeeWithAssignment();

    Sanctum::actingAs($manager->user);

    $this->postJson("/api/employees/{$manager->id}/delegations", [
        'delegate_id' => $delegate->id,
        'started_at' => now()->toDateString(),
    ])->assertForbidden();
});

it('lets hr revoke a delegation, immediately removing the delegates visibility', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $manager = createEmployeeWithAssignment();
    $subordinate = createEmployeeWithAssignment(assignmentAttributes: ['manager_id' => $manager->id]);
    $delegate = createEmployeeWithAssignment();

    $enrollment = TrainingEnrollment::factory()->create(['employee_id' => $subordinate->id]);

    $delegation = Delegation::factory()->create([
        'delegator_id' => $manager->id,
        'delegate_id' => $delegate->id,
        'started_at' => now()->subDay(),
        'ended_at' => null,
    ]);

    Sanctum::actingAs($delegate->user);
    $this->getJson("/api/training-enrollments/{$enrollment->id}")->assertOk();

    Sanctum::actingAs($hr->user);
    $this->deleteJson("/api/delegations/{$delegation->id}")->assertOk();

    Sanctum::actingAs($delegate->user);
    $this->getJson("/api/training-enrollments/{$enrollment->id}")->assertForbidden();
});

it('does not let a delegate update the subordinates progress (view only, same as a manager)', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $manager = createEmployeeWithAssignment();
    $subordinate = createEmployeeWithAssignment(assignmentAttributes: ['manager_id' => $manager->id]);
    $delegate = createEmployeeWithAssignment();

    $enrollment = TrainingEnrollment::factory()->create(['employee_id' => $subordinate->id]);

    Delegation::factory()->create([
        'delegator_id' => $manager->id,
        'delegate_id' => $delegate->id,
        'started_at' => now()->subDay(),
        'ended_at' => null,
    ]);

    Sanctum::actingAs($delegate->user);

    $this->patchJson("/api/training-enrollments/{$enrollment->id}", ['progress' => 50])->assertForbidden();
});
