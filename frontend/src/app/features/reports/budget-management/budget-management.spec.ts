import { TestBed } from '@angular/core/testing';
import { of, throwError } from 'rxjs';
import { vi } from 'vitest';
import { DepartmentBudgetService } from '../../../core/services/department-budget.service';
import { ReportService } from '../../../core/services/report.service';
import { BudgetUsageRow } from '../../../core/models/department-budget';
import { BudgetManagement } from './budget-management';

function makeRow(overrides: Partial<BudgetUsageRow> = {}): BudgetUsageRow {
  return {
    department_id: 1,
    department_name: '営業部',
    fiscal_year: 2026,
    budget_id: null,
    budget_amount: null,
    consumed_amount: 0,
    remaining_amount: null,
    is_over_budget: false,
    ...overrides,
  };
}

describe('BudgetManagement', () => {
  function createComponent(
    reportServiceMock: Partial<ReportService>,
    departmentBudgetsMock: Partial<DepartmentBudgetService> = {},
  ) {
    TestBed.configureTestingModule({
      imports: [BudgetManagement],
      providers: [
        { provide: ReportService, useValue: reportServiceMock },
        { provide: DepartmentBudgetService, useValue: departmentBudgetsMock },
      ],
    });

    const fixture = TestBed.createComponent(BudgetManagement);
    fixture.detectChanges();
    return fixture;
  }

  it('初期化時に今年度の予算消費状況を読み込む', () => {
    const rows = [makeRow()];
    const budgetUsage = vi.fn().mockReturnValue(of(rows));
    const fixture = createComponent({ budgetUsage });

    expect(fixture.componentInstance.rows()).toEqual(rows);
    expect(fixture.componentInstance.loading()).toBe(false);
    expect(budgetUsage).toHaveBeenCalledWith(fixture.componentInstance.fiscalYear());
  });

  it('changeFiscalYearは年度を更新して読み込み直す', () => {
    const budgetUsage = vi.fn().mockReturnValue(of([]));
    const fixture = createComponent({ budgetUsage });

    fixture.componentInstance.changeFiscalYear(2025);

    expect(fixture.componentInstance.fiscalYear()).toBe(2025);
    expect(budgetUsage).toHaveBeenLastCalledWith(2025);
  });

  it('startEditは既存の予算額をeditFormに反映する', () => {
    const fixture = createComponent({ budgetUsage: () => of([]) });
    const row = makeRow({ department_id: 2, budget_id: 5, budget_amount: 100000 });

    fixture.componentInstance.startEdit(row);

    expect(fixture.componentInstance.editingDepartmentId()).toBe(2);
    expect(fixture.componentInstance.editForm.getRawValue()).toEqual({ budget_amount: 100000 });
  });

  it('startEditは予算未設定の部署では0を初期値にする', () => {
    const fixture = createComponent({ budgetUsage: () => of([]) });

    fixture.componentInstance.startEdit(makeRow({ budget_amount: null }));

    expect(fixture.componentInstance.editForm.getRawValue()).toEqual({ budget_amount: 0 });
  });

  describe('submitEdit', () => {
    it('無効なフォームでは何もしない', () => {
      const create = vi.fn();
      const fixture = createComponent({ budgetUsage: () => of([]) }, { create });

      fixture.componentInstance.editForm.setValue({ budget_amount: -1 });
      fixture.componentInstance.submitEdit(makeRow());

      expect(create).not.toHaveBeenCalled();
    });

    it('budget_idが無い場合はcreateを呼ぶ', () => {
      const budgetUsageSpy = vi.fn().mockReturnValue(of([]));
      const create = vi.fn().mockReturnValue(of(makeRow()));
      const fixture = createComponent({ budgetUsage: budgetUsageSpy }, { create });

      fixture.componentInstance.editForm.setValue({ budget_amount: 500000 });
      fixture.componentInstance.submitEdit(makeRow({ department_id: 3, budget_id: null }));

      expect(create).toHaveBeenCalledWith({
        department_id: 3,
        fiscal_year: fixture.componentInstance.fiscalYear(),
        budget_amount: 500000,
      });
      expect(fixture.componentInstance.editingDepartmentId()).toBeNull();
      expect(budgetUsageSpy).toHaveBeenCalledTimes(2);
    });

    it('budget_idがある場合はupdateを呼ぶ', () => {
      const update = vi.fn().mockReturnValue(of(makeRow()));
      const fixture = createComponent({ budgetUsage: () => of([]) }, { update });

      fixture.componentInstance.editForm.setValue({ budget_amount: 700000 });
      fixture.componentInstance.submitEdit(makeRow({ budget_id: 9 }));

      expect(update).toHaveBeenCalledWith(9, { budget_amount: 700000 });
    });

    it('失敗するとエラーメッセージを表示する', () => {
      const create = vi.fn().mockReturnValue(throwError(() => ({ error: { message: '保存に失敗しました。' } })));
      const fixture = createComponent({ budgetUsage: () => of([]) }, { create });

      fixture.componentInstance.editForm.setValue({ budget_amount: 500000 });
      fixture.componentInstance.submitEdit(makeRow());

      expect(fixture.componentInstance.editError()).toBe('保存に失敗しました。');
      expect(fixture.componentInstance.editSubmitting()).toBe(false);
    });
  });
});
