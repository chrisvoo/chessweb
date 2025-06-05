import { DestroyRef, inject, Injectable, OnDestroy } from '@angular/core';
import type { User } from '../../../types/models';
import {
  BehaviorSubject,
  catchError, EMPTY, exhaustMap,
  finalize,
  map,
  Observable,
  of, shareReplay, Subject, switchMap, takeUntil,
  tap,
  throwError, timer
} from 'rxjs';
import { HttpClient } from '@angular/common/http';
import { LoginResponse, RefreshTokenResponse } from '../../../types/requests';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';

@Injectable({
  providedIn: 'root'
})
export class AuthService implements OnDestroy {
  #tokenSubject = new BehaviorSubject<string | null>(null);
  #authenticatedUser = new BehaviorSubject<User | undefined>(undefined)
  private timeout?: ReturnType<typeof setTimeout>;
  #destroyRef = inject(DestroyRef);
  #refreshing$?: Observable<void>;
  #logout$ = new Subject<void>();

  constructor(
    private http: HttpClient
  ) { }

  ngOnDestroy() {
    if (this.timeout) {
      clearTimeout(this.timeout)
    }
  }

  isAuthenticated(): Observable<boolean> {
    return this.#tokenSubject.asObservable().pipe(map(token => !!token));
  }


  getLoggedUser(): Observable<User | undefined> {
    return this.#authenticatedUser.asObservable();
  }

  getToken(): string | null {
    return this.#tokenSubject.value;
  }

  logout(): void {
    // Clear observables and state
    this.#logout$.next();                    // cancel auto-refresh
    this.#tokenSubject.next(null);           // remove token
    this.#authenticatedUser.next(undefined); // clear user info

    // @TODO navigate to login and erase refresh token cookie
  }

  login(email: string, password: string): Observable<boolean> {
      return this.http.post<LoginResponse>(
        '/api/login',
        { email, password }
      )
        .pipe(
          takeUntilDestroyed(this.#destroyRef),
          tap((response) => {
            const { access_token, expires_in, user } = response.data;

            this.#authenticatedUser.next(user)
            this.#tokenSubject.next(access_token)

            this.timeout = setTimeout(() => this.refreshToken, expires_in * 1000);
          }),
          map(() => true)
      )
  }

  refreshToken(): Observable<void> {
    // If we already have a token, no need to refresh
    if (this.#tokenSubject.value) {
      return of(void 0);
    }

    // If there's already an in-flight refresh, return it
    if (!this.#refreshing$) {
      this.#refreshing$ = this.http.post<RefreshTokenResponse>(
        '/api/refresh',
        null
      ).pipe(
        tap(response => {
          const { access_token, expires_in } = response.data
          if (access_token && expires_in) {
            this.#tokenSubject.next(access_token)

            timer(expires_in * 1000).pipe(
              takeUntil(this.#logout$), // Automatically cancels the scheduled refresh if the user logs out before it fires.
              exhaustMap(() => {
                // If the refreshToken() is already in progress, it ignores any new emissions from the timer() or accidental triggers.
                // Prevents duplicate or overlapping refreshes.
                // Ensures no refresh is ever cancelled mid-way.
                this.#tokenSubject.next(null);
                return this.refreshToken().pipe(catchError(() => EMPTY));
              }),
              catchError(() => EMPTY)
            ).subscribe(); // start refresh loop
          } else {
            this.#authenticatedUser.next(undefined)
          }
        }),
        catchError(err => {
          this.#tokenSubject.next(null);
          return throwError(() => err);
        }),
        map(() => undefined),
        finalize(() => {
          // Clear the shared observable so it can be recreated next time
          this.#refreshing$ = undefined;
        }),
        /* A successfully completed source will stay cached in the shareReplayed observable forever, but an
           errored source can be retried. If refCount is true, the source will be unsubscribed from once
           the reference count drops to zero, i.e. the inner ReplaySubject will be unsubscribed. All new subscribers
           will receive value emissions from a new ReplaySubject which in turn will cause a new subscription to
           the source observable.
           However, we don't want the shared observable to unsubscribe and reset when there are no subscribers.
           We want to share the result of the refresh call even if a late subscriber subscribes after the request
           completed. */
        shareReplay({ bufferSize: 1, refCount: false }) // share result among all subscribers
      );
    }

    return this.#refreshing$;
  }
}
