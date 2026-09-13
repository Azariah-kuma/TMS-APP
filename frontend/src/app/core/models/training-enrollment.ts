import { Training } from './training';
import { TrainingFeedback } from './training-feedback';

export type TrainingEnrollmentStatus = 'not_started' | 'in_progress' | 'completed';

export interface TrainingEnrollment {
  id: number;
  employee_id: number;
  employee_name?: string;
  training: Training | null;
  status: TrainingEnrollmentStatus;
  progress: number;
  due_at: string | null;
  started_at: string | null;
  completed_at: string | null;
  completed_lesson_ids?: number[];
  /** 研修効果測定（アンケート・テスト）の提出済みフィードバック（未提出・未読込時はundefined/null）。 */
  training_feedback?: TrainingFeedback | null;
}
