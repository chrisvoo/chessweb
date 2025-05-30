import { User } from './models';

export interface LoginResponse {
  statusCode: number
  data: {
    access_token: string
    expires_in: number
    user: User
  }
}

export interface ErrorResponse {
  statusCode: number
  data: {
    success: boolean
    message: string
    code: number
  }
  error: {
    type: string
    description: string
  }
}

export type RefreshTokenResponse = Omit<LoginResponse, "user">

export interface ListItemsRequest<T> {
  statusCode: number
  data: {
    items: T[]
  },
  total_items: number,
  total_pages: number,
  has_more_items: boolean,
  page: number
  page_size: number
}

export interface ManagedEntityResponse {
  statusCode: number
  data: {
    success: boolean
    message: string
    code: number
    affected_rows: number
    entity_id: number
  }
}

export interface SortParams {
  sort_by: string
  sort_order: 'asc' | 'desc'
}

export interface PaginationParams {
  page: number
  page_size: number
}

export interface ListTagsParams extends SortParams, PaginationParams {
  name: string
}
