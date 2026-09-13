export type AuditLogAction = 'created' | 'updated' | 'deleted';

export interface AuditLog {
  id: number;
  auditable_type: string;
  auditable_id: number;
  action: AuditLogAction;
  actor_employee_id: number | null;
  actor_name: string | null;
  changes: Record<string, unknown> | null;
  created_at: string;
}

export interface AuditLogPage {
  data: AuditLog[];
  current_page: number;
  last_page: number;
  total: number;
}
