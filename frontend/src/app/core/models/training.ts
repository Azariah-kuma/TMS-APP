import { TrainingLesson } from './training-lesson';

export interface Training {
  id: number;
  title: string;
  description: string | null;
  category: string | null;
  is_active: boolean;
  audience_department_id: number | null;
  audience_department_name?: string | null;
  audience_managers_only: boolean;
  audience_new_hires_only: boolean;
  /** 高額・重要な研修向けに、複数段階の承認（部長→役員など）を必須にするか。 */
  requires_multistage_approval: boolean;
  /** 必要な承認段階数（requires_multistage_approvalがtrueの場合のみ意味を持つ）。 */
  approval_stage_count: number | null;
  lessons?: TrainingLesson[];
}
