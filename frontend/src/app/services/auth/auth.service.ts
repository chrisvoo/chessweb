import {DestroyRef, inject, Injectable, OnDestroy} from '@angular/core';
import type { User } from '../../../types/models';
import {BehaviorSubject, catchError, EMPTY, map, Observable, of, tap} from 'rxjs';
import { HttpClient } from '@angular/common/http';
import {ErrorResponse, LoginResponse, RefreshTokenResponse} from '../../../types/requests';
import { Router } from '@angular/router';
import {takeUntilDestroyed} from '@angular/core/rxjs-interop';

@Injectable({
  providedIn: 'root'
})
export class AuthService implements OnDestroy {
  #authenticated = new BehaviorSubject(false);
  #authenticatedUser = new BehaviorSubject<User | undefined>(undefined)
  private token?: string;
  private timeout?: ReturnType<typeof setTimeout>;
  #destroyRef = inject(DestroyRef);

  constructor(
    private http: HttpClient,
    private router: Router
  ) { }

  ngOnDestroy() {
    if (this.timeout) {
      clearTimeout(this.timeout)
    }
  }

  isAuthenticated(): Observable<boolean> {
    return this.#authenticated.asObservable();
  }

  getLoggedUser(): Observable<User | undefined> {
    return this.#authenticatedUser.asObservable();
  }

  getToken(): string | undefined {
    return this.token;
  }

  login(email: string, password: string): Observable<boolean> {
      return this.http.post<LoginResponse>('/api/login', { email, password }).pipe(
        takeUntilDestroyed(this.#destroyRef),
        tap((response) => {
          const { access_token, expires_in, user } = response.data;

          this.#authenticatedUser.next(user)
          this.#authenticated.next(true)
          this.token = access_token

          this.timeout = setTimeout(() => this.refreshToken, expires_in * 1000);
        }),
        map(() => true)
      )
  }

  refreshToken(): Observable<boolean> {
    this.token = undefined

    return this.http.post<RefreshTokenResponse>('/api/refresh', { refresh_token: this.token }).pipe(
      takeUntilDestroyed(this.#destroyRef),
      tap((response) => {
        const { access_token, expires_in } = response.data
        this.token = access_token
        this.timeout = setTimeout(() => this.refreshToken, expires_in * 1000);
      }),
      catchError(err => {
        const errResonse = err.error as ErrorResponse
        if (errResonse.statusCode === 400) {
          console.log('Refresh token expired')
        }
        return of(false);
      }),
      map(() => true)
    )
  }
}
