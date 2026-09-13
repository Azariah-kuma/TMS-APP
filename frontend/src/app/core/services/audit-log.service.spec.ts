import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { provideHttpClient } from '@angular/common/http';
import { TestBed } from '@angular/core/testing';
import { environment } from '../../../environments/environment';
import { AuditLogService } from './audit-log.service';

describe('AuditLogService', () => {
  let service: AuditLogService;
  let httpMock: HttpTestingController;
  const base = environment.apiUrl;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting()],
    });

    service = TestBed.inject(AuditLogService);
    httpMock = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    httpMock.verify();
  });

  it('フィルタ無指定ならGET /api/audit-logsをそのまま呼ぶ', () => {
    service.list().subscribe();
    const req = httpMock.expectOne(`${base}/api/audit-logs`);
    expect(req.request.method).toBe('GET');
    req.flush({ data: [], current_page: 1, last_page: 1, total: 0 });
  });

  it('auditableType・auditableIdを指定するとクエリパラメータとして送る', () => {
    service.list({ auditableType: 'Department', auditableId: 3 }).subscribe();

    const req = httpMock.expectOne(`${base}/api/audit-logs?auditable_type=Department&auditable_id=3`);
    expect(req.request.method).toBe('GET');
    req.flush({ data: [], current_page: 1, last_page: 1, total: 0 });
  });
});
