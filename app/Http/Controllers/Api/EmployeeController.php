<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Employees\OnboardEmployeeAction;
use App\Enums\EmployeeRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employees\StoreEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

/*
 * 従業員に関するAPIエンドポイントを提供するコントローラー。
 */
final class EmployeeController extends Controller
{
    public function index(): JsonResponse
    {
        Gate::authorize('viewAny', Employee::class);

        $employees = Employee::query()
            ->with(['user', 'currentAssignment'])
            ->get();

        return response()->json(EmployeeResource::collection($employees));
    }

    public function store(StoreEmployeeRequest $request, OnboardEmployeeAction $action): JsonResponse
    {
        $validated = $request->validated();

        $employee = $action->execute(
            name: $validated['name'],
            email: $validated['email'],
            password: $validated['password'],
            employeeCode: $validated['employee_code'],
            role: EmployeeRole::from($validated['role']),
            hiredAt: Carbon::parse($validated['hired_at']),
            departmentId: $validated['department_id'],
            positionId: $validated['position_id'],
            managerId: $validated['manager_id'] ?? null,
        );

        return response()->json(new EmployeeResource($employee), Response::HTTP_CREATED);
    }

    public function show(Employee $employee): JsonResponse
    {
        Gate::authorize('view', $employee);

        $employee->load(['user', 'currentAssignment.department', 'currentAssignment.position']);

        return response()->json(new EmployeeResource($employee));
    }
}
