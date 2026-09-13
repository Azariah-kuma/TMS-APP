/** 研修別の効果測定平均スコアと簡易ROI指標（GET /api/reports/training-roi）。 */
export interface TrainingRoiRow {
  training_id: number;
  title: string;
  unit_cost: number | null;
  feedback_count: number;
  avg_satisfaction_score: number;
  avg_understanding_score: number;
  avg_quiz_score: number | null;
  effectiveness_score: number;
  roi_index: number | null;
}
