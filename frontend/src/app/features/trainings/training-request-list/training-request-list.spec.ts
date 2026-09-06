import { provideRouter } from '@angular/router';
import { TestBed } from '@angular/core/testing';
import { of, throwError } from 'rxjs';
import { vi } from 'vitest';
import { AuthService } from '../../../core/services/auth.service';
import { EmployeeService } from '../../../core/services/employee.service';
import { TrainingRequestService } from '../../../core/services/training-request.service';
import { TrainingService } from '../../../core/services/training.service';
import { TrainingRequest } from '../../../core/models/training-request';
import { TrainingRequestList } from './training-request-list';

function makeRequest(overrides: Partial<TrainingRequest> = {}): TrainingRequest {
  return {
    id: 1,
    employee_id: 10,
    requested_by_employee_id: 10,
    is_self_requested: true,
    training: { id: 1, title: '研修A' } as never,
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
    can_decide: true,
    can_cancel: false,
    ...overrides,
  };
}

describe('TrainingRequestList', () => {
  function createComponent(options: {
    requestServiceMock?: Partial<TrainingRequestService>;
    trainingServiceMock?: Partial<TrainingService>;
    employeeServiceMock?: Partial<EmployeeService>;
    currentEmployee?: Record<string, unknown> | null;
    isHr?: boolean;
  }) {
    TestBed.configureTestingModule({
      imports: [TrainingRequestList],
      providers: [
        provideRouter([]),
        {
          provide: TrainingRequestService,
          useValue: { list: () => of([]), ...options.requestServiceMock },
        },
        { provide: TrainingService, useValue: { list: () => of([]), ...options.trainingServiceMock } },
        {
          provide: EmployeeService,
          useValue: { subordinates: () => of([]), ...options.employeeServiceMock },
        },
        {
          provide: AuthService,
          useValue: {
            isHr: () => options.isHr ?? false,
            currentEmployee: () => options.currentEmployee ?? { id: 10, is_manager: false },
          },
        },
      ],
    });

    const fixture = TestBed.createComponent(TrainingRequestList);
    fixture.detectChanges();
    return fixture;
  }

  it('初期化時に申請一覧・研修一覧を読み込む', () => {
    const requests = [makeRequest()];
    const trainings = [{ id: 1, title: '研修A' }];
    const fixture = createComponent({
      requestServiceMock: { list: () => of(requests) },
      trainingServiceMock: { list: () => of(trainings) } as never,
    });

    expect(fixture.componentInstance.requests()).toEqual(requests);
    expect(fixture.componentInstance.trainings()).toEqual(trainings);
    expect(fixture.componentInstance.loading()).toBe(false);
  });

  it('管理職（人事でない）の場合のみ部下一覧を読み込む', () => {
    const subordinatesSpy = vi.fn().mockReturnValue(of([]));
    createComponent({
      employeeServiceMock: { subordinates: subordinatesSpy },
      currentEmployee: { id: 10, is_manager: true },
      isHr: false,
    });

    expect(subordinatesSpy).toHaveBeenCalled();
  });

  it('人事の場合はisManagerがfalseになり、部下一覧を読み込まない', () => {
    const subordinatesSpy = vi.fn().mockReturnValue(of([]));
    const fixture = createComponent({
      employeeServiceMock: { subordinates: subordinatesSpy },
      currentEmployee: { id: 10, is_manager: true },
      isHr: true,
    });

    expect(fixture.componentInstance.isManager()).toBe(false);
    expect(subordinatesSpy).not.toHaveBeenCalled();
  });

  it('togglePanelは同じパネルなら閉じ、違えば開く', () => {
    const fixture = createComponent({});

    fixture.componentInstance.togglePanel('self');
    expect(fixture.componentInstance.activePanel()).toBe('self');

    fixture.componentInstance.togglePanel('self');
    expect(fixture.componentInstance.activePanel()).toBeNull();

    fixture.componentInstance.togglePanel('delegate');
    expect(fixture.componentInstance.activePanel()).toBe('delegate');
  });

  describe('stageProgressLabel', () => {
    it('単層承認(required_approval_stages=1)ではnullを返す', () => {
      const fixture = createComponent({});
      expect(fixture.componentInstance.stageProgressLabel(makeRequest({ required_approval_stages: 1 }))).toBeNull();
    });

    it('多段階承認が承認待ちの場合、現在の段階を表示する', () => {
      const fixture = createComponent({});
      const request = makeRequest({ required_approval_stages: 2, current_approval_stage: 1, status: 'pending' });
      expect(fixture.componentInstance.stageProgressLabel(request)).toBe('2段階中1段階目が承認待ち');
    });

    it('多段階承認が確定済みの場合、決裁履歴の件数を表示する', () => {
      const fixture = createComponent({});
      const request = makeRequest({
        required_approval_stages: 2,
        status: 'approved',
        approval_history: [
          { stage_number: 1, status: 'approved', decided_at: '2026-01-01', comment: null },
          { stage_number: 2, status: 'approved', decided_at: '2026-01-02', comment: null },
        ],
      });
      expect(fixture.componentInstance.stageProgressLabel(request)).toBe('2段階中2段階承認済み');
    });
  });

  describe('選択・一括承認', () => {
    it('isSelectableはcan_decideかつpendingの場合のみtrue', () => {
      const fixture = createComponent({});
      expect(fixture.componentInstance.isSelectable(makeRequest({ can_decide: true, status: 'pending' }))).toBe(
        true,
      );
      expect(fixture.componentInstance.isSelectable(makeRequest({ can_decide: false, status: 'pending' }))).toBe(
        false,
      );
      expect(fixture.componentInstance.isSelectable(makeRequest({ can_decide: true, status: 'approved' }))).toBe(
        false,
      );
    });

    it('toggleSelectで選択・選択解除ができる', () => {
      const fixture = createComponent({});

      fixture.componentInstance.toggleSelect(1);
      expect(fixture.componentInstance.isSelected(1)).toBe(true);

      fixture.componentInstance.toggleSelect(1);
      expect(fixture.componentInstance.isSelected(1)).toBe(false);
    });

    it('bulkApproveは選択が空なら何もしない', () => {
      const bulkApprove = vi.fn();
      const fixture = createComponent({ requestServiceMock: { bulkApprove } });

      fixture.componentInstance.bulkApprove();

      expect(bulkApprove).not.toHaveBeenCalled();
    });

    it('bulkApproveは成功すると件数メッセージを表示し、一覧を再読み込みする', () => {
      const listSpy = vi.fn().mockReturnValue(of([]));
      const bulkApprove = vi.fn().mockReturnValue(of({ approved: 2, skipped: 1 }));
      const fixture = createComponent({ requestServiceMock: { list: listSpy, bulkApprove } });

      fixture.componentInstance.toggleSelect(1);
      fixture.componentInstance.toggleSelect(2);
      fixture.componentInstance.bulkApprove();

      expect(bulkApprove).toHaveBeenCalledWith([1, 2]);
      expect(fixture.componentInstance.bulkApproveMessage()).toBe('2件を承認しました（対象外: 1件）。');
      expect(listSpy).toHaveBeenCalledTimes(2);
    });

    it('bulkApproveが失敗するとエラーメッセージを表示する', () => {
      const bulkApprove = vi.fn().mockReturnValue(throwError(() => ({ error: { message: '失敗しました' } })));
      const fixture = createComponent({ requestServiceMock: { bulkApprove } });

      fixture.componentInstance.toggleSelect(1);
      fixture.componentInstance.bulkApprove();

      expect(fixture.componentInstance.bulkApproveMessage()).toBe('失敗しました');
    });
  });

  describe('submitApply（自己申請）', () => {
    it('成功するとフォームをリセットし、パネルを閉じ、一覧を再読み込みする', () => {
      const listSpy = vi.fn().mockReturnValue(of([]));
      const create = vi.fn().mockReturnValue(of(makeRequest()));
      const fixture = createComponent({ requestServiceMock: { list: listSpy, create } });

      fixture.componentInstance.activePanel.set('self');
      fixture.componentInstance.applyForm.setValue({ training_id: 1, reason: '理由', due_at: '2026-06-01' });
      fixture.componentInstance.submitApply();

      expect(create).toHaveBeenCalledWith(1, '理由', '2026-06-01');
      expect(fixture.componentInstance.activePanel()).toBeNull();
      expect(fixture.componentInstance.applyForm.getRawValue()).toEqual({
        training_id: 0,
        reason: '',
        due_at: '',
      });
      expect(listSpy).toHaveBeenCalledTimes(2);
    });

    it('失敗するとエラーメッセージを表示する', () => {
      const create = vi.fn().mockReturnValue(throwError(() => ({ error: { message: '既に申請済みです。' } })));
      const fixture = createComponent({ requestServiceMock: { create } });

      fixture.componentInstance.applyForm.setValue({ training_id: 1, reason: '', due_at: '' });
      fixture.componentInstance.submitApply();

      expect(fixture.componentInstance.applyError()).toBe('既に申請済みです。');
    });
  });

  describe('submitDelegate（代理申請）', () => {
    it('研修が未選択なら何もしない', () => {
      const create = vi.fn();
      const fixture = createComponent({ requestServiceMock: { create } });

      fixture.componentInstance.delegateForm.patchValue({ scope: 'individual', training_id: 0 });
      fixture.componentInstance.submitDelegate();

      expect(create).not.toHaveBeenCalled();
    });

    it('個別指定で部下が未選択なら何もしない', () => {
      const create = vi.fn();
      const fixture = createComponent({ requestServiceMock: { create } });

      fixture.componentInstance.delegateForm.patchValue({ scope: 'individual', training_id: 1, employee_id: 0 });
      fixture.componentInstance.submitDelegate();

      expect(create).not.toHaveBeenCalled();
    });

    it('個別指定で成功すると、部下選択欄をリセットしメッセージを表示する', () => {
      const listSpy = vi.fn().mockReturnValue(of([]));
      const create = vi.fn().mockReturnValue(of(makeRequest()));
      const fixture = createComponent({ requestServiceMock: { list: listSpy, create } });

      fixture.componentInstance.delegateForm.setValue({
        scope: 'individual',
        training_id: 1,
        employee_id: 5,
        reason: '理由',
        due_at: '',
      });
      fixture.componentInstance.submitDelegate();

      expect(create).toHaveBeenCalledWith(1, '理由', null, 5);
      expect(fixture.componentInstance.delegateMessage()).toBe('申請しました（人事の承認待ちです）。');
      expect(fixture.componentInstance.delegateForm.getRawValue().employee_id).toBe(0);
      expect(listSpy).toHaveBeenCalledTimes(2);
    });

    it('個別指定で失敗するとエラーメッセージを表示する', () => {
      const create = vi.fn().mockReturnValue(throwError(() => ({ error: { message: '失敗しました' } })));
      const fixture = createComponent({ requestServiceMock: { create } });

      fixture.componentInstance.delegateForm.setValue({
        scope: 'individual',
        training_id: 1,
        employee_id: 5,
        reason: '',
        due_at: '',
      });
      fixture.componentInstance.submitDelegate();

      expect(fixture.componentInstance.delegateMessage()).toBe('失敗しました');
    });

    it('部署一括で自分の所属部署が無ければ何もしない', () => {
      const bulkRequest = vi.fn();
      const fixture = createComponent({
        requestServiceMock: { bulkRequest },
        currentEmployee: { id: 10, is_manager: true, current_assignment: null },
      });

      fixture.componentInstance.delegateForm.patchValue({ scope: 'department', training_id: 1 });
      fixture.componentInstance.submitDelegate();

      expect(bulkRequest).not.toHaveBeenCalled();
    });

    it('部署一括で成功すると件数メッセージを表示する', () => {
      const listSpy = vi.fn().mockReturnValue(of([]));
      const bulkRequest = vi.fn().mockReturnValue(of({ requested: 3, skipped: 1 }));
      const fixture = createComponent({
        requestServiceMock: { list: listSpy, bulkRequest },
        currentEmployee: { id: 10, is_manager: true, current_assignment: { department_id: 7 } },
      });

      fixture.componentInstance.delegateForm.patchValue({ scope: 'department', training_id: 1 });
      fixture.componentInstance.submitDelegate();

      expect(bulkRequest).toHaveBeenCalledWith(1, 7);
      expect(fixture.componentInstance.delegateMessage()).toBe(
        '3名を申請しました（既に受講登録・申請済みで対象外: 1名）。人事の承認待ちです。',
      );
      expect(listSpy).toHaveBeenCalledTimes(2);
    });

    it('部署一括で失敗するとエラーメッセージを表示する', () => {
      const bulkRequest = vi.fn().mockReturnValue(throwError(() => ({ error: { message: '失敗しました' } })));
      const fixture = createComponent({
        requestServiceMock: { bulkRequest },
        currentEmployee: { id: 10, is_manager: true, current_assignment: { department_id: 7 } },
      });

      fixture.componentInstance.delegateForm.patchValue({ scope: 'department', training_id: 1 });
      fixture.componentInstance.submitDelegate();

      expect(fixture.componentInstance.delegateMessage()).toBe('失敗しました');
    });
  });

  describe('approve / reject / cancel', () => {
    it('approveは成功すると一覧を再読み込みする', () => {
      const listSpy = vi.fn().mockReturnValue(of([]));
      const approve = vi.fn().mockReturnValue(of(makeRequest()));
      const fixture = createComponent({ requestServiceMock: { list: listSpy, approve } });

      fixture.componentInstance.approve(1);

      expect(approve).toHaveBeenCalledWith(1);
      expect(fixture.componentInstance.actioningId()).toBeNull();
      expect(listSpy).toHaveBeenCalledTimes(2);
    });

    it('rejectはprompt()でキャンセル(null)されると何もしない', () => {
      vi.spyOn(window, 'prompt').mockReturnValue(null);
      const reject = vi.fn();
      const fixture = createComponent({ requestServiceMock: { reject } });

      fixture.componentInstance.reject(1);

      expect(reject).not.toHaveBeenCalled();
    });

    it('rejectはprompt()の入力内容をコメントとして送る', () => {
      vi.spyOn(window, 'prompt').mockReturnValue('却下理由');
      const listSpy = vi.fn().mockReturnValue(of([]));
      const reject = vi.fn().mockReturnValue(of(makeRequest()));
      const fixture = createComponent({ requestServiceMock: { list: listSpy, reject } });

      fixture.componentInstance.reject(1);

      expect(reject).toHaveBeenCalledWith(1, '却下理由');
      expect(listSpy).toHaveBeenCalledTimes(2);
    });

    it('rejectは空文字の入力ならnullとして送る', () => {
      vi.spyOn(window, 'prompt').mockReturnValue('');
      const reject = vi.fn().mockReturnValue(of(makeRequest()));
      const fixture = createComponent({ requestServiceMock: { reject } });

      fixture.componentInstance.reject(1);

      expect(reject).toHaveBeenCalledWith(1, null);
    });

    it('cancelはconfirm()でキャンセルされると何もしない', () => {
      vi.spyOn(window, 'confirm').mockReturnValue(false);
      const cancel = vi.fn();
      const fixture = createComponent({ requestServiceMock: { cancel } });

      fixture.componentInstance.cancel(1);

      expect(cancel).not.toHaveBeenCalled();
    });

    it('cancelは確認後に取消を実行し、一覧を再読み込みする', () => {
      vi.spyOn(window, 'confirm').mockReturnValue(true);
      const listSpy = vi.fn().mockReturnValue(of([]));
      const cancel = vi.fn().mockReturnValue(of(makeRequest()));
      const fixture = createComponent({ requestServiceMock: { list: listSpy, cancel } });

      fixture.componentInstance.cancel(1);

      expect(cancel).toHaveBeenCalledWith(1);
      expect(listSpy).toHaveBeenCalledTimes(2);
    });
  });
});
