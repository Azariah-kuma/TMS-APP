export interface TrainingFeedback {
  id: number;
  training_enrollment_id: number;
  satisfaction_score: number;
  understanding_score: number;
  quiz_score: number | null;
  comment: string | null;
  submitted_at: string;
}
