<?php

declare(strict_types=1);

namespace App\Actions\Trainings;

use App\Actions\Trainings\Support\ApplyTrainingProgressAction;
use App\Exceptions\LessonBasedProgressException;
use App\Models\TrainingEnrollment;

/**
 * 進捗率を手動で直接更新する（Lessonによる内訳を持たない研修向け）。
 */
final class UpdateTrainingProgressAction
{
    public function __construct(
        private readonly ApplyTrainingProgressAction $applyTrainingProgressAction,
    ) {}

    public function execute(TrainingEnrollment $enrollment, int $progress): TrainingEnrollment
    {
        if ($enrollment->training->lessons()->exists()) {
            throw new LessonBasedProgressException(
                'この研修はLesson単位で進捗を管理しています。進捗の直接更新はできません。'
            );
        }

        return $this->applyTrainingProgressAction->execute($enrollment, $progress);
    }
}
