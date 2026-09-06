import { provideRouter } from '@angular/router';
import { TestBed } from '@angular/core/testing';
import { signal } from '@angular/core';
import { AuthService } from '../../core/services/auth.service';
import { Dashboard } from './dashboard';

describe('Dashboard', () => {
  it('AuthServiceの現在のユーザー・従業員・人事フラグをそのまま公開する', () => {
    const user = signal({ id: 1, name: '山田太郎' });
    const employee = signal({ id: 1, role: 'hr' });

    TestBed.configureTestingModule({
      imports: [Dashboard],
      providers: [
        provideRouter([]),
        {
          provide: AuthService,
          useValue: { currentUser: user, currentEmployee: employee, isHr: () => true },
        },
      ],
    });

    const fixture = TestBed.createComponent(Dashboard);
    fixture.detectChanges();

    expect(fixture.componentInstance.user()).toEqual({ id: 1, name: '山田太郎' });
    expect(fixture.componentInstance.employee()).toEqual({ id: 1, role: 'hr' });
    expect(fixture.componentInstance.isHr()).toBe(true);
  });

  it('従業員情報が未登録（employeeがnull）の場合も表示できる', () => {
    TestBed.configureTestingModule({
      imports: [Dashboard],
      providers: [
        provideRouter([]),
        {
          provide: AuthService,
          useValue: { currentUser: signal(null), currentEmployee: signal(null), isHr: () => false },
        },
      ],
    });

    const fixture = TestBed.createComponent(Dashboard);
    fixture.detectChanges();

    expect(fixture.componentInstance.employee()).toBeNull();
  });
});
