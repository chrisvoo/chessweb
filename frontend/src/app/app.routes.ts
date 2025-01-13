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
  },
  {
    path: 'corsi',
    loadComponent: () => import('./main/pages/courses/courses.component').then(m => m.CoursesComponent)
  },
  {
    path: 'documenti',
    loadComponent: () => import('./main/pages/documents/documents.component').then(m => m.DocumentsComponent)
  },
  {
    path: 'tesseramento',
    loadComponent: () => import('./main/pages/membership/membership.component').then(m => m.MembershipComponent)
  }
];
