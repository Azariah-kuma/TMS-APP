import { ActivatedRoute, convertToParamMap } from '@angular/router';
import { TestBed } from '@angular/core/testing';
import { of, throwError } from 'rxjs';
import { vi } from 'vitest';
import { AuthService } from '../../../core/services/auth.service';
import { TrainingEnrollmentService } from '../../../core/services/training-enrollment.service';
import { TrainingFeedbackService } from '../../../core/services/training-feedback.service';
import { TrainingEnrollment } from '../../../core/models/training-enrollment';
import { TrainingLesson } from '../../../core/models/training-lesson';
import { EnrollmentDetail } from './enrollment-detail';

function makeEnrollment(overrides: Partial<TrainingEnrollment> = {}): TrainingEnrollment {
  return {
    id: 1,
    employee_id: 10,
    training: { id: 1, title: '研修A', lessons: [] } as never,
    status: 'in_progress',
    progress: 40,
    due_at: null,
    started_at: null,
    completed_at: null,
    completed_lesson_ids: [],
    ...overrides,
  };
}

function makeLesson(overrides: Partial<TrainingLesson> = {}): TrainingLesson {
  return { id: 1, training_id: 1, title: '第1章', position: 1, attachments: [], ...overrides };
}

describe('EnrollmentDetail', () => {
  function createComponent(options: {
    serviceMock?: Partial<TrainingEnrollmentService>;
    feedbackServiceMock?: Partial<TrainingFeedbackService>;
    currentEmployeeId?: number;
    isHr?: boolean;
  }) {
    TestBed.configureTestingModule({
      imports: [EnrollmentDetail],
      providers: [
        {
          provide: TrainingEnrollmentService,
          useValue: { get: () => of(makeEnrollment()), ...options.serviceMock },
        },
        {
          provide: TrainingFeedbackService,
          useValue: { ...options.feedbackServiceMock },
        },
        {
          provide: AuthService,
          useValue: {
            isHr: () => options.isHr ?? false,
            currentEmployee: () => ({ id: options.currentEmployeeId ?? 10 }),
          },
        },
        { provide: ActivatedRoute, useValue: { snapshot: { paramMap: convertToParamMap({ id: '1' }) } } },
      ],
    });

    const fixture = TestBed.createComponent(EnrollmentDetail);
    fixture.detectChanges();
    return fixture;
  }

  it('初期化時にルートパラメータのidで受講記録を取得し、進捗の初期値を設定する', () => {
    const getSpy = vi.fn().mockReturnValue(of(makeEnrollment({ progress: 75 })));
    const fixture = createComponent({ serviceMock: { get: getSpy } });

    expect(getSpy).toHaveBeenCalledWith(1);
    expect(fixture.componentInstance.manualProgress()).toBe(75);
    expect(fixture.componentInstance.loading()).toBe(false);
  });

  describe('canEdit', () => {
    it('人事であれば本人でなくてもtrue', () => {
      const fixture = createComponent({
        serviceMock: { get: () => of(makeEnrollment({ employee_id: 99 })) },
        currentEmployeeId: 10,
        isHr: true,
      });
      expect(fixture.componentInstance.canEdit()).toBe(true);
    });

    it('本人であればtrue', () => {
      const fixture = createComponent({
        serviceMock: { get: () => of(makeEnrollment({ employee_id: 10 })) },
        currentEmployeeId: 10,
        isHr: false,
      });
      expect(fixture.componentInstance.canEdit()).toBe(true);
    });

    it('上司など本人・人事以外はfalse', () => {
      const fixture = createComponent({
        serviceMock: { get: () => of(makeEnrollment({ employee_id: 99 })) },
        currentEmployeeId: 10,
        isHr: false,
      });
      expect(fixture.componentInstance.canEdit()).toBe(false);
    });
  });

  describe('hasLessons / hasVideoAttachment / isLessonCompleted', () => {
    it('Lessonが1件も無ければhasLessonsはfalse', () => {
      const fixture = createComponent({
        serviceMock: { get: () => of(makeEnrollment({ training: { id: 1, lessons: [] } as never })) },
      });
      expect(fixture.componentInstance.hasLessons()).toBe(false);
    });

    it('Lessonがあればtrue', () => {
      const fixture = createComponent({
        serviceMock: { get: () => of(makeEnrollment({ training: { id: 1, lessons: [makeLesson()] } as never })) },
      });
      expect(fixture.componentInstance.hasLessons()).toBe(true);
    });

    it('動画添付があればhasVideoAttachmentはtrue', () => {
      const fixture = createComponent({});
      const lesson = makeLesson({
        attachments: [{ id: 1, url: 'x', original_name: 'a.mp4', mime_type: 'video/mp4' }],
      });
      expect(fixture.componentInstance.hasVideoAttachment(lesson)).toBe(true);
    });

    it('動画添付が無ければfalse', () => {
      const fixture = createComponent({});
      const lesson = makeLesson({
        attachments: [{ id: 1, url: 'x', original_name: 'a.pdf', mime_type: 'application/pdf' }],
      });
      expect(fixture.componentInstance.hasVideoAttachment(lesson)).toBe(false);
    });

    it('completed_lesson_idsに含まれていればisLessonCompletedはtrue', () => {
      const fixture = createComponent({
        serviceMock: { get: () => of(makeEnrollment({ completed_lesson_ids: [5] })) },
      });
      expect(fixture.componentInstance.isLessonCompleted(5)).toBe(true);
      expect(fixture.componentInstance.isLessonCompleted(6)).toBe(false);
    });
  });

  describe('toggleLesson', () => {
    it('未完了のLessonはcompleteLessonを呼ぶ', () => {
      const completeLesson = vi.fn().mockReturnValue(of(makeEnrollment()));
      const fixture = createComponent({
        serviceMock: { get: () => of(makeEnrollment({ id: 7, completed_lesson_ids: [] })), completeLesson },
      });

      fixture.componentInstance.toggleLesson(5);

      expect(completeLesson).toHaveBeenCalledWith(7, 5);
      expect(fixture.componentInstance.savingLessonId()).toBeNull();
    });

    it('完了済みのLessonはuncompleteLessonを呼ぶ', () => {
      const uncompleteLesson = vi.fn().mockReturnValue(of(makeEnrollment()));
      const fixture = createComponent({
        serviceMock: { get: () => of(makeEnrollment({ id: 7, completed_lesson_ids: [5] })), uncompleteLesson },
      });

      fixture.componentInstance.toggleLesson(5);

      expect(uncompleteLesson).toHaveBeenCalledWith(7, 5);
    });

    it('失敗するとsavingLessonIdをnullに戻す', () => {
      const completeLesson = vi.fn().mockReturnValue(throwError(() => new Error('failed')));
      const fixture = createComponent({ serviceMock: { completeLesson } });

      fixture.componentInstance.toggleLesson(5);

      expect(fixture.componentInstance.savingLessonId()).toBeNull();
    });
  });

  describe('onVideoEnded', () => {
    it('編集可能かつ未完了なら完了させる', () => {
      const completeLesson = vi.fn().mockReturnValue(of(makeEnrollment()));
      const fixture = createComponent({
        serviceMock: { get: () => of(makeEnrollment({ employee_id: 10, completed_lesson_ids: [] })), completeLesson },
        currentEmployeeId: 10,
      });

      fixture.componentInstance.onVideoEnded(5);

      expect(completeLesson).toHaveBeenCalled();
    });

    it('既に完了済みなら何もしない', () => {
      const completeLesson = vi.fn();
      const fixture = createComponent({
        serviceMock: {
          get: () => of(makeEnrollment({ employee_id: 10, completed_lesson_ids: [5] })),
          completeLesson,
        },
        currentEmployeeId: 10,
      });

      fixture.componentInstance.onVideoEnded(5);

      expect(completeLesson).not.toHaveBeenCalled();
    });

    it('編集不可（上司の参照専用）なら何もしない', () => {
      const completeLesson = vi.fn();
      const fixture = createComponent({
        serviceMock: {
          get: () => of(makeEnrollment({ employee_id: 99, completed_lesson_ids: [] })),
          completeLesson,
        },
        currentEmployeeId: 10,
        isHr: false,
      });

      fixture.componentInstance.onVideoEnded(5);

      expect(completeLesson).not.toHaveBeenCalled();
    });
  });

  describe('submitManualProgress', () => {
    it('現在の受講記録IDとmanualProgressの値で更新する', () => {
      const updateProgress = vi.fn().mockReturnValue(of(makeEnrollment()));
      const fixture = createComponent({
        serviceMock: { get: () => of(makeEnrollment({ id: 7, progress: 30 })), updateProgress },
      });

      fixture.componentInstance.manualProgress.set(80);
      fixture.componentInstance.submitManualProgress();

      expect(updateProgress).toHaveBeenCalledWith(7, 80);
      expect(fixture.componentInstance.savingProgress()).toBe(false);
    });

    it('失敗するとsavingProgressをfalseに戻す', () => {
      const updateProgress = vi.fn().mockReturnValue(throwError(() => new Error('failed')));
      const fixture = createComponent({ serviceMock: { updateProgress } });

      fixture.componentInstance.submitManualProgress();

      expect(fixture.componentInstance.savingProgress()).toBe(false);
    });
  });

  describe('canSubmitFeedback', () => {
    it('受講完了済み・本人・未提出なら提出できる', () => {
      const fixture = createComponent({
        serviceMock: { get: () => of(makeEnrollment({ status: 'completed', employee_id: 10 })) },
        currentEmployeeId: 10,
      });

      expect(fixture.componentInstance.canSubmitFeedback()).toBe(true);
    });

    it('受講完了していなければ提出できない', () => {
      const fixture = createComponent({
        serviceMock: { get: () => of(makeEnrollment({ status: 'in_progress', employee_id: 10 })) },
        currentEmployeeId: 10,
      });

      expect(fixture.componentInstance.canSubmitFeedback()).toBe(false);
    });

    it('本人以外（上司の閲覧など）は提出できない', () => {
      const fixture = createComponent({
        serviceMock: { get: () => of(makeEnrollment({ status: 'completed', employee_id: 10 })) },
        currentEmployeeId: 99,
      });

      expect(fixture.componentInstance.canSubmitFeedback()).toBe(false);
    });

    it('既に提出済みなら提出できない', () => {
      const fixture = createComponent({
        serviceMock: {
          get: () =>
            of(
              makeEnrollment({
                status: 'completed',
                employee_id: 10,
                training_feedback: {
                  id: 1,
                  training_enrollment_id: 1,
                  satisfaction_score: 5,
                  understanding_score: 5,
                  quiz_score: null,
                  comment: null,
                  submitted_at: '2026-09-06T00:00:00Z',
                },
              }),
            ),
        },
        currentEmployeeId: 10,
      });

      expect(fixture.componentInstance.canSubmitFeedback()).toBe(false);
    });
  });

  describe('submitFeedback', () => {
    it('無効なフォームでは何もしない', () => {
      const submit = vi.fn();
      const fixture = createComponent({
        serviceMock: { get: () => of(makeEnrollment({ status: 'completed', employee_id: 10 })) },
        feedbackServiceMock: { submit },
        currentEmployeeId: 10,
      });

      fixture.componentInstance.feedbackForm.patchValue({ satisfaction_score: 6 });
      fixture.componentInstance.submitFeedback();

      expect(submit).not.toHaveBeenCalled();
    });

    it('成功すると受講記録にフィードバックを反映する', () => {
      const feedback = {
        id: 1,
        training_enrollment_id: 1,
        satisfaction_score: 5,
        understanding_score: 4,
        quiz_score: 80,
        comment: '良かった',
        submitted_at: '2026-09-06T00:00:00Z',
      };
      const submit = vi.fn().mockReturnValue(of(feedback));
      const fixture = createComponent({
        serviceMock: { get: () => of(makeEnrollment({ id: 5, status: 'completed', employee_id: 10 })) },
        feedbackServiceMock: { submit },
        currentEmployeeId: 10,
      });

      fixture.componentInstance.submitFeedback();

      expect(submit).toHaveBeenCalledWith(5, fixture.componentInstance.feedbackForm.getRawValue());
      expect(fixture.componentInstance.enrollment()?.training_feedback).toEqual(feedback);
      expect(fixture.componentInstance.feedbackSubmitting()).toBe(false);
    });

    it('失敗するとエラーメッセージを表示する', () => {
      const submit = vi.fn().mockReturnValue(throwError(() => ({ error: { message: '提出に失敗しました。' } })));
      const fixture = createComponent({
        serviceMock: { get: () => of(makeEnrollment({ status: 'completed', employee_id: 10 })) },
        feedbackServiceMock: { submit },
        currentEmployeeId: 10,
      });

      fixture.componentInstance.submitFeedback();

      expect(fixture.componentInstance.feedbackError()).toBe('提出に失敗しました。');
      expect(fixture.componentInstance.feedbackSubmitting()).toBe(false);
    });
  });
});
