import { createRouter, createWebHistory } from 'vue-router'
import AmortizationList from '../views/AmortizationList.vue'
import AmortizationDetail from '../views/AmortizationDetail.vue'
import Metrics from '../views/Metrics.vue'
import ProjectManagement from '../views/ProjectManagement.vue'
import AmortizationManagement from '../views/AmortizationManagement.vue'
import PaymentsManagement from '../views/PaymentsManagement.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'list',
      component: AmortizationList
    },
    {
      path: '/amortization/:id',
      name: 'detail',
      component: AmortizationDetail
    },
    {
      path: '/metrics',
      name: 'metrics',
      component: Metrics
    },
    {
      path: '/projects',
      name: 'projects',
      component: ProjectManagement
    },
    {
      path: '/amortization-management',
      name: 'amortization-management',
      component: AmortizationManagement
    },
    {
      path: '/payments',
      name: 'payments',
      component: PaymentsManagement
    }
  ]
})

export default router
