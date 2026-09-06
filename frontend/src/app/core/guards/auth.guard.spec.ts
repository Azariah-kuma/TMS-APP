import { Router, UrlTree } from '@angular/router';
import { TestBed } from '@angular/core/testing';
import { firstValueFrom, isObservable, of, throwError } from 'rxjs';
import { vi } from 'vitest';
import { AuthService } from '../services/auth.service';
import { authGuard } from './auth.guard';

describe('authGuard', () => {
  function setup(options: { isAuthenticated: boolean; fetchUserResult?: 'success' | 'error' }) {
    const authServiceMock = {
      isAuthenticated: vi.fn().mockReturnValue(options.isAuthenticated),
      fetchUser:
        options.fetchUserResult === 'error'
          ? vi.fn().mockReturnValue(throwError(() => new Error('unauthenticated')))
          : vi.fn().mockReturnValue(of({})),
    };

    const routerMock = {
      createUrlTree: vi.fn().mockReturnValue('URL_TREE' as unknown as UrlTree),
    };

    TestBed.configureTestingModule({
      providers: [
        { provide: AuthService, useValue: authServiceMock },
        { provide: Router, useValue: routerMock },
      ],
    });

    return { authServiceMock, routerMock };
  }

  it('既に認証済みならtrueを即座に返し、fetchUserは呼ばない', () => {
    const { authServiceMock } = setup({ isAuthenticated: true });

    const result = TestBed.runInInjectionContext(() =>
      authGuard({} as never, {} as never),
    );

    expect(result).toBe(true);
    expect(authServiceMock.fetchUser).not.toHaveBeenCalled();
  });

  it('未認証だがfetchUserが成功すればtrueを返す', async () => {
    setup({ isAuthenticated: false, fetchUserResult: 'success' });

    const result = TestBed.runInInjectionContext(() => authGuard({} as never, {} as never));

    expect(isObservable(result)).toBe(true);
    await expect(firstValueFrom(result as never)).resolves.toBe(true);
  });

  it('未認証でfetchUserが失敗すれば/loginへのUrlTreeを返す', async () => {
    const { routerMock } = setup({ isAuthenticated: false, fetchUserResult: 'error' });

    const result = TestBed.runInInjectionContext(() => authGuard({} as never, {} as never));

    await expect(firstValueFrom(result as never)).resolves.toBe('URL_TREE');
    expect(routerMock.createUrlTree).toHaveBeenCalledWith(['/login']);
  });
});
