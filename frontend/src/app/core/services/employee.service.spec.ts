import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { provideHttpClient } from '@angular/common/http';
import { TestBed } from '@angular/core/testing';
import { environment } from '../../../environments/environment';
import { EmployeeService } from './employee.service';

describe('EmployeeService', () => {
  let service: EmployeeService;
  let httpMock: HttpTestingController;
  const base = environment.apiUrl;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting()],
    });

    service = TestBed.inject(EmployeeService);
    httpMock = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    httpMock.verify();
  });

  it('listはGET /api/employeesを呼ぶ', () => {
    service.list().subscribe();
    const req = httpMock.expectOne(`${base}/api/employees`);
    expect(req.request.method).toBe('GET');
    req.flush([]);
  });

  it('getはGET /api/employees/:idを呼ぶ', () => {
    service.get(5).subscribe();
    const req = httpMock.expectOne(`${base}/api/employees/5`);
    expect(req.request.method).toBe('GET');
    req.flush({});
  });

  it('subordinatesはGET /api/employees/subordinatesを呼ぶ', () => {
    service.subordinates().subscribe();
    const req = httpMock.expectOne(`${base}/api/employees/subordinates`);
    expect(req.request.method).toBe('GET');
    req.flush([]);
  });

  it('onboardはPOST /api/employeesにペイロードをそのまま送る', () => {
    const payload = {
      last_name: '山田',
      first_name: '太郎',
      last_name_kana: 'ヤマダ',
      first_name_kana: 'タロウ',
      email: 'yamada@example.com',
      employee_code: 'EMP-001',
      role: 'employee' as const,
      hired_at: '2026-04-01',
      department_id: 1,
      position_id: 2,
      manager_id: null,
    };

    service.onboard(payload).subscribe();
    const req = httpMock.expectOne(`${base}/api/employees`);
    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual(payload);
    req.flush({});
  });

  it('bulkImportはPOST /api/employees/bulk-importにFormDataでファイルを送る', () => {
    const file = new File(['a,b'], 'employees.csv', { type: 'text/csv' });

    service.bulkImport(file).subscribe();
    const req = httpMock.expectOne(`${base}/api/employees/bulk-import`);
    expect(req.request.method).toBe('POST');
    expect(req.request.body instanceof FormData).toBe(true);
    expect((req.request.body as FormData).get('file')).toBe(file);
    req.flush({ created: [], errors: [] });
  });

  it('assignmentsはGET /api/employees/:id/assignmentsを呼ぶ', () => {
    service.assignments(3).subscribe();
    const req = httpMock.expectOne(`${base}/api/employees/3/assignments`);
    expect(req.request.method).toBe('GET');
    req.flush([]);
  });

  it('transferはPOST /api/employees/:id/assignmentsにペイロードを送る', () => {
    const payload = { department_id: 1, position_id: 2, manager_id: 3, started_at: '2026-04-01' };

    service.transfer(7, payload).subscribe();
    const req = httpMock.expectOne(`${base}/api/employees/7/assignments`);
    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual(payload);
    req.flush({});
  });

  it('delegationsGivenはGET /api/employees/:id/delegationsを呼ぶ', () => {
    service.delegationsGiven(4).subscribe();
    const req = httpMock.expectOne(`${base}/api/employees/4/delegations`);
    expect(req.request.method).toBe('GET');
    req.flush([]);
  });

  it('createDelegationはPOST /api/employees/:id/delegationsにペイロードを送る', () => {
    const payload = { delegate_id: 9, started_at: '2026-04-01', ended_at: null };

    service.createDelegation(4, payload).subscribe();
    const req = httpMock.expectOne(`${base}/api/employees/4/delegations`);
    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual(payload);
    req.flush({});
  });

  it('revokeDelegationはDELETE /api/delegations/:idを呼ぶ', () => {
    service.revokeDelegation(10).subscribe();
    const req = httpMock.expectOne(`${base}/api/delegations/10`);
    expect(req.request.method).toBe('DELETE');
    req.flush({});
  });

  it('resendInviteはPOST /api/employees/:id/resend-inviteを呼ぶ', () => {
    service.resendInvite(6).subscribe();
    const req = httpMock.expectOne(`${base}/api/employees/6/resend-invite`);
    expect(req.request.method).toBe('POST');
    req.flush({ message: 'ok' });
  });
});
