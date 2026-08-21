import { EmployeeAssignment } from './employee-assignment';

export type EmployeeRole = 'employee' | 'hr';

export interface Employee {
  id: number;
  employee_code: string;
  name: string;
  name_kana: string;
  last_name: string;
  first_name: string;
  last_name_kana: string;
  first_name_kana: string;
  email: string;
  role: EmployeeRole;
  hired_at: string;
  retired_at: string | null;
  /** 現在、直属の部下を持っているか（＝組織上の権限を実際に行使しているか）。 */
  is_manager: boolean;
  current_assignment: EmployeeAssignment | null;
}
