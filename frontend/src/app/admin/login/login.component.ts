import {Component, DestroyRef, inject, OnDestroy} from '@angular/core';
import { ButtonDirective, ButtonIcon, ButtonLabel } from 'primeng/button';
import { FormBuilder, FormGroup, FormsModule, ReactiveFormsModule, Validators } from '@angular/forms';
import { InputText } from 'primeng/inputtext';
import { WindowMaximizeIcon } from 'primeng/icons';
import { Ripple } from 'primeng/ripple';
import { Message } from 'primeng/message';
import { AuthService } from '../../services/auth/auth.service';
import { catchError, tap, throwError } from 'rxjs';
import { Router } from '@angular/router';
import { ErrorResponse } from '../../../types/requests';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';

@Component({
  selector: 'login',
  imports: [
    FormsModule,
    InputText,
    ReactiveFormsModule,
    WindowMaximizeIcon,
    ButtonIcon,
    ButtonLabel,
    ButtonDirective,
    Ripple,
    Message
  ],
  templateUrl: './login.component.html',
  styleUrl: './login.component.css',
  standalone: true
})
export class LoginComponent {
  loginForm: FormGroup
  error = false
  errorMessage = ''
  #destroyRef = inject(DestroyRef);

  constructor(
    private formBuilder: FormBuilder,
    private authService: AuthService,
    private router: Router
  ) {
    this.loginForm = this.formBuilder.group({
      email: ['', Validators.compose([Validators.required, Validators.email])],
      password: ['', Validators.required],
    })
  }

  onSubmit() {
    if (this.loginForm.valid) {
      this.authService.login(
        this.loginForm.get('email')?.value,
        this.loginForm.get('password')?.value,
      ).pipe(
        takeUntilDestroyed(this.#destroyRef),
        catchError(err => {
          this.error = true
          const errResonse = err.error as ErrorResponse
          if (errResonse.statusCode === 403) {
            this.errorMessage = 'Credenziali non corrette.'
          } else {
            this.errorMessage = 'È avvenuto un errore, contattare l\'amministratore'
          }

          return throwError(() => err)
        }),
        tap(() => true)
      ).subscribe(res => {
        this.router.navigate(['/admin'])
      })
    }
  }
}
