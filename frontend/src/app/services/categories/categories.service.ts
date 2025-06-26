import { Injectable } from '@angular/core';
import {HttpClient} from '@angular/common/http';
import {AuthService} from '../auth/auth.service';
import {
  CategoryStatsItem,
  ListAllItemsParams,
  ListAllItemsResponse,
  ManagedEntityResponse, TagCloudItem
} from '../../../types/requests';
import {map, Observable} from 'rxjs';
import {NamedEntity} from '../../../types/models';
import {formatDate, parseISO} from 'date-fns';

@Injectable({
  providedIn: 'root'
})
export class CategoriesService {
  LIST_ENDPOINT = '/api/categories'
  UPDATE_DELETE_ENDPOINT = '/api/category/:id';
  CREATE_ENDPOINT = '/api/category';
  CAT_GROUPED_ENDPOINT = '/api/categories/stats';

  constructor(
    private readonly http: HttpClient,
    private readonly authService: AuthService,
  ) { }

  getCategoriesStats(): Observable<ListAllItemsResponse<CategoryStatsItem>> {
    return this.http.get<ListAllItemsResponse<CategoryStatsItem>>(
      this.CAT_GROUPED_ENDPOINT
    )
  }

  listCategories(params: ListAllItemsParams): Observable<ListAllItemsResponse<NamedEntity>> {
    const token = this.authService.getToken();

    return this.http.get<ListAllItemsResponse<NamedEntity>>(
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
          ...res,
          data: {
            items: res.data.items.map((category: NamedEntity) => {
              const { created_at, updated_at } = category;
              const createdAtFormatted = formatDate(parseISO(created_at),  'dd-MM-yyyy');
              const updatedAtFormatted = updated_at ? formatDate(parseISO(updated_at),  'dd-MM-yyyy') : ''
              return { ...category, created_at: createdAtFormatted, updated_at: updatedAtFormatted }
            })
          }
        }
      })
    )
  }

  updateCategory(category: NamedEntity): Observable<ManagedEntityResponse> {
    return this.http.put<ManagedEntityResponse>(
      this.UPDATE_DELETE_ENDPOINT.replace(':id', `${category.id}`),
      { name: category.name },
      {
        headers: {
          'Authorization': `Bearer ${this.authService.getToken()}`
        }
      }
    )
  }

  deleteCategory(categoryId: number): Observable<ManagedEntityResponse> {
    return this.http.delete<ManagedEntityResponse>(
      this.UPDATE_DELETE_ENDPOINT.replace(':id', `${categoryId}`),
      {
        headers: {
          'Authorization': `Bearer ${this.authService.getToken()}`
        }
      }
    )
  }

  createCategory(name: string): Observable<ManagedEntityResponse> {
    return this.http.post<ManagedEntityResponse>(
      this.CREATE_ENDPOINT,
      { name },
      {
        headers: {
          'Authorization': `Bearer ${this.authService.getToken()}`
        }
      }
    )
  }
}
