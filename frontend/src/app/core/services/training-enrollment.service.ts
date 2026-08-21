import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { TrainingEnrollment } from '../models/training-enrollment';

@Injectable({ providedIn: 'root' })
export class TrainingEnrollmentService {
  private readonly http = inject(HttpClient);
  private readonly apiUrl = environment.apiUrl;

  /** ロールに応じて閲覧可能な受講記録一覧（自分／部下／全件）。 */
  list(): Observable<TrainingEnrollment[]> {
    return this.http.get<TrainingEnrollment[]>(`${this.apiUrl}/api/training-enrollments`);
  }

  get(id: number): Observable<TrainingEnrollment> {
    return this.http.get<TrainingEnrollment>(`${this.apiUrl}/api/training-enrollments/${id}`);
  }

  /** 人事のみ：従業員を研修に割り当てる。 */
  enroll(employeeId: number, trainingId: number, dueAt: string | null): Observable<TrainingEnrollment> {
    return this.http.post<TrainingEnrollment>(`${this.apiUrl}/api/employees/${employeeId}/training-enrollments`, {
      training_id: trainingId,
      due_at: dueAt,
    });
  }

  /** 人事のみ：部署単位、またはdepartmentIdを省略して全社一括で研修を割り当てる。 */
  bulkEnroll(
    trainingId: number,
    departmentId: number | null,
    dueAt: string | null,
  ): Observable<{ enrolled: number; skipped: number }> {
    return this.http.post<{ enrolled: number; skipped: number }>(
      `${this.apiUrl}/api/trainings/${trainingId}/bulk-enroll`,
      { department_id: departmentId, due_at: dueAt },
    );
  }

  /** Lessonが定義されていない研修のみ利用可（本人／人事）。 */
  updateProgress(id: number, progress: number): Observable<TrainingEnrollment> {
    return this.http.patch<TrainingEnrollment>(`${this.apiUrl}/api/training-enrollments/${id}`, { progress });
  }

  /** Lessonを完了にする（本人／人事）。 */
  completeLesson(enrollmentId: number, lessonId: number): Observable<TrainingEnrollment> {
    return this.http.put<TrainingEnrollment>(
      `${this.apiUrl}/api/training-enrollments/${enrollmentId}/lessons/${lessonId}`,
      {},
    );
  }

  /** Lessonのチェックを外す（本人／人事）。 */
  uncompleteLesson(enrollmentId: number, lessonId: number): Observable<TrainingEnrollment> {
    return this.http.delete<TrainingEnrollment>(
      `${this.apiUrl}/api/training-enrollments/${enrollmentId}/lessons/${lessonId}`,
    );
  }
}
