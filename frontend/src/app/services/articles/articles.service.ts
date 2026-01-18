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
import {ArticleWithTagsAndCategories} from '../../../types/models';

@Injectable({
  providedIn: 'root'
})
export class ArticlesService {
  LIST_ENDPOINT = '/api/articles'
  VIEW_SINGLE_ARTICLE_ENDPOINT = '/api/article/:ref';
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
            items: res.data.items,
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

  updateArticle(article: ArticleWithTagsAndCategories): Observable<ManagedEntityResponse> {
    return this.http.put<ManagedEntityResponse>(
      this.VIEW_SINGLE_ARTICLE_ENDPOINT.replace(':ref', `${article.id}`),
      { ...article },
      {
        headers: {
          'Authorization': `Bearer ${this.authService.getToken()}`
        }
      }
    )
  }

  viewArticleByRef(slugOrId: number|string, extraInfo: boolean = false): Observable<ViewArticleResponse> {
    return this.http.get<ViewArticleResponse>(
      this.VIEW_SINGLE_ARTICLE_ENDPOINT.replace(':ref', `${slugOrId}`),
      {
        params: {
          extra_info: extraInfo
        }
      }
    )
  }

  deleteArticle(articleId: number): Observable<ManagedEntityResponse> {
    return this.http.delete<ManagedEntityResponse>(
      this.VIEW_SINGLE_ARTICLE_ENDPOINT.replace(':ref', `${articleId}`),
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
