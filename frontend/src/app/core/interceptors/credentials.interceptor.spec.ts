import { HttpHeaders, HttpRequest } from '@angular/common/http';
import { vi } from 'vitest';
import { credentialsInterceptor } from './credentials.interceptor';

function clearCookies(): void {
  document.cookie.split(';').forEach((cookie) => {
    const name = cookie.split('=')[0].trim();
    if (name) {
      document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;`;
    }
  });
}

describe('credentialsInterceptor', () => {
  afterEach(() => {
    clearCookies();
  });

  it('全てのリクエストにwithCredentials:trueを付与する', () => {
    const req = new HttpRequest('GET', '/api/user');
    const next = vi.fn().mockReturnValue('NEXT_RESULT');

    credentialsInterceptor(req, next);

    const forwarded = next.mock.calls[0][0] as HttpRequest<unknown>;
    expect(forwarded.withCredentials).toBe(true);
  });

  it('GET/HEAD/OPTIONS/TRACEにはXSRFヘッダーを付与しない', () => {
    document.cookie = 'XSRF-TOKEN=some-token';
    const req = new HttpRequest('GET', '/api/user');
    const next = vi.fn().mockReturnValue('NEXT_RESULT');

    credentialsInterceptor(req, next);

    const forwarded = next.mock.calls[0][0] as HttpRequest<unknown>;
    expect(forwarded.headers.has('X-XSRF-TOKEN')).toBe(false);
  });

  it('POST等の状態変更リクエストには、Cookieの値をデコードしてXSRFヘッダーに付与する', () => {
    document.cookie = `XSRF-TOKEN=${encodeURIComponent('abc 123')}`;
    const req = new HttpRequest('POST', '/api/login', {});
    const next = vi.fn().mockReturnValue('NEXT_RESULT');

    credentialsInterceptor(req, next);

    const forwarded = next.mock.calls[0][0] as HttpRequest<unknown>;
    expect(forwarded.headers.get('X-XSRF-TOKEN')).toBe('abc 123');
  });

  it('XSRF-TOKEN Cookieが無ければヘッダーを付与しない', () => {
    const req = new HttpRequest('POST', '/api/login', {});
    const next = vi.fn().mockReturnValue('NEXT_RESULT');

    credentialsInterceptor(req, next);

    const forwarded = next.mock.calls[0][0] as HttpRequest<unknown>;
    expect(forwarded.headers.has('X-XSRF-TOKEN')).toBe(false);
  });

  it('既にX-XSRF-TOKENヘッダーが設定されている場合は上書きしない', () => {
    document.cookie = 'XSRF-TOKEN=from-cookie';
    const req = new HttpRequest('POST', '/api/login', {}, { headers: new HttpHeaders({ 'X-XSRF-TOKEN': 'explicit' }) });
    const next = vi.fn().mockReturnValue('NEXT_RESULT');

    credentialsInterceptor(req, next);

    const forwarded = next.mock.calls[0][0] as HttpRequest<unknown>;
    expect(forwarded.headers.get('X-XSRF-TOKEN')).toBe('explicit');
  });
});
