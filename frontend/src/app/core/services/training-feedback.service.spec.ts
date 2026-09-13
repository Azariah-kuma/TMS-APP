import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { provideHttpClient } from '@angular/common/http';
import { TestBed } from '@angular/core/testing';
import { environment } from '../../../environments/environment';
import { TrainingFeedbackService } from './training-feedback.service';

describe('TrainingFeedbackService', () => {
  let service: TrainingFeedbackService;
  let httpMock: HttpTestingController;
  const base = environment.apiUrl;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting()],
    });

    service = TestBed.inject(TrainingFeedbackService);
    httpMock = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    httpMock.verify();
  });

  it('submitはPOST /api/training-enrollments/:id/feedbackに入力内容を送る', () => {
    const payload = { satisfaction_score: 5, understanding_score: 4, quiz_score: 80, comment: '良かった' };
    service.submit(10, payload).subscribe();

    const req = httpMock.expectOne(`${base}/api/training-enrollments/10/feedback`);
    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual(payload);
    req.flush({ id: 1, training_enrollment_id: 10, ...payload, submitted_at: '2026-09-06T00:00:00Z' });
  });
});
