import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { TrainingFeedback } from '../models/training-feedback';

export interface SubmitTrainingFeedbackPayload {
  satisfaction_score: number;
  understanding_score: number;
  quiz_score?: number | null;
  comment?: string | null;
}

/** 研修効果測定（受講完了後のアンケート・簡易テスト）の提出。 */
@Injectable({ providedIn: 'root' })
export class TrainingFeedbackService {
  private readonly http = inject(HttpClient);
  private readonly apiUrl = environment.apiUrl;

  submit(enrollmentId: number, payload: SubmitTrainingFeedbackPayload): Observable<TrainingFeedback> {
    return this.http.post<TrainingFeedback>(
      `${this.apiUrl}/api/training-enrollments/${enrollmentId}/feedback`,
      payload,
    );
  }
}
