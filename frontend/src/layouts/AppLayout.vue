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
            <span v-if="lastChecked" class="last-checked">{{ timeAgo(lastChecked) }}</span>

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
            </div>

            <!-- Profile -->
            <button class="profile-chip" @click="openProfile">
              <div class="profile-avatar">{{ userInitial }}</div>
              <div class="profile-info">
                <span class="profile-name">{{ auth.user?.name?.split(' ')[0] ?? 'User' }}</span>
                <span class="profile-role">{{ roleLabel }}</span>
              </div>
            </button>
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

  <!-- ── Alerts Dropdown (Teleported to escape backdrop-filter stacking context) -->
  <Teleport to="body">
    <template v-if="showAlerts">
      <div class="al-backdrop" @click="showAlerts = false"></div>
      <div class="al-dropdown">
        <div class="al-header">
          <h3>Active Alerts</h3>
          <button @click="fetchAlerts()" class="al-refresh-btn">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Refresh
          </button>
        </div>
        <div class="al-list">
          <div v-if="alertsLoading" class="al-loading">
            <div class="al-spinner"></div>
            Checking alerts…
          </div>
          <div v-else-if="!alerts.length" class="al-empty">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
              <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p>All clear!</p>
          </div>
          <button v-for="alert in alerts" :key="alert.id" @click="goToAlert(alert)" class="al-item" :class="alert.severity">
            <div class="al-icon" :class="alert.severity">
              <svg v-if="alert.severity === 'critical'" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
              </svg>
              <svg v-else-if="alert.severity === 'warning'" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
              </svg>
              <svg v-else width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/>
              </svg>
            </div>
            <div class="al-content">
              <p class="al-title">{{ alert.title }}</p>
              <p class="al-msg">{{ alert.message }}</p>
            </div>
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
          </button>
        </div>
        <div class="al-footer">
          <span v-if="lastChecked" class="al-checked">Checked {{ timeAgo(lastChecked) }}</span>
          <RouterLink to="/inventory" @click="showAlerts = false" class="al-link">Inventory →</RouterLink>
          <RouterLink to="/analytics" @click="showAlerts = false" class="al-link">Analytics →</RouterLink>
        </div>
      </div>
    </template>
  </Teleport>

  <!-- ── Profile Edit Modal -->
  <Teleport to="body">
    <div v-if="showProfile" class="pm-backdrop" @click.self="closeProfile">
      <div class="pm-box">
        <div class="pm-header">
          <div class="pm-header-left">
            <div class="pm-avatar-lg">{{ userInitial }}</div>
            <div>
              <p class="pm-title">Edit Profile</p>
              <p class="pm-sub">{{ roleLabel }}</p>
            </div>
          </div>
          <button @click="closeProfile" class="pm-close">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <form @submit.prevent="saveProfile" class="pm-body">
          <div class="pm-grid">
            <div class="pm-fg pm-fg--full">
              <label class="pm-label">Full Name *</label>
              <input v-model="profileForm.name" class="pm-input" required />
            </div>
            <div class="pm-fg">
              <label class="pm-label">Email *</label>
              <input v-model="profileForm.email" type="email" class="pm-input" required />
            </div>
            <div class="pm-fg">
              <label class="pm-label">Phone</label>
              <input v-model="profileForm.phone" class="pm-input" placeholder="e.g. 09XX-XXX-XXXX" />
            </div>

            <div class="pm-divider pm-fg--full"><span>Change Password</span></div>
            <div class="pm-fg">
              <label class="pm-label">New Password <span class="pm-hint">(leave blank to keep)</span></label>
              <input v-model="profileForm.password" type="password" class="pm-input" autocomplete="new-password" />
            </div>
            <div class="pm-fg">
              <label class="pm-label">Confirm Password</label>
              <input v-model="profileForm.password_confirmation" type="password" class="pm-input" autocomplete="new-password" />
            </div>

            <div v-if="profileError" class="pm-fg pm-fg--full">
              <div class="pm-error">{{ profileError }}</div>
            </div>
            <div v-if="profileSuccess" class="pm-fg pm-fg--full">
              <div class="pm-success">{{ profileSuccess }}</div>
            </div>
          </div>

          <div class="pm-footer">
            <button type="button" @click="closeProfile" class="pm-btn-cancel">Cancel</button>
            <button type="submit" class="pm-btn-save" :disabled="profileSaving">
              {{ profileSaving ? 'Saving…' : 'Save Changes' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import Sidebar from '@/components/Sidebar.vue'
import Topbar from '@/components/TopBar.vue'
import api from '@/services/api'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const showAlerts = ref(false)
const alerts = ref([])
const alertsLoading = ref(false)
const lastChecked = ref(null)
const criticalCount = ref(0)
const warningCount = ref(0)

const showProfile   = ref(false)
const profileSaving = ref(false)
const profileError  = ref('')
const profileSuccess = ref('')
const profileForm   = ref({ name: '', email: '', phone: '', password: '', password_confirmation: '' })

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
const IconStockMovements = {
  template: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>`
}

const allNavItems = [
  { to: '/dashboard',       label: 'Dashboard',       icon: IconDashboard,       roles: ['admin', 'receptionist', 'optometrist', 'inventory_staff'] },
  { to: '/patients',        label: 'Patients',         icon: IconPatients,        roles: ['admin', 'receptionist', 'optometrist'] },
  { to: '/appointments',    label: 'Appointments',     icon: IconCalendar,        roles: ['admin', 'receptionist', 'optometrist'] },
  { to: '/prescriptions',   label: 'Prescriptions',    icon: IconPrescription,    roles: ['admin', 'receptionist', 'optometrist'] },
  { to: '/inventory',       label: 'Inventory',        icon: IconInventory,       roles: ['admin', 'inventory_staff'] },
  { to: '/stock-movements', label: 'Stock Movements',  icon: IconStockMovements,  roles: ['admin', 'inventory_staff'] },
  { to: '/transactions',    label: 'Transactions',     icon: IconSales,           roles: ['admin', 'receptionist'] },
  { to: '/reports',         label: 'Reports',          icon: IconReports,         roles: ['admin', 'inventory_staff'] },
  { to: '/analytics',       label: 'Analytics',        icon: IconAnalytics,       roles: ['admin', 'inventory_staff'] },
  { to: '/accounts',        label: 'Accounts',         icon: IconAccounts,        roles: ['admin'] },
]

const navItems = computed(() => allNavItems.filter(item => item.roles.includes(auth.user?.role)))

const pageTitles = {
  '/dashboard': 'Dashboard',
  '/patients': 'Patient Records',
  '/appointments': 'Appointments',
  '/inventory': 'Inventory Management',
  '/stock-movements': 'Stock Movements',
  '/transactions': 'Transactions',
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

const userInitial = computed(() => (auth.user?.name?.charAt(0) || 'U').toUpperCase())

async function fetchAlerts() {
  alertsLoading.value = true
  try {
    const { data } = await api.get('/alerts')
    alerts.value        = data.alerts        ?? []
    criticalCount.value = data.critical_count ?? 0
    warningCount.value  = data.warning_count  ?? 0
    lastChecked.value   = new Date(data.checked_at)
  } catch { /* silently ignore if backend unavailable */ }
  finally { alertsLoading.value = false }
}

function goToAlert(alert) {
  showAlerts.value = false
  router.push('/inventory')
}

onMounted(fetchAlerts)

function openProfile() {
  profileError.value   = ''
  profileSuccess.value = ''
  profileForm.value = {
    name:                  auth.user?.name  ?? '',
    email:                 auth.user?.email ?? '',
    phone:                 auth.user?.phone ?? '',
    password:              '',
    password_confirmation: '',
  }
  showProfile.value = true
}

function closeProfile() { showProfile.value = false }

async function saveProfile() {
  profileError.value   = ''
  profileSuccess.value = ''
  profileSaving.value  = true
  try {
    const { data } = await api.put('/me', profileForm.value)
    auth.user = data.user
    localStorage.setItem('user', JSON.stringify(data.user))
    profileSuccess.value = 'Profile updated successfully.'
    profileForm.value.password = ''
    profileForm.value.password_confirmation = ''
  } catch (e) {
    profileError.value = Object.values(e.response?.data?.errors ?? {}).flat().join(' ') || 'Failed to update profile.'
  } finally {
    profileSaving.value = false
  }
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

/* ── Teleported Alerts Dropdown ───────────────────────── */
.al-backdrop {
  position: fixed; inset: 0; z-index: 9990;
  background: transparent;
}
.al-dropdown {
  position: fixed; top: 68px; right: 24px;
  width: 360px; z-index: 9991;
  background: #fff; border: 1px solid var(--border);
  border-radius: 14px; box-shadow: 0 16px 48px rgba(26,39,68,.18);
  overflow: hidden;
}
.al-header {
  padding: 13px 16px; background: var(--bg);
  border-bottom: 1px solid var(--border);
  display: flex; align-items: center; justify-content: space-between;
}
.al-header h3 { font-size: 13px; font-weight: 700; color: var(--navy); margin: 0; }
.al-refresh-btn {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: 11px; color: var(--teal); background: none; border: none;
  cursor: pointer; font-weight: 600; font-family: inherit;
  transition: color var(--duration) var(--ease);
}
.al-refresh-btn:hover { color: var(--teal-dark); }
.al-list { max-height: 340px; overflow-y: auto; display: flex; flex-direction: column; }
.al-loading {
  display: flex; align-items: center; gap: 10px; justify-content: center;
  padding: 28px 16px; font-size: 13px; color: var(--muted);
}
.al-spinner {
  width: 16px; height: 16px; border: 2px solid var(--border);
  border-top-color: var(--teal); border-radius: 50%;
  animation: spin 0.8s linear infinite; flex-shrink: 0;
}
.al-empty {
  padding: 28px 16px; text-align: center; font-size: 13px;
  color: var(--muted); display: flex; flex-direction: column;
  align-items: center; gap: 8px;
}
.al-empty svg { color: var(--success); opacity: 0.6; }
.al-empty p   { font-weight: 600; color: var(--success); margin: 0; }
.al-item {
  display: flex; align-items: flex-start; gap: 12px;
  padding: 11px 16px; border-bottom: 1px solid var(--border);
  background: none; border-left: none; border-right: none; border-top: none;
  cursor: pointer; transition: background var(--duration) var(--ease);
  text-align: left; width: 100%; font-family: inherit;
}
.al-item:last-child { border-bottom: none; }
.al-item:hover { background: var(--bg); }
.al-item.critical { border-left: 3px solid var(--danger) !important; }
.al-item.warning  { border-left: 3px solid #f59e0b !important; }
.al-item.info     { border-left: 3px solid var(--teal) !important; }
.al-icon {
  width: 30px; height: 30px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.al-icon.critical { background: #fee2e2; color: var(--danger); }
.al-icon.warning  { background: #fef3c7; color: #d97706; }
.al-icon.info     { background: #e0f2fe; color: #0284c7; }
.al-content { flex: 1; min-width: 0; }
.al-title { font-size: 12px; font-weight: 700; color: var(--navy); margin: 0 0 2px; }
.al-msg   { font-size: 11px; color: var(--muted); line-height: 1.4; margin: 0; }
.al-footer {
  padding: 9px 16px; border-top: 1px solid var(--border);
  background: var(--bg); display: flex; align-items: center; gap: 12px;
}
.al-checked { font-size: 10px; color: var(--muted); flex: 1; }
.al-link {
  font-size: 11px; color: var(--teal); text-decoration: none;
  font-weight: 600; transition: color var(--duration) var(--ease);
}
.al-link:hover { color: var(--teal-dark); text-decoration: underline; }

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

/* Profile chip */
.profile-chip {
  display: flex; align-items: center; gap: 8px;
  padding: 5px 12px 5px 5px;
  border: 1.5px solid var(--border); border-radius: 24px;
  background: #fff; cursor: pointer; font-family: inherit;
  transition: border-color var(--duration) var(--ease), box-shadow var(--duration) var(--ease);
}
.profile-chip:hover {
  border-color: var(--teal);
  box-shadow: 0 2px 8px rgba(91,200,192,.2);
}
.profile-avatar {
  width: 30px; height: 30px; border-radius: 50%;
  background: linear-gradient(135deg, var(--teal), #0891b2);
  color: #fff; font-size: 12px; font-weight: 800;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.profile-info { display: flex; flex-direction: column; line-height: 1.2; }
.profile-name { font-size: 12px; font-weight: 700; color: var(--navy); }
.profile-role { font-size: 10px; color: var(--muted); }

/* ── Profile Edit Modal ───────────────────────────────── */
.pm-backdrop {
  position: fixed; inset: 0; z-index: 9995;
  background: rgba(0,0,0,.5);
  display: flex; align-items: center; justify-content: center; padding: 16px;
}
.pm-box {
  background: #fff; border-radius: 16px;
  box-shadow: 0 24px 64px rgba(0,0,0,.2);
  width: 100%; max-width: 560px;
}
.pm-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 18px 22px; border-bottom: 1.5px solid #f3f4f6;
}
.pm-header-left { display: flex; align-items: center; gap: 14px; }
.pm-avatar-lg {
  width: 48px; height: 48px; border-radius: 50%; flex-shrink: 0;
  background: linear-gradient(135deg, var(--teal), #0891b2);
  color: #fff; font-size: 20px; font-weight: 800;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 4px 12px rgba(91,200,192,.35);
}
.pm-title { font-size: 15px; font-weight: 800; color: #111827; margin: 0 0 2px; }
.pm-sub   { font-size: 12px; color: var(--muted); margin: 0; }
.pm-close {
  width: 32px; height: 32px; border: none; background: #f3f4f6;
  border-radius: 8px; cursor: pointer; color: #6b7280;
  display: flex; align-items: center; justify-content: center;
  transition: background .2s; flex-shrink: 0;
}
.pm-close:hover { background: #e5e7eb; color: #374151; }

.pm-body   { padding: 22px; }
.pm-grid   { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 16px; }
.pm-fg     { display: flex; flex-direction: column; gap: 5px; }
.pm-fg--full { grid-column: 1 / -1; }
.pm-label  { font-size: 11px; font-weight: 700; color: #374151; }
.pm-hint   { font-weight: 500; color: #9ca3af; }
.pm-input  {
  padding: 9px 13px; border: 1.5px solid #e5e7eb; border-radius: 10px;
  font-size: 13px; font-family: inherit; color: #111827; background: #fff; outline: none;
  transition: border-color .2s;
}
.pm-input:focus { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(91,200,192,.15); }
.pm-divider {
  font-size: 11px; font-weight: 700; color: var(--teal-dark);
  text-transform: uppercase; letter-spacing: 1px;
  border-bottom: 1.5px solid #a5f3fc; padding-bottom: 5px; margin-top: 6px;
}
.pm-error   { font-size: 12px; color: #b91c1c; background: #fee2e2; border-radius: 8px; padding: 9px 13px; }
.pm-success { font-size: 12px; color: #15803d; background: #dcfce7; border-radius: 8px; padding: 9px 13px; }
.pm-footer  {
  display: flex; justify-content: flex-end; gap: 10px;
  margin-top: 20px; padding-top: 16px; border-top: 1.5px solid #f3f4f6;
}
.pm-btn-cancel {
  padding: 9px 18px; border: 1.5px solid #e5e7eb; border-radius: 10px;
  background: #fff; font-size: 13px; font-weight: 600; color: #374151;
  font-family: inherit; cursor: pointer; transition: background .2s;
}
.pm-btn-cancel:hover { background: #f9fafb; }
.pm-btn-save {
  padding: 9px 22px; border: none; border-radius: 10px;
  background: linear-gradient(135deg, var(--teal), #0891b2);
  color: #fff; font-size: 13px; font-weight: 700; font-family: inherit;
  cursor: pointer; box-shadow: 0 4px 12px rgba(91,200,192,.35);
  transition: opacity .2s;
}
.pm-btn-save:disabled { opacity: .6; cursor: not-allowed; }
</style>