import { Routes } from '@angular/router';

export const routes: Routes = [
  { path: '', redirectTo: 'home', pathMatch: 'full' },
  {
    path: 'home',
    loadComponent: () => import('./main/pages/home/home.component').then(m => m.HomeComponent)
  },
  {
    path: 'tornei',
    loadComponent: () => import('./main/pages/tournaments/tournaments.component').then(m => m.TournamentsComponent)
  }
];
