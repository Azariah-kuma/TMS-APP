import { Training } from './training';

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
}
