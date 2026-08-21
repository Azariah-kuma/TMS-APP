<?php

declare(strict_types=1);

use App\Actions\Trainings\ToggleTrainingLessonCompletionAction;
use App\Enums\TrainingEnrollmentStatus;
use App\Exceptions\InvalidTrainingLessonException;
use App\Models\Training;
use App\Models\TrainingEnrollment;
use App\Models\TrainingLesson;

it('完了したLessonの割合として進捗を再計算する', function () {
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

it('全てのLessonにチェックが入ると受講記録を完了にする', function () {
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

it('Lessonのチェックを外すと進捗が下方に再計算される', function () {
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

it('別の研修に属するLessonの切り替えは拒否される', function () {
    $enrollment = TrainingEnrollment::factory()->create();
    $otherLesson = TrainingLesson::factory()->create();

    expect(fn () => (app(ToggleTrainingLessonCompletionAction::class))->execute($enrollment, $otherLesson, completed: true))
        ->toThrow(InvalidTrainingLessonException::class);
});

it('研修にLessonが1つも無い状態になった場合、進捗は0にフォールバックする', function () {
    $training = Training::factory()->create();
    $lesson = TrainingLesson::factory()->for($training)->create();
    $enrollment = TrainingEnrollment::factory()->create(['training_id' => $training->id]);

    $action = app(ToggleTrainingLessonCompletionAction::class);
    $action->execute($enrollment, $lesson, completed: true);

    // Lessonそのものが削除された場合、完了記録も連鎖して消える（外部キー制約による）。
    $lesson->delete();

    $enrollment = $action->execute($enrollment, $lesson, completed: false);

    expect($enrollment->progress)->toBe(0);
});
