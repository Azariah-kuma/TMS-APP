import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { BudgetUsageRow } from '../models/department-budget';
import { TrainingSummaryReport } from '../models/report';
import { TrainingRoiRow } from '../models/training-roi';

@Injectable({ providedIn: 'root' })
export class ReportService {
  private readonly http = inject(HttpClient);
  private readonly apiUrl = environment.apiUrl;

  /** 人事のみ：研修別・部署別の受講状況サマリー。 */
  summary(): Observable<TrainingSummaryReport> {
    return this.http.get<TrainingSummaryReport>(`${this.apiUrl}/api/reports/training-summary`);
  }

  /** 人事のみ：部署別の年度研修予算・消費額（fiscalYearを省略すると今年度）。 */
  budgetUsage(fiscalYear?: number): Observable<BudgetUsageRow[]> {
    const params = fiscalYear ? new HttpParams().set('fiscal_year', fiscalYear) : undefined;

    return this.http.get<BudgetUsageRow[]>(`${this.apiUrl}/api/reports/budget-usage`, { params });
  }

  /** 人事のみ：研修別の効果測定平均スコアと簡易ROI指標。 */
  roi(): Observable<TrainingRoiRow[]> {
    return this.http.get<TrainingRoiRow[]>(`${this.apiUrl}/api/reports/training-roi`);
  }

  /** 人事のみ：受講記録CSVエクスポートのURL（Sanctumのcookie認証がそのまま効くため素のリンクでよい）。 */
  get csvExportUrl(): string {
    return `${this.apiUrl}/api/reports/training-enrollments.csv`;
  }
}
