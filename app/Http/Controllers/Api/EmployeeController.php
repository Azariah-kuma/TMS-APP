<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Employees\OnboardEmployeeAction;
use App\Actions\Employees\SendEmployeeInviteAction;
use App\Enums\EmployeeRole;
use App\Exceptions\InviteEmailFailedException;
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
            ->with(['user', 'currentAssignment.department', 'currentAssignment.position'])
            ->withExists('currentDirectReportAssignments as is_manager')
            ->get();

        return response()->json(EmployeeResource::collection($employees));
    }

    public function store(StoreEmployeeRequest $request, OnboardEmployeeAction $action): JsonResponse
    {
        $validated = $request->validated();

        $employee = $action->execute(
            lastName: $validated['last_name'],
            firstName: $validated['first_name'],
            lastNameKana: $validated['last_name_kana'],
            firstNameKana: $validated['first_name_kana'],
            email: $validated['email'],
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
        $employee->loadExists('currentDirectReportAssignments as is_manager');

        return response()->json(new EmployeeResource($employee));
    }

    /** 招待メールが届かなかった従業員向けに、初期パスワード設定用の招待メールを再送する。 */
    public function resendInvite(Employee $employee, SendEmployeeInviteAction $action): JsonResponse
    {
        Gate::authorize('resendInvite', $employee);

        $sent = $action->execute($employee->user->email);

        if (! $sent) {
            throw new InviteEmailFailedException('招待メールの送信に失敗しました。時間をおいて再度お試しください。');
        }

        return response()->json(['message' => '招待メールを再送信しました。']);
    }
}
