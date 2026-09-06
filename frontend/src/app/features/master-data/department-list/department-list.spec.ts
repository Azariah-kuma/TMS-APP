import { TestBed } from '@angular/core/testing';
import { of, throwError } from 'rxjs';
import { vi } from 'vitest';
import { AuthService } from '../../../core/services/auth.service';
import { MasterDataService } from '../../../core/services/master-data.service';
import { Department } from '../../../core/models/department';
import { DepartmentList } from './department-list';

function makeDepartment(overrides: Partial<Department> = {}): Department {
  return { id: 1, name: '営業部', code: 'DEPT-SALES', ...overrides };
}

describe('DepartmentList', () => {
  function createComponent(masterDataMock: Partial<MasterDataService>) {
    TestBed.configureTestingModule({
      imports: [DepartmentList],
      providers: [
        { provide: MasterDataService, useValue: masterDataMock },
        { provide: AuthService, useValue: { isHr: () => true } },
      ],
    });

    const fixture = TestBed.createComponent(DepartmentList);
    fixture.detectChanges();
    return fixture;
  }

  it('初期化時にdepartments()を呼び、一覧を保持する', () => {
    const departments = [makeDepartment()];
    const fixture = createComponent({ departments: () => of(departments) });

    expect(fixture.componentInstance.departments()).toEqual(departments);
    expect(fixture.componentInstance.loading()).toBe(false);
  });

  describe('submit (新規追加)', () => {
    it('無効なフォームでは何もしない', () => {
      const createDepartment = vi.fn();
      const fixture = createComponent({ departments: () => of([]), createDepartment });

      fixture.componentInstance.form.patchValue({ name: '', code: '' });
      fixture.componentInstance.submit();

      expect(createDepartment).not.toHaveBeenCalled();
    });

    it('成功すると、名前順を保ちつつ一覧にその場で追加し、フォームをリセットする', () => {
      const existing = makeDepartment({ id: 1, name: 'あ部署', code: 'DEPT-A' });
      const created = makeDepartment({ id: 2, name: 'か部署', code: 'DEPT-K' });
      const createDepartment = vi.fn().mockReturnValue(of(created));
      const fixture = createComponent({ departments: () => of([existing]), createDepartment });

      fixture.componentInstance.form.setValue({ name: 'か部署', code: 'DEPT-K' });
      fixture.componentInstance.submit();

      expect(fixture.componentInstance.departments().map((d) => d.id)).toEqual([1, 2]);
      expect(fixture.componentInstance.form.getRawValue()).toEqual({ name: '', code: '' });
      expect(fixture.componentInstance.submitting()).toBe(false);
    });

    it('失敗するとエラーメッセージを表示する', () => {
      const createDepartment = vi.fn().mockReturnValue(throwError(() => ({ error: { message: '重複しています。' } })));
      const fixture = createComponent({ departments: () => of([]), createDepartment });

      fixture.componentInstance.form.setValue({ name: '営業部', code: 'DEPT-SALES' });
      fixture.componentInstance.submit();

      expect(fixture.componentInstance.error()).toBe('重複しています。');
      expect(fixture.componentInstance.submitting()).toBe(false);
    });
  });

  describe('編集', () => {
    it('startEditはeditFormに既存値を反映する', () => {
      const fixture = createComponent({ departments: () => of([]) });
      const department = makeDepartment({ id: 2, name: '人事部', code: 'DEPT-HR' });

      fixture.componentInstance.startEdit(department);

      expect(fixture.componentInstance.editingId()).toBe(2);
      expect(fixture.componentInstance.editForm.getRawValue()).toEqual({ name: '人事部', code: 'DEPT-HR' });
    });

    it('submitEdit成功時は編集状態を閉じ、一覧を再読み込みする', () => {
      const departmentsSpy = vi.fn().mockReturnValue(of([]));
      const updateDepartment = vi.fn().mockReturnValue(of(makeDepartment()));
      const fixture = createComponent({ departments: departmentsSpy, updateDepartment });

      fixture.componentInstance.openActionsId.set(1);
      fixture.componentInstance.editForm.setValue({ name: '人事部', code: 'DEPT-HR' });
      fixture.componentInstance.submitEdit(1);

      expect(updateDepartment).toHaveBeenCalledWith(1, { name: '人事部', code: 'DEPT-HR' });
      expect(fixture.componentInstance.editingId()).toBeNull();
      expect(fixture.componentInstance.openActionsId()).toBeNull();
      expect(departmentsSpy).toHaveBeenCalledTimes(2);
    });

    it('submitEdit失敗時はeditErrorを表示する', () => {
      const updateDepartment = vi.fn().mockReturnValue(throwError(() => ({ error: { message: '訂正に失敗' } })));
      const fixture = createComponent({ departments: () => of([]), updateDepartment });

      fixture.componentInstance.editForm.setValue({ name: '人事部', code: 'DEPT-HR' });
      fixture.componentInstance.submitEdit(1);

      expect(fixture.componentInstance.editError()).toBe('訂正に失敗');
    });
  });

  describe('deleteDepartment', () => {
    it('確認ダイアログでキャンセルすると削除しない', () => {
      vi.spyOn(window, 'confirm').mockReturnValue(false);
      const deleteDepartment = vi.fn();
      const fixture = createComponent({ departments: () => of([]), deleteDepartment });

      fixture.componentInstance.deleteDepartment(makeDepartment());

      expect(deleteDepartment).not.toHaveBeenCalled();
    });

    it('確認して失敗すると（使用中の部署など）エラーメッセージを表示する', () => {
      vi.spyOn(window, 'confirm').mockReturnValue(true);
      const deleteDepartment = vi
        .fn()
        .mockReturnValue(throwError(() => ({ error: { message: 'この部署は使用されているため削除できません。' } })));
      const fixture = createComponent({ departments: () => of([]), deleteDepartment });

      fixture.componentInstance.deleteDepartment(makeDepartment({ id: 1 }));

      expect(fixture.componentInstance.deleteError()).toBe('この部署は使用されているため削除できません。');
      expect(fixture.componentInstance.deletingId()).toBeNull();
    });
  });
});
