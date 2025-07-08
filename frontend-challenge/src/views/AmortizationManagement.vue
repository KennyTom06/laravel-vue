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
            <h1 class="text-3xl font-bold text-gray-900">Amortization Management</h1>
          </div>
        </div>
      </div>

      <!-- Amortization List -->
      <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Amortizations List</h3>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">State</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Promoter</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="amortization in amortizations" :key="amortization.id">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ amortization.id }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${{ amortization.amount?.toLocaleString() }}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    :class="{
                      'bg-green-100 text-green-800': amortization.state === 'paid',
                      'bg-yellow-100 text-yellow-800': amortization.state === 'pending'
                    }"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  >
                    {{ amortization.state }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ getProjectName(amortization.project_id) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ amortization.promoter?.name || `Promoter #${amortization.promoter_id}` }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                  <button
                    @click="editAmortization(amortization)"
                    class="text-indigo-600 hover:text-indigo-900"
                  >
                    Edit
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Edit Amortization Modal -->
      <div v-if="showEditModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
          <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Edit Amortization #{{ editingAmortization?.id }}</h3>
            
            <form @submit.prevent="updateAmortization" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Amount</label>
                <input
                  v-model="editForm.amount"
                  type="number"
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">State</label>
                <select
                  v-model="editForm.state"
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                >
                  <option value="pending">Pending</option>
                  <option value="paid">Paid</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Project (Protected)</label>
                <select
                  v-model="editForm.project_id"
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                  disabled
                >
                  <option v-for="project in projects" :key="project.id" :value="project.id">
                    {{ project.name }}
                  </option>
                </select>
                <p class="mt-1 text-xs text-gray-500">Project cannot be changed for security reasons</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Promoter (Protected)</label>
                <select
                  v-model="editForm.promoter_id"
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                  disabled
                >
                  <option v-for="promoter in promoters" :key="promoter.id" :value="promoter.id">
                    {{ promoter.name }}
                  </option>
                </select>
                <p class="mt-1 text-xs text-gray-500">Promoter cannot be changed for security reasons</p>
              </div>

              <div class="flex space-x-4">
                <button
                  type="submit"
                  :disabled="loading"
                  class="bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-400 text-white px-4 py-2 rounded-md text-sm font-medium"
                >
                  {{ loading ? 'Updating...' : 'Update' }}
                </button>
                <button
                  type="button"
                  @click="closeEditModal"
                  class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium"
                >
                  Cancel
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Test Cases -->
      <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Protection Test Cases</h3>
        <div class="space-y-4">
          <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
            <h4 class="font-medium text-blue-800 mb-2">Test Case 1: Try to change Project</h4>
            <p class="text-sm text-blue-700 mb-2">1. Click "Edit" on any amortization</p>
            <p class="text-sm text-blue-700 mb-2">2. Try to change the Project dropdown (it should be disabled)</p>
            <p class="text-sm text-blue-700">3. Submit the form - the Project should remain unchanged</p>
          </div>

          <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
            <h4 class="font-medium text-blue-800 mb-2">Test Case 2: Try to change Promoter</h4>
            <p class="text-sm text-blue-700 mb-2">1. Click "Edit" on any amortization</p>
            <p class="text-sm text-blue-700 mb-2">2. Try to change the Promoter dropdown (it should be disabled)</p>
            <p class="text-sm text-blue-700">3. Submit the form - the Promoter should remain unchanged</p>
          </div>

          <div class="bg-green-50 border border-green-200 rounded-md p-4">
            <h4 class="font-medium text-green-800 mb-2">Test Case 3: Valid Update</h4>
            <p class="text-sm text-green-700 mb-2">1. Click "Edit" on any amortization</p>
            <p class="text-sm text-green-700 mb-2">2. Change only Amount and State</p>
            <p class="text-sm text-green-700">3. Submit - only Amount and State should be updated</p>
          </div>
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

const amortizations = ref([])
const projects = ref([])
const promoters = ref([])
const showEditModal = ref(false)
const editingAmortization = ref(null)
const loading = ref(false)
const message = ref('')
const errorMessage = ref('')

const editForm = ref({
  amount: 0,
  state: 'pending',
  project_id: null,
  promoter_id: null,
  schedule_date: null
})

const fetchAmortizations = async () => {
  try {
    const response = await axios.get('/amortizations')
    amortizations.value = response.data.data
  } catch (error) {
    console.error('Error fetching amortizations:', error)
  }
}

const fetchProjects = async () => {
  try {
    const response = await axios.get('/projects')
    projects.value = response.data.data
  } catch (error) {
    console.error('Error fetching projects:', error)
  }
}

const fetchPromoters = async () => {
  try {
    const response = await axios.get('/promoters')
    promoters.value = response.data.data
  } catch (error) {
    console.error('Error fetching promoters:', error)
  }
}

const getProjectName = (projectId: number) => {
  const project = projects.value.find(p => p.id === projectId)
  return project ? project.name : `Project #${projectId}`
}

const getPromoterName = (promoterId: number) => {
  const promoter = promoters.value.find(p => p.id === promoterId)
  return promoter ? promoter.name : `Promoter #${promoterId}`
}

const editAmortization = (amortization: any) => {
  editingAmortization.value = amortization
  editForm.value = {
    amount: amortization.amount,
    state: amortization.state,
    project_id: amortization.project_id,
    promoter_id: amortization.promoter_id,
    schedule_date: amortization.schedule_date
  }
  showEditModal.value = true
}

const closeEditModal = () => {
  showEditModal.value = false
  editingAmortization.value = null
  editForm.value = {
    amount: 0,
    state: 'pending',
    project_id: null,
    promoter_id: null,
    schedule_date: null
  }
}

const updateAmortization = async () => {
  loading.value = true
  message.value = ''
  errorMessage.value = ''

  try {
    const response = await axios.put(`/amortizations/${editingAmortization.value.id}`, editForm.value)
    message.value = response.data.message
    closeEditModal()
    fetchAmortizations() // Refresh the list
  } catch (error: any) {
    errorMessage.value = error.response?.data?.message || 'An error occurred'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchAmortizations()
  fetchProjects()
  fetchPromoters()
})
</script> 