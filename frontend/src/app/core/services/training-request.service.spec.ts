import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { provideHttpClient } from '@angular/common/http';
import { TestBed } from '@angular/core/testing';
import { environment } from '../../../environments/environment';
import { TrainingRequestService } from './training-request.service';

describe('TrainingRequestService', () => {
  let service: TrainingRequestService;
  let httpMock: HttpTestingController;
  const base = environment.apiUrl;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting()],
    });

    service = TestBed.inject(TrainingRequestService);
    httpMock = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    httpMock.verify();
  });

  it('listはGET /api/training-requestsを呼ぶ', () => {
    service.list().subscribe();
    const req = httpMock.expectOne(`${base}/api/training-requests`);
    expect(req.request.method).toBe('GET');
    req.flush([]);
  });

  it('createはemployeeId省略時、employee_id:nullで自己申請として送る', () => {
    service.create(1, '理由', '2026-06-01').subscribe();
    const req = httpMock.expectOne(`${base}/api/training-requests`);
    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual({
      employee_id: null,
      training_id: 1,
      reason: '理由',
      due_at: '2026-06-01',
    });
    req.flush({});
  });

  it('createはemployeeId指定時、代理申請としてemployee_idを含めて送る', () => {
    service.create(1, null, null, 9).subscribe();
    const req = httpMock.expectOne(`${base}/api/training-requests`);
    expect(req.request.body).toEqual({
      employee_id: 9,
      training_id: 1,
      reason: null,
      due_at: null,
    });
    req.flush({});
  });

  it('bulkRequestはPOST /api/trainings/:id/bulk-requestにdepartment_idを送る', () => {
    service.bulkRequest(1, 4).subscribe();
    const req = httpMock.expectOne(`${base}/api/trainings/1/bulk-request`);
    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual({ department_id: 4 });
    req.flush({ requested: 0, skipped: 0 });
  });

  it('approveはPOST /api/training-requests/:id/approveを呼ぶ', () => {
    service.approve(1).subscribe();
    const req = httpMock.expectOne(`${base}/api/training-requests/1/approve`);
    expect(req.request.method).toBe('POST');
    req.flush({});
  });

  it('bulkApproveはPOST /api/training-requests/bulk-approveにidsを送る', () => {
    service.bulkApprove([1, 2, 3]).subscribe();
    const req = httpMock.expectOne(`${base}/api/training-requests/bulk-approve`);
    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual({ ids: [1, 2, 3] });
    req.flush({ approved: 0, skipped: 0 });
  });

  it('rejectはPOST /api/training-requests/:id/rejectにcommentを送る', () => {
    service.reject(1, '却下理由').subscribe();
    const req = httpMock.expectOne(`${base}/api/training-requests/1/reject`);
    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual({ comment: '却下理由' });
    req.flush({});
  });

  it('cancelはDELETE /api/training-requests/:idを呼ぶ', () => {
    service.cancel(1).subscribe();
    const req = httpMock.expectOne(`${base}/api/training-requests/1`);
    expect(req.request.method).toBe('DELETE');
    req.flush({});
  });
});
