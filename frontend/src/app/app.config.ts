import {ApplicationConfig, LOCALE_ID, provideZoneChangeDetection} from '@angular/core';
import { provideRouter } from '@angular/router';
import { withComponentInputBinding } from '@angular/router';
import { routes } from './app.routes';
import { providePrimeNG } from 'primeng/config';
import CustomPreset from './app.style-preset'
import { provideHttpClient } from '@angular/common/http';
import localeIt from '@angular/common/locales/it';
import {registerLocaleData} from '@angular/common';

registerLocaleData(localeIt);

export const appConfig: ApplicationConfig = {
  providers: [
    { provide: LOCALE_ID, useValue: 'it' },
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
