<?php

declare(strict_types=1);

use App\Models\TrainingLesson;
use App\Models\TrainingLessonAttachment;

it('紐づくLessonを取得できる', function () {
    $lesson = TrainingLesson::factory()->create();
    $attachment = TrainingLessonAttachment::factory()->create(['training_lesson_id' => $lesson->id]);

    expect($attachment->lesson->is($lesson))->toBeTrue();
});
