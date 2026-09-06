import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { provideHttpClient } from '@angular/common/http';
import { TestBed } from '@angular/core/testing';
import { environment } from '../../../environments/environment';
import { MasterDataService } from './master-data.service';

describe('MasterDataService', () => {
  let service: MasterDataService;
  let httpMock: HttpTestingController;
  const base = environment.apiUrl;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting()],
    });

    service = TestBed.inject(MasterDataService);
    httpMock = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    httpMock.verify();
  });

  it('departmentsはGET /api/departmentsを呼ぶ', () => {
    service.departments().subscribe();
    const req = httpMock.expectOne(`${base}/api/departments`);
    expect(req.request.method).toBe('GET');
    req.flush([]);
  });

  it('createDepartmentはPOST /api/departmentsにペイロードを送る', () => {
    const payload = { name: '開発部', code: 'DEPT-DEV' };
    service.createDepartment(payload).subscribe();
    const req = httpMock.expectOne(`${base}/api/departments`);
    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual(payload);
    req.flush({});
  });

  it('updateDepartmentはPATCH /api/departments/:idにペイロードを送る', () => {
    const payload = { name: '開発部', code: 'DEPT-DEV' };
    service.updateDepartment(3, payload).subscribe();
    const req = httpMock.expectOne(`${base}/api/departments/3`);
    expect(req.request.method).toBe('PATCH');
    expect(req.request.body).toEqual(payload);
    req.flush({});
  });

  it('deleteDepartmentはDELETE /api/departments/:idを呼ぶ', () => {
    service.deleteDepartment(3).subscribe();
    const req = httpMock.expectOne(`${base}/api/departments/3`);
    expect(req.request.method).toBe('DELETE');
    req.flush(null);
  });

  it('positionsはGET /api/positionsを呼ぶ', () => {
    service.positions().subscribe();
    const req = httpMock.expectOne(`${base}/api/positions`);
    expect(req.request.method).toBe('GET');
    req.flush([]);
  });

  it('createPositionはPOST /api/positionsにペイロード（after_position_id含む）を送る', () => {
    const payload = { name: '班長', code: 'POS-TL', after_position_id: 5 };
    service.createPosition(payload).subscribe();
    const req = httpMock.expectOne(`${base}/api/positions`);
    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual(payload);
    req.flush({});
  });

  it('updatePositionはPATCH /api/positions/:idにペイロードを送る', () => {
    const payload = { name: '班長', code: 'POS-TL' };
    service.updatePosition(5, payload).subscribe();
    const req = httpMock.expectOne(`${base}/api/positions/5`);
    expect(req.request.method).toBe('PATCH');
    expect(req.request.body).toEqual(payload);
    req.flush({});
  });

  it('deletePositionはDELETE /api/positions/:idを呼ぶ', () => {
    service.deletePosition(5).subscribe();
    const req = httpMock.expectOne(`${base}/api/positions/5`);
    expect(req.request.method).toBe('DELETE');
    req.flush(null);
  });
});
