<?php

declare(strict_types=1);

use App\Actions\Trainings\UpdateTrainingProgressAction;
use App\Enums\TrainingEnrollmentStatus;
use App\Exceptions\LessonBasedProgressException;
use App\Models\Training;
use App\Models\TrainingEnrollment;
use App\Models\TrainingLesson;

it('marks the enrollment as not started when progress is zero', function () {
    $enrollment = TrainingEnrollment::factory()->create([
        'progress' => 50,
        'status' => TrainingEnrollmentStatus::InProgress,
    ]);

    $updated = app(UpdateTrainingProgressAction::class)->execute($enrollment, 0);

    expect($updated->status)->toBe(TrainingEnrollmentStatus::NotStarted)
        ->and($updated->progress)->toBe(0)
        ->and($updated->completed_at)->toBeNull();
});

it('records the start time on first progress and keeps it on further updates', function () {
    $enrollment = TrainingEnrollment::factory()->create([
        'progress' => 0,
        'status' => TrainingEnrollmentStatus::NotStarted,
        'started_at' => null,
    ]);

    $action = app(UpdateTrainingProgressAction::class);

    $first = $action->execute($enrollment, 30);

    expect($first->status)->toBe(TrainingEnrollmentStatus::InProgress)
        ->and($first->started_at)->not->toBeNull();

    $startedAt = $first->started_at;

    $second = $action->execute($first, 60);

    expect($second->started_at->equalTo($startedAt))->toBeTrue();
});

it('marks the enrollment as completed and records completed_at at 100%', function () {
    $enrollment = TrainingEnrollment::factory()->create([
        'progress' => 90,
        'status' => TrainingEnrollmentStatus::InProgress,
    ]);

    $updated = app(UpdateTrainingProgressAction::class)->execute($enrollment, 100);

    expect($updated->status)->toBe(TrainingEnrollmentStatus::Completed)
        ->and($updated->completed_at)->not->toBeNull();
});

it('clamps out-of-range progress values into 0-100', function () {
    $enrollment = TrainingEnrollment::factory()->create(['progress' => 0]);

    $updated = app(UpdateTrainingProgressAction::class)->execute($enrollment, 150);

    expect($updated->progress)->toBe(100)
        ->and($updated->status)->toBe(TrainingEnrollmentStatus::Completed);
});

it('rejects manual progress updates once the training has lessons defined', function () {
    $training = Training::factory()->create();
    TrainingLesson::factory()->for($training)->create();
    $enrollment = TrainingEnrollment::factory()->create(['training_id' => $training->id]);

    expect(fn () => app(UpdateTrainingProgressAction::class)->execute($enrollment, 50))
        ->toThrow(LessonBasedProgressException::class);
});
