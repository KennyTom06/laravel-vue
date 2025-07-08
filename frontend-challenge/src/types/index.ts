// Base interfaces for all entities
export interface BaseEntity {
  id: number
  created_at: string
  updated_at: string
}

// Amortization interface
export interface Amortization extends BaseEntity {
  amount: number
  state: 'pending' | 'paid' | 'delayed'
  due_date: string
  project_id: number
  promoter_id: number
  project?: Project
  promoter?: Promoter
}

// Project interface
export interface Project extends BaseEntity {
  name: string
  description?: string
  total_amount?: number
  status?: string
}

// Promoter interface
export interface Promoter extends BaseEntity {
  name: string
  email: string
  phone?: string
  address?: string
}

// Payment interface
export interface Payment extends BaseEntity {
  amount: number
  state: 'pending' | 'completed' | 'failed' | 'delayed'
  payment_date?: string
  user_id?: number
  amortization_id?: number
  amortization?: Amortization
}

// Pagination interface
export interface Pagination {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number
  to: number
}

// API Response interfaces
export interface ApiResponse<T> {
  data: T
  message?: string
  status?: string
}

export interface PaginatedResponse<T> extends ApiResponse<T[]> {
  pagination: Pagination
}

// Filter interfaces
export interface AmortizationFilters {
  state?: string
  amount_from?: number
  amount_to?: number
  amount?: number // backward compatibility
  page?: number
  per_page?: number
}

export interface PaymentFilters {
  state?: string
  amount_from?: number
  amount_to?: number
  amount?: number // backward compatibility
  user?: string
  page?: number
  per_page?: number
}

export interface ProjectFilters {
  name?: string
  page?: number
  per_page?: number
}

// Store state interface
export interface AmortizationStoreState {
  amortizations: Amortization[]
  amortization: Amortization | null
  payments: Payment[]
  projects: Project[]
  promoters: Promoter[]
  pagination: Pagination | null
  loading: boolean
  error: string | null
}

// Vuex context types
export interface ActionContext {
  commit: (mutation: string, payload?: any) => void
  state: AmortizationStoreState
  getters: any
  dispatch: (action: string, payload?: any) => Promise<any>
} 
