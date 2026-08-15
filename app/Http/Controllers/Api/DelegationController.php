<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Employees\CreateDelegationAction;
use App\Actions\Employees\RevokeDelegationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employees\StoreDelegationRequest;
use App\Http\Resources\DelegationResource;
use App\Models\Delegation;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

/*
 * 従業員の上司権限を、期間限定で他の従業員に委任するためのAPIエンドポイントを提供するコントローラー。
 */
final class DelegationController extends Controller
{
    /** $employee（委任元）が行った委任の一覧。 */
    public function index(Employee $employee): JsonResponse
    {
        Gate::authorize('view', $employee);

        $delegations = $employee->delegationsGiven()->with('delegate.user')->get();

        return response()->json(DelegationResource::collection($delegations));
    }

    /** $employee（委任元）の上司権限を、期間限定で他の従業員に委任する。 */
    public function store(
        StoreDelegationRequest $request,
        Employee $employee,
        CreateDelegationAction $action,
    ): JsonResponse {
        $validated = $request->validated();

        $delegation = $action->execute(
            delegator: $employee,
            delegate: Employee::findOrFail($validated['delegate_id']),
            startedAt: Carbon::parse($validated['started_at']),
            endedAt: isset($validated['ended_at']) ? Carbon::parse($validated['ended_at']) : null,
        );

        return response()->json(new DelegationResource($delegation), Response::HTTP_CREATED);
    }

    /** 委任を即時取り消す。 */
    public function destroy(Delegation $delegation, RevokeDelegationAction $action): JsonResponse
    {
        Gate::authorize('delete', $delegation);

        $delegation = $action->execute($delegation);

        return response()->json(new DelegationResource($delegation));
    }
}
