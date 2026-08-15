import { HttpInterceptorFn } from '@angular/common/http';

const XSRF_COOKIE_NAME = 'XSRF-TOKEN';
const XSRF_HEADER_NAME = 'X-XSRF-TOKEN';

function readCookie(name: string): string | null {
  const match = document.cookie.match(new RegExp(`(?:^|; )${name}=([^;]*)`));
  return match ? decodeURIComponent(match[1]) : null;
}

/**
 * Laravel Sanctum's SPA (cookie) auth needs every request to carry the
 * session/XSRF cookies. Angular's built-in XSRF interceptor only attaches
 * the header for same-origin requests, but during local development the
 * Angular dev server (:4200) and the Laravel API (:8000) are different
 * origins, so the header is added manually here instead.
 */
export const credentialsInterceptor: HttpInterceptorFn = (req, next) => {
  let request = req.clone({ withCredentials: true });

  if (!['GET', 'HEAD', 'OPTIONS', 'TRACE'].includes(request.method)) {
    const token = readCookie(XSRF_COOKIE_NAME);
    if (token && !request.headers.has(XSRF_HEADER_NAME)) {
      request = request.clone({ headers: request.headers.set(XSRF_HEADER_NAME, token) });
    }
  }

  return next(request);
};
