export interface User {
  id: number
  email: string
  first_name: string
  last_name: string
  is_admin: boolean
  created_at: Date
  updated_at: Date|null
}

