import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { AuthService } from '../services/auth.service';

/** 人事・監査のどちらかがアクセス可能なルートを守る（閲覧専用の管理系画面用）。authGuardの後段として使う想定。 */
export const viewAllGuard: CanActivateFn = () => {
  const auth = inject(AuthService);
  const router = inject(Router);

  return auth.canViewAll() ? true : router.createUrlTree(['/dashboard']);
};
