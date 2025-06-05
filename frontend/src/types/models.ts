export interface User {
  id: number
  email: string
  first_name: string
  last_name: string
  is_admin: boolean
  created_at: Date
  updated_at: Date|null
}

export interface NamedEntity {
  id: number|null
  name: string
  created_at: string
  updated_at: string|null
}

export interface Article {
  id: number|null
  title: string
  content: string
  author_id: number
  created_at: string
  updated_at: string|null
}
