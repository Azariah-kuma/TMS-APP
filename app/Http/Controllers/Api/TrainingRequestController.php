<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Trainings\ApproveTrainingRequestAction;
use App\Actions\Trainings\BulkApproveTrainingRequestsAction;
use App\Actions\Trainings\BulkRequestTrainingForDepartmentAction;
use App\Actions\Trainings\CancelTrainingRequestAction;
use App\Actions\Trainings\RejectTrainingRequestAction;
use App\Actions\Trainings\RequestTrainingAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Trainings\BulkApproveTrainingRequestsRequest;
use App\Http\Requests\Trainings\BulkRequestTrainingRequest;
use App\Http\Requests\Trainings\RejectTrainingRequestRequest;
use App\Http\Requests\Trainings\StoreTrainingRequestRequest;
use App\Http\Resources\TrainingRequestResource;
use App\Models\Employee;
use App\Models\Training;
use App\Models\TrainingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

/*
 * 研修受講申請に関するAPIエンドポイントを提供するコントローラー。
 */
final class TrainingRequestController extends Controller
{
    /** ログイン中の従業員のロールに応じて閲覧可能な申請一覧を返す。 */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', TrainingRequest::class);

        $trainingRequests = TrainingRequest::query()
            ->visibleTo($request->user()->employee)
            ->with(['training', 'employee.user', 'requestedBy.user', 'decidedBy.user', 'approvalHistory.decidedBy.user'])
            ->latest()
            ->get();

        return response()->json(TrainingRequestResource::collection($trainingRequests));
    }

    public function show(TrainingRequest $trainingRequest): JsonResponse
    {
        Gate::authorize('view', $trainingRequest);

        return response()->json(
            new TrainingRequestResource($trainingRequest->load(['training', 'employee.user', 'requestedBy.user', 'decidedBy.user', 'approvalHistory.decidedBy.user'])),
        );
    }

    /**
     * 研修受講を申請する。employee_id を省略するとログイン中の従業員自身の申請になり、
     * 指定した場合（部下の代理申請）はその従業員の分として申請される。
     */
    public function store(StoreTrainingRequestRequest $request, RequestTrainingAction $action): JsonResponse
    {
        $validated = $request->validated();
        $requestedBy = $request->user()->employee;
        $employee = isset($validated['employee_id']) ? Employee::findOrFail($validated['employee_id']) : $requestedBy;

        $trainingRequest = $action->execute(
            employee: $employee,
            requestedBy: $requestedBy,
            training: Training::findOrFail($validated['training_id']),
            reason: $validated['reason'] ?? null,
            dueAt: isset($validated['due_at']) ? Carbon::parse($validated['due_at']) : null,
        );

        return response()->json(
            new TrainingRequestResource($trainingRequest->load(['training', 'employee.user', 'requestedBy.user', 'approvalHistory.decidedBy.user'])),
            Response::HTTP_CREATED,
        );
    }

    /** 部署単位で、上司が自分の部下（HRの場合は部署内全員）をまとめて研修申請する。 */
    public function bulkRequest(
        BulkRequestTrainingRequest $request,
        Training $training,
        BulkRequestTrainingForDepartmentAction $action,
    ): JsonResponse {
        $validated = $request->validated();

        $result = $action->execute($training, $request->user()->employee, $validated['department_id']);

        return response()->json($result);
    }

    /** 申請を承認し、実際の受講登録を行う。申請者の上司または人事のみ。 */
    public function approve(
        Request $request,
        TrainingRequest $trainingRequest,
        ApproveTrainingRequestAction $action,
    ): JsonResponse {
        Gate::authorize('approve', $trainingRequest);

        $trainingRequest = $action->execute($trainingRequest, $request->user()->employee);

        return response()->json(
            new TrainingRequestResource($trainingRequest->load(['training', 'employee.user', 'requestedBy.user', 'decidedBy.user', 'approvalHistory.decidedBy.user'])),
        );
    }

    /**
     * 複数の申請をまとめて承認する。
     * 承認権限のないIDや、既に承認待ちでなくなっているIDが混ざっていた場合はスキップした件数として返す。
     */
    public function bulkApprove(
        BulkApproveTrainingRequestsRequest $request,
        BulkApproveTrainingRequestsAction $action,
    ): JsonResponse {
        $ids = array_unique($request->validated('ids'));

        $trainingRequests = TrainingRequest::query()
            ->whereIn('id', $ids)
            ->with(['employee.user', 'training'])
            ->get()
            ->filter(fn (TrainingRequest $trainingRequest) => $request->user()->can('approve', $trainingRequest))
            ->values();

        $result = $action->execute($trainingRequests, $request->user()->employee);

        return response()->json([
            'approved' => $result['approved'],
            'skipped' => count($ids) - $result['approved'],
        ]);
    }

    /** 申請を却下する。申請者の上司または人事のみ。 */
    public function reject(
        RejectTrainingRequestRequest $request,
        TrainingRequest $trainingRequest,
        RejectTrainingRequestAction $action,
    ): JsonResponse {
        $trainingRequest = $action->execute(
            $trainingRequest,
            $request->user()->employee,
            $request->validated('comment'),
        );

        return response()->json(
            new TrainingRequestResource($trainingRequest->load(['training', 'employee.user', 'requestedBy.user', 'decidedBy.user', 'approvalHistory.decidedBy.user'])),
        );
    }

    /** 申請者本人が、承認待ちの申請を取り消す。 */
    public function destroy(TrainingRequest $trainingRequest, CancelTrainingRequestAction $action): JsonResponse
    {
        Gate::authorize('cancel', $trainingRequest);

        $trainingRequest = $action->execute($trainingRequest);

        return response()->json(
            new TrainingRequestResource($trainingRequest->load(['training', 'employee.user', 'requestedBy.user', 'approvalHistory.decidedBy.user'])),
        );
    }
}
