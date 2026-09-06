import { ActivatedRoute, convertToParamMap } from '@angular/router';
import { TestBed } from '@angular/core/testing';
import { of, throwError } from 'rxjs';
import { vi } from 'vitest';
import { AuthService } from '../../../core/services/auth.service';
import { EmployeeService } from '../../../core/services/employee.service';
import { MasterDataService } from '../../../core/services/master-data.service';
import { Delegation } from '../../../core/models/delegation';
import { Employee } from '../../../core/models/employee';
import { EmployeeAssignment } from '../../../core/models/employee-assignment';
import { EmployeeDetail } from './employee-detail';

function makeDelegation(overrides: Partial<Delegation> = {}): Delegation {
  return {
    id: 1,
    delegator_id: 1,
    delegate_id: 2,
    delegate_name: '鈴木花子',
    started_at: '2026-04-01',
    ended_at: null,
    is_active: true,
    ...overrides,
  };
}

describe('EmployeeDetail', () => {
  function createComponent(options: {
    employeeServiceMock?: Partial<EmployeeService>;
    masterDataMock?: Partial<MasterDataService>;
    isHr?: boolean;
  }) {
    TestBed.configureTestingModule({
      imports: [EmployeeDetail],
      providers: [
        {
          provide: EmployeeService,
          useValue: {
            get: () => of({ id: 1 }),
            assignments: () => of([]),
            delegationsGiven: () => of([]),
            list: () => of([]),
            ...options.employeeServiceMock,
          },
        },
        {
          provide: MasterDataService,
          useValue: { departments: () => of([]), positions: () => of([]), ...options.masterDataMock },
        },
        { provide: AuthService, useValue: { isHr: () => options.isHr ?? true } },
        { provide: ActivatedRoute, useValue: { snapshot: { paramMap: convertToParamMap({ id: '1' }) } } },
      ],
    });

    const fixture = TestBed.createComponent(EmployeeDetail);
    fixture.detectChanges();
    return fixture;
  }

  it('初期化時に従業員情報と異動履歴を読み込む（ロールに関わらず）', () => {
    const fixture = createComponent({
      employeeServiceMock: {
        get: () => of({ id: 1, name: '山田太郎' } as unknown as Employee),
        assignments: () => of([{ id: 1 }] as unknown as EmployeeAssignment[]),
      },
      isHr: false,
    });

    expect(fixture.componentInstance.employee()).toEqual({ id: 1, name: '山田太郎' });
    expect(fixture.componentInstance.assignments()).toHaveLength(1);
    expect(fixture.componentInstance.loading()).toBe(false);
  });

  it('人事の場合のみ委任・部署・役職・同僚一覧を読み込む', () => {
    const delegationsSpy = vi.fn().mockReturnValue(of([]));
    const departmentsSpy = vi.fn().mockReturnValue(of([]));
    const listSpy = vi.fn().mockReturnValue(of([]));

    createComponent({
      employeeServiceMock: { delegationsGiven: delegationsSpy, list: listSpy },
      masterDataMock: { departments: departmentsSpy },
      isHr: true,
    });

    expect(delegationsSpy).toHaveBeenCalled();
    expect(departmentsSpy).toHaveBeenCalled();
    expect(listSpy).toHaveBeenCalled();
  });

  it('人事でない場合は委任・部署・役職・同僚一覧を読み込まない', () => {
    const delegationsSpy = vi.fn().mockReturnValue(of([]));

    createComponent({ employeeServiceMock: { delegationsGiven: delegationsSpy }, isHr: false });

    expect(delegationsSpy).not.toHaveBeenCalled();
  });

  describe('submitTransfer', () => {
    it('無効なフォームでは何もしない', () => {
      const transfer = vi.fn();
      const fixture = createComponent({ employeeServiceMock: { transfer } });

      fixture.componentInstance.submitTransfer();

      expect(transfer).not.toHaveBeenCalled();
    });

    it('有効なフォームでは、IDを数値化し上司未指定はnullで送る', () => {
      const transfer = vi.fn().mockReturnValue(of({}));
      const fixture = createComponent({ employeeServiceMock: { transfer } });

      fixture.componentInstance.transferForm.setValue({
        department_id: 2,
        position_id: 3,
        manager_id: '',
        started_at: '2026-04-01',
      });
      fixture.componentInstance.submitTransfer();

      expect(transfer).toHaveBeenCalledWith(1, {
        department_id: 2,
        position_id: 3,
        manager_id: null,
        started_at: '2026-04-01',
      });
    });

    it('成功するとフォームを閉じ、従業員情報・異動履歴を再読み込みする', () => {
      const getSpy = vi.fn().mockReturnValue(of({ id: 1 }));
      const assignmentsSpy = vi.fn().mockReturnValue(of([]));
      const transfer = vi.fn().mockReturnValue(of({}));
      const fixture = createComponent({
        employeeServiceMock: { get: getSpy, assignments: assignmentsSpy, transfer },
      });

      fixture.componentInstance.showTransferForm.set(true);
      fixture.componentInstance.transferForm.setValue({
        department_id: 2,
        position_id: 3,
        manager_id: '',
        started_at: '2026-04-01',
      });
      fixture.componentInstance.submitTransfer();

      expect(fixture.componentInstance.showTransferForm()).toBe(false);
      expect(getSpy).toHaveBeenCalledTimes(2);
      expect(assignmentsSpy).toHaveBeenCalledTimes(2);
    });

    it('失敗するとエラーメッセージを表示する', () => {
      const transfer = vi.fn().mockReturnValue(throwError(() => ({ error: { message: '循環した指揮系統です。' } })));
      const fixture = createComponent({ employeeServiceMock: { transfer } });

      fixture.componentInstance.transferForm.setValue({
        department_id: 2,
        position_id: 3,
        manager_id: '',
        started_at: '2026-04-01',
      });
      fixture.componentInstance.submitTransfer();

      expect(fixture.componentInstance.transferError()).toBe('循環した指揮系統です。');
    });
  });

  describe('submitDelegation', () => {
    it('無効なフォームでは何もしない', () => {
      const createDelegation = vi.fn();
      const fixture = createComponent({ employeeServiceMock: { createDelegation } });

      fixture.componentInstance.submitDelegation();

      expect(createDelegation).not.toHaveBeenCalled();
    });

    it('成功すると一覧の先頭に追加し、フォームを閉じる', () => {
      const newDelegation = makeDelegation({ id: 9 });
      const createDelegation = vi.fn().mockReturnValue(of(newDelegation));
      const fixture = createComponent({ employeeServiceMock: { createDelegation } });

      fixture.componentInstance.delegations.set([makeDelegation({ id: 1 })]);
      fixture.componentInstance.showDelegationForm.set(true);
      fixture.componentInstance.delegationForm.setValue({ delegate_id: 2, started_at: '2026-04-01', ended_at: '' });
      fixture.componentInstance.submitDelegation();

      expect(createDelegation).toHaveBeenCalledWith(1, { delegate_id: 2, started_at: '2026-04-01', ended_at: null });
      expect(fixture.componentInstance.delegations().map((d) => d.id)).toEqual([9, 1]);
      expect(fixture.componentInstance.showDelegationForm()).toBe(false);
    });

    it('失敗するとエラーメッセージを表示する', () => {
      const createDelegation = vi
        .fn()
        .mockReturnValue(throwError(() => ({ error: { message: '委任期間が重複しています。' } })));
      const fixture = createComponent({ employeeServiceMock: { createDelegation } });

      fixture.componentInstance.delegationForm.setValue({ delegate_id: 2, started_at: '2026-04-01', ended_at: '' });
      fixture.componentInstance.submitDelegation();

      expect(fixture.componentInstance.delegationError()).toBe('委任期間が重複しています。');
    });
  });

  it('revokeDelegationは該当する委任だけを更新後の内容に差し替える', () => {
    const updated = makeDelegation({ id: 1, is_active: false, ended_at: '2026-05-01' });
    const revokeDelegation = vi.fn().mockReturnValue(of(updated));
    const fixture = createComponent({ employeeServiceMock: { revokeDelegation } });

    fixture.componentInstance.delegations.set([makeDelegation({ id: 1 }), makeDelegation({ id: 2 })]);
    fixture.componentInstance.revokeDelegation(1);

    expect(revokeDelegation).toHaveBeenCalledWith(1);
    const list = fixture.componentInstance.delegations();
    expect(list.find((d) => d.id === 1)?.is_active).toBe(false);
    expect(list.find((d) => d.id === 2)?.is_active).toBe(true);
  });

  describe('resendInvite', () => {
    it('成功するとサーバーからのメッセージを表示する', () => {
      const resendInvite = vi.fn().mockReturnValue(of({ message: '招待メールを再送しました。' }));
      const fixture = createComponent({ employeeServiceMock: { resendInvite } });

      fixture.componentInstance.resendInvite();

      expect(fixture.componentInstance.resendInviteMessage()).toBe('招待メールを再送しました。');
      expect(fixture.componentInstance.resendingInvite()).toBe(false);
    });

    it('失敗するとエラーメッセージを表示する', () => {
      const resendInvite = vi.fn().mockReturnValue(throwError(() => ({ error: { message: '送信に失敗しました。' } })));
      const fixture = createComponent({ employeeServiceMock: { resendInvite } });

      fixture.componentInstance.resendInvite();

      expect(fixture.componentInstance.resendInviteError()).toBe('送信に失敗しました。');
    });
  });
});
