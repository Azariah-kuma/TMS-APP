import { provideRouter, Router } from '@angular/router';
import { TestBed } from '@angular/core/testing';
import { of, throwError } from 'rxjs';
import { vi } from 'vitest';
import { AuthService } from '../../../core/services/auth.service';
import { Login } from './login';

describe('Login', () => {
  function createComponent(authServiceMock: Partial<AuthService>) {
    TestBed.configureTestingModule({
      imports: [Login],
      providers: [provideRouter([]), { provide: AuthService, useValue: authServiceMock }],
    });

    const fixture = TestBed.createComponent(Login);
    fixture.detectChanges();
    return fixture;
  }

  it('未入力のまま送信しても、フォームが無効なのでloginは呼ばれない', () => {
    const login = vi.fn();
    const fixture = createComponent({ login });

    fixture.componentInstance.submit();

    expect(login).not.toHaveBeenCalled();
  });

  it('有効な入力で送信すると、成功時に/dashboardへ遷移する', () => {
    const login = vi.fn().mockReturnValue(of({}));
    const fixture = createComponent({ login });
    const router = TestBed.inject(Router);
    const navigateSpy = vi.spyOn(router, 'navigateByUrl');

    fixture.componentInstance.form.setValue({ email: 'yamada@example.com', password: 'password' });
    fixture.componentInstance.submit();

    expect(login).toHaveBeenCalledWith({ email: 'yamada@example.com', password: 'password' });
    expect(navigateSpy).toHaveBeenCalledWith('/dashboard');
  });

  it('ログイン失敗時はエラーメッセージを表示し、submittingをfalseに戻す', () => {
    const login = vi.fn().mockReturnValue(throwError(() => new Error('unauthorized')));
    const fixture = createComponent({ login });

    fixture.componentInstance.form.setValue({ email: 'yamada@example.com', password: 'wrong' });
    fixture.componentInstance.submit();

    expect(fixture.componentInstance.error()).toBe('メールアドレスまたはパスワードが正しくありません。');
    expect(fixture.componentInstance.submitting()).toBe(false);
  });
});
