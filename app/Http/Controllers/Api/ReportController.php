<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Reports\BuildDepartmentBudgetUsageReportAction;
use App\Actions\Reports\BuildTrainingEnrollmentsCsvAction;
use App\Actions\Reports\BuildTrainingRoiReportAction;
use App\Actions\Reports\BuildTrainingSummaryReportAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    /** 部署別の年度研修予算・消費額（未指定なら今年度）。 */
    public function budgetUsage(Request $request, BuildDepartmentBudgetUsageReportAction $action): JsonResponse
    {
        Gate::authorize('viewReports');

        $fiscalYear = $request->filled('fiscal_year') ? (int) $request->query('fiscal_year') : null;

        return response()->json($action->execute($fiscalYear));
    }

    /** 研修別の効果測定（アンケート・テスト）平均スコアと簡易ROI指標。 */
    public function roi(BuildTrainingRoiReportAction $action): JsonResponse
    {
        Gate::authorize('viewReports');

        return response()->json($action->execute());
    }
}
