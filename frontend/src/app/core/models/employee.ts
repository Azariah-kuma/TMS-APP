import { EmployeeAssignment } from './employee-assignment';

export type EmployeeRole = 'employee' | 'hr';

export interface Employee {
  id: number;
  employee_code: string;
  name: string;
  email: string;
  role: EmployeeRole;
  hired_at: string;
  retired_at: string | null;
  current_assignment: EmployeeAssignment | null;
}
