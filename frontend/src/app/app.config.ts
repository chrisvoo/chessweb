import { ApplicationConfig, provideZoneChangeDetection } from '@angular/core';
import { provideRouter } from '@angular/router';
import { withComponentInputBinding } from '@angular/router';
import { routes } from './app.routes';
import { providePrimeNG } from 'primeng/config';
import CustomPreset from './app.style-preset'
import { provideHttpClient } from '@angular/common/http';

export const appConfig: ApplicationConfig = {
  providers: [
    provideZoneChangeDetection({eventCoalescing: true}),
    provideRouter(routes, withComponentInputBinding()),
    provideHttpClient(),
    providePrimeNG({
      theme: {
        preset: CustomPreset,
        options: {
          darkModeSelector: false
        }
      }
    })
  ],
};
