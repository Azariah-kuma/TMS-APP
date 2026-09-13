import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { AuditLogPage } from '../models/audit-log';

/** 人事・研修管理の主要モデルへの変更操作の監査ログ（人事のみ閲覧可能）。 */
@Injectable({ providedIn: 'root' })
export class AuditLogService {
  private readonly http = inject(HttpClient);
  private readonly apiUrl = environment.apiUrl;

  list(filter: { auditableType?: string; auditableId?: number } = {}): Observable<AuditLogPage> {
    let params = new HttpParams();

    if (filter.auditableType) {
      params = params.set('auditable_type', filter.auditableType);
    }
    if (filter.auditableId) {
      params = params.set('auditable_id', filter.auditableId);
    }

    return this.http.get<AuditLogPage>(`${this.apiUrl}/api/audit-logs`, { params });
  }
}
