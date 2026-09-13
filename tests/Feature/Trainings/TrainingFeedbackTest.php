<?php

declare(strict_types=1);

use App\Enums\TrainingEnrollmentStatus;
use App\Models\TrainingEnrollment;
use App\Models\TrainingFeedback;
use Laravel\Sanctum\Sanctum;

it('本人は受講完了後にアンケート・テスト結果を提出できる', function () {
    $employee = createEmployeeWithAssignment();
    $enrollment = TrainingEnrollment::factory()->create([
        'employee_id' => $employee->id,
        'status' => TrainingEnrollmentStatus::Completed,
    ]);

    Sanctum::actingAs($employee->user);

    $this->postJson("/api/training-enrollments/{$enrollment->id}/feedback", [
        'satisfaction_score' => 5,
        'understanding_score' => 4,
        'quiz_score' => 80,
        'comment' => 'とても勉強になりました',
    ])->assertCreated()->assertJsonPath('satisfaction_score', 5);

    $this->assertDatabaseHas('training_feedbacks', ['training_enrollment_id' => $enrollment->id]);
});

it('本人以外は他人の受講記録にフィードバックを提出できない', function () {
    $employee = createEmployeeWithAssignment();
    $other = createEmployeeWithAssignment();
    $enrollment = TrainingEnrollment::factory()->create([
        'employee_id' => $other->id,
        'status' => TrainingEnrollmentStatus::Completed,
    ]);

    Sanctum::actingAs($employee->user);

    $this->postJson("/api/training-enrollments/{$enrollment->id}/feedback", [
        'satisfaction_score' => 5,
        'understanding_score' => 5,
    ])->assertForbidden();
});

it('受講が完了していない場合は422を返す', function () {
    $employee = createEmployeeWithAssignment();
    $enrollment = TrainingEnrollment::factory()->create([
        'employee_id' => $employee->id,
        'status' => TrainingEnrollmentStatus::InProgress,
    ]);

    Sanctum::actingAs($employee->user);

    $this->postJson("/api/training-enrollments/{$enrollment->id}/feedback", [
        'satisfaction_score' => 5,
        'understanding_score' => 5,
    ])->assertStatus(422);
});

it('既に提出済みの場合は422を返す', function () {
    $employee = createEmployeeWithAssignment();
    $enrollment = TrainingEnrollment::factory()->create([
        'employee_id' => $employee->id,
        'status' => TrainingEnrollmentStatus::Completed,
    ]);
    TrainingFeedback::factory()->create(['training_enrollment_id' => $enrollment->id]);

    Sanctum::actingAs($employee->user);

    $this->postJson("/api/training-enrollments/{$enrollment->id}/feedback", [
        'satisfaction_score' => 5,
        'understanding_score' => 5,
    ])->assertStatus(422);
});

it('スコアが範囲外の場合はバリデーションエラーになる', function () {
    $employee = createEmployeeWithAssignment();
    $enrollment = TrainingEnrollment::factory()->create([
        'employee_id' => $employee->id,
        'status' => TrainingEnrollmentStatus::Completed,
    ]);

    Sanctum::actingAs($employee->user);

    $this->postJson("/api/training-enrollments/{$enrollment->id}/feedback", [
        'satisfaction_score' => 6,
        'understanding_score' => 5,
    ])->assertStatus(422);
});

it('受講記録の詳細取得時、提出済みのフィードバックが含まれる', function () {
    $employee = createEmployeeWithAssignment();
    $enrollment = TrainingEnrollment::factory()->create([
        'employee_id' => $employee->id,
        'status' => TrainingEnrollmentStatus::Completed,
    ]);
    TrainingFeedback::factory()->create(['training_enrollment_id' => $enrollment->id, 'satisfaction_score' => 5]);

    Sanctum::actingAs($employee->user);

    $this->getJson("/api/training-enrollments/{$enrollment->id}")
        ->assertOk()
        ->assertJsonPath('training_feedback.satisfaction_score', 5);
});
