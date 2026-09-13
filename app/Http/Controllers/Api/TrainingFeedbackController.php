<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Trainings\SubmitTrainingFeedbackAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Trainings\StoreTrainingFeedbackRequest;
use App\Http\Resources\TrainingFeedbackResource;
use App\Models\TrainingEnrollment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/*
 * 研修効果測定（受講後アンケート・簡易テスト）に関するAPIエンドポイントを提供するコントローラー。
 */
final class TrainingFeedbackController extends Controller
{
    public function store(
        StoreTrainingFeedbackRequest $request,
        TrainingEnrollment $trainingEnrollment,
        SubmitTrainingFeedbackAction $action,
    ): JsonResponse {
        $validated = $request->validated();

        $feedback = $action->execute(
            $trainingEnrollment,
            $validated['satisfaction_score'],
            $validated['understanding_score'],
            $validated['quiz_score'] ?? null,
            $validated['comment'] ?? null,
        );

        return response()->json(new TrainingFeedbackResource($feedback), Response::HTTP_CREATED);
    }
}
