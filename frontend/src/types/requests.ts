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

export interface ListAllItemsResponse<T> {
  statusCode: number
  data: {
    items: T[]
  }
}

export interface ListPaginatedItemsResponse<T> extends ListAllItemsResponse<T> {
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

export interface NameParam {
  name: string
}

export type ListItemsParams<T extends boolean> =
  T extends true ? SortParams & NameParam & { all_items: boolean }
  : SortParams & NameParam & PaginationParams;

