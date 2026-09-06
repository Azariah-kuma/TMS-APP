import { provideRouter, Router } from '@angular/router';
import { TestBed } from '@angular/core/testing';
import { of, throwError } from 'rxjs';
import { vi } from 'vitest';
import { EmployeeService } from '../../../core/services/employee.service';
import { MasterDataService } from '../../../core/services/master-data.service';
import { Employee } from '../../../core/models/employee';
import { EmployeeOnboard } from './employee-onboard';

function fakeCompositionEvent(value: string, isComposing: boolean): Event {
  return { isComposing, target: { value } } as unknown as Event;
}

describe('EmployeeOnboard', () => {
  function createComponent(
    employeeServiceMock: Partial<EmployeeService> = {},
    masterDataMock: Partial<MasterDataService> = {},
  ) {
    TestBed.configureTestingModule({
      imports: [EmployeeOnboard],
      providers: [
        provideRouter([]),
        {
          provide: EmployeeService,
          useValue: { list: () => of([]), ...employeeServiceMock },
        },
        {
          provide: MasterDataService,
          useValue: { departments: () => of([]), positions: () => of([]), ...masterDataMock },
        },
      ],
    });

    // 実際のルート解決を避けるため、既定でnavigateをスタブ化しておく（個別テストで再スパイして検証する）。
    vi.spyOn(TestBed.inject(Router), 'navigate').mockResolvedValue(true);

    const fixture = TestBed.createComponent(EmployeeOnboard);
    fixture.detectChanges();
    return fixture;
  }

  it('初期化時に部署・役職・従業員（上司候補）一覧を読み込む', () => {
    const departments = [{ id: 1, name: '開発部', code: 'DEPT-DEV' }];
    const positions = [{ id: 1, name: '主任', code: 'POS-JR', rank: 1 }];
    const employees = [{ id: 1 }] as unknown as Employee[];

    const fixture = createComponent(
      { list: () => of(employees) },
      { departments: () => of(departments), positions: () => of(positions) },
    );

    expect(fixture.componentInstance.departments()).toEqual(departments);
    expect(fixture.componentInstance.positions()).toEqual(positions);
    expect(fixture.componentInstance.employees()).toEqual(employees);
  });

  describe('convertToKatakana', () => {
    it('IME変換中(isComposing)は何もしない', () => {
      const fixture = createComponent();
      fixture.componentInstance.convertToKatakana(fakeCompositionEvent('やまだ', true), 'last_name_kana');

      expect(fixture.componentInstance.form.controls.last_name_kana.value).toBe('');
    });

    it('確定後はひらがなをカタカナに変換してフォームに反映する', () => {
      const fixture = createComponent();
      fixture.componentInstance.convertToKatakana(fakeCompositionEvent('やまだ', false), 'last_name_kana');

      expect(fixture.componentInstance.form.controls.last_name_kana.value).toBe('ヤマダ');
    });

    it('既にカタカナであれば値は変わらない', () => {
      const fixture = createComponent();
      fixture.componentInstance.form.controls.first_name_kana.setValue('タロウ');
      fixture.componentInstance.convertToKatakana(fakeCompositionEvent('タロウ', false), 'first_name_kana');

      expect(fixture.componentInstance.form.controls.first_name_kana.value).toBe('タロウ');
    });
  });

  describe('submit', () => {
    const validRawValue = {
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
      manager_id: '',
    };

    it('無効なフォームでは何もしない', () => {
      const onboard = vi.fn();
      const fixture = createComponent({ onboard });

      fixture.componentInstance.submit();

      expect(onboard).not.toHaveBeenCalled();
    });

    it('有効なフォームでは、department_id/position_idを数値化し、manager_id空欄はnullで送る', () => {
      const onboard = vi.fn().mockReturnValue(of({ id: 5 }));
      const fixture = createComponent({ onboard });

      fixture.componentInstance.form.setValue(validRawValue);
      fixture.componentInstance.submit();

      expect(onboard).toHaveBeenCalledWith({
        ...validRawValue,
        department_id: 1,
        position_id: 2,
        manager_id: null,
      });
    });

    it('manager_idが指定されていれば数値化して送る', () => {
      const onboard = vi.fn().mockReturnValue(of({ id: 5 }));
      const fixture = createComponent({ onboard });

      fixture.componentInstance.form.setValue({ ...validRawValue, manager_id: '9' });
      fixture.componentInstance.submit();

      expect(onboard).toHaveBeenCalledWith(expect.objectContaining({ manager_id: 9 }));
    });

    it('成功すると、作成された従業員の詳細ページへ遷移する', () => {
      const onboard = vi.fn().mockReturnValue(of({ id: 5 }));
      const fixture = createComponent({ onboard });
      const router = TestBed.inject(Router);
      const navigateSpy = vi.spyOn(router, 'navigate');

      fixture.componentInstance.form.setValue(validRawValue);
      fixture.componentInstance.submit();

      expect(navigateSpy).toHaveBeenCalledWith(['/employees', 5]);
    });

    it('失敗するとエラーメッセージを表示する', () => {
      const onboard = vi.fn().mockReturnValue(throwError(() => ({ error: { message: '重複しています。' } })));
      const fixture = createComponent({ onboard });

      fixture.componentInstance.form.setValue(validRawValue);
      fixture.componentInstance.submit();

      expect(fixture.componentInstance.error()).toBe('重複しています。');
      expect(fixture.componentInstance.submitting()).toBe(false);
    });
  });
});
