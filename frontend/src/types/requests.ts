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

export type RefreshTokenResponse = {
  statusCode: number
  data: {
    access_token?: string
    expires_in?: number
  }
}

export interface ListAllItemsResponse<T> {
  statusCode: number
  data: {
    items: T[]
  }
}

export interface ListPaginatedItemsResponse<T> {
  statusCode: number
  data: {
    items: T[],
    total_items: number,
    total_pages: number,
    has_more_items: boolean,
    page: number
    page_size: number
  }
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
  sort_by: string | string[]
  sort_order: 'asc' | 'desc'
}

export interface PaginationParams {
  page: number
  page_size: number
}

export interface SearchParams {
  search_text?: string
}

export type ListPaginatedParams = SortParams & PaginationParams & SearchParams;

export type ListAllItemsParams = SortParams & { all_items: boolean }


