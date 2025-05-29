import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { map, Observable } from 'rxjs';
import { formatDate, parseISO } from 'date-fns'
import { AuthService } from '../auth/auth.service';
import { ListItemsRequest, ListTagsParams } from '../../../types/requests';
import { Tag } from '../../../types/models';

@Injectable({
  providedIn: 'root'
})
export class TagsService {
  ENDPOINT = '/api/tags'

  constructor(
    private readonly http: HttpClient,
    private readonly authService: AuthService,
  ) { }

  listTags(params: Partial<ListTagsParams>): Observable<ListItemsRequest<Tag>> {
    const token = this.authService.getToken();

    return this.http.get<ListItemsRequest<Tag>>(
      this.ENDPOINT,
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
            items: res.data.items.map((tag: Tag) => {
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
}
