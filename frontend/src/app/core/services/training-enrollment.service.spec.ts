import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { provideHttpClient } from '@angular/common/http';
import { TestBed } from '@angular/core/testing';
import { environment } from '../../../environments/environment';
import { TrainingEnrollmentService } from './training-enrollment.service';

describe('TrainingEnrollmentService', () => {
  let service: TrainingEnrollmentService;
  let httpMock: HttpTestingController;
  const base = environment.apiUrl;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting()],
    });

    service = TestBed.inject(TrainingEnrollmentService);
    httpMock = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    httpMock.verify();
  });

  it('listはGET /api/training-enrollmentsを呼ぶ', () => {
    service.list().subscribe();
    const req = httpMock.expectOne(`${base}/api/training-enrollments`);
    expect(req.request.method).toBe('GET');
    req.flush([]);
  });

  it('getはGET /api/training-enrollments/:idを呼ぶ', () => {
    service.get(1).subscribe();
    const req = httpMock.expectOne(`${base}/api/training-enrollments/1`);
    expect(req.request.method).toBe('GET');
    req.flush({});
  });

  it('enrollはPOST /api/employees/:id/training-enrollmentsにtraining_id/due_atを送る', () => {
    service.enroll(2, 3, '2026-06-01').subscribe();
    const req = httpMock.expectOne(`${base}/api/employees/2/training-enrollments`);
    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual({ training_id: 3, due_at: '2026-06-01' });
    req.flush({});
  });

  it('bulkEnrollはPOST /api/trainings/:id/bulk-enrollにdepartment_id/due_atを送る（全社一括はnull）', () => {
    service.bulkEnroll(3, null, null).subscribe();
    const req = httpMock.expectOne(`${base}/api/trainings/3/bulk-enroll`);
    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual({ department_id: null, due_at: null });
    req.flush({ enrolled: 0, skipped: 0 });
  });

  it('updateProgressはPATCH /api/training-enrollments/:idにprogressを送る', () => {
    service.updateProgress(1, 50).subscribe();
    const req = httpMock.expectOne(`${base}/api/training-enrollments/1`);
    expect(req.request.method).toBe('PATCH');
    expect(req.request.body).toEqual({ progress: 50 });
    req.flush({});
  });

  it('completeLessonはPUT /api/training-enrollments/:id/lessons/:idを呼ぶ', () => {
    service.completeLesson(1, 2).subscribe();
    const req = httpMock.expectOne(`${base}/api/training-enrollments/1/lessons/2`);
    expect(req.request.method).toBe('PUT');
    req.flush({});
  });

  it('uncompleteLessonはDELETE /api/training-enrollments/:id/lessons/:idを呼ぶ', () => {
    service.uncompleteLesson(1, 2).subscribe();
    const req = httpMock.expectOne(`${base}/api/training-enrollments/1/lessons/2`);
    expect(req.request.method).toBe('DELETE');
    req.flush({});
  });
});
