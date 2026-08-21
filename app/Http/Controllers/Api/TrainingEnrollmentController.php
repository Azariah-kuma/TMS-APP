<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Trainings\BulkEnrollEmployeesInTrainingAction;
use App\Actions\Trainings\EnrollEmployeeInTrainingAction;
use App\Actions\Trainings\UpdateTrainingProgressAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Trainings\BulkEnrollTrainingRequest;
use App\Http\Requests\Trainings\StoreTrainingEnrollmentRequest;
use App\Http\Requests\Trainings\UpdateTrainingEnrollmentProgressRequest;
use App\Http\Resources\TrainingEnrollmentResource;
use App\Models\Employee;
use App\Models\Training;
use App\Models\TrainingEnrollment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

/*
 * 研修受講記録に関するAPIエンドポイントを提供するコントローラー。
 */
final class TrainingEnrollmentController extends Controller
{
    /** ログイン中の従業員のロールに応じて閲覧可能な受講記録一覧を返す。 */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', TrainingEnrollment::class);

        $enrollments = TrainingEnrollment::query()
            ->visibleTo($request->user()->employee)
            ->with(['training.lessons', 'employee.user', 'lessonCompletions'])
            ->get();

        return response()->json(TrainingEnrollmentResource::collection($enrollments));
    }

    public function show(TrainingEnrollment $trainingEnrollment): JsonResponse
    {
        Gate::authorize('view', $trainingEnrollment);

        return response()->json(new TrainingEnrollmentResource($trainingEnrollment->load(['training.lessons', 'lessonCompletions'])));
    }

    /** 研修の割り当て（受講登録）を行う。 */
    public function store(
        StoreTrainingEnrollmentRequest $request,
        Employee $employee,
        EnrollEmployeeInTrainingAction $action,
    ): JsonResponse {
        $validated = $request->validated();

        $enrollment = $action->execute(
            employee: $employee,
            training: Training::findOrFail($validated['training_id']),
            dueAt: isset($validated['due_at']) ? Carbon::parse($validated['due_at']) : null,
        );

        return response()->json(
            new TrainingEnrollmentResource($enrollment->load(['training.lessons', 'lessonCompletions'])),
            Response::HTTP_CREATED,
        );
    }

    /**
     * 部署単位、または全社一括で研修を受講登録する。
     * department_id を指定しなければ在籍中の全従業員が対象になる。
     */
    public function bulkEnroll(
        BulkEnrollTrainingRequest $request,
        Training $training,
        BulkEnrollEmployeesInTrainingAction $action,
    ): JsonResponse {
        $validated = $request->validated();

        $result = $action->execute(
            training: $training,
            departmentId: $validated['department_id'] ?? null,
            dueAt: isset($validated['due_at']) ? Carbon::parse($validated['due_at']) : null,
        );

        return response()->json($result);
    }

    /** 受講進捗の更新。本人または人事のみ実行できる（Policy参照）。 */
    public function update(
        UpdateTrainingEnrollmentProgressRequest $request,
        TrainingEnrollment $trainingEnrollment,
        UpdateTrainingProgressAction $action,
    ): JsonResponse {
        $enrollment = $action->execute($trainingEnrollment, (int) $request->validated('progress'));

        return response()->json(new TrainingEnrollmentResource($enrollment->load(['training.lessons', 'lessonCompletions'])));
    }

    public function destroy(TrainingEnrollment $trainingEnrollment): JsonResponse
    {
        Gate::authorize('delete', $trainingEnrollment);

        $trainingEnrollment->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
