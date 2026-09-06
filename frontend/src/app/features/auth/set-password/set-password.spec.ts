import { ActivatedRoute, convertToParamMap, provideRouter, Router } from '@angular/router';
import { TestBed } from '@angular/core/testing';
import { of, throwError } from 'rxjs';
import { vi } from 'vitest';
import { AuthService } from '../../../core/services/auth.service';
import { SetPassword } from './set-password';

describe('SetPassword', () => {
  function createComponent(
    authServiceMock: Partial<AuthService>,
    queryParams: Record<string, string> = { token: 'abc', email: 'yamada@example.com' },
  ) {
    TestBed.configureTestingModule({
      imports: [SetPassword],
      providers: [
        provideRouter([]),
        { provide: AuthService, useValue: authServiceMock },
        {
          provide: ActivatedRoute,
          useValue: { snapshot: { queryParamMap: convertToParamMap(queryParams) } },
        },
      ],
    });

    const fixture = TestBed.createComponent(SetPassword);
    fixture.detectChanges();
    return fixture;
  }

  it('token・emailが両方揃っていればlinkInvalidはfalseで、emailを表示用に保持する', () => {
    const fixture = createComponent({});

    expect(fixture.componentInstance.linkInvalid()).toBe(false);
    expect(fixture.componentInstance.email()).toBe('yamada@example.com');
  });

  it('tokenが無ければlinkInvalidがtrueになる', () => {
    const fixture = createComponent({}, { token: '', email: 'yamada@example.com' });

    expect(fixture.componentInstance.linkInvalid()).toBe(true);
  });

  it('emailが無ければlinkInvalidがtrueになる', () => {
    const fixture = createComponent({}, { token: 'abc', email: '' });

    expect(fixture.componentInstance.linkInvalid()).toBe(true);
  });

  it('有効なフォームで送信すると、token/emailを含めてsetPasswordを呼び、成功時に/dashboardへ遷移する', () => {
    const setPassword = vi.fn().mockReturnValue(of({}));
    const fixture = createComponent({ setPassword });
    const router = TestBed.inject(Router);
    const navigateSpy = vi.spyOn(router, 'navigateByUrl');

    fixture.componentInstance.form.setValue({ password: 'password123', password_confirmation: 'password123' });
    fixture.componentInstance.submit();

    expect(setPassword).toHaveBeenCalledWith({
      token: 'abc',
      email: 'yamada@example.com',
      password: 'password123',
      password_confirmation: 'password123',
    });
    expect(navigateSpy).toHaveBeenCalledWith('/dashboard');
  });

  it('無効なフォームのまま送信してもsetPasswordは呼ばれない', () => {
    const setPassword = vi.fn();
    const fixture = createComponent({ setPassword });

    fixture.componentInstance.submit();

    expect(setPassword).not.toHaveBeenCalled();
  });

  it('サーバーからのエラーメッセージがあればそれを表示する', () => {
    const setPassword = vi
      .fn()
      .mockReturnValue(throwError(() => ({ error: { message: 'トークンが無効です。' } })));
    const fixture = createComponent({ setPassword });

    fixture.componentInstance.form.setValue({ password: 'password123', password_confirmation: 'password123' });
    fixture.componentInstance.submit();

    expect(fixture.componentInstance.error()).toBe('トークンが無効です。');
    expect(fixture.componentInstance.submitting()).toBe(false);
  });

  it('サーバーからのエラーメッセージが無ければ既定の文言を表示する', () => {
    const setPassword = vi.fn().mockReturnValue(throwError(() => ({})));
    const fixture = createComponent({ setPassword });

    fixture.componentInstance.form.setValue({ password: 'password123', password_confirmation: 'password123' });
    fixture.componentInstance.submit();

    expect(fixture.componentInstance.error()).toBe(
      '設定に失敗しました。招待リンクの有効期限が切れている可能性があります。',
    );
  });
});
