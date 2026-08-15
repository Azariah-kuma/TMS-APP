import { TrainingEnrollmentStatus } from '../core/models/training-enrollment';

const LABELS: Record<TrainingEnrollmentStatus, string> = {
  not_started: '未着手',
  in_progress: '受講中',
  completed: '完了',
};

export function statusLabel(status: TrainingEnrollmentStatus): string {
  return LABELS[status];
}
