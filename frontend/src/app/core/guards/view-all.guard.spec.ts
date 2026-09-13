import { Router, UrlTree } from '@angular/router';
import { TestBed } from '@angular/core/testing';
import { vi } from 'vitest';
import { AuthService } from '../services/auth.service';
import { viewAllGuard } from './view-all.guard';

describe('viewAllGuard', () => {
  function setup(canViewAll: boolean) {
    const authServiceMock = { canViewAll: vi.fn().mockReturnValue(canViewAll) };
    const routerMock = { createUrlTree: vi.fn().mockReturnValue('URL_TREE' as unknown as UrlTree) };

    TestBed.configureTestingModule({
      providers: [
        { provide: AuthService, useValue: authServiceMock },
        { provide: Router, useValue: routerMock },
      ],
    });

    return { routerMock };
  }

  it('人事・監査のいずれかであればtrueを返す', () => {
    setup(true);

    const result = TestBed.runInInjectionContext(() => viewAllGuard({} as never, {} as never));

    expect(result).toBe(true);
  });

  it('どちらでもなければ/dashboardへのUrlTreeを返す', () => {
    const { routerMock } = setup(false);

    const result = TestBed.runInInjectionContext(() => viewAllGuard({} as never, {} as never));

    expect(result).toBe('URL_TREE');
    expect(routerMock.createUrlTree).toHaveBeenCalledWith(['/dashboard']);
  });
});
