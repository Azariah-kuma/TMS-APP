import { Training } from './training';

export type TrainingRequestStatus = 'pending' | 'approved' | 'rejected' | 'cancelled';

export interface TrainingRequest {
  id: number;
  employee_id: number;
  employee_name?: string;
  requested_by_employee_id: number;
  requested_by_name?: string | null;
  /** 本人自身の申請か（false の場合は上司による代理申請で、承認は人事のみ）。 */
  is_self_requested: boolean;
  training: Training | null;
  status: TrainingRequestStatus;
  reason: string | null;
  due_at: string | null;
  decided_by_employee_id: number | null;
  decided_by_name?: string | null;
  decided_at: string | null;
  decision_comment: string | null;
  requested_at: string;
  /** 自分がこの申請を承認/却下できるか（サーバー側のPolicy判定結果）。 */
  can_decide: boolean;
  /** 自分がこの申請を取り消せるか（サーバー側のPolicy判定結果）。 */
  can_cancel: boolean;
  /** 別ルート（人事の直接登録等）で既に受講登録済みかどうか。 */
  already_enrolled?: boolean;
}
