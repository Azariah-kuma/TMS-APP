import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { provideHttpClient } from '@angular/common/http';
import { TestBed } from '@angular/core/testing';
import { environment } from '../../../environments/environment';
import { TrainingService } from './training.service';

describe('TrainingService', () => {
  let service: TrainingService;
  let httpMock: HttpTestingController;
  const base = environment.apiUrl;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting()],
    });

    service = TestBed.inject(TrainingService);
    httpMock = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    httpMock.verify();
  });

  it('listはGET /api/trainingsを呼ぶ', () => {
    service.list().subscribe();
    const req = httpMock.expectOne(`${base}/api/trainings`);
    expect(req.request.method).toBe('GET');
    req.flush([]);
  });

  it('getはGET /api/trainings/:idを呼ぶ', () => {
    service.get(1).subscribe();
    const req = httpMock.expectOne(`${base}/api/trainings/1`);
    expect(req.request.method).toBe('GET');
    req.flush({});
  });

  it('createはPOST /api/trainingsに対象者・多段階承認の設定を含めて送る', () => {
    const payload = {
      title: '高額な海外研修',
      requires_multistage_approval: true,
      approval_stage_count: 2,
    };

    service.create(payload).subscribe();
    const req = httpMock.expectOne(`${base}/api/trainings`);
    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual(payload);
    req.flush({});
  });

  it('addLessonはファイル添付が無ければJSONでPOSTする', () => {
    service.addLesson(1, { title: '第1章', position: 1 }).subscribe();

    const req = httpMock.expectOne(`${base}/api/trainings/1/lessons`);
    expect(req.request.method).toBe('POST');
    expect(req.request.body instanceof FormData).toBe(false);
    expect(req.request.body).toEqual({ title: '第1章', position: 1 });
    req.flush({});
  });

  it('addLessonは空配列のcontentsが渡されてもJSONでPOSTする', () => {
    service.addLesson(1, { title: '第1章', contents: [] }).subscribe();

    const req = httpMock.expectOne(`${base}/api/trainings/1/lessons`);
    expect(req.request.body instanceof FormData).toBe(false);
    req.flush({});
  });

  it('addLessonはファイル添付があればmultipart/form-dataで複数ファイルをcontents[]として送る', () => {
    const video = new File(['dummy'], 'lesson.mp4', { type: 'video/mp4' });
    const pdf = new File(['dummy'], 'slide.pdf', { type: 'application/pdf' });

    service.addLesson(1, { title: '第1章', contents: [video, pdf] }).subscribe();

    const req = httpMock.expectOne(`${base}/api/trainings/1/lessons`);
    expect(req.request.method).toBe('POST');
    const body = req.request.body as FormData;
    expect(body instanceof FormData).toBe(true);
    expect(body.get('title')).toBe('第1章');
    expect(body.getAll('contents[]')).toEqual([video, pdf]);
    req.flush({});
  });

  it('addLessonはpositionが指定されていればFormDataにも含める', () => {
    const video = new File(['dummy'], 'lesson.mp4', { type: 'video/mp4' });

    service.addLesson(1, { title: '第1章', position: 2, contents: [video] }).subscribe();

    const req = httpMock.expectOne(`${base}/api/trainings/1/lessons`);
    const body = req.request.body as FormData;
    expect(body.get('position')).toBe('2');
    req.flush({});
  });

  it('deleteLessonはDELETE /api/trainings/:id/lessons/:idを呼ぶ', () => {
    service.deleteLesson(1, 2).subscribe();
    const req = httpMock.expectOne(`${base}/api/trainings/1/lessons/2`);
    expect(req.request.method).toBe('DELETE');
    req.flush(null);
  });
});
