import { ActivatedRoute, convertToParamMap, provideRouter } from '@angular/router';
import { TestBed } from '@angular/core/testing';
import { of, throwError } from 'rxjs';
import { vi } from 'vitest';
import { AuthService } from '../../../core/services/auth.service';
import { EmployeeService } from '../../../core/services/employee.service';
import { MasterDataService } from '../../../core/services/master-data.service';
import { TrainingEnrollmentService } from '../../../core/services/training-enrollment.service';
import { TrainingRequestService } from '../../../core/services/training-request.service';
import { TrainingService } from '../../../core/services/training.service';
import { Training } from '../../../core/models/training';
import { TrainingEnrollment } from '../../../core/models/training-enrollment';
import { TrainingRequest } from '../../../core/models/training-request';
import { TrainingDetail } from './training-detail';

function makeTraining(overrides: Partial<Training> = {}): Training {
  return {
    id: 1,
    title: '研修A',
    description: null,
    category: null,
    is_active: true,
    audience_department_id: null,
    audience_managers_only: false,
    audience_new_hires_only: false,
    requires_multistage_approval: false,
    approval_stage_count: null,
    lessons: [],
    ...overrides,
  };
}

describe('TrainingDetail', () => {
  function createComponent(options: {
    trainingServiceMock?: Partial<TrainingService>;
    enrollmentServiceMock?: Partial<TrainingEnrollmentService>;
    requestServiceMock?: Partial<TrainingRequestService>;
    employeeServiceMock?: Partial<EmployeeService>;
    masterDataMock?: Partial<MasterDataService>;
    currentEmployeeId?: number;
    isHr?: boolean;
  }) {
    TestBed.configureTestingModule({
      imports: [TrainingDetail],
      providers: [
        provideRouter([]),
        {
          provide: TrainingService,
          useValue: { get: () => of(makeTraining()), ...options.trainingServiceMock },
        },
        {
          provide: TrainingEnrollmentService,
          useValue: { list: () => of([]), ...options.enrollmentServiceMock },
        },
        {
          provide: TrainingRequestService,
          useValue: { list: () => of([]), ...options.requestServiceMock },
        },
        {
          provide: EmployeeService,
          useValue: { list: () => of([]), ...options.employeeServiceMock },
        },
        {
          provide: MasterDataService,
          useValue: { departments: () => of([]), ...options.masterDataMock },
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

    const fixture = TestBed.createComponent(TrainingDetail);
    fixture.detectChanges();
    return fixture;
  }

  function makeEnrollment(overrides: Partial<TrainingEnrollment> = {}): TrainingEnrollment {
    return {
      id: 1,
      employee_id: 10,
      training: { id: 1 } as never,
      status: 'in_progress',
      progress: 50,
      due_at: null,
      started_at: null,
      completed_at: null,
      completed_lesson_ids: [],
      ...overrides,
    };
  }

  function makeRequest(overrides: Partial<TrainingRequest> = {}): TrainingRequest {
    return {
      id: 1,
      employee_id: 10,
      requested_by_employee_id: 10,
      is_self_requested: true,
      training: { id: 1 } as never,
      status: 'pending',
      reason: null,
      due_at: null,
      required_approval_stages: 1,
      current_approval_stage: 1,
      approval_history: [],
      decided_by_employee_id: null,
      decided_at: null,
      decision_comment: null,
      requested_at: '2026-06-01T00:00:00Z',
      can_decide: false,
      can_cancel: true,
      ...overrides,
    };
  }

  describe('ngOnInit', () => {
    it('研修情報を読み込み、自分の受講記録が見つかればmyEnrollmentに設定する', () => {
      const fixture = createComponent({
        enrollmentServiceMock: { list: () => of([makeEnrollment({ employee_id: 10, training: { id: 1 } as never })]) },
        currentEmployeeId: 10,
      });

      expect(fixture.componentInstance.training()).toEqual(makeTraining());
      expect(fixture.componentInstance.myEnrollment()?.employee_id).toBe(10);
    });

    it('自分の受講記録が無ければmyEnrollmentはnull', () => {
      const fixture = createComponent({
        enrollmentServiceMock: { list: () => of([makeEnrollment({ employee_id: 99 })]) },
        currentEmployeeId: 10,
      });

      expect(fixture.componentInstance.myEnrollment()).toBeNull();
    });

    it('人事でなければ自分の申請を読み込む', () => {
      const listSpy = vi.fn().mockReturnValue(of([makeRequest({ employee_id: 10, training: { id: 1 } as never })]));
      const fixture = createComponent({ requestServiceMock: { list: listSpy }, isHr: false, currentEmployeeId: 10 });

      expect(listSpy).toHaveBeenCalled();
      expect(fixture.componentInstance.myRequest()?.employee_id).toBe(10);
    });

    it('人事の場合は自分の申請を読み込まず、従業員・部署一覧を読み込む', () => {
      const requestListSpy = vi.fn().mockReturnValue(of([]));
      const employeeListSpy = vi.fn().mockReturnValue(of([]));
      const departmentsSpy = vi.fn().mockReturnValue(of([]));

      createComponent({
        requestServiceMock: { list: requestListSpy },
        employeeServiceMock: { list: employeeListSpy },
        masterDataMock: { departments: departmentsSpy },
        isHr: true,
      });

      expect(requestListSpy).not.toHaveBeenCalled();
      expect(employeeListSpy).toHaveBeenCalled();
      expect(departmentsSpy).toHaveBeenCalled();
    });
  });

  describe('applyForTraining / cancelMyRequest', () => {
    it('成功すると申請結果を保持し、フォームをリセットする', () => {
      const create = vi.fn().mockReturnValue(of(makeRequest()));
      const fixture = createComponent({ requestServiceMock: { create } });

      fixture.componentInstance.applyForm.setValue({ reason: '理由', due_at: '2026-06-01' });
      fixture.componentInstance.applyForTraining();

      expect(create).toHaveBeenCalledWith(1, '理由', '2026-06-01');
      expect(fixture.componentInstance.myRequest()).toEqual(makeRequest());
      expect(fixture.componentInstance.applyForm.getRawValue()).toEqual({ reason: '', due_at: '' });
    });

    it('失敗するとエラーメッセージを表示する', () => {
      const create = vi.fn().mockReturnValue(throwError(() => ({ error: { message: '既に申請済みです。' } })));
      const fixture = createComponent({ requestServiceMock: { create } });

      fixture.componentInstance.applyForTraining();

      expect(fixture.componentInstance.applyError()).toBe('既に申請済みです。');
    });

    it('cancelMyRequestは申請が無ければ何もしない', () => {
      vi.spyOn(window, 'confirm').mockReturnValue(true);
      const cancel = vi.fn();
      const fixture = createComponent({ requestServiceMock: { cancel } });

      fixture.componentInstance.cancelMyRequest();

      expect(cancel).not.toHaveBeenCalled();
    });

    it('cancelMyRequestは確認をキャンセルすると何もしない', () => {
      vi.spyOn(window, 'confirm').mockReturnValue(false);
      const cancel = vi.fn();
      const fixture = createComponent({
        requestServiceMock: { list: () => of([makeRequest({ employee_id: 10 })]), cancel },
        currentEmployeeId: 10,
      });

      fixture.componentInstance.cancelMyRequest();

      expect(cancel).not.toHaveBeenCalled();
    });

    it('cancelMyRequestは確認後、取消結果でmyRequestを更新する', () => {
      vi.spyOn(window, 'confirm').mockReturnValue(true);
      const cancelled = makeRequest({ status: 'cancelled' });
      const cancel = vi.fn().mockReturnValue(of(cancelled));
      const fixture = createComponent({
        requestServiceMock: { list: () => of([makeRequest({ id: 3, employee_id: 10 })]), cancel },
        currentEmployeeId: 10,
      });

      fixture.componentInstance.cancelMyRequest();

      expect(cancel).toHaveBeenCalledWith(3);
      expect(fixture.componentInstance.myRequest()?.status).toBe('cancelled');
    });
  });

  describe('onLessonContentSelected', () => {
    it('選択したファイル一覧を保持する', () => {
      const fixture = createComponent({});
      const file = new File(['a'], 'lesson.mp4', { type: 'video/mp4' });

      fixture.componentInstance.onLessonContentSelected({ target: { files: [file] } } as unknown as Event);

      expect(fixture.componentInstance.lessonContents()).toEqual([file]);
    });

    it('Lesson名が未入力なら、1つ目のファイル名（拡張子なし）を自動設定する', () => {
      const fixture = createComponent({});
      const file = new File(['a'], '第1章講義.mp4', { type: 'video/mp4' });

      fixture.componentInstance.onLessonContentSelected({ target: { files: [file] } } as unknown as Event);

      expect(fixture.componentInstance.lessonForm.controls.title.value).toBe('第1章講義');
    });

    it('Lesson名が既に入力済みなら上書きしない', () => {
      const fixture = createComponent({});
      fixture.componentInstance.lessonForm.controls.title.setValue('既存タイトル');
      const file = new File(['a'], 'lesson.mp4', { type: 'video/mp4' });

      fixture.componentInstance.onLessonContentSelected({ target: { files: [file] } } as unknown as Event);

      expect(fixture.componentInstance.lessonForm.controls.title.value).toBe('既存タイトル');
    });
  });

  describe('addLesson / deleteLesson', () => {
    it('addLessonは無効なフォームでは何もしない', () => {
      const addLesson = vi.fn();
      const fixture = createComponent({ trainingServiceMock: { addLesson } });

      fixture.componentInstance.lessonForm.patchValue({ title: '' });
      fixture.componentInstance.addLesson();

      expect(addLesson).not.toHaveBeenCalled();
    });

    it('addLessonは成功するとフォーム・添付ファイルをリセットし、研修情報を再取得する', () => {
      const getSpy = vi.fn().mockReturnValue(of(makeTraining()));
      const addLesson = vi.fn().mockReturnValue(of({}));
      const fixture = createComponent({ trainingServiceMock: { get: getSpy, addLesson } });

      const file = new File(['a'], 'lesson.mp4', { type: 'video/mp4' });
      fixture.componentInstance.lessonForm.setValue({ title: '第1章' });
      fixture.componentInstance.lessonContents.set([file]);
      fixture.componentInstance.addLesson();

      expect(addLesson).toHaveBeenCalledWith(1, { title: '第1章', contents: [file] });
      expect(fixture.componentInstance.lessonContents()).toEqual([]);
      expect(getSpy).toHaveBeenCalledTimes(2);
    });

    it('addLessonは失敗するとエラーメッセージを表示する', () => {
      const addLesson = vi.fn().mockReturnValue(throwError(() => ({ error: { message: '教材が大きすぎます。' } })));
      const fixture = createComponent({ trainingServiceMock: { addLesson } });

      fixture.componentInstance.lessonForm.setValue({ title: '第1章' });
      fixture.componentInstance.addLesson();

      expect(fixture.componentInstance.lessonError()).toBe('教材が大きすぎます。');
    });

    it('deleteLessonは確認をキャンセルすると何もしない', () => {
      vi.spyOn(window, 'confirm').mockReturnValue(false);
      const deleteLesson = vi.fn();
      const fixture = createComponent({ trainingServiceMock: { deleteLesson } });

      fixture.componentInstance.deleteLesson(5);

      expect(deleteLesson).not.toHaveBeenCalled();
    });

    it('deleteLessonは確認後、成功すると研修情報を再取得する', () => {
      vi.spyOn(window, 'confirm').mockReturnValue(true);
      const getSpy = vi.fn().mockReturnValue(of(makeTraining()));
      const deleteLesson = vi.fn().mockReturnValue(of(undefined));
      const fixture = createComponent({ trainingServiceMock: { get: getSpy, deleteLesson } });

      fixture.componentInstance.deleteLesson(5);

      expect(deleteLesson).toHaveBeenCalledWith(1, 5);
      expect(fixture.componentInstance.lessonDeletingId()).toBeNull();
      expect(getSpy).toHaveBeenCalledTimes(2);
    });

    it('deleteLessonは失敗するとエラーメッセージを表示する', () => {
      vi.spyOn(window, 'confirm').mockReturnValue(true);
      const deleteLesson = vi.fn().mockReturnValue(throwError(() => ({ error: { message: '削除に失敗' } })));
      const fixture = createComponent({ trainingServiceMock: { deleteLesson } });

      fixture.componentInstance.deleteLesson(5);

      expect(fixture.componentInstance.lessonError()).toBe('削除に失敗');
    });
  });

  describe('enrollEmployee', () => {
    it('個別指定で従業員が未選択なら何もしない', () => {
      const enroll = vi.fn();
      const fixture = createComponent({ enrollmentServiceMock: { enroll } });

      fixture.componentInstance.enrollForm.patchValue({ scope: 'individual', employee_id: 0 });
      fixture.componentInstance.enrollEmployee();

      expect(enroll).not.toHaveBeenCalled();
    });

    it('個別指定で成功すると、メッセージを表示し選択欄をリセットする', () => {
      const enroll = vi.fn().mockReturnValue(of({}));
      const fixture = createComponent({ enrollmentServiceMock: { enroll } });

      fixture.componentInstance.enrollForm.setValue({
        scope: 'individual',
        employee_id: 5,
        department_id: 0,
        due_at: '2026-06-01',
      });
      fixture.componentInstance.enrollEmployee();

      expect(enroll).toHaveBeenCalledWith(5, 1, '2026-06-01');
      expect(fixture.componentInstance.enrollMessage()).toBe('割り当てました。');
      expect(fixture.componentInstance.enrollForm.getRawValue().employee_id).toBe(0);
    });

    it('個別指定で失敗するとエラーメッセージを表示する', () => {
      const enroll = vi.fn().mockReturnValue(throwError(() => ({ error: { message: '既に受講登録済みです。' } })));
      const fixture = createComponent({ enrollmentServiceMock: { enroll } });

      fixture.componentInstance.enrollForm.patchValue({ scope: 'individual', employee_id: 5 });
      fixture.componentInstance.enrollEmployee();

      expect(fixture.componentInstance.enrollMessage()).toBe('既に受講登録済みです。');
    });

    it('部署一括で部署が未選択なら何もしない', () => {
      const bulkEnroll = vi.fn();
      const fixture = createComponent({ enrollmentServiceMock: { bulkEnroll } });

      fixture.componentInstance.enrollForm.patchValue({ scope: 'department', department_id: 0 });
      fixture.componentInstance.enrollEmployee();

      expect(bulkEnroll).not.toHaveBeenCalled();
    });

    it('部署一括で成功すると件数メッセージを表示する', () => {
      const bulkEnroll = vi.fn().mockReturnValue(of({ enrolled: 4, skipped: 1 }));
      const fixture = createComponent({ enrollmentServiceMock: { bulkEnroll } });

      fixture.componentInstance.enrollForm.setValue({
        scope: 'department',
        employee_id: 0,
        department_id: 3,
        due_at: '',
      });
      fixture.componentInstance.enrollEmployee();

      expect(bulkEnroll).toHaveBeenCalledWith(1, 3, null);
      expect(fixture.componentInstance.bulkEnrollMessage()).toBe('4名を割り当てました（既に登録済みで対象外: 1名）。');
    });

    it('全社一括ではdepartmentIdをnullとして送る', () => {
      const bulkEnroll = vi.fn().mockReturnValue(of({ enrolled: 10, skipped: 0 }));
      const fixture = createComponent({ enrollmentServiceMock: { bulkEnroll } });

      fixture.componentInstance.enrollForm.patchValue({ scope: 'company' });
      fixture.componentInstance.enrollEmployee();

      expect(bulkEnroll).toHaveBeenCalledWith(1, null, null);
    });

    it('一括割り当てが失敗するとエラーメッセージを表示する', () => {
      const bulkEnroll = vi.fn().mockReturnValue(throwError(() => ({ error: { message: '失敗しました' } })));
      const fixture = createComponent({ enrollmentServiceMock: { bulkEnroll } });

      fixture.componentInstance.enrollForm.patchValue({ scope: 'company' });
      fixture.componentInstance.enrollEmployee();

      expect(fixture.componentInstance.bulkEnrollMessage()).toBe('失敗しました');
    });
  });
});
