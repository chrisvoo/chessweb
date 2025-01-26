import { Routes } from '@angular/router';
import {HomeComponent} from './main/pages/home/home.component';
import {NotFoundComponent} from './main/pages/not-found/not-found.component';

export const routes: Routes = [
  { path: '', redirectTo: 'home', pathMatch: 'full' },
  {
    path: 'home',
    component: HomeComponent
  },
  {
    path: 'tornei',
    loadComponent: () => import('./main/pages/tournaments/tournaments.component')
      .then(m => m.TournamentsComponent)
  },
  {
    path: 'corsi',
    loadComponent: () => import('./main/pages/courses/courses.component')
      .then(m => m.CoursesComponent)
  },
  {
    path: 'documenti',
    loadComponent: () => import('./main/pages/documents/documents.component')
      .then(m => m.DocumentsComponent)
  },
  {
    path: 'tesseramento',
    loadComponent: () => import('./main/pages/membership/membership.component')
      .then(m => m.MembershipComponent)
  },
  {
    path: 'news',
    loadComponent: () => import('./main/pages/news/news.component')
      .then(m => m.NewsComponent)
  },
  {
    path: 'galleria',
    loadComponent: () => import('./main/pages/gallery/gallery.component')
      .then(m => m.GalleryComponent)
  },
  {
    path: 'credits',
    loadComponent: () => import('./main/pages/credits/credits.component')
      .then(m => m.CreditsComponent)
  },
  {
    path: 'admin',
    children: [
      {
        path: '',
        redirectTo: 'articoli',
        pathMatch: 'full'
      },
      {
        path: 'articoli',
        loadComponent: () => import('./admin/admin-articles/admin-articles.component')
          .then(m => m.AdminArticlesComponent)
      },
      {
        path: 'categorie',
        loadComponent: () => import('./admin/admin-categories/admin-categories.component')
          .then(m => m.AdminCategoriesComponent)
      },
      {
        path: 'tags',
        loadComponent: () => import('./admin/admin-tags/admin-tags.component')
          .then(m => m.AdminTagsComponent)
      }
    ]
  },
  {
    path:  '**',
    component: NotFoundComponent
  }
];
