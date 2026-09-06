<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Employees\DeletePositionAction;
use App\Actions\Employees\InsertPositionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employees\StorePositionRequest;
use App\Http\Requests\Employees\UpdatePositionRequest;
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

    public function store(StorePositionRequest $request, InsertPositionAction $action): JsonResponse
    {
        $validated = $request->validated();

        $position = $action->execute(
            name: $validated['name'],
            code: $validated['code'],
            afterPositionId: $validated['after_position_id'] ?? null,
        );

        return response()->json($position, Response::HTTP_CREATED);
    }

    /** 誤登録した役職名・役職コードの訂正。序列(rank)はここでは変更しない。 */
    public function update(UpdatePositionRequest $request, Position $position): JsonResponse
    {
        $position->update($request->validated());

        return response()->json($position);
    }

    /** 誤登録した役職の削除。従業員の配属履歴で一度でも使われている役職は削除できない。 */
    public function destroy(Position $position, DeletePositionAction $action): JsonResponse
    {
        Gate::authorize('delete', $position);

        $action->execute($position);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
