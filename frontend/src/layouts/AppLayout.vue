<template>
  <div class="flex h-screen bg-gray-50">
    <!-- Sidebar -->
    <aside class="w-64 bg-white border-r border-gray-200 flex flex-col shadow-sm shrink-0">
      <!-- Logo -->
      <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-100">
        <div class="w-9 h-9 rounded-lg bg-blue-600 flex items-center justify-center shrink-0">
          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
          </svg>
        </div>
        <div>
          <p class="text-sm font-bold text-gray-900 leading-tight">Acebedo</p>
          <p class="text-xs text-gray-500">Optical Clinic</p>
        </div>
      </div>

      <!-- Nav -->
      <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
        <RouterLink v-for="item in navItems" :key="item.to" :to="item.to"
          class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150 group"
          :class="isActive(item.to)
            ? 'bg-blue-50 text-blue-700'
            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'">
          <component :is="item.icon"
            class="w-5 h-5 shrink-0"
            :class="isActive(item.to) ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600'" />
          {{ item.label }}
          <span v-if="item.badge" class="ml-auto badge-red text-xs px-1.5 py-0.5 rounded-full">{{ item.badge }}</span>
        </RouterLink>
      </nav>

      <!-- User -->
      <div class="px-4 py-4 border-t border-gray-100">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-semibold text-sm shrink-0">
            {{ auth.user?.name?.charAt(0)?.toUpperCase() }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 truncate">{{ auth.user?.name }}</p>
            <span :class="roleBadgeClass" class="inline-block text-xs font-semibold px-2 py-0.5 rounded-full mt-0.5 capitalize">
              {{ roleLabel }}
            </span>
          </div>
          <button @click="handleLogout" class="text-gray-400 hover:text-red-500 transition-colors" title="Logout">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
          </button>
        </div>
      </div>
    </aside>

    <!-- Main -->
    <div class="flex-1 flex flex-col overflow-hidden">
      <header class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between shrink-0">
        <div>
          <h1 class="text-lg font-semibold text-gray-900">{{ currentPageTitle }}</h1>
          <p class="text-xs text-gray-500 mt-0.5">{{ currentDate }}</p>
        </div>
        <div class="flex items-center gap-4">
          <span class="badge-green text-xs">System Online</span>

          <!-- Last checked time -->
          <span v-if="lastChecked" class="text-xs text-gray-400 hidden sm:block">
            Alerts checked {{ timeAgo(lastChecked) }}
          </span>

          <!-- Notification Bell -->
          <div class="relative">
            <button @click="showAlerts = !showAlerts"
              class="relative p-2 rounded-lg hover:bg-gray-100 transition-colors focus:outline-none"
              :class="criticalCount > 0 ? 'text-red-500' : warningCount > 0 ? 'text-yellow-500' : 'text-gray-400'">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
              </svg>
              <!-- Badge -->
              <span v-if="badgeCount > 0"
                class="absolute -top-1 -right-1 min-w-[18px] h-[18px] flex items-center justify-center rounded-full text-white text-xs font-bold px-1"
                :class="criticalCount > 0 ? 'bg-red-500' : 'bg-yellow-500'">
                {{ badgeCount > 99 ? '99+' : badgeCount }}
              </span>
              <!-- Pulse animation for critical -->
              <span v-if="criticalCount > 0"
                class="absolute -top-1 -right-1 w-[18px] h-[18px] rounded-full bg-red-400 animate-ping opacity-75"></span>
            </button>

            <!-- Alerts Dropdown -->
            <div v-if="showAlerts"
              class="absolute right-0 top-10 w-96 bg-white rounded-xl shadow-xl border border-gray-200 z-50 overflow-hidden">
              <!-- Header -->
              <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 bg-gray-50">
                <div class="flex items-center gap-2">
                  <h3 class="text-sm font-semibold text-gray-800">Active Alerts</h3>
                  <span v-if="criticalCount" class="badge-red text-xs">{{ criticalCount }} critical</span>
                  <span v-if="warningCount" class="badge-yellow text-xs">{{ warningCount }} warning</span>
                </div>
                <button @click="fetchAlerts(); showAlerts = false" class="text-xs text-blue-600 hover:underline">Refresh</button>
              </div>

              <!-- Alert List -->
              <div class="max-h-80 overflow-y-auto divide-y divide-gray-50">
                <div v-if="alertsLoading" class="flex items-center justify-center py-8 text-gray-400 text-sm gap-2">
                  <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                  </svg>
                  Checking alerts...
                </div>

                <div v-else-if="!alerts.length" class="flex flex-col items-center justify-center py-8 text-gray-400">
                  <svg class="w-8 h-8 mb-2 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                  <p class="text-sm font-medium text-green-600">All clear!</p>
                  <p class="text-xs">No active alerts</p>
                </div>

                <button v-for="alert in alerts" :key="alert.id"
                  @click="goToAlert(alert)"
                  class="w-full flex gap-3 px-4 py-3 hover:bg-gray-50 transition-colors text-left cursor-pointer group">
                  <!-- Severity Icon -->
                  <div class="shrink-0 mt-0.5">
                    <div v-if="alert.severity === 'critical'" class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                      <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                      </svg>
                    </div>
                    <div v-else-if="alert.severity === 'warning'" class="w-8 h-8 rounded-full bg-yellow-100 flex items-center justify-center">
                      <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                      </svg>
                    </div>
                    <div v-else class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                      <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                      </svg>
                    </div>
                  </div>
                  <!-- Content -->
                  <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold"
                      :class="alert.severity === 'critical' ? 'text-red-700' : alert.severity === 'warning' ? 'text-yellow-700' : 'text-blue-700'">
                      {{ alert.title }}
                    </p>
                    <p class="text-xs text-gray-600 mt-0.5 leading-relaxed">{{ alert.message }}</p>
                    <div v-if="alert.product" class="mt-1 flex items-center gap-2">
                      <span class="text-xs font-mono text-gray-400">{{ alert.product.sku }}</span>
                      <span class="text-xs text-gray-400">·</span>
                      <span class="text-xs font-semibold" :class="alert.product.stock === 0 ? 'text-red-600' : 'text-yellow-600'">
                        {{ alert.product.stock }} in stock
                      </span>
                    </div>
                  </div>
                  <!-- Arrow -->
                  <svg class="w-4 h-4 text-gray-300 group-hover:text-gray-500 shrink-0 mt-1 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                  </svg>
                </button>
              </div>

              <!-- Footer -->
              <div class="px-4 py-2 border-t border-gray-100 bg-gray-50 flex justify-center gap-4">
                <RouterLink to="/inventory" @click="showAlerts = false" class="text-xs text-blue-600 hover:underline font-medium">Inventory →</RouterLink>
                <RouterLink to="/analytics" @click="showAlerts = false" class="text-xs text-blue-600 hover:underline font-medium">Analytics →</RouterLink>
                <RouterLink to="/appointments" @click="showAlerts = false" class="text-xs text-blue-600 hover:underline font-medium">Appointments →</RouterLink>
              </div>
            </div>
          </div>

          <!-- Click outside to close -->
          <div v-if="showAlerts" class="fixed inset-0 z-40" @click="showAlerts = false"></div>
        </div>
      </header>

      <main class="flex-1 overflow-y-auto p-6">
        <RouterView />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useAlerts } from '@/composables/useAlerts'

const auth   = useAuthStore()
const route  = useRoute()
const router = useRouter()

const showAlerts = ref(false)
const { alerts, loading: alertsLoading, lastChecked, criticalCount, warningCount, badgeCount, fetchAlerts } = useAlerts()

function timeAgo(date) {
  const secs = Math.floor((new Date() - date) / 1000)
  if (secs < 60)  return 'just now'
  if (secs < 120) return '1 min ago'
  return Math.floor(secs / 60) + ' mins ago'
}

// Inline icon components — must be declared before allNavItems uses them
const IconDashboard  = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v2a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10-2a1 1 0 011-1h4a1 1 0 011 1v6a1 1 0 01-1 1h-4a1 1 0 01-1-1v-6z"/></svg>` }
const IconPatients   = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>` }
const IconCalendar   = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>` }
const IconInventory  = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>` }
const IconSales      = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>` }
const IconReports    = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>` }
const IconMovements  = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>` }
const IconAnalytics  = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>` }
const IconAccounts      = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>` }
const IconPrescription  = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>` }

// Role-based nav — each item only appears for the listed roles
const allNavItems = [
  { to: '/dashboard',    label: 'Dashboard',       icon: IconDashboard, roles: ['admin', 'receptionist', 'optometrist', 'inventory_staff'] },
  { to: '/patients',     label: 'Patients',         icon: IconPatients,  roles: ['admin', 'receptionist', 'optometrist'] },
  { to: '/appointments',   label: 'Appointments',    icon: IconCalendar,      roles: ['admin', 'receptionist', 'optometrist'] },
  { to: '/prescriptions',  label: 'Prescriptions',   icon: IconPrescription,  roles: ['admin', 'receptionist', 'optometrist'] },
  { to: '/inventory',       label: 'Inventory',        icon: IconInventory,  roles: ['admin', 'inventory_staff'] },
  { to: '/stock-movements', label: 'Stock Movements',  icon: IconMovements,  roles: ['admin', 'inventory_staff'] },
  { to: '/sales',           label: 'Sales / Billing',  icon: IconSales,      roles: ['admin', 'receptionist'] },
  { to: '/reports',         label: 'Reports',           icon: IconReports,    roles: ['admin', 'inventory_staff'] },
  { to: '/analytics',       label: 'Analytics',         icon: IconAnalytics,  roles: ['admin', 'inventory_staff'] },
  { to: '/accounts',        label: 'User Accounts',     icon: IconAccounts,   roles: ['admin'] },
]

const navItems = computed(() =>
  allNavItems.filter(item => item.roles.includes(auth.user?.role))
)

const pageTitles = {
  '/dashboard':    'Dashboard',
  '/patients':     'Patient Records',
  '/appointments': 'Appointments',
  '/inventory':    'Inventory Management',
  '/sales':        'Sales & Billing',
  '/reports':      'Reports',
  '/analytics':        'Predictive Analytics',
  '/stock-movements':  'Stock Movements',
  '/prescriptions':    'Prescriptions',
  '/accounts':         'User Accounts',
}

const currentPageTitle = computed(() => pageTitles[route.path] ?? 'Acebedo Optical Clinic')
const currentDate = computed(() => new Date().toLocaleDateString('en-PH', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }))

const roleLabel = computed(() => ({
  admin:           'Administrator',
  receptionist:    'Receptionist',
  optometrist:     'Optometrist',
  inventory_staff: 'Inventory Staff',
}[auth.user?.role] ?? auth.user?.role))

const roleBadgeClass = computed(() => ({
  admin:           'bg-blue-100 text-blue-700',
  receptionist:    'bg-green-100 text-green-700',
  optometrist:     'bg-purple-100 text-purple-700',
  inventory_staff: 'bg-orange-100 text-orange-700',
}[auth.user?.role] ?? 'bg-gray-100 text-gray-600'))

function alertRoute(alert) {
  if (alert.type === 'out_of_stock' || alert.type === 'low_stock') return '/inventory?low_stock=1'
  if (alert.type === 'predictive_restock') return '/analytics'
  if (alert.type === 'appointments' || alert.type === 'no_show' || alert.type === 'overdue_appointment') return '/appointments'
  if (alert.type === 'prescription_expiring') return '/prescriptions'
  return '/dashboard'
}

function goToAlert(alert) {
  showAlerts.value = false
  router.push(alertRoute(alert))
}

function isActive(to) {
  return route.path === to || (to !== '/dashboard' && route.path.startsWith(to))
}

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}

</script>
