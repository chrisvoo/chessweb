import { ResolveFn } from '@angular/router';
import {inject} from '@angular/core';
import {ArticlesService} from '../services/articles/articles.service';
import {catchError, of} from 'rxjs';
import {ViewArticleResponse} from '../../types/requests';

export const articleResolver: ResolveFn<ViewArticleResponse | undefined> = (route, state) => {
  const articleService = inject(ArticlesService)
  const id = route.queryParamMap.get('id');

  if (id) {
    return articleService.viewArticle(+id, true).pipe(
      catchError(error => {
        console.error('Error in article resolver:', error);
        // On error (e.g., article not found), resolve to undefined
        return of(undefined);
      })
    )
  }

  return of(undefined);
};
