<?php

declare(strict_types=1);

namespace App\Actions\Trainings;

use App\Actions\Trainings\Support\ApplyTrainingProgressAction;
use App\Exceptions\InvalidTrainingLessonException;
use App\Models\TrainingEnrollment;
use App\Models\TrainingLesson;
use App\Models\TrainingLessonCompletion;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

/**
 * Lessonの完了/未完了チェックを切り替え、研修全体の進捗率を再計算する。
 * 進捗率 = 完了Lesson数 ÷ その研修の総Lesson数（%、四捨五入）。
 */
final class ToggleTrainingLessonCompletionAction
{
    public function __construct(
        private readonly ApplyTrainingProgressAction $applyTrainingProgressAction,
    ) {}

    public function execute(TrainingEnrollment $enrollment, TrainingLesson $lesson, bool $completed): TrainingEnrollment
    {
        if ($lesson->training_id !== $enrollment->training_id) {
            throw new InvalidTrainingLessonException('このLessonは、この受講記録の研修に属していません。');
        }

        return DB::transaction(function () use ($enrollment, $lesson, $completed) {
            if ($completed) {
                TrainingLessonCompletion::query()->firstOrCreate(
                    [
                        'training_enrollment_id' => $enrollment->id,
                        'training_lesson_id' => $lesson->id,
                    ],
                    ['completed_at' => Date::now()],
                );
            } else {
                TrainingLessonCompletion::query()
                    ->where('training_enrollment_id', $enrollment->id)
                    ->where('training_lesson_id', $lesson->id)
                    ->delete();
            }

            $totalLessons = $lesson->training->lessons()->count();
            $completedLessons = $enrollment->lessonCompletions()->count();

            $progress = $totalLessons > 0
                ? (int) round($completedLessons / $totalLessons * 100)
                : 0;

            return $this->applyTrainingProgressAction->execute($enrollment, $progress);
        });
    }
}
