<?php

declare(strict_types=1);

use App\Models\Training;
use App\Models\TrainingLesson;
use App\Models\TrainingLessonAttachment;
use App\Models\TrainingLessonCompletion;

it('定義元の研修に属する', function () {
    $training = Training::factory()->create();
    $lesson = TrainingLesson::factory()->for($training)->create();

    expect($lesson->training->is($training))->toBeTrue();
});

it('Lessonに紐づく完了記録一覧を取得できる', function () {
    $lesson = TrainingLesson::factory()->create();
    $completion = TrainingLessonCompletion::factory()->create(['training_lesson_id' => $lesson->id]);

    expect($lesson->completions()->pluck('id'))->toEqual(collect([$completion->id]));
});

it('Lessonに紐づく添付教材ファイル一覧を取得できる', function () {
    $lesson = TrainingLesson::factory()->create();
    $attachment = TrainingLessonAttachment::factory()->create(['training_lesson_id' => $lesson->id]);

    expect($lesson->attachments()->pluck('id'))->toEqual(collect([$attachment->id]));
});
