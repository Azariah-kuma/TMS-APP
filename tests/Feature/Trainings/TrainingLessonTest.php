<?php

declare(strict_types=1);

use App\Enums\EmployeeRole;
use App\Models\Training;
use App\Models\TrainingEnrollment;
use App\Models\TrainingLesson;
use Laravel\Sanctum\Sanctum;

it('lets hr define lessons for a training', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $training = Training::factory()->create();

    Sanctum::actingAs($hr->user);

    $this->postJson("/api/trainings/{$training->id}/lessons", [
        'title' => '第1章 イントロダクション',
        'position' => 1,
    ])->assertCreated()->assertJsonPath('title', '第1章 イントロダクション');
});

it('forbids a general employee from defining lessons', function () {
    $employee = createEmployeeWithAssignment();
    $training = Training::factory()->create();

    Sanctum::actingAs($employee->user);

    $this->postJson("/api/trainings/{$training->id}/lessons", ['title' => '第1章'])->assertForbidden();
});

it('lets the enrolled employee check off their own lesson completion and see progress update', function () {
    $employee = createEmployeeWithAssignment();
    $training = Training::factory()->create();
    $lessons = TrainingLesson::factory()->for($training)->count(2)->create();
    $enrollment = TrainingEnrollment::factory()->create([
        'employee_id' => $employee->id,
        'training_id' => $training->id,
    ]);

    Sanctum::actingAs($employee->user);

    $response = $this->putJson(
        "/api/training-enrollments/{$enrollment->id}/lessons/{$lessons[0]->id}",
    )->assertOk();

    $response->assertJsonPath('progress', 50)
        ->assertJsonPath('status', 'in_progress')
        ->assertJsonFragment(['completed_lesson_ids' => [$lessons[0]->id]]);

    $this->deleteJson("/api/training-enrollments/{$enrollment->id}/lessons/{$lessons[0]->id}")
        ->assertOk()
        ->assertJsonPath('progress', 0)
        ->assertJsonPath('status', 'not_started');
});

it('forbids a manager from checking off a subordinates lesson (view only)', function () {
    $manager = createEmployeeWithAssignment();
    $subordinate = createEmployeeWithAssignment(assignmentAttributes: ['manager_id' => $manager->id]);
    $training = Training::factory()->create();
    $lesson = TrainingLesson::factory()->for($training)->create();
    $enrollment = TrainingEnrollment::factory()->create([
        'employee_id' => $subordinate->id,
        'training_id' => $training->id,
    ]);

    Sanctum::actingAs($manager->user);

    $this->putJson("/api/training-enrollments/{$enrollment->id}/lessons/{$lesson->id}")->assertForbidden();
});

it('rejects manual progress updates once lessons exist for the training', function () {
    $employee = createEmployeeWithAssignment();
    $training = Training::factory()->create();
    TrainingLesson::factory()->for($training)->create();
    $enrollment = TrainingEnrollment::factory()->create([
        'employee_id' => $employee->id,
        'training_id' => $training->id,
    ]);

    Sanctum::actingAs($employee->user);

    $this->patchJson("/api/training-enrollments/{$enrollment->id}", ['progress' => 50])
        ->assertStatus(422);
});
