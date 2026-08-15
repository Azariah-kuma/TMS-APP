import { HttpClient } from '@angular/common/http';
import { Injectable, computed, inject, signal } from '@angular/core';
import { Observable, tap } from 'rxjs';
import { environment } from '../../../environments/environment';
import { User } from '../models/user';

export interface Credentials {
  email: string;
  password: string;
}

export interface RegisterPayload extends Credentials {
  name: string;
  password_confirmation: string;
}

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly http = inject(HttpClient);
  private readonly apiUrl = environment.apiUrl;
  private readonly user = signal<User | null>(null);

  readonly currentUser = this.user.asReadonly();
  readonly isAuthenticated = computed(() => this.user() !== null);
  readonly currentEmployee = computed(() => this.user()?.employee ?? null);
  readonly isHr = computed(() => this.currentEmployee()?.role === 'hr');

  /** Fetches the CSRF cookie Sanctum needs before any state-changing request. */
  private csrfCookie(): Observable<void> {
    return this.http.get<void>(`${this.apiUrl}/sanctum/csrf-cookie`);
  }

  login(credentials: Credentials): Observable<User> {
    return new Observable<User>((subscriber) => {
      this.csrfCookie().subscribe({
        next: () =>
          this.http
            .post<User>(`${this.apiUrl}/api/login`, credentials)
            .pipe(tap((user) => this.user.set(user)))
            .subscribe(subscriber),
        error: (err) => subscriber.error(err),
      });
    });
  }

  register(payload: RegisterPayload): Observable<User> {
    return new Observable<User>((subscriber) => {
      this.csrfCookie().subscribe({
        next: () =>
          this.http
            .post<User>(`${this.apiUrl}/api/register`, payload)
            .pipe(tap((user) => this.user.set(user)))
            .subscribe(subscriber),
        error: (err) => subscriber.error(err),
      });
    });
  }

  logout(): Observable<void> {
    return this.http
      .post<void>(`${this.apiUrl}/api/logout`, {})
      .pipe(tap(() => this.user.set(null)));
  }

  /** Asks the API who the current session belongs to (e.g. on app start / route guard). */
  fetchUser(): Observable<User> {
    return this.http
      .get<User>(`${this.apiUrl}/api/user`)
      .pipe(tap((user) => this.user.set(user)));
  }
}
