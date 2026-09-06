import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { provideHttpClient } from '@angular/common/http';
import { TestBed } from '@angular/core/testing';
import { environment } from '../../../environments/environment';
import { User } from '../models/user';
import { AuthService } from './auth.service';

function makeUser(overrides: Partial<User> = {}): User {
  return {
    id: 1,
    name: '山田太郎',
    name_kana: 'ヤマダタロウ',
    last_name: '山田',
    first_name: '太郎',
    last_name_kana: 'ヤマダ',
    first_name_kana: 'タロウ',
    email: 'yamada@example.com',
    employee: null,
    ...overrides,
  };
}

describe('AuthService', () => {
  let service: AuthService;
  let httpMock: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting()],
    });

    service = TestBed.inject(AuthService);
    httpMock = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    httpMock.verify();
  });

  it('未ログイン状態ではisAuthenticated/currentEmployee/isHrがすべてfalse/nullになる', () => {
    expect(service.isAuthenticated()).toBe(false);
    expect(service.currentEmployee()).toBeNull();
    expect(service.isHr()).toBe(false);
  });

  it('loginはCSRF Cookie取得後にログインAPIを呼び、成功するとユーザー状態が更新される', () => {
    const user = makeUser({ employee: { role: 'hr' } as never });

    service.login({ email: 'yamada@example.com', password: 'password' }).subscribe();

    httpMock.expectOne(`${environment.apiUrl}/sanctum/csrf-cookie`).flush(null);

    const loginReq = httpMock.expectOne(`${environment.apiUrl}/api/login`);
    expect(loginReq.request.method).toBe('POST');
    expect(loginReq.request.body).toEqual({ email: 'yamada@example.com', password: 'password' });
    loginReq.flush(user);

    expect(service.isAuthenticated()).toBe(true);
    expect(service.isHr()).toBe(true);
  });

  it('CSRF Cookie取得が失敗した場合、loginはエラーを伝播しユーザー状態は更新されない', () => {
    let errored = false;

    service.login({ email: 'yamada@example.com', password: 'password' }).subscribe({
      error: () => {
        errored = true;
      },
    });

    httpMock
      .expectOne(`${environment.apiUrl}/sanctum/csrf-cookie`)
      .flush(null, { status: 500, statusText: 'Server Error' });

    expect(errored).toBe(true);
    expect(service.isAuthenticated()).toBe(false);
  });

  it('setPasswordはCSRF Cookie取得後にAPIを呼び、成功するとログイン状態になる', () => {
    const user = makeUser();

    service
      .setPassword({
        token: 'abc',
        email: 'yamada@example.com',
        password: 'password123',
        password_confirmation: 'password123',
      })
      .subscribe();

    httpMock.expectOne(`${environment.apiUrl}/sanctum/csrf-cookie`).flush(null);

    const req = httpMock.expectOne(`${environment.apiUrl}/api/set-password`);
    expect(req.request.method).toBe('POST');
    req.flush(user);

    expect(service.isAuthenticated()).toBe(true);
  });

  it('logoutはAPIを呼び、成功するとユーザー状態がnullに戻る', () => {
    // まずログイン状態を作る
    service.login({ email: 'a@example.com', password: 'x' }).subscribe();
    httpMock.expectOne(`${environment.apiUrl}/sanctum/csrf-cookie`).flush(null);
    httpMock.expectOne(`${environment.apiUrl}/api/login`).flush(makeUser());
    expect(service.isAuthenticated()).toBe(true);

    service.logout().subscribe();
    const req = httpMock.expectOne(`${environment.apiUrl}/api/logout`);
    expect(req.request.method).toBe('POST');
    req.flush(null);

    expect(service.isAuthenticated()).toBe(false);
    expect(service.currentEmployee()).toBeNull();
  });

  it('fetchUserはセッションから現在ユーザーを取得し、ユーザー状態を更新する', () => {
    const user = makeUser({ employee: { role: 'employee' } as never });

    service.fetchUser().subscribe();

    const req = httpMock.expectOne(`${environment.apiUrl}/api/user`);
    expect(req.request.method).toBe('GET');
    req.flush(user);

    expect(service.isAuthenticated()).toBe(true);
    expect(service.isHr()).toBe(false);
  });
});
