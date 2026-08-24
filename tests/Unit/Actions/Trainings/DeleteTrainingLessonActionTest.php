<?php

declare(strict_types=1);

use App\Actions\Trainings\DeleteTrainingLessonAction;
use App\Exceptions\InvalidTrainingLessonException;
use App\Models\Training;
use App\Models\TrainingLesson;
use App\Models\TrainingLessonCompletion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('Lessonを削除できる', function () {
    $training = Training::factory()->create();
    $lesson = TrainingLesson::factory()->for($training)->create();

    (new DeleteTrainingLessonAction)->execute($training, $lesson);

    expect(TrainingLesson::find($lesson->id))->toBeNull();
});

it('Lessonを削除すると、紐づく完了記録も連鎖して削除される', function () {
    $training = Training::factory()->create();
    $lesson = TrainingLesson::factory()->for($training)->create();
    $completion = TrainingLessonCompletion::factory()->create(['training_lesson_id' => $lesson->id]);

    (new DeleteTrainingLessonAction)->execute($training, $lesson);

    expect(TrainingLessonCompletion::find($completion->id))->toBeNull();
});

it('教材ファイルが添付されている場合、publicディスクからも削除する', function () {
    Storage::fake('public');

    $training = Training::factory()->create();
    $file = UploadedFile::fake()->create('lesson.mp4', 1024, 'video/mp4');
    $path = $file->store('training-lessons', 'public');
    $lesson = TrainingLesson::factory()->for($training)->create(['content_path' => $path]);

    Storage::disk('public')->assertExists($path);

    (new DeleteTrainingLessonAction)->execute($training, $lesson);

    Storage::disk('public')->assertMissing($path);
});

it('別の研修に属するLessonの削除は拒否される', function () {
    $training = Training::factory()->create();
    $otherLesson = TrainingLesson::factory()->create();

    expect(fn () => (new DeleteTrainingLessonAction)->execute($training, $otherLesson))
        ->toThrow(InvalidTrainingLessonException::class);

    expect(TrainingLesson::find($otherLesson->id))->not->toBeNull();
});
