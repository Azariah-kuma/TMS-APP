<?php

declare(strict_types=1);

namespace App\Actions\Trainings;

use App\Exceptions\InvalidTrainingLessonException;
use App\Models\Training;
use App\Models\TrainingLesson;
use Illuminate\Support\Facades\Storage;

final class DeleteTrainingLessonAction
{
    /**
     * Lessonを削除する。完了記録は外部キー制約により連鎖して削除される。
     * 教材ファイルが添付されている場合は、publicディスクからも削除する。
     */
    public function execute(Training $training, TrainingLesson $lesson): void
    {
        if ($lesson->training_id !== $training->id) {
            throw new InvalidTrainingLessonException('このLessonは、この研修に属していません。');
        }

        if ($lesson->content_path !== null) {
            Storage::disk('public')->delete($lesson->content_path);
        }

        $lesson->delete();
    }
}
