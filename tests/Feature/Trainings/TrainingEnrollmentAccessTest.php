<?php

declare(strict_types=1);

use App\Enums\EmployeeRole;
use App\Models\Training;
use App\Models\TrainingEnrollment;
use Laravel\Sanctum\Sanctum;

it('only shows a general employee their own enrollments', function () {
    $employee = createEmployeeWithAssignment();
    $other = createEmployeeWithAssignment();

    $mine = TrainingEnrollment::factory()->create(['employee_id' => $employee->id]);
    TrainingEnrollment::factory()->create(['employee_id' => $other->id]);

    Sanctum::actingAs($employee->user);

    $ids = collect($this->getJson('/api/training-enrollments')->assertOk()->json())->pluck('id');

    expect($ids->all())->toBe([$mine->id]);
});

it('shows a manager their own and their subordinates enrollments only', function () {
    $manager = createEmployeeWithAssignment();
    $subordinate = createEmployeeWithAssignment(assignmentAttributes: ['manager_id' => $manager->id]);
    $unrelated = createEmployeeWithAssignment();

    $managerEnrollment = TrainingEnrollment::factory()->create(['employee_id' => $manager->id]);
    $subordinateEnrollment = TrainingEnrollment::factory()->create(['employee_id' => $subordinate->id]);
    TrainingEnrollment::factory()->create(['employee_id' => $unrelated->id]);

    Sanctum::actingAs($manager->user);

    $ids = collect($this->getJson('/api/training-enrollments')->assertOk()->json())
        ->pluck('id')->sort()->values();

    expect($ids->all())->toBe(collect([$managerEnrollment->id, $subordinateEnrollment->id])->sort()->values()->all());
});

it('shows hr every enrollment', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    TrainingEnrollment::factory()->count(3)->create();

    Sanctum::actingAs($hr->user);

    $this->getJson('/api/training-enrollments')->assertOk()->assertJsonCount(3);
});

it('allows a manager to view but not update a subordinates progress', function () {
    $manager = createEmployeeWithAssignment();
    $subordinate = createEmployeeWithAssignment(assignmentAttributes: ['manager_id' => $manager->id]);
    $enrollment = TrainingEnrollment::factory()->create(['employee_id' => $subordinate->id]);

    Sanctum::actingAs($manager->user);

    $this->getJson("/api/training-enrollments/{$enrollment->id}")->assertOk();
    $this->patchJson("/api/training-enrollments/{$enrollment->id}", ['progress' => 50])->assertForbidden();
});

it('allows an employee to update their own progress', function () {
    $employee = createEmployeeWithAssignment();
    $enrollment = TrainingEnrollment::factory()->create(['employee_id' => $employee->id]);

    Sanctum::actingAs($employee->user);

    $this->patchJson("/api/training-enrollments/{$enrollment->id}", ['progress' => 40])
        ->assertOk()
        ->assertJsonPath('progress', 40)
        ->assertJsonPath('status', 'in_progress');
});

it('forbids an unrelated employee from viewing anothers enrollment', function () {
    $employee = createEmployeeWithAssignment();
    $other = createEmployeeWithAssignment();
    $enrollment = TrainingEnrollment::factory()->create(['employee_id' => $other->id]);

    Sanctum::actingAs($employee->user);

    $this->getJson("/api/training-enrollments/{$enrollment->id}")->assertForbidden();
});

it('lets hr enroll an employee in a training', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $employee = createEmployeeWithAssignment();
    $training = Training::factory()->create();

    Sanctum::actingAs($hr->user);

    $this->postJson("/api/employees/{$employee->id}/training-enrollments", [
        'training_id' => $training->id,
    ])->assertCreated();
});

it('forbids a general employee from enrolling themselves in a training', function () {
    $employee = createEmployeeWithAssignment();
    $training = Training::factory()->create();

    Sanctum::actingAs($employee->user);

    $this->postJson("/api/employees/{$employee->id}/training-enrollments", [
        'training_id' => $training->id,
    ])->assertForbidden();
});
