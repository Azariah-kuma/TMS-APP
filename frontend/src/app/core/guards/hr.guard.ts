import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { AuthService } from '../services/auth.service';

/** 人事のみアクセス可能なルートを守る。authGuardの後段として使う想定。 */
export const hrGuard: CanActivateFn = () => {
  const auth = inject(AuthService);
  const router = inject(Router);

  return auth.isHr() ? true : router.createUrlTree(['/dashboard']);
};
