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
});
