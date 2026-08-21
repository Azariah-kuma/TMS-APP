<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Trainings\StoreTrainingLessonAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Trainings\StoreTrainingLessonRequest;
use App\Http\Resources\TrainingLessonResource;
use App\Models\Training;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/*
 * 研修Lessonに関するAPIエンドポイントを提供するコントローラー。
 */
final class TrainingLessonController extends Controller
{
    public function index(Training $training): JsonResponse
    {
        Gate::authorize('view', $training);

        return response()->json(TrainingLessonResource::collection($training->lessons));
    }

    public function store(
        StoreTrainingLessonRequest $request,
        Training $training,
        StoreTrainingLessonAction $action,
    ): JsonResponse {
        $validated = $request->validated();

        $lesson = $action->execute(
            training: $training,
            title: $validated['title'],
            position: $validated['position'] ?? null,
            content: $request->file('content'),
        );

        return response()->json(new TrainingLessonResource($lesson), Response::HTTP_CREATED);
    }
}
