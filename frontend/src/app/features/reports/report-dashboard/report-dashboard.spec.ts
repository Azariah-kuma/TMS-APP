import { TestBed } from '@angular/core/testing';
import { of } from 'rxjs';
import { ReportService } from '../../../core/services/report.service';
import { ReportDashboard } from './report-dashboard';

describe('ReportDashboard', () => {
  function createComponent(report: unknown, roi: unknown[] = []) {
    TestBed.configureTestingModule({
      imports: [ReportDashboard],
      providers: [
        {
          provide: ReportService,
          useValue: {
            summary: () => of(report),
            roi: () => of(roi),
            csvExportUrl: 'https://api.example.com/export.csv',
          },
        },
      ],
    });

    const fixture = TestBed.createComponent(ReportDashboard);
    fixture.detectChanges();
    return fixture;
  }

  it('初期化時にROIレポートを読み込む', () => {
    const roi = [
      {
        training_id: 1,
        title: '研修A',
        unit_cost: 10000,
        feedback_count: 2,
        avg_satisfaction_score: 4,
        avg_understanding_score: 4,
        avg_quiz_score: 80,
        effectiveness_score: 80,
        roi_index: 0.008,
      },
    ];
    const fixture = createComponent({ by_training: [], by_department: [] }, roi);

    expect(fixture.componentInstance.roi()).toEqual(roi);
    expect(fixture.componentInstance.roiLoading()).toBe(false);
  });

  it('初期化時にサマリーを読み込む', () => {
    const report = { by_training: [], by_department: [] };
    const fixture = createComponent(report);

    expect(fixture.componentInstance.report()).toEqual(report);
    expect(fixture.componentInstance.loading()).toBe(false);
  });

  it('csvExportUrlはReportServiceの値をそのまま公開する', () => {
    const fixture = createComponent({ by_training: [], by_department: [] });
    expect(fixture.componentInstance.csvExportUrl).toBe('https://api.example.com/export.csv');
  });

  it('toRowsはname/内訳をBarChartRow形式（label/内訳）に変換する', () => {
    const fixture = createComponent({ by_training: [], by_department: [] });

    const rows = fixture.componentInstance.toRows([
      { name: '研修A', not_started: 1, in_progress: 2, completed: 3 },
    ]);

    expect(rows).toEqual([{ label: '研修A', not_started: 1, in_progress: 2, completed: 3 }]);
  });
});
