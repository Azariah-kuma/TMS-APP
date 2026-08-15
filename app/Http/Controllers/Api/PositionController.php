<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employees\StorePositionRequest;
use App\Models\Position;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/*
 * 役職に関するAPIエンドポイントを提供するコントローラー。
 */
final class PositionController extends Controller
{
    public function index(): JsonResponse
    {
        Gate::authorize('viewAny', Position::class);

        return response()->json(Position::query()->orderBy('rank')->get());
    }

    public function store(StorePositionRequest $request): JsonResponse
    {
        $position = Position::create($request->validated());

        return response()->json($position, Response::HTTP_CREATED);
    }
}
