<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\StoreDepartmentBudgetRequest;
use App\Http\Requests\Reports\UpdateDepartmentBudgetRequest;
use App\Http\Resources\DepartmentBudgetResource;
use App\Models\DepartmentBudget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/*
 * 部署の年度研修予算に関するAPIエンドポイントを提供するコントローラー。
 */
final class DepartmentBudgetController extends Controller
{
    public function index(): JsonResponse
    {
        Gate::authorize('viewAny', DepartmentBudget::class);

        $budgets = DepartmentBudget::query()
            ->with('department')
            ->orderBy('fiscal_year', 'desc')
            ->get();

        return response()->json(DepartmentBudgetResource::collection($budgets));
    }

    public function store(StoreDepartmentBudgetRequest $request): JsonResponse
    {
        $budget = DepartmentBudget::create($request->validated());

        return response()->json(new DepartmentBudgetResource($budget), Response::HTTP_CREATED);
    }

    /** 年度予算額の訂正。 */
    public function update(UpdateDepartmentBudgetRequest $request, DepartmentBudget $departmentBudget): JsonResponse
    {
        $departmentBudget->update($request->validated());

        return response()->json(new DepartmentBudgetResource($departmentBudget));
    }
}
