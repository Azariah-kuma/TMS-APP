export interface Delegation {
  id: number;
  delegator_id: number;
  delegate_id: number;
  delegate_name: string | null;
  started_at: string;
  ended_at: string | null;
  is_active: boolean;
}
