<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Trainings\StoreTrainingRequest;
use App\Http\Requests\Trainings\UpdateTrainingRequest;
use App\Http\Resources\TrainingResource;
use App\Models\Training;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/*
 * 研修に関するAPIエンドポイントを提供するコントローラー。
 */
final class TrainingController extends Controller
{
    public function index(): JsonResponse
    {
        Gate::authorize('viewAny', Training::class);

        $trainings = Training::query()->with('lessons')->orderBy('title')->get();

        return response()->json(TrainingResource::collection($trainings));
    }

    public function show(Training $training): JsonResponse
    {
        Gate::authorize('view', $training);

        return response()->json(new TrainingResource($training->load('lessons')));
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
