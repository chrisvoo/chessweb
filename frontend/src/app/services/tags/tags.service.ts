import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { map, Observable } from 'rxjs';
import { formatDate, parseISO } from 'date-fns'
import { AuthService } from '../auth/auth.service';
import {
  ListAllItemsResponse,
  ListAllItemsParams,
  ManagedEntityResponse, TagCloudItem
} from '../../../types/requests';
import {NamedEntity} from '../../../types/models';

@Injectable({
  providedIn: 'root'
})
export class TagsService {
  LIST_ENDPOINT = '/api/tags'
  UPDATE_DELETE_ENDPOINT = '/api/tag/:id';
  CREATE_ENDPOINT = '/api/tag';
  TAG_CLOUD_ENDPOINT = '/api/tags/stats';

  constructor(
    private readonly http: HttpClient,
    private readonly authService: AuthService,
  ) { }

  getTagCloud(): Observable<ListAllItemsResponse<TagCloudItem>> {
    return this.http.get<ListAllItemsResponse<TagCloudItem>>(
      this.TAG_CLOUD_ENDPOINT
    )
  }

  listTags(params: ListAllItemsParams): Observable<ListAllItemsResponse<NamedEntity>> {
    return this.http.get<ListAllItemsResponse<NamedEntity>>(
      this.LIST_ENDPOINT,
      {
        params: { ...params }
      }
    ).pipe(
      map((res) => {
        return {
          ...res,
          data: {
            items: res.data.items.map((tag: NamedEntity) => {
              const { created_at, updated_at } = tag;
              const createdAtFormatted = formatDate(parseISO(created_at),  'dd-MM-yyyy');
              const updatedAtFormatted = updated_at ? formatDate(parseISO(updated_at),  'dd-MM-yyyy') : ''
              return { ...tag, created_at: createdAtFormatted, updated_at: updatedAtFormatted }
            })
          }
        }
      })
    )
  }

  updateTag(tag: NamedEntity): Observable<ManagedEntityResponse> {
    return this.http.put<ManagedEntityResponse>(
      this.UPDATE_DELETE_ENDPOINT.replace(':id', `${tag.id}`),
      { name: tag.name },
      {
        headers: {
          'Authorization': `Bearer ${this.authService.getToken()}`
        }
      }
    )
  }

  deleteTag(tagId: number): Observable<ManagedEntityResponse> {
    return this.http.delete<ManagedEntityResponse>(
      this.UPDATE_DELETE_ENDPOINT.replace(':id', `${tagId}`),
      {
        headers: {
          'Authorization': `Bearer ${this.authService.getToken()}`
        }
      }
    )
  }

  createTag(name: string): Observable<ManagedEntityResponse> {
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
