<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Trainings\DeleteTrainingLessonAction;
use App\Actions\Trainings\StoreTrainingLessonAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Trainings\StoreTrainingLessonRequest;
use App\Http\Resources\TrainingLessonResource;
use App\Models\Training;
use App\Models\TrainingLesson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/*
 * 研修Lessonに関するAPIエンドポイントを提供するコントローラー。
 */
final class TrainingLessonController extends Controller
{
    /** 教材の中身は、人事または実際に受講登録済みの本人にのみ返す。 */
    public function index(Request $request, Training $training): JsonResponse
    {
        Gate::authorize('view', $training);

        if (! $training->hasLessonContentAccessFor($request->user()->employee)) {
            return response()->json([]);
        }

        return response()->json(
            TrainingLessonResource::collection($training->lessons()->with('attachments')->get()),
        );
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
            contents: $request->file('contents', []),
        );

        return response()->json(new TrainingLessonResource($lesson), Response::HTTP_CREATED);
    }

    /** Lessonの削除は、Lessonを追加できる人事のみ（update on Training）。 */
    public function destroy(
        Training $training,
        TrainingLesson $trainingLesson,
        DeleteTrainingLessonAction $action,
    ): JsonResponse {
        Gate::authorize('update', $training);

        $action->execute($training, $trainingLesson);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
