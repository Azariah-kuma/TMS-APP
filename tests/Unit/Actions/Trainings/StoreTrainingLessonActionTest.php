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
        contents: [],
    );

    expect($lesson->title)->toBe('第1章 イントロダクション')
        ->and($lesson->position)->toBe(1)
        ->and($lesson->attachments)->toBeEmpty();
});

it('アップロードされた教材ファイルをpublicディスクに保存し、メタデータを記録する', function () {
    Storage::fake('public');

    $training = Training::factory()->create();
    $video = UploadedFile::fake()->create('lesson.mp4', 2048, 'video/mp4');

    $lesson = (new StoreTrainingLessonAction)->execute(
        training: $training,
        title: '第1章 講義動画',
        position: null,
        contents: [$video],
    );

    expect($lesson->attachments)->toHaveCount(1);

    $attachment = $lesson->attachments->first();

    expect($attachment->path)->not->toBeNull()
        ->and($attachment->original_name)->toBe('lesson.mp4')
        ->and($attachment->mime_type)->toBe('video/mp4');

    Storage::disk('public')->assertExists($attachment->path);
});

it('複数の教材ファイルを一度に添付できる', function () {
    Storage::fake('public');

    $training = Training::factory()->create();
    $video = UploadedFile::fake()->create('lesson.mp4', 2048, 'video/mp4');
    $pdf = UploadedFile::fake()->create('slide.pdf', 512, 'application/pdf');

    $lesson = (new StoreTrainingLessonAction)->execute(
        training: $training,
        title: '第1章',
        position: null,
        contents: [$video, $pdf],
    );

    expect($lesson->attachments)->toHaveCount(2);

    $names = $lesson->attachments->pluck('original_name')->all();
    expect($names)->toBe(['lesson.mp4', 'slide.pdf']);

    foreach ($lesson->attachments as $attachment) {
        Storage::disk('public')->assertExists($attachment->path);
    }
});
