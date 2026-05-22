import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  { path: '/login', name: 'Login', component: () => import('@/views/LoginView.vue'), meta: { guest: true } },

  {
    path: '/',
    component: () => import('@/layouts/AppLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      { path: '',             redirect: '/dashboard' },
      { path: 'dashboard',   name: 'Dashboard',     component: () => import('@/views/DashboardView.vue') },

      // All roles can view patients; write restricted in-page
      { path: 'patients',    name: 'Patients',      component: () => import('@/views/PatientsView.vue'),
        meta: { roles: ['admin', 'receptionist', 'optometrist'] } },
      { path: 'patients/:id', name: 'PatientDetail', component: () => import('@/views/PatientDetailView.vue'),
        meta: { roles: ['admin', 'receptionist', 'optometrist'] } },

      // Receptionist schedules; optometrist can update status
      { path: 'appointments', name: 'Appointments',  component: () => import('@/views/AppointmentsView.vue'),
        meta: { roles: ['admin', 'receptionist', 'optometrist'] } },

      // Inventory — admin & inventory_staff
      { path: 'inventory',   name: 'Inventory',     component: () => import('@/views/InventoryView.vue'),
        meta: { roles: ['admin', 'inventory_staff'] } },

      // Transactions — admin & receptionist
      { path: 'transactions', name: 'Transactions',  component: () => import('@/views/SalesView.vue'),
        meta: { roles: ['admin', 'receptionist'] } },

      // Reports — admin full; inventory_staff sees inventory tabs only (enforced in-page)
      { path: 'reports',     name: 'Reports',       component: () => import('@/views/ReportsView.vue'),
        meta: { roles: ['admin', 'inventory_staff'] } },

      // Stock Movements — admin & inventory_staff
      { path: 'stock-movements', name: 'StockMovements', component: () => import('@/views/StockMovementsView.vue'),
        meta: { roles: ['admin', 'inventory_staff'] } },

      // Analytics — admin & inventory_staff
      { path: 'analytics',   name: 'Analytics',     component: () => import('@/views/AnalyticsView.vue'),
        meta: { roles: ['admin', 'inventory_staff'] } },

      // Prescriptions — admin, receptionist, optometrist
      { path: 'prescriptions', name: 'Prescriptions', component: () => import('@/views/PrescriptionsView.vue'),
        meta: { roles: ['admin', 'receptionist', 'optometrist'] } },

      // User accounts — admin only
      { path: 'accounts',    name: 'Accounts',      component: () => import('@/views/AccountsView.vue'),
        meta: { roles: ['admin'] } },
    ],
  },

  { path: '/:pathMatch(.*)*', redirect: '/' },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to, _from, next) => {
  const auth = useAuthStore()

  if (to.meta.requiresAuth && !auth.isAuthenticated) return next('/login')
  if (to.meta.guest && auth.isAuthenticated) return next('/')

  // Role-based route guard
  if (to.meta.roles && !to.meta.roles.includes(auth.user?.role)) {
    return next('/dashboard')  // redirect to dashboard instead of showing 403
  }

  next()
})

export default router
