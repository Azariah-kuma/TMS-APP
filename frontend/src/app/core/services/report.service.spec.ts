import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { provideHttpClient } from '@angular/common/http';
import { TestBed } from '@angular/core/testing';
import { environment } from '../../../environments/environment';
import { ReportService } from './report.service';

describe('ReportService', () => {
  let service: ReportService;
  let httpMock: HttpTestingController;
  const base = environment.apiUrl;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting()],
    });

    service = TestBed.inject(ReportService);
    httpMock = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    httpMock.verify();
  });

  it('summaryはGET /api/reports/training-summaryを呼ぶ', () => {
    service.summary().subscribe();
    const req = httpMock.expectOne(`${base}/api/reports/training-summary`);
    expect(req.request.method).toBe('GET');
    req.flush({ by_training: [], by_department: [] });
  });

  it('csvExportUrlはCSVエクスポート用の絶対URLを返す', () => {
    expect(service.csvExportUrl).toBe(`${base}/api/reports/training-enrollments.csv`);
  });

  it('budgetUsageは年度を指定せずGET /api/reports/budget-usageを呼ぶ', () => {
    service.budgetUsage().subscribe();
    const req = httpMock.expectOne(`${base}/api/reports/budget-usage`);
    expect(req.request.method).toBe('GET');
    req.flush([]);
  });

  it('budgetUsageは年度を指定するとクエリパラメータで送る', () => {
    service.budgetUsage(2026).subscribe();
    const req = httpMock.expectOne(`${base}/api/reports/budget-usage?fiscal_year=2026`);
    expect(req.request.method).toBe('GET');
    req.flush([]);
  });

  it('roiはGET /api/reports/training-roiを呼ぶ', () => {
    service.roi().subscribe();
    const req = httpMock.expectOne(`${base}/api/reports/training-roi`);
    expect(req.request.method).toBe('GET');
    req.flush([]);
  });
});
