import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { provideHttpClient } from '@angular/common/http';
import { TestBed } from '@angular/core/testing';
import { environment } from '../../../environments/environment';
import { DepartmentBudgetService } from './department-budget.service';

describe('DepartmentBudgetService', () => {
  let service: DepartmentBudgetService;
  let httpMock: HttpTestingController;
  const base = environment.apiUrl;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting()],
    });

    service = TestBed.inject(DepartmentBudgetService);
    httpMock = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    httpMock.verify();
  });

  it('listはGET /api/department-budgetsを呼ぶ', () => {
    service.list().subscribe();
    const req = httpMock.expectOne(`${base}/api/department-budgets`);
    expect(req.request.method).toBe('GET');
    req.flush([]);
  });

  it('createはPOST /api/department-budgetsに入力内容を送る', () => {
    const payload = { department_id: 1, fiscal_year: 2026, budget_amount: 500000 };
    service.create(payload).subscribe();

    const req = httpMock.expectOne(`${base}/api/department-budgets`);
    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual(payload);
    req.flush({ id: 1, ...payload });
  });

  it('updateはPATCH /api/department-budgets/:idに予算額を送る', () => {
    service.update(1, { budget_amount: 600000 }).subscribe();

    const req = httpMock.expectOne(`${base}/api/department-budgets/1`);
    expect(req.request.method).toBe('PATCH');
    expect(req.request.body).toEqual({ budget_amount: 600000 });
    req.flush({ id: 1, department_id: 1, fiscal_year: 2026, budget_amount: 600000 });
  });
});
