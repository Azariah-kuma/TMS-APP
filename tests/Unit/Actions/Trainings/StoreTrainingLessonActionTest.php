<?php

declare(strict_types=1);

use App\Actions\Trainings\StoreTrainingLessonAction;
use App\Models\Training;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('教材を添付せずにLessonを作成できる', function () {
    $training = Training::factory()->create();

    $lesson = (new StoreTrainingLessonAction)->execute(
        training: $training,
        title: '第1章 イントロダクション',
        position: 1,
        content: null,
    );

    expect($lesson->title)->toBe('第1章 イントロダクション')
        ->and($lesson->position)->toBe(1)
        ->and($lesson->content_path)->toBeNull();
});

it('アップロードされた教材ファイルをpublicディスクに保存し、メタデータを記録する', function () {
    Storage::fake('public');

    $training = Training::factory()->create();
    $video = UploadedFile::fake()->create('lesson.mp4', 2048, 'video/mp4');

    $lesson = (new StoreTrainingLessonAction)->execute(
        training: $training,
        title: '第1章 講義動画',
        position: null,
        content: $video,
    );

    expect($lesson->content_path)->not->toBeNull()
        ->and($lesson->content_original_name)->toBe('lesson.mp4')
        ->and($lesson->content_mime_type)->toBe('video/mp4');

    Storage::disk('public')->assertExists($lesson->content_path);
});
