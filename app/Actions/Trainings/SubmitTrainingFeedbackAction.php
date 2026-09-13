<?php

declare(strict_types=1);

namespace App\Actions\Trainings;

use App\Enums\TrainingEnrollmentStatus;
use App\Exceptions\TrainingEnrollmentNotCompletedException;
use App\Exceptions\TrainingFeedbackAlreadySubmittedException;
use App\Models\TrainingEnrollment;
use App\Models\TrainingFeedback;

final class SubmitTrainingFeedbackAction
{
    /** 受講完了後、本人が満足度・理解度アンケートと簡易テストの得点を提出する。 */
    public function execute(
        TrainingEnrollment $enrollment,
        int $satisfactionScore,
        int $understandingScore,
        ?int $quizScore,
        ?string $comment,
    ): TrainingFeedback {
        if ($enrollment->status !== TrainingEnrollmentStatus::Completed) {
            throw new TrainingEnrollmentNotCompletedException('受講が完了していないため、研修効果測定を提出できません。');
        }

        if ($enrollment->trainingFeedback()->exists()) {
            throw new TrainingFeedbackAlreadySubmittedException('この受講記録の研修効果測定は提出済みです。');
        }

        return $enrollment->trainingFeedback()->create([
            'satisfaction_score' => $satisfactionScore,
            'understanding_score' => $understandingScore,
            'quiz_score' => $quizScore,
            'comment' => $comment,
            'submitted_at' => now(),
        ]);
    }
}
