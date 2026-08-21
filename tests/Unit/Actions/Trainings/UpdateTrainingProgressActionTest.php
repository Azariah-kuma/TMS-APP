<?php

declare(strict_types=1);

use App\Actions\Trainings\UpdateTrainingProgressAction;
use App\Enums\TrainingEnrollmentStatus;
use App\Exceptions\LessonBasedProgressException;
use App\Models\Training;
use App\Models\TrainingEnrollment;
use App\Models\TrainingLesson;

it('進捗が0の場合、受講記録を未着手にする', function () {
    $enrollment = TrainingEnrollment::factory()->create([
        'progress' => 50,
        'status' => TrainingEnrollmentStatus::InProgress,
    ]);

    $updated = app(UpdateTrainingProgressAction::class)->execute($enrollment, 0);

    expect($updated->status)->toBe(TrainingEnrollmentStatus::NotStarted)
        ->and($updated->progress)->toBe(0)
        ->and($updated->completed_at)->toBeNull();
});

it('初回の進捗更新時に開始日時を記録し、以降の更新では維持する', function () {
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

it('進捗が100%になると受講記録を完了にし、completed_atを記録する', function () {
    $enrollment = TrainingEnrollment::factory()->create([
        'progress' => 90,
        'status' => TrainingEnrollmentStatus::InProgress,
    ]);

    $updated = app(UpdateTrainingProgressAction::class)->execute($enrollment, 100);

    expect($updated->status)->toBe(TrainingEnrollmentStatus::Completed)
        ->and($updated->completed_at)->not->toBeNull();
});

it('範囲外の進捗値は0〜100の範囲に丸められる', function () {
    $enrollment = TrainingEnrollment::factory()->create(['progress' => 0]);

    $updated = app(UpdateTrainingProgressAction::class)->execute($enrollment, 150);

    expect($updated->progress)->toBe(100)
        ->and($updated->status)->toBe(TrainingEnrollmentStatus::Completed);
});

it('研修にLessonが定義されている場合、手動での進捗更新は拒否される', function () {
    $training = Training::factory()->create();
    TrainingLesson::factory()->for($training)->create();
    $enrollment = TrainingEnrollment::factory()->create(['training_id' => $training->id]);

    expect(fn () => app(UpdateTrainingProgressAction::class)->execute($enrollment, 50))
        ->toThrow(LessonBasedProgressException::class);
});
