import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Department } from '../models/department';
import { Position } from '../models/position';

/** 部署・役職マスタ（異動フォームの選択肢などに使う）。 */
@Injectable({ providedIn: 'root' })
export class MasterDataService {
  private readonly http = inject(HttpClient);
  private readonly apiUrl = environment.apiUrl;

  departments(): Observable<Department[]> {
    return this.http.get<Department[]>(`${this.apiUrl}/api/departments`);
  }

  createDepartment(payload: { name: string; code: string }): Observable<Department> {
    return this.http.post<Department>(`${this.apiUrl}/api/departments`, payload);
  }

  positions(): Observable<Position[]> {
    return this.http.get<Position[]>(`${this.apiUrl}/api/positions`);
  }

  /** after_position_id を省略（null）すると最上位に挿入される。指定した役職の直後に挿入し、以降の序列は自動的に繰り下がる。 */
  createPosition(payload: { name: string; code: string; after_position_id: number | null }): Observable<Position> {
    return this.http.post<Position>(`${this.apiUrl}/api/positions`, payload);
  }

  /** 誤登録した役職名・役職コードの訂正。序列(rank)は変更しない。 */
  updatePosition(id: number, payload: { name: string; code: string }): Observable<Position> {
    return this.http.patch<Position>(`${this.apiUrl}/api/positions/${id}`, payload);
  }

  /** 誤登録した役職の削除。配属履歴で使われている役職は削除できない（サーバー側でエラーになる）。 */
  deletePosition(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/api/positions/${id}`);
  }
}
