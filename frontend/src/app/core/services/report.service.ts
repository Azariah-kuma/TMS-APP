import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { TrainingSummaryReport } from '../models/report';

@Injectable({ providedIn: 'root' })
export class ReportService {
  private readonly http = inject(HttpClient);
  private readonly apiUrl = environment.apiUrl;

  /** 人事のみ：研修別・部署別の受講状況サマリー。 */
  summary(): Observable<TrainingSummaryReport> {
    return this.http.get<TrainingSummaryReport>(`${this.apiUrl}/api/reports/training-summary`);
  }

  /** 人事のみ：受講記録CSVエクスポートのURL（Sanctumのcookie認証がそのまま効くため素のリンクでよい）。 */
  get csvExportUrl(): string {
    return `${this.apiUrl}/api/reports/training-enrollments.csv`;
  }
}
