<?php

declare(strict_types=1);

use App\Models\TrainingEnrollment;
use App\Models\TrainingLesson;
use App\Models\TrainingLessonCompletion;

it('記録対象の受講記録に属する', function () {
    $enrollment = TrainingEnrollment::factory()->create();
    $completion = TrainingLessonCompletion::factory()->create(['training_enrollment_id' => $enrollment->id]);

    expect($completion->trainingEnrollment->is($enrollment))->toBeTrue();
});

it('完了とマークしたLessonに属する', function () {
    $lesson = TrainingLesson::factory()->create();
    $completion = TrainingLessonCompletion::factory()->create(['training_lesson_id' => $lesson->id]);

    expect($completion->trainingLesson->is($lesson))->toBeTrue();
});
