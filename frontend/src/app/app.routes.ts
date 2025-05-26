import { Routes } from '@angular/router';
import { HomeComponent } from './main/pages/home/home.component';
import { NotFoundComponent } from './main/pages/not-found/not-found.component';
import { AdminComponent } from './admin/admin.component';
import { environment } from '../environments/environment';

const mainTitle = environment.TITLE

export const routes: Routes = [
  { path: '', redirectTo: 'home', pathMatch: 'full' },
  {
    path: 'home',
    title: `${mainTitle} - Home`,
    component: HomeComponent
  },
  {
    path: 'tornei',
    title: `${mainTitle} - Tornei`,
    loadComponent: () => import('./main/pages/tournaments/tournaments.component')
      .then(m => m.TournamentsComponent)
  },
  {
    path: 'corsi',
    title: `${mainTitle} - Corsi`,
    loadComponent: () => import('./main/pages/courses/courses.component')
      .then(m => m.CoursesComponent)
  },
  {
    path: 'documenti',
    title: `${mainTitle} - Documenti`,
    loadComponent: () => import('./main/pages/documents/documents.component')
      .then(m => m.DocumentsComponent)
  },
  {
    path: 'tesseramento',
    title: `${mainTitle} - Tesseramento`,
    loadComponent: () => import('./main/pages/membership/membership.component')
      .then(m => m.MembershipComponent)
  },
  {
    path: 'news',
    title: `${mainTitle} - News`,
    loadComponent: () => import('./main/pages/news/news.component')
      .then(m => m.NewsComponent)
  },
  {
    path: 'galleria',
    title: `${mainTitle} - Galleria`,
    loadComponent: () => import('./main/pages/gallery/gallery.component')
      .then(m => m.GalleryComponent)
  },
  {
    path: 'credits',
    title: `${mainTitle} - Riconoscimenti`,
    loadComponent: () => import('./main/pages/credits/credits.component')
      .then(m => m.CreditsComponent)
  },
  {
    path: 'admin',
    title: `${mainTitle} - Admin`,
    children: [
      {
        path: '',
        loadComponent: () => import('./admin/admin.component')
          .then(m => AdminComponent)
      },
      {
        path: 'login',
        title: `${mainTitle} - Admin > Login`,
        loadComponent: () => import('./admin/login/login.component')
          .then(m => m.LoginComponent)
      },
      {
        path: 'articoli',
        title: `${mainTitle} - Admin > Articoli`,
        loadComponent: () => import('./admin/admin-articles/admin-articles.component')
          .then(m => m.AdminArticlesComponent)
      },
      {
        path: 'categorie',
        title: `${mainTitle} - Admin > Categorie`,
        loadComponent: () => import('./admin/admin-categories/admin-categories.component')
          .then(m => m.AdminCategoriesComponent)
      },
      {
        path: 'tags',
        title: `${mainTitle} - Admin > Tags`,
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
