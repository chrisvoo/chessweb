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
  error: {
    type: string
    description: string
  }
}

export type RefreshTokenResponse = Omit<LoginResponse, "user">
