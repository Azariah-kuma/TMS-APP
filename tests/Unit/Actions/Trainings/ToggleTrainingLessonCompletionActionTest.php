<?php

declare(strict_types=1);

use App\Actions\Trainings\ToggleTrainingLessonCompletionAction;
use App\Enums\TrainingEnrollmentStatus;
use App\Exceptions\InvalidTrainingLessonException;
use App\Models\Training;
use App\Models\TrainingEnrollment;
use App\Models\TrainingLesson;

it('recalculates progress as a percentage of completed lessons', function () {
    $training = Training::factory()->create();
    $lessons = TrainingLesson::factory()->for($training)->count(4)->create();
    $enrollment = TrainingEnrollment::factory()->create(['training_id' => $training->id]);

    $action = app(ToggleTrainingLessonCompletionAction::class);

    $enrollment = $action->execute($enrollment, $lessons[0], completed: true);
    expect($enrollment->progress)->toBe(25)
        ->and($enrollment->status)->toBe(TrainingEnrollmentStatus::InProgress);

    $enrollment = $action->execute($enrollment, $lessons[1], completed: true);
    expect($enrollment->progress)->toBe(50);
});

it('marks the enrollment as completed once every lesson is checked', function () {
    $training = Training::factory()->create();
    $lessons = TrainingLesson::factory()->for($training)->count(2)->create();
    $enrollment = TrainingEnrollment::factory()->create(['training_id' => $training->id]);

    $action = app(ToggleTrainingLessonCompletionAction::class);
    $action->execute($enrollment, $lessons[0], completed: true);
    $enrollment = $action->execute($enrollment, $lessons[1], completed: true);

    expect($enrollment->progress)->toBe(100)
        ->and($enrollment->status)->toBe(TrainingEnrollmentStatus::Completed)
        ->and($enrollment->completed_at)->not->toBeNull();
});

it('recalculates progress downward when a lesson is unchecked', function () {
    $training = Training::factory()->create();
    $lessons = TrainingLesson::factory()->for($training)->count(2)->create();
    $enrollment = TrainingEnrollment::factory()->create(['training_id' => $training->id]);

    $action = app(ToggleTrainingLessonCompletionAction::class);
    $enrollment = $action->execute($enrollment, $lessons[0], completed: true);
    $enrollment = $action->execute($enrollment, $lessons[1], completed: true);
    expect($enrollment->progress)->toBe(100);

    $enrollment = $action->execute($enrollment, $lessons[0], completed: false);

    expect($enrollment->progress)->toBe(50)
        ->and($enrollment->status)->toBe(TrainingEnrollmentStatus::InProgress);
});

it('rejects toggling a lesson that belongs to a different training', function () {
    $enrollment = TrainingEnrollment::factory()->create();
    $otherLesson = TrainingLesson::factory()->create();

    expect(fn () => (app(ToggleTrainingLessonCompletionAction::class))->execute($enrollment, $otherLesson, completed: true))
        ->toThrow(InvalidTrainingLessonException::class);
});
