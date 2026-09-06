<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Employees\DeleteDepartmentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employees\StoreDepartmentRequest;
use App\Http\Requests\Employees\UpdateDepartmentRequest;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/*
 * 部署に関するAPIエンドポイントを提供するコントローラー。
 */
final class DepartmentController extends Controller
{
    public function index(): JsonResponse
    {
        Gate::authorize('viewAny', Department::class);

        return response()->json(Department::query()->orderBy('name')->get());
    }

    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $department = Department::create($request->validated());

        return response()->json($department, Response::HTTP_CREATED);
    }

    /** 統合・組織変更に伴う部署名・部署コードの訂正。 */
    public function update(UpdateDepartmentRequest $request, Department $department): JsonResponse
    {
        $department->update($request->validated());

        return response()->json($department);
    }

    /** 従業員の配属履歴、または研修の閲覧対象部署として一度でも使われている部署は削除できない。 */
    public function destroy(Department $department, DeleteDepartmentAction $action): JsonResponse
    {
        Gate::authorize('delete', $department);

        $action->execute($department);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
