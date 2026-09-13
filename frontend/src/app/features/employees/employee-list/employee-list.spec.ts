import { provideRouter } from '@angular/router';
import { TestBed } from '@angular/core/testing';
import { of } from 'rxjs';
import { vi } from 'vitest';
import { AuthService } from '../../../core/services/auth.service';
import { EmployeeService } from '../../../core/services/employee.service';
import { Employee } from '../../../core/models/employee';
import { EmployeeList } from './employee-list';

describe('EmployeeList', () => {
  function createComponent(
    options: { employees?: Employee[]; isHr?: boolean } = {},
    employeeServiceMock: Partial<EmployeeService> = {},
  ) {
    const employees = options.employees ?? ([{ id: 1, name: '山田太郎' }] as unknown as Employee[]);

    TestBed.configureTestingModule({
      imports: [EmployeeList],
      providers: [
        provideRouter([]),
        { provide: EmployeeService, useValue: { list: () => of(employees), ...employeeServiceMock } },
        { provide: AuthService, useValue: { isHr: () => options.isHr ?? false } },
      ],
    });

    const fixture = TestBed.createComponent(EmployeeList);
    fixture.detectChanges();
    return fixture;
  }

  it('初期化時に従業員一覧を読み込む', () => {
    const employees = [{ id: 1, name: '山田太郎' }] as unknown as Employee[];
    const fixture = createComponent({ employees });

    expect(fixture.componentInstance.employees()).toEqual(employees);
    expect(fixture.componentInstance.loading()).toBe(false);
  });

  it('人事のみ新規追加・CSV一括登録の導線が表示される', () => {
    const fixture = createComponent({ isHr: true });

    expect(fixture.componentInstance.isHr()).toBe(true);
  });

  it('人事以外は新規追加・CSV一括登録の導線を表示しない', () => {
    const fixture = createComponent({ isHr: false });

    expect(fixture.componentInstance.isHr()).toBe(false);
  });

  it('初期化時は退職済みを含めずに一覧を読み込む', () => {
    const list = vi.fn().mockReturnValue(of([]));
    createComponent({}, { list });

    expect(list).toHaveBeenCalledWith(false);
  });

  it('toggleWithRetiredは退職済みを含めて一覧を読み込み直す', () => {
    const list = vi.fn().mockReturnValue(of([]));
    const fixture = createComponent({}, { list });

    fixture.componentInstance.toggleWithRetired(true);

    expect(fixture.componentInstance.withRetired()).toBe(true);
    expect(list).toHaveBeenLastCalledWith(true);
  });
});
