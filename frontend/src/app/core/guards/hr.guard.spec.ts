import { Router, UrlTree } from '@angular/router';
import { TestBed } from '@angular/core/testing';
import { vi } from 'vitest';
import { AuthService } from '../services/auth.service';
import { hrGuard } from './hr.guard';

describe('hrGuard', () => {
  function setup(isHr: boolean) {
    const authServiceMock = { isHr: vi.fn().mockReturnValue(isHr) };
    const routerMock = { createUrlTree: vi.fn().mockReturnValue('URL_TREE' as unknown as UrlTree) };

    TestBed.configureTestingModule({
      providers: [
        { provide: AuthService, useValue: authServiceMock },
        { provide: Router, useValue: routerMock },
      ],
    });

    return { routerMock };
  }

  it('人事であればtrueを返す', () => {
    setup(true);

    const result = TestBed.runInInjectionContext(() => hrGuard({} as never, {} as never));

    expect(result).toBe(true);
  });

  it('人事でなければ/dashboardへのUrlTreeを返す', () => {
    const { routerMock } = setup(false);

    const result = TestBed.runInInjectionContext(() => hrGuard({} as never, {} as never));

    expect(result).toBe('URL_TREE');
    expect(routerMock.createUrlTree).toHaveBeenCalledWith(['/dashboard']);
  });
});
