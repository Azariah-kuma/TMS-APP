<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Trainings\StoreTrainingRequest;
use App\Http\Requests\Trainings\UpdateTrainingRequest;
use App\Http\Resources\TrainingResource;
use App\Models\Training;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/*
 * 研修に関するAPIエンドポイントを提供するコントローラー。
 */
final class TrainingController extends Controller
{
    /**
     * 一覧ではLesson教材の中身（添付ファイルURL等）までは返さず、件数のみ返す。
     * 研修の閲覧対象外・未受講の従業員にまで教材ファイルのURLを渡さないため。
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Training::class);

        $trainings = Training::query()
            ->with(['audienceDepartment'])
            ->withCount('lessons')
            ->visibleTo($request->user()->employee)
            ->orderBy('title')
            ->get();

        return response()->json(TrainingResource::collection($trainings));
    }

    /**
     * Lesson教材の中身（添付ファイル等）は、人事または実際に受講登録済みの本人にのみ返す
     * （カタログで研修が見えることと、教材をダウンロードできることは別に扱う）。
     */
    public function show(Request $request, Training $training): JsonResponse
    {
        Gate::authorize('view', $training);

        $training->loadCount('lessons')->load('audienceDepartment');

        if ($training->hasLessonContentAccessFor($request->user()->employee)) {
            $training->load('lessons.attachments');
        }

        return response()->json(new TrainingResource($training));
    }

    public function store(StoreTrainingRequest $request): JsonResponse
    {
        $training = Training::create($request->validated());

        return response()->json(new TrainingResource($training), Response::HTTP_CREATED);
    }

    public function update(UpdateTrainingRequest $request, Training $training): JsonResponse
    {
        $training->update($request->validated());

        return response()->json(new TrainingResource($training));
    }

    public function destroy(Training $training): JsonResponse
    {
        Gate::authorize('delete', $training);

        $training->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
