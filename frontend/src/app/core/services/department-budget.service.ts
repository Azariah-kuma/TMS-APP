import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { DepartmentBudget } from '../models/department-budget';

/** 部署の年度研修予算（人事のみ管理可能）。 */
@Injectable({ providedIn: 'root' })
export class DepartmentBudgetService {
  private readonly http = inject(HttpClient);
  private readonly apiUrl = environment.apiUrl;

  list(): Observable<DepartmentBudget[]> {
    return this.http.get<DepartmentBudget[]>(`${this.apiUrl}/api/department-budgets`);
  }

  create(payload: { department_id: number; fiscal_year: number; budget_amount: number }): Observable<DepartmentBudget> {
    return this.http.post<DepartmentBudget>(`${this.apiUrl}/api/department-budgets`, payload);
  }

  /** 予算額の訂正（部署・年度は変更できない）。 */
  update(id: number, payload: { budget_amount: number }): Observable<DepartmentBudget> {
    return this.http.patch<DepartmentBudget>(`${this.apiUrl}/api/department-budgets/${id}`, payload);
  }
}
