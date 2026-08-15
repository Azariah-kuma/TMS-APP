export interface EmployeeAssignment {
  id: number;
  department_id: number;
  department_name: string | null;
  position_id: number;
  position_name: string | null;
  manager_id: number | null;
  started_at: string;
  ended_at: string | null;
  is_active: boolean;
}
