import { Injectable } from '@angular/core';
import {HttpClient} from '@angular/common/http';
import {AuthService} from '../auth/auth.service';
import {
  ListPaginatedItemsResponse,
  ListPaginatedParams,
  ManagedEntityResponse,
  ViewArticleResponse
} from '../../../types/requests';
import {map, Observable} from 'rxjs';
import {Article, NamedEntity, ArticleWithTagsAndCategories} from '../../../types/models';
import {formatDate, parseISO} from 'date-fns';

@Injectable({
  providedIn: 'root'
})
export class ArticlesService {
  LIST_ENDPOINT = '/api/articles'
  MANAGE_SINGLE_ARTICLE_ENDPOINT = '/api/article/:id';
  CREATE_ENDPOINT = '/api/article';

  constructor(
    private readonly http: HttpClient,
    private readonly authService: AuthService,
  ) { }

  listArticles(params: ListPaginatedParams): Observable<ListPaginatedItemsResponse<ArticleWithTagsAndCategories>> {
    return this.http.get<ListPaginatedItemsResponse<ArticleWithTagsAndCategories>>(
      this.LIST_ENDPOINT,
      {
        params: { ...params }
      }
    ).pipe(
      map((res) => {
        return {
          statusCode: res.statusCode,
          data: {
            items: res.data.items.map((article: ArticleWithTagsAndCategories) => {
              const { created_at, updated_at } = article;
              const createdAtFormatted = created_at !== "0000-00-00 00:00:00" && created_at
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
      this.MANAGE_SINGLE_ARTICLE_ENDPOINT.replace(':id', `${article.id}`),
      { ...article },
      {
        headers: {
          'Authorization': `Bearer ${this.authService.getToken()}`
        }
      }
    )
  }

  viewArticle(articleId: number, extraInfo: boolean = false): Observable<ViewArticleResponse> {
    return this.http.get<ViewArticleResponse>(
      this.MANAGE_SINGLE_ARTICLE_ENDPOINT.replace(':id', `${articleId}`),
      {
        params: {
          extra_info: extraInfo
        }
      }
    )
  }

  deleteArticle(articleId: number): Observable<ManagedEntityResponse> {
    return this.http.delete<ManagedEntityResponse>(
      this.MANAGE_SINGLE_ARTICLE_ENDPOINT.replace(':id', `${articleId}`),
      {
        headers: {
          'Authorization': `Bearer ${this.authService.getToken()}`
        }
      }
    )
  }

  createArticle(article: ArticleWithTagsAndCategories): Observable<ManagedEntityResponse> {
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
