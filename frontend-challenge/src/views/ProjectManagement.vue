<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="px-4 py-6 sm:px-0">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-4">
            <router-link
              to="/"
              class="text-indigo-600 hover:text-indigo-900"
            >
              ← Back to Amortizations
            </router-link>
            <h1 class="text-3xl font-bold text-gray-900">Project Management</h1>
          </div>
          <button
            @click="showCreateModal = true"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium"
          >
            Add Project
          </button>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Filter Projects</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Project Name</label>
            <input
              v-model="filters.name"
              @input="fetchProjects"
              type="text"
              placeholder="Search by name"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            />
          </div>
          <div class="flex items-end">
            <button
              @click="fetchProjects"
              class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium"
            >
              Apply Filters
            </button>
          </div>
        </div>
      </div>

      <!-- Projects List -->
      <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <div class="px-4 py-5 sm:px-6">
          <h3 class="text-lg leading-6 font-medium text-gray-900">Projects List</h3>
          <p class="mt-1 max-w-2xl text-sm text-gray-500">All available projects.</p>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Promoter</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Wallet Balance</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Investment Goal</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created At</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="project in projects" :key="project.id">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ project.id }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ project.name }}</td>
                <td class="px-6 py-4 text-sm text-gray-900">{{ project.description || 'N/A' }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ getPromoterName(project.promoter_id) }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${{ project.wallet_balance?.toLocaleString() || '0' }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${{ project.investment_goal?.toLocaleString() || '0' }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ new Date(project.created_at).toLocaleDateString() }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                  <button
                    @click="editProject(project)"
                    class="text-indigo-600 hover:text-indigo-900 mr-4"
                  >
                    Edit
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="pagination" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
        <div class="flex-1 flex justify-between sm:hidden">
          <button
            @click="changePage(pagination.current_page - 1)"
            :disabled="pagination.current_page <= 1"
            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Previous
          </button>
          <button
            @click="changePage(pagination.current_page + 1)"
            :disabled="pagination.current_page >= pagination.last_page"
            class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Next
          </button>
        </div>
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
          <div>
            <p class="text-sm text-gray-700">
              Showing
              <span class="font-medium">{{ pagination.from }}</span>
              to
              <span class="font-medium">{{ pagination.to }}</span>
              of
              <span class="font-medium">{{ pagination.total }}</span>
              results
            </p>
          </div>
          <div>
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
              <button
                @click="changePage(pagination.current_page - 1)"
                :disabled="pagination.current_page <= 1"
                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span class="sr-only">Previous</span>
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                  <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
              </button>
              
              <template v-for="page in getPageNumbers()" :key="page">
                <button
                  v-if="page !== '...'"
                  @click="changePage(page)"
                  :class="[
                    page === pagination.current_page
                      ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                      : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                    'relative inline-flex items-center px-4 py-2 border text-sm font-medium'
                  ]"
                >
                  {{ page }}
                </button>
                <span
                  v-else
                  class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700"
                >
                  ...
                </span>
              </template>
              
              <button
                @click="changePage(pagination.current_page + 1)"
                :disabled="pagination.current_page >= pagination.last_page"
                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span class="sr-only">Next</span>
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                  <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
              </button>
            </nav>
          </div>
        </div>
      </div>

      <!-- Create/Edit Project Modal -->
      <div v-if="showCreateModal || showEditModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
          <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
              {{ showEditModal ? `Edit Project #${editingProject?.id}` : 'Create New Project' }}
            </h3>
            
            <form @submit.prevent="showEditModal ? updateProject() : createProject()" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Name</label>
                <input
                  v-model="projectForm.name"
                  type="text"
                  required
                  maxlength="30"
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                />
                <p class="mt-1 text-xs text-gray-500">{{ projectForm.name.length }}/30 characters</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Description</label>
                <textarea
                  v-model="projectForm.description"
                  rows="3"
                  required
                  maxlength="400"
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                ></textarea>
                <p class="mt-1 text-xs text-gray-500">{{ projectForm.description.length }}/400 characters</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Promoter</label>
                <select
                  v-model="projectForm.promoter_id"
                  required
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                >
                  <option value="">Select a promoter</option>
                  <option v-for="promoter in promoters" :key="promoter.id" :value="promoter.id">
                    {{ promoter.name }}
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Wallet Balance</label>
                <input
                  v-model="projectForm.wallet_balance"
                  type="number"
                  min="0"
                  placeholder="0"
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Investment Goal</label>
                <input
                  v-model="projectForm.investment_goal"
                  type="number"
                  min="0"
                  placeholder="0"
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                />
              </div>

              <div class="flex space-x-4">
                <button
                  type="submit"
                  :disabled="localLoading"
                  class="bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-400 text-white px-4 py-2 rounded-md text-sm font-medium"
                >
                  {{ localLoading ? 'Saving...' : (showEditModal ? 'Update' : 'Create') }}
                </button>
                <button
                  type="button"
                  @click="closeModal"
                  class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium"
                >
                  Cancel
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="text-center py-8">
        <div class="inline-flex items-center px-4 py-2 font-semibold leading-6 text-sm shadow rounded-md text-white bg-indigo-500 hover:bg-indigo-400 transition ease-in-out duration-150 cursor-not-allowed">
          <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          Loading projects...
        </div>
      </div>

      <!-- Success/Error Messages -->
      <div v-if="message" class="bg-green-50 border border-green-200 rounded-md p-4 mb-6">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-green-800">Success</h3>
            <div class="mt-2 text-sm text-green-700">{{ message }}</div>
          </div>
        </div>
      </div>

      <div v-if="errorMessage" class="bg-red-50 border border-red-200 rounded-md p-4 mb-6">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">Error</h3>
            <div class="mt-2 text-sm text-red-700">{{ errorMessage }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useStore } from 'vuex'
import axios from 'axios'

const store = useStore()

const filters = ref({
  name: ''
})

const showCreateModal = ref(false)
const showEditModal = ref(false)
const editingProject = ref(null)
const localLoading = ref(false)
const message = ref('')
const errorMessage = ref('')

const projectForm = ref({
  name: '',
  description: '',
  promoter_id: '',
  wallet_balance: 0,
  investment_goal: 0
})

const projects = computed(() => store.getters.projects)
const promoters = computed(() => store.getters.promoters)
const pagination = computed(() => store.getters.pagination)
const loading = computed(() => store.getters.loading)
const error = computed(() => store.getters.error)

const fetchProjects = () => {
  store.dispatch('fetchProjects', filters.value)
}

const fetchPromoters = () => {
  store.dispatch('fetchPromoters')
}

const changePage = (page: number) => {
  if (page >= 1 && page <= pagination.value?.last_page) {
    store.dispatch('fetchProjects', { ...filters.value, page })
  }
}

const getPageNumbers = () => {
  if (!pagination.value) return []
  
  const current = pagination.value.current_page
  const last = pagination.value.last_page
  const delta = 2
  const range = []
  const rangeWithDots = []

  for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
    range.push(i)
  }

  if (current - delta > 2) {
    rangeWithDots.push(1, '...')
  } else {
    rangeWithDots.push(1)
  }

  rangeWithDots.push(...range)

  if (current + delta < last - 1) {
    rangeWithDots.push('...', last)
  } else {
    rangeWithDots.push(last)
  }

  return rangeWithDots
}

const editProject = (project: any) => {
  editingProject.value = project
  projectForm.value = {
    name: project.name,
    description: project.description || '',
    promoter_id: project.promoter_id || '',
    wallet_balance: project.wallet_balance || 0,
    investment_goal: project.investment_goal || 0
  }
  showEditModal.value = true
}

const closeModal = () => {
  showCreateModal.value = false
  showEditModal.value = false
  editingProject.value = null
  projectForm.value = {
    name: '',
    description: '',
    promoter_id: '',
    wallet_balance: 0,
    investment_goal: 0
  }
}

const createProject = async () => {
  localLoading.value = true
  message.value = ''
  errorMessage.value = ''
  
  // Ensure all required fields are set
  const projectData = {
    name: projectForm.value.name,
    description: projectForm.value.description,
    promoter_id: projectForm.value.promoter_id,
    wallet_balance: projectForm.value.wallet_balance || 0,
    investment_goal: projectForm.value.investment_goal || 0
  }
  
  console.log('Sending project data:', projectData)
  
  try {
    await axios.post('/projects', projectData)
    message.value = 'Project created successfully!'
    closeModal()
    fetchProjects() // Refresh the list
  } catch (error: any) {
    console.error('Error creating project:', error.response?.data)
    if (error.response?.data?.errors) {
      // Handle validation errors
      const errors = error.response.data.errors
      const errorMessages = Object.values(errors).flat()
      errorMessage.value = errorMessages.join(', ')
    } else {
      errorMessage.value = error.response?.data?.message || 'An error occurred'
    }
  } finally {
    localLoading.value = false
  }
}

const updateProject = async () => {
  localLoading.value = true
  message.value = ''
  errorMessage.value = ''
  
  // Ensure all required fields are set
  const projectData = {
    name: projectForm.value.name,
    description: projectForm.value.description,
    promoter_id: projectForm.value.promoter_id,
    wallet_balance: projectForm.value.wallet_balance || 0,
    investment_goal: projectForm.value.investment_goal || 0
  }
  
  try {
    await axios.put(`/projects/${editingProject.value.id}`, projectData)
    message.value = 'Project updated successfully!'
    closeModal()
    fetchProjects() // Refresh the list
  } catch (error: any) {
    console.error('Error updating project:', error.response?.data)
    if (error.response?.data?.errors) {
      // Handle validation errors
      const errors = error.response.data.errors
      const errorMessages = Object.values(errors).flat()
      errorMessage.value = errorMessages.join(', ')
    } else {
      errorMessage.value = error.response?.data?.message || 'An error occurred'
    }
  } finally {
    localLoading.value = false
  }
}

const getPromoterName = (promoterId: number) => {
  const promoter = promoters.value.find(p => p.id === promoterId)
  return promoter ? promoter.name : `Promoter #${promoterId}`
}

onMounted(() => {
  fetchProjects()
  fetchPromoters()
})
</script> 