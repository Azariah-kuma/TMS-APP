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

  createPosition(payload: { name: string; code: string; rank: number }): Observable<Position> {
    return this.http.post<Position>(`${this.apiUrl}/api/positions`, payload);
  }
}
