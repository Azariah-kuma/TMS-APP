import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { TrainingRequest } from '../models/training-request';

@Injectable({ providedIn: 'root' })
export class TrainingRequestService {
  private readonly http = inject(HttpClient);
  private readonly apiUrl = environment.apiUrl;

  /** ロールに応じて閲覧可能な申請一覧（自分／部下／全件）。 */
  list(): Observable<TrainingRequest[]> {
    return this.http.get<TrainingRequest[]>(`${this.apiUrl}/api/training-requests`);
  }

  /**
   * 研修受講を申請する。employeeId を省略すると自分自身の申請になり、
   * 指定した場合（部下の代理申請）はその従業員の分として申請される。
   */
  create(
    trainingId: number,
    reason: string | null,
    dueAt: string | null,
    employeeId: number | null = null,
  ): Observable<TrainingRequest> {
    return this.http.post<TrainingRequest>(`${this.apiUrl}/api/training-requests`, {
      employee_id: employeeId,
      training_id: trainingId,
      reason,
      due_at: dueAt,
    });
  }

  /** 上司が自部署の部下（人事の場合は部署内全員）をまとめて研修申請する。 */
  bulkRequest(trainingId: number, departmentId: number): Observable<{ requested: number; skipped: number }> {
    return this.http.post<{ requested: number; skipped: number }>(
      `${this.apiUrl}/api/trainings/${trainingId}/bulk-request`,
      { department_id: departmentId },
    );
  }

  /** 申請者の上司または人事のみ：申請を承認し、受講登録する。 */
  approve(id: number): Observable<TrainingRequest> {
    return this.http.post<TrainingRequest>(`${this.apiUrl}/api/training-requests/${id}/approve`, {});
  }

  /** 複数の申請をまとめて承認する。承認権限のないIDは黙ってスキップされる。 */
  bulkApprove(ids: number[]): Observable<{ approved: number; skipped: number }> {
    return this.http.post<{ approved: number; skipped: number }>(
      `${this.apiUrl}/api/training-requests/bulk-approve`,
      { ids },
    );
  }

  /** 申請者の上司または人事のみ：申請を却下する。 */
  reject(id: number, comment: string | null): Observable<TrainingRequest> {
    return this.http.post<TrainingRequest>(`${this.apiUrl}/api/training-requests/${id}/reject`, { comment });
  }

  /** 申請者本人のみ：承認待ちの申請を取り消す。 */
  cancel(id: number): Observable<TrainingRequest> {
    return this.http.delete<TrainingRequest>(`${this.apiUrl}/api/training-requests/${id}`);
  }
}
