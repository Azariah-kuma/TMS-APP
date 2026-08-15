import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Delegation } from '../models/delegation';
import { Employee, EmployeeRole } from '../models/employee';
import { EmployeeAssignment } from '../models/employee-assignment';

export interface OnboardEmployeePayload {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  employee_code: string;
  role: EmployeeRole;
  hired_at: string;
  department_id: number;
  position_id: number;
  manager_id: number | null;
}

export interface TransferEmployeePayload {
  department_id: number;
  position_id: number;
  manager_id: number | null;
  started_at: string;
}

export interface CreateDelegationPayload {
  delegate_id: number;
  started_at: string;
  ended_at: string | null;
}

@Injectable({ providedIn: 'root' })
export class EmployeeService {
  private readonly http = inject(HttpClient);
  private readonly apiUrl = environment.apiUrl;

  /** 人事のみ利用可能：全従業員の一覧。 */
  list(): Observable<Employee[]> {
    return this.http.get<Employee[]>(`${this.apiUrl}/api/employees`);
  }

  get(id: number): Observable<Employee> {
    return this.http.get<Employee>(`${this.apiUrl}/api/employees/${id}`);
  }

  /** 人事のみ：ログインアカウントと従業員レコードをまとめて作成する。 */
  onboard(payload: OnboardEmployeePayload): Observable<Employee> {
    return this.http.post<Employee>(`${this.apiUrl}/api/employees`, payload);
  }

  assignments(employeeId: number): Observable<EmployeeAssignment[]> {
    return this.http.get<EmployeeAssignment[]>(`${this.apiUrl}/api/employees/${employeeId}/assignments`);
  }

  /** 人事のみ：異動（部署・役職・上司の変更）を登録する。 */
  transfer(employeeId: number, payload: TransferEmployeePayload): Observable<EmployeeAssignment> {
    return this.http.post<EmployeeAssignment>(
      `${this.apiUrl}/api/employees/${employeeId}/assignments`,
      payload,
    );
  }

  delegationsGiven(employeeId: number): Observable<Delegation[]> {
    return this.http.get<Delegation[]>(`${this.apiUrl}/api/employees/${employeeId}/delegations`);
  }

  /** 人事のみ：上司権限を期間限定で他の従業員に委任する。 */
  createDelegation(employeeId: number, payload: CreateDelegationPayload): Observable<Delegation> {
    return this.http.post<Delegation>(`${this.apiUrl}/api/employees/${employeeId}/delegations`, payload);
  }

  /** 人事のみ：委任を即時取り消す。 */
  revokeDelegation(delegationId: number): Observable<Delegation> {
    return this.http.delete<Delegation>(`${this.apiUrl}/api/delegations/${delegationId}`);
  }
}
