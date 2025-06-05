import {CanActivateFn, Router} from '@angular/router';
import {inject} from '@angular/core';
import {AuthService} from '../services/auth/auth.service';
import {catchError, map, of, switchMap} from 'rxjs';

export const isLoggedInGuard: CanActivateFn = (route, state) => {
  const authService = inject(AuthService);
  const router = inject(Router)

  return authService.refreshToken().pipe(
    switchMap(() => authService.isAuthenticated()),
    map(isAuthenticated => {
      if (isAuthenticated) {
        return true
      }
      router.navigate(['/login'])
      return false
    }),
    catchError(() => {
      router.navigate(['/login']);
      return of(false);
    })
  )
};
