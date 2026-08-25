import { Component, OnInit, inject, signal } from '@angular/core';
import { ReportService } from '../../../core/services/report.service';
import { TrainingSummaryReport } from '../../../core/models/report';
import { BarChart, BarChartRow } from '../../../shared/bar-chart/bar-chart';

@Component({
  selector: 'app-report-dashboard',
  imports: [BarChart],
  templateUrl: './report-dashboard.html',
})
export class ReportDashboard implements OnInit {
  private readonly service = inject(ReportService);

  readonly loading = signal(true);
  readonly report = signal<TrainingSummaryReport | null>(null);
  readonly csvExportUrl = this.service.csvExportUrl;

  ngOnInit(): void {
    this.service.summary().subscribe((report) => {
      this.report.set(report);
      this.loading.set(false);
    });
  }

  toRows(rows: { name: string; not_started: number; in_progress: number; completed: number }[]): BarChartRow[] {
    return rows.map((r) => ({
      label: r.name,
      not_started: r.not_started,
      in_progress: r.in_progress,
      completed: r.completed,
    }));
  }
}
