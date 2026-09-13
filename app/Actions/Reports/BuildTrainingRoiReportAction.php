<?php

declare(strict_types=1);

namespace App\Actions\Reports;

use App\Models\Training;
use Illuminate\Support\Facades\DB;

final class BuildTrainingRoiReportAction
{
    /**
     * 研修ごとに、提出された研修効果測定（アンケート＋テスト）の平均スコアと、
     * 単価から算出した簡易ROI指標（効果スコア÷単価。値が大きいほど費用対効果が高い）を集計する。
     *
     * 効果スコア（0〜100）は、満足度・理解度（いずれも5段階を100点満点換算）と
     * テスト得点（提出されていれば0〜100点）の平均。単価未設定の研修はroi_indexがnullになる。
     *
     * @return list<array{
     *     training_id: int,
     *     title: string,
     *     unit_cost: float|null,
     *     feedback_count: int,
     *     avg_satisfaction_score: float|null,
     *     avg_understanding_score: float|null,
     *     avg_quiz_score: float|null,
     *     effectiveness_score: float|null,
     *     roi_index: float|null,
     * }>
     */
    public function execute(): array
    {
        $rows = DB::table('training_feedbacks')
            ->join('training_enrollments', 'training_enrollments.id', '=', 'training_feedbacks.training_enrollment_id')
            ->groupBy('training_enrollments.training_id')
            ->select(
                'training_enrollments.training_id',
                DB::raw('count(*) as feedback_count'),
                DB::raw('avg(training_feedbacks.satisfaction_score) as avg_satisfaction_score'),
                DB::raw('avg(training_feedbacks.understanding_score) as avg_understanding_score'),
                DB::raw('avg(training_feedbacks.quiz_score) as avg_quiz_score'),
            )
            ->get()
            ->keyBy('training_id');

        return Training::query()
            ->whereIn('id', $rows->keys())
            ->orderBy('title')
            ->get()
            ->map(fn (Training $training) => $this->summarize($training, $rows[$training->id]))
            ->values()
            ->all();
    }

    /**
     * @param  object{feedback_count: int, avg_satisfaction_score: float|string, avg_understanding_score: float|string, avg_quiz_score: float|string|null}  $row
     * @return array{
     *     training_id: int,
     *     title: string,
     *     unit_cost: float|null,
     *     feedback_count: int,
     *     avg_satisfaction_score: float|null,
     *     avg_understanding_score: float|null,
     *     avg_quiz_score: float|null,
     *     effectiveness_score: float|null,
     *     roi_index: float|null,
     * }
     */
    private function summarize(Training $training, object $row): array
    {
        $avgSatisfaction = round((float) $row->avg_satisfaction_score, 2);
        $avgUnderstanding = round((float) $row->avg_understanding_score, 2);
        $avgQuiz = $row->avg_quiz_score === null ? null : round((float) $row->avg_quiz_score, 2);

        $components = [$avgSatisfaction / 5 * 100, $avgUnderstanding / 5 * 100];

        if ($avgQuiz !== null) {
            $components[] = $avgQuiz;
        }

        $effectivenessScore = round(array_sum($components) / count($components), 2);
        $unitCost = $training->unit_cost === null ? null : (float) $training->unit_cost;

        return [
            'training_id' => $training->id,
            'title' => $training->title,
            'unit_cost' => $unitCost,
            'feedback_count' => (int) $row->feedback_count,
            'avg_satisfaction_score' => $avgSatisfaction,
            'avg_understanding_score' => $avgUnderstanding,
            'avg_quiz_score' => $avgQuiz,
            'effectiveness_score' => $effectivenessScore,
            'roi_index' => ($unitCost === null || $unitCost <= 0.0) ? null : round($effectivenessScore / $unitCost, 4),
        ];
    }
}
