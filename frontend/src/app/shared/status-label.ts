import { TrainingEnrollmentStatus } from '../core/models/training-enrollment';
import { TrainingRequestStatus } from '../core/models/training-request';

const LABELS: Record<TrainingEnrollmentStatus, string> = {
  not_started: '未着手',
  in_progress: '受講中',
  completed: '完了',
};

export function statusLabel(status: TrainingEnrollmentStatus): string {
  return LABELS[status];
}

const REQUEST_STATUS_LABELS: Record<TrainingRequestStatus, string> = {
  pending: '承認待ち',
  approved: '承認済み',
  rejected: '却下',
  cancelled: '取消',
};

export function requestStatusLabel(status: TrainingRequestStatus): string {
  return REQUEST_STATUS_LABELS[status];
}
