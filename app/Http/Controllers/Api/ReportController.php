<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Reports\BuildTrainingEnrollmentsCsvAction;
use App\Actions\Reports\BuildTrainingSummaryReportAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/*
 * 人事向けレポートに関するAPIエンドポイントを提供するコントローラー。
 */
final class ReportController extends Controller
{
    /** 研修別・部署別の受講状況サマリー。 */
    public function summary(BuildTrainingSummaryReportAction $action): JsonResponse
    {
        Gate::authorize('viewReports');

        return response()->json($action->execute());
    }

    /** 全受講記録のCSVエクスポート。 */
    public function exportCsv(BuildTrainingEnrollmentsCsvAction $action): Response
    {
        Gate::authorize('viewReports');

        return response($action->execute(), Response::HTTP_OK, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="training_enrollments.csv"',
        ]);
    }
}
