export interface User {
  id: number
  email: string
  first_name: string
  last_name: string
  is_admin: boolean
  created_at: Date
  updated_at: Date|null
}

export interface Tag {
  id: number|null
  name: string
  created_at: string
  updated_at: string|null
}

export interface Category {
  id: number
  name: string
  created_at: string
  updated_at: string|null
}
