<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Employees\TransferEmployeeAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employees\StoreEmployeeAssignmentRequest;
use App\Http\Resources\EmployeeAssignmentResource;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

/*
 * 従業員の部署・役職・上司の割り当てに関するAPIエンドポイントを提供するコントローラー。
 */
final class EmployeeAssignmentController extends Controller
{
    /** 従業員の部署・役職・上司の割り当て履歴を、新しい順に返す。 */
    public function index(Employee $employee): JsonResponse
    {
        Gate::authorize('view', $employee);

        $assignments = $employee->assignments()
            ->with(['department', 'position'])
            ->get();

        return response()->json(EmployeeAssignmentResource::collection($assignments));
    }

    /** 異動（部署・役職・上司の変更）を登録する。既存の割り当ては履歴として残る。 */
    public function store(
        StoreEmployeeAssignmentRequest $request,
        Employee $employee,
        TransferEmployeeAction $action,
    ): JsonResponse {
        $validated = $request->validated();

        $assignment = $action->execute(
            employee: $employee,
            departmentId: $validated['department_id'],
            positionId: $validated['position_id'],
            managerId: $validated['manager_id'] ?? null,
            startedAt: Carbon::parse($validated['started_at']),
        );

        return response()->json(new EmployeeAssignmentResource($assignment), Response::HTTP_CREATED);
    }
}
