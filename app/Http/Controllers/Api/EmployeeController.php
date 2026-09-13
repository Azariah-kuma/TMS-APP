<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Employees\BulkImportEmployeesAction;
use App\Actions\Employees\OnboardEmployeeAction;
use App\Actions\Employees\RetireEmployeeAction;
use App\Actions\Employees\SendEmployeeInviteAction;
use App\Enums\EmployeeRole;
use App\Exceptions\InviteEmailFailedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employees\BulkImportEmployeesRequest;
use App\Http\Requests\Employees\RetireEmployeeRequest;
use App\Http\Requests\Employees\StoreEmployeeRequest;
use App\Http\Requests\Employees\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

/*
 * 従業員に関するAPIエンドポイントを提供するコントローラー。
 */
final class EmployeeController extends Controller
{
    /** クエリで`with_retired=1`を指定しない限り、退職済みの従業員は一覧から除外する。 */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Employee::class);

        $employees = Employee::query()
            ->when(! $request->boolean('with_retired'), fn ($query) => $query->whereNull('retired_at'))
            ->with(['user', 'currentAssignment.department', 'currentAssignment.position'])
            ->withExists('currentDirectReportAssignments as is_manager')
            ->get();

        return response()->json(EmployeeResource::collection($employees));
    }

    /**
     * ログイン中の従業員の部下一覧（直接・間接、有効な委任経由を含む）。
     */
    public function subordinates(Request $request): JsonResponse
    {
        $actor = $request->user()->employee;
        abort_if($actor === null, Response::HTTP_FORBIDDEN);

        $employees = Employee::query()
            ->whereIn('id', $actor->subordinateIds())
            ->whereNull('retired_at')
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
            departmentId: $validated['department_id'] ?? null,
            positionId: $validated['position_id'] ?? null,
            managerId: $validated['manager_id'] ?? null,
        );

        return response()->json(new EmployeeResource($employee), Response::HTTP_CREATED);
    }

    /**
     * CSVファイルから複数の社員をまとめて登録する。行単位で成否を分けて返し、
     * 一部の行が不正でも他の行の登録は継続する。
     */
    public function bulkImport(BulkImportEmployeesRequest $request, BulkImportEmployeesAction $action): JsonResponse
    {
        $result = $action->execute($request->file('file'));

        return response()->json([
            'created' => EmployeeResource::collection($result['created']),
            'errors' => $result['errors'],
        ]);
    }

    public function show(Employee $employee): JsonResponse
    {
        Gate::authorize('view', $employee);

        $employee->load(['user', 'currentAssignment.department', 'currentAssignment.position']);
        $employee->loadExists('currentDirectReportAssignments as is_manager');

        return response()->json(new EmployeeResource($employee));
    }

    /** 氏名等の訂正（婚姻等による姓の変更など）。ユーザーの氏名・フリガナを更新する。 */
    public function update(UpdateEmployeeRequest $request, Employee $employee): JsonResponse
    {
        $employee->user->update($request->validated());

        $employee->load(['user', 'currentAssignment.department', 'currentAssignment.position']);
        $employee->loadExists('currentDirectReportAssignments as is_manager');

        return response()->json(new EmployeeResource($employee));
    }

    /** 退職登録。退職日を記録し、現在の配属（あれば）を同日付で終了させる。 */
    public function retire(RetireEmployeeRequest $request, Employee $employee, RetireEmployeeAction $action): JsonResponse
    {
        $employee = $action->execute($employee, Carbon::parse($request->validated('retired_at')));

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
