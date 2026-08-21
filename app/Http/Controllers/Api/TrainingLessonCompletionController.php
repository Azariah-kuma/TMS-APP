<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Trainings\ToggleTrainingLessonCompletionAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\TrainingEnrollmentResource;
use App\Models\TrainingEnrollment;
use App\Models\TrainingLesson;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/*
 * 研修受講記録のLesson完了状態に関するAPIエンドポイントを提供するコントローラー。
 */
final class TrainingLessonCompletionController extends Controller
{
    /** Lessonを完了済みにする。本人または人事のみ */
    public function complete(
        TrainingEnrollment $trainingEnrollment,
        TrainingLesson $trainingLesson,
        ToggleTrainingLessonCompletionAction $action,
    ): JsonResponse {
        Gate::authorize('update', $trainingEnrollment);

        $enrollment = $action->execute($trainingEnrollment, $trainingLesson, completed: true);

        return response()->json(
            new TrainingEnrollmentResource($enrollment->load(['training.lessons', 'lessonCompletions'])),
        );
    }

    /** Lessonのチェックを外す。本人または人事のみ。 */
    public function incomplete(
        TrainingEnrollment $trainingEnrollment,
        TrainingLesson $trainingLesson,
        ToggleTrainingLessonCompletionAction $action,
    ): JsonResponse {
        Gate::authorize('update', $trainingEnrollment);

        $enrollment = $action->execute($trainingEnrollment, $trainingLesson, completed: false);

        return response()->json(
            new TrainingEnrollmentResource($enrollment->load(['training.lessons', 'lessonCompletions'])),
        );
    }
}
