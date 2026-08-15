<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employees\StoreDepartmentRequest;
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
}
