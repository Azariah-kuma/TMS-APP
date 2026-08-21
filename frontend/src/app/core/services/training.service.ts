import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Training } from '../models/training';
import { TrainingLesson } from '../models/training-lesson';

export interface CreateTrainingPayload {
  title: string;
  description?: string;
  category?: string;
}

export interface CreateTrainingLessonPayload {
  title: string;
  position?: number;
  content?: File | null;
}

@Injectable({ providedIn: 'root' })
export class TrainingService {
  private readonly http = inject(HttpClient);
  private readonly apiUrl = environment.apiUrl;

  list(): Observable<Training[]> {
    return this.http.get<Training[]>(`${this.apiUrl}/api/trainings`);
  }

  get(id: number): Observable<Training> {
    return this.http.get<Training>(`${this.apiUrl}/api/trainings/${id}`);
  }

  /** 人事のみ：研修カタログへの新規登録。 */
  create(payload: CreateTrainingPayload): Observable<Training> {
    return this.http.post<Training>(`${this.apiUrl}/api/trainings`, payload);
  }

  /** 人事のみ：研修にLesson（教材）を追加する。動画等のファイルを添付する場合はmultipart/form-dataで送る。 */
  addLesson(trainingId: number, payload: CreateTrainingLessonPayload): Observable<TrainingLesson> {
    const { content, ...rest } = payload;

    if (!content) {
      return this.http.post<TrainingLesson>(`${this.apiUrl}/api/trainings/${trainingId}/lessons`, rest);
    }

    const formData = new FormData();
    formData.append('title', rest.title);
    if (rest.position !== undefined) {
      formData.append('position', String(rest.position));
    }
    formData.append('content', content);

    return this.http.post<TrainingLesson>(`${this.apiUrl}/api/trainings/${trainingId}/lessons`, formData);
  }
}
