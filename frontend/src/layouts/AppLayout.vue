<template>
  <div class="app-container">
    <!-- Sidebar Component -->
    <Sidebar 
      :nav-items="navItems"
      :is-active="isActive"
      :role-label="roleLabel"
      :role-badge-color="roleBadgeColor"
      @logout="handleLogout"
    />

    <!-- Main Content -->
    <div class="main-wrapper">
      <!-- Page Header -->
      <header class="page-header">
        <div class="title-row">
          <!-- Curved teal accent line -->
          <svg class="title-accent" viewBox="0 0 300 20" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M0 15 C60 5, 120 18, 180 10 S250 2, 300 12" stroke="#5BC8C0" stroke-width="1.8" fill="none" opacity="0.7"/>
          </svg>
          <h1 class="page-title">{{ currentPageTitle }}</h1>
          
          <!-- Header Right Section -->
          <div class="header-right">
            <span class="system-status">System Online</span>
            <span v-if="lastChecked" class="last-checked">Alerts checked {{ timeAgo(lastChecked) }}</span>
            
            <!-- Alerts Bell -->
            <div class="alerts-wrapper">
              <button @click="showAlerts = !showAlerts" class="alerts-btn" :class="{ active: badgeCount > 0 }">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                  <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                  <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                <span v-if="badgeCount > 0" class="badge" :class="{ critical: criticalCount > 0 }">
                  {{ badgeCount > 99 ? '99+' : badgeCount }}
                </span>
                <span v-if="criticalCount > 0" class="pulse"></span>
              </button>

              <!-- Alerts Dropdown -->
              <div v-if="showAlerts" class="alerts-dropdown">
                <div class="alerts-header">
                  <h3>Active Alerts</h3>
                  <button @click="fetchAlerts(); showAlerts = false" class="refresh-btn">Refresh</button>
                </div>
                
                <div class="alerts-list">
                  <div v-if="alertsLoading" class="loading">Checking alerts...</div>
                  <div v-else-if="!alerts.length" class="empty">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                      <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p>All clear!</p>
                  </div>
                  <button v-for="alert in alerts" :key="alert.id" @click="goToAlert(alert)" class="alert-item" :class="alert.severity">
                    <div class="alert-icon" :class="alert.severity">
                      <svg v-if="alert.severity === 'critical'" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                      </svg>
                      <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                      </svg>
                    </div>
                    <div class="alert-content">
                      <p class="alert-title">{{ alert.title }}</p>
                      <p class="alert-message">{{ alert.message }}</p>
                    </div>
                    <svg class="alert-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <polyline points="9 18 15 12 9 6"/>
                    </svg>
                  </button>
                </div>

                <div class="alerts-footer">
                  <RouterLink to="/inventory" @click="showAlerts = false" class="footer-link">Inventory →</RouterLink>
                  <RouterLink to="/analytics" @click="showAlerts = false" class="footer-link">Analytics →</RouterLink>
                </div>
              </div>
              <div v-if="showAlerts" class="alerts-backdrop" @click="showAlerts = false"></div>
            </div>
          </div>
        </div>

        <!-- Top Navigation Bar (Centered) using Topbar Component -->
        <Topbar 
          :nav-items="navItems"
          :is-active="isActive"
        />
      </header>

      <!-- Main Content Area -->
      <main class="main-area">
        <Transition name="page">
          <RouterView />
        </Transition>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import Sidebar from '@/components/Sidebar.vue'
import Topbar from '@/components/Topbar.vue'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const showAlerts = ref(false)
const alerts = ref([])
const alertsLoading = ref(false)
const lastChecked = ref(null)
const criticalCount = ref(0)
const warningCount = ref(0)

const badgeCount = computed(() => criticalCount.value + warningCount.value)

function timeAgo(date) {
  const secs = Math.floor((new Date() - date) / 1000)
  if (secs < 60) return 'just now'
  if (secs < 120) return '1 min ago'
  return Math.floor(secs / 60) + ' mins ago'
}

// Icon components
const IconDashboard = { 
  template: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>`
}
const IconPatients = { 
  template: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>`
}
const IconCalendar = { 
  template: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>`
}
const IconInventory = { 
  template: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>`
}
const IconSales = { 
  template: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2v20m0 0l-7-3.5m7 3.5l7-3.5"/></svg>`
}
const IconReports = { 
  template: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>`
}
const IconAnalytics = { 
  template: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>`
}
const IconAccounts = { 
  template: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`
}
const IconPrescription = { 
  template: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>`
}

const allNavItems = [
  { to: '/dashboard', label: 'Dashboard', icon: IconDashboard, roles: ['admin', 'receptionist', 'optometrist', 'inventory_staff'] },
  { to: '/patients', label: 'Patients', icon: IconPatients, roles: ['admin', 'receptionist', 'optometrist'] },
  { to: '/appointments', label: 'Appointments', icon: IconCalendar, roles: ['admin', 'receptionist', 'optometrist'] },
  { to: '/prescriptions', label: 'Prescriptions', icon: IconPrescription, roles: ['admin', 'receptionist', 'optometrist'] },
  { to: '/inventory', label: 'Inventory', icon: IconInventory, roles: ['admin', 'inventory_staff'] },
  { to: '/sales', label: 'Sales', icon: IconSales, roles: ['admin', 'receptionist'] },
  { to: '/reports', label: 'Reports', icon: IconReports, roles: ['admin', 'inventory_staff'] },
  { to: '/analytics', label: 'Analytics', icon: IconAnalytics, roles: ['admin', 'inventory_staff'] },
  { to: '/accounts', label: 'Accounts', icon: IconAccounts, roles: ['admin'] },
]

const navItems = computed(() => allNavItems.filter(item => item.roles.includes(auth.user?.role)))

const pageTitles = {
  '/dashboard': 'Dashboard',
  '/patients': 'Patient Records',
  '/appointments': 'Appointments',
  '/inventory': 'Inventory Management',
  '/sales': 'Sales & Billing',
  '/reports': 'Reports',
  '/analytics': 'Predictive Analytics',
  '/prescriptions': 'Prescriptions',
  '/accounts': 'User Accounts',
}

const currentPageTitle = computed(() => pageTitles[route.path] ?? 'Dashboard')

const roleLabel = computed(() => ({
  admin: 'Administrator',
  receptionist: 'Receptionist',
  optometrist: 'Optometrist',
  inventory_staff: 'Inventory Staff',
}[auth.user?.role] ?? auth.user?.role))

const roleBadgeColor = computed(() => ({
  admin: '#E3F2FD',
  receptionist: '#E8F5E9',
  optometrist: '#F3E5F5',
  inventory_staff: '#FFF3E0',
}[auth.user?.role] ?? '#F5F5F5'))

function isActive(to) {
  return route.path === to || (to !== '/dashboard' && route.path.startsWith(to))
}

async function fetchAlerts() {
  // TODO: Fetch from API
  alertsLoading.value = true
  await new Promise(r => setTimeout(r, 1000))
  alertsLoading.value = false
}

function goToAlert(alert) {
  showAlerts.value = false
  router.push('/inventory')
}

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}
</script>

<style scoped>
* { box-sizing: border-box; }

.app-container {
  display: flex;
  height: 100vh;
  overflow: hidden;
  background: var(--bg);
}

/* ════════════════════ MAIN WRAPPER ════════════════════ */
.main-wrapper {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  background-image: url('/bg.jpg');
  background-size: 110% 110vh;
  background-position: center;
  background-repeat: no-repeat;
  background-attachment: fixed;
}

/* Header */
.page-header {
  flex-shrink: 0;
  background: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border-bottom: 1px solid rgba(226, 232, 240, 0.5);
  padding: 0;
}

/* Title row */
.title-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 32px 4px;
  position: relative;
}

.title-accent {
  position: absolute;
  top: 8px;
  left: 0;
  width: 180px;
  height: 18px;
  pointer-events: none;
}

.page-title {
  font-family: var(--font-display);
  font-size: 24px;
  font-weight: 800;
  color: var(--navy);
  letter-spacing: -0.5px;
  margin: 0;
  position: relative;
  z-index: 1;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.system-status {
  font-size: 11px;
  font-weight: 700;
  color: #fff;
  background: var(--success);
  padding: 4px 10px;
  border-radius: 4px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.last-checked {
  font-size: 11px;
  color: var(--muted);
}

/* Alerts */
.alerts-wrapper {
  position: relative;
}

.alerts-btn {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: 1.5px solid var(--border);
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--slate);
  cursor: pointer;
  transition: all var(--duration) var(--ease);
  position: relative;
}

.alerts-btn:hover {
  background: var(--bg);
  border-color: var(--teal);
  color: var(--teal);
}

.alerts-btn.active {
  border-color: var(--danger);
  color: var(--danger);
}

.badge {
  position: absolute;
  top: -6px;
  right: -6px;
  width: 18px;
  height: 18px;
  background: var(--danger);
  color: #fff;
  font-size: 9px;
  font-weight: 800;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid #fff;
}

.badge.critical {
  background: var(--danger);
}

.pulse {
  position: absolute;
  top: -4px;
  right: -4px;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: var(--danger);
  opacity: 0.75;
  animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { transform: scale(1); opacity: 0.75; }
  50% { transform: scale(1.2); opacity: 0.5; }
}

/* Alerts Dropdown */
.alerts-dropdown {
  position: absolute;
  top: 100%;
  right: 0;
  margin-top: 8px;
  width: 360px;
  background: #fff;
  border: 1px solid var(--border);
  border-radius: 12px;
  box-shadow: 0 12px 40px rgba(26, 39, 68, 0.15);
  z-index: 100;
  overflow: hidden;
}

.alerts-header {
  padding: 14px 16px;
  background: var(--bg);
  border-bottom: 1px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.alerts-header h3 {
  font-size: 13px;
  font-weight: 700;
  color: var(--navy);
  margin: 0;
}

.refresh-btn {
  font-size: 11px;
  color: var(--teal);
  background: none;
  border: none;
  cursor: pointer;
  font-weight: 600;
  transition: color var(--duration) var(--ease);
}

.refresh-btn:hover {
  color: var(--teal-dark);
  text-decoration: underline;
}

.alerts-list {
  max-height: 320px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
}

.loading, .empty {
  padding: 24px 16px;
  text-align: center;
  font-size: 13px;
  color: var(--muted);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.empty svg {
  color: var(--success);
  opacity: 0.6;
}

.empty p {
  font-weight: 600;
  color: var(--success);
  margin: 0;
}

.alert-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 12px 16px;
  border-bottom: 1px solid var(--border);
  background: none;
  border: none;
  cursor: pointer;
  transition: background var(--duration) var(--ease);
  text-align: left;
  width: 100%;
}

.alert-item:hover {
  background: var(--bg);
}

.alert-icon {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.alert-icon.critical {
  background: #FEE2E2;
  color: var(--danger);
}

.alert-icon.warning {
  background: #FEF3C7;
  color: #D97706;
}

.alert-content {
  flex: 1;
  min-width: 0;
}

.alert-title {
  font-size: 12px;
  font-weight: 700;
  color: var(--navy);
  margin-bottom: 2px;
  margin: 0 0 2px 0;
}

.alert-message {
  font-size: 11px;
  color: var(--muted);
  line-height: 1.4;
  margin: 0;
}

.alert-arrow {
  color: var(--muted);
  flex-shrink: 0;
  margin-top: 2px;
}

.alerts-footer {
  padding: 10px 16px;
  border-top: 1px solid var(--border);
  background: var(--bg);
  display: flex;
  gap: 12px;
  justify-content: center;
}

.footer-link {
  font-size: 11px;
  color: var(--teal);
  text-decoration: none;
  font-weight: 600;
  transition: color var(--duration) var(--ease);
}

.footer-link:hover {
  color: var(--teal-dark);
  text-decoration: underline;
}

.alerts-backdrop {
  position: fixed;
  inset: 0;
  z-index: 50;
}

/* Main Area */
.main-area {
  flex: 1;
  overflow-y: auto;
  background: transparent;
}

/* Page Transition */
.page-enter-active, .page-leave-active {
  transition: opacity 0.18s ease, transform 0.18s ease;
}
.page-enter-from { opacity: 0; transform: translateY(8px); }
.page-leave-to { opacity: 0; }

/* Scrollbar Styling */
.alerts-list::-webkit-scrollbar,
.main-area::-webkit-scrollbar {
  width: 6px;
}

.alerts-list::-webkit-scrollbar-track,
.main-area::-webkit-scrollbar-track {
  background: transparent;
}

.alerts-list::-webkit-scrollbar-thumb,
.main-area::-webkit-scrollbar-thumb {
  background: var(--border);
  border-radius: 3px;
}

.alerts-list::-webkit-scrollbar-thumb:hover,
.main-area::-webkit-scrollbar-thumb:hover {
  background: var(--slate);
}
</style>