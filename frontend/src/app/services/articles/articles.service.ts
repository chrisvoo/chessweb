import { Injectable } from '@angular/core';
import {HttpClient} from '@angular/common/http';
import {AuthService} from '../auth/auth.service';
import {ListPaginatedItemsResponse, ListPaginatedParams, ManagedEntityResponse} from '../../../types/requests';
import {map, Observable} from 'rxjs';
import {Article, NamedEntity} from '../../../types/models';
import {formatDate, parseISO} from 'date-fns';

@Injectable({
  providedIn: 'root'
})
export class ArticlesService {
  LIST_ENDPOINT = '/api/articles'
  UPDATE_DELETE_ENDPOINT = '/api/article/:id';
  CREATE_ENDPOINT = '/api/article';

  constructor(
    private readonly http: HttpClient,
    private readonly authService: AuthService,
  ) { }

  listArticles(params: ListPaginatedParams): Observable<ListPaginatedItemsResponse<Article>> {
    const token = this.authService.getToken();

    return this.http.get<ListPaginatedItemsResponse<Article>>(
      this.LIST_ENDPOINT,
      {
        headers: {
          'Authorization': `Bearer ${token}`
        },
        params: { ...params }
      }
    ).pipe(
      map((res) => {
        return {
          statusCode: res.statusCode,
          data: {
            items: res.data.items.map((article: Article) => {
              const { created_at, updated_at } = article;
              const createdAtFormatted = created_at !== "0000-00-00 00:00:00"
                                                ? formatDate(parseISO(created_at),  'dd-MM-yyyy') : '';
              const updatedAtFormatted = updated_at
                                                ? formatDate(parseISO(updated_at),  'dd-MM-yyyy') : ''
              return { ...article, created_at: createdAtFormatted, updated_at: updatedAtFormatted }
            }),
            total_items: res.data.total_items,
            total_pages: res.data.total_pages,
            page: res.data.page,
            has_more_items: res.data.has_more_items,
            page_size: res.data.page_size
          }
        }
      })
    )
  }

  updateArticle(article: Article): Observable<ManagedEntityResponse> {
    return this.http.put<ManagedEntityResponse>(
      this.UPDATE_DELETE_ENDPOINT.replace(':id', `${article.id}`),
      { ...article },
      {
        headers: {
          'Authorization': `Bearer ${this.authService.getToken()}`
        }
      }
    )
  }

  deleteArticle(articleId: number): Observable<ManagedEntityResponse> {
    return this.http.delete<ManagedEntityResponse>(
      this.UPDATE_DELETE_ENDPOINT.replace(':id', `${articleId}`),
      {
        headers: {
          'Authorization': `Bearer ${this.authService.getToken()}`
        }
      }
    )
  }

  createArticle(article: Pick<Article, 'title' | 'content'>): Observable<ManagedEntityResponse> {
    return this.http.post<ManagedEntityResponse>(
      this.CREATE_ENDPOINT,
      { ...article },
      {
        headers: {
          'Authorization': `Bearer ${this.authService.getToken()}`
        }
      }
    )
  }
}
