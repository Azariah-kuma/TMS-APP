import { provideRouter } from '@angular/router';
import { TestBed } from '@angular/core/testing';
import { of } from 'rxjs';
import { EmployeeService } from '../../../core/services/employee.service';
import { Employee } from '../../../core/models/employee';
import { EmployeeList } from './employee-list';

describe('EmployeeList', () => {
  it('初期化時に従業員一覧を読み込む', () => {
    const employees = [{ id: 1, name: '山田太郎' }] as unknown as Employee[];

    TestBed.configureTestingModule({
      imports: [EmployeeList],
      providers: [provideRouter([]), { provide: EmployeeService, useValue: { list: () => of(employees) } }],
    });

    const fixture = TestBed.createComponent(EmployeeList);
    fixture.detectChanges();

    expect(fixture.componentInstance.employees()).toEqual(employees);
    expect(fixture.componentInstance.loading()).toBe(false);
  });
});
