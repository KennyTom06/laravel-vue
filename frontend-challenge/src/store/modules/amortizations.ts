import axios from 'axios'
import type { 
  Amortization, 
  Payment, 
  Project, 
  Promoter, 
  Pagination,
  AmortizationStoreState,
  AmortizationFilters,
  PaymentFilters,
  ProjectFilters,
  PaginatedResponse,
  ApiResponse,
  ActionContext 
} from '@/types'

export default {
  state: (): AmortizationStoreState => ({
    amortizations: [],
    amortization: null,
    payments: [],
    projects: [],
    promoters: [],
    pagination: null,
    loading: false,
    error: null
  }),
  
  mutations: {
    SET_AMORTIZATIONS(state: AmortizationStoreState, amortizations: Amortization[]): void {
      state.amortizations = amortizations
    },
    SET_AMORTIZATION(state: AmortizationStoreState, amortization: Amortization): void {
      state.amortization = amortization
    },
    SET_PAYMENTS(state: AmortizationStoreState, payments: Payment[]): void {
      state.payments = payments
    },
    SET_PROJECTS(state: AmortizationStoreState, projects: Project[]): void {
      state.projects = projects
    },
    SET_PROMOTERS(state: AmortizationStoreState, promoters: Promoter[]): void {
      state.promoters = promoters
    },
    SET_PAGINATION(state: AmortizationStoreState, pagination: Pagination): void {
      state.pagination = pagination
    },
    SET_LOADING(state: AmortizationStoreState, loading: boolean): void {
      state.loading = loading
    },
    SET_ERROR(state: AmortizationStoreState, error: string | null): void {
      state.error = error
    }
  },
  
  actions: {
    async fetchAmortizations({ commit }: ActionContext, filters: AmortizationFilters = {}): Promise<void> {
      commit('SET_LOADING', true)
      try {
        const params = new URLSearchParams()
        if (filters.state) params.append('state', filters.state)
        if (filters.amount_from) params.append('amount_from', filters.amount_from.toString())
        if (filters.amount_to) params.append('amount_to', filters.amount_to.toString())
        if (filters.amount) params.append('amount', filters.amount.toString()) // backward compatibility
        if (filters.page) params.append('page', filters.page.toString())
        if (filters.per_page) params.append('per_page', filters.per_page.toString())
        
        const response = await axios.get<PaginatedResponse<Amortization>>(`/amortizations?${params}`)
        commit('SET_AMORTIZATIONS', response.data.data)
        if (response.data.pagination) {
          commit('SET_PAGINATION', response.data.pagination)
        }
      } catch (error: any) {
        const errorMessage = error?.response?.data?.message || error?.message || 'An error occurred'
        commit('SET_ERROR', errorMessage)
      } finally {
        commit('SET_LOADING', false)
      }
    },
    
    async fetchAmortization({ commit }: ActionContext, id: number): Promise<void> {
      commit('SET_LOADING', true)
      try {
        const response = await axios.get<ApiResponse<Amortization>>(`/amortizations/${id}`)
        commit('SET_AMORTIZATION', response.data.data)
      } catch (error: any) {
        const errorMessage = error?.response?.data?.message || error?.message || 'An error occurred'
        commit('SET_ERROR', errorMessage)
      } finally {
        commit('SET_LOADING', false)
      }
    },
    
    async fetchPayments({ commit }: ActionContext, filters: PaymentFilters = {}): Promise<void> {
      commit('SET_LOADING', true)
      try {
        const params = new URLSearchParams()
        if (filters.state) params.append('state', filters.state)
        if (filters.amount_from) params.append('amount_from', filters.amount_from.toString())
        if (filters.amount_to) params.append('amount_to', filters.amount_to.toString())
        if (filters.amount) params.append('amount', filters.amount.toString()) // backward compatibility
        if (filters.user) params.append('user', filters.user)
        if (filters.page) params.append('page', filters.page.toString())
        if (filters.per_page) params.append('per_page', filters.per_page.toString())
        
        const response = await axios.get<PaginatedResponse<Payment>>(`/payments?${params}`)
        commit('SET_PAYMENTS', response.data.data)
        if (response.data.pagination) {
          commit('SET_PAGINATION', response.data.pagination)
        }
      } catch (error: any) {
        const errorMessage = error?.response?.data?.message || error?.message || 'An error occurred'
        commit('SET_ERROR', errorMessage)
      } finally {
        commit('SET_LOADING', false)
      }
    },
    
    async fetchProjects({ commit }: ActionContext, filters: ProjectFilters = {}): Promise<void> {
      commit('SET_LOADING', true)
      try {
        const params = new URLSearchParams()
        if (filters.name) params.append('name', filters.name)
        if (filters.page) params.append('page', filters.page.toString())
        if (filters.per_page) params.append('per_page', filters.per_page.toString())
        
        const response = await axios.get<PaginatedResponse<Project>>(`/projects?${params}`)
        commit('SET_PROJECTS', response.data.data)
        if (response.data.pagination) {
          commit('SET_PAGINATION', response.data.pagination)
        }
      } catch (error: any) {
        const errorMessage = error?.response?.data?.message || error?.message || 'An error occurred'
        commit('SET_ERROR', errorMessage)
      } finally {
        commit('SET_LOADING', false)
      }
    },
    
    async fetchPromoters({ commit }: ActionContext): Promise<void> {
      commit('SET_LOADING', true)
      try {
        const response = await axios.get<ApiResponse<Promoter[]>>('/promoters')
        commit('SET_PROMOTERS', response.data.data)
      } catch (error: any) {
        const errorMessage = error?.response?.data?.message || error?.message || 'An error occurred'
        commit('SET_ERROR', errorMessage)
      } finally {
        commit('SET_LOADING', false)
      }
    },
    
    async payAmortizations({ commit }: ActionContext, date: string | null = null): Promise<any> {
      commit('SET_LOADING', true)
      try {
        const payload = date ? { date } : {}
        const response = await axios.post<ApiResponse<any>>('/amortizations/pay', payload)
        return response.data
      } catch (error: any) {
        const errorMessage = error?.response?.data?.message || error?.message || 'An error occurred'
        commit('SET_ERROR', errorMessage)
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    }
  },
  
  getters: {
    amortizations: (state: AmortizationStoreState): Amortization[] => state.amortizations,
    amortization: (state: AmortizationStoreState): Amortization | null => state.amortization,
    payments: (state: AmortizationStoreState): Payment[] => state.payments,
    projects: (state: AmortizationStoreState): Project[] => state.projects,
    promoters: (state: AmortizationStoreState): Promoter[] => state.promoters,
    pagination: (state: AmortizationStoreState): Pagination | null => state.pagination,
    loading: (state: AmortizationStoreState): boolean => state.loading,
    error: (state: AmortizationStoreState): string | null => state.error
  }
} 