import { provideRouter, Router } from '@angular/router';
import { TestBed } from '@angular/core/testing';
import { signal } from '@angular/core';
import { of } from 'rxjs';
import { vi } from 'vitest';
import { AuthService } from '../../core/services/auth.service';
import { Shell } from './shell';

describe('Shell', () => {
  function createComponent(logout = vi.fn().mockReturnValue(of(undefined))) {
    TestBed.configureTestingModule({
      imports: [Shell],
      providers: [
        provideRouter([]),
        {
          provide: AuthService,
          useValue: {
            currentUser: signal({ id: 1, name: '山田太郎' }),
            currentEmployee: signal({ id: 1, role: 'hr' }),
            isHr: () => true,
            logout,
          },
        },
      ],
    });

    const fixture = TestBed.createComponent(Shell);
    fixture.detectChanges();
    return { fixture, logout };
  }

  it('AuthServiceの現在のユーザー・従業員・人事フラグを公開する', () => {
    const { fixture } = createComponent();

    expect(fixture.componentInstance.user()?.name).toBe('山田太郎');
    expect(fixture.componentInstance.isHr()).toBe(true);
  });

  it('logoutはAuthService.logout()を呼び、成功すると/loginへ遷移する', () => {
    const { fixture, logout } = createComponent();
    const router = TestBed.inject(Router);
    const navigateSpy = vi.spyOn(router, 'navigateByUrl').mockResolvedValue(true);

    fixture.componentInstance.logout();

    expect(logout).toHaveBeenCalled();
    expect(navigateSpy).toHaveBeenCalledWith('/login');
  });
});
