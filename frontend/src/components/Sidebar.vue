<template>
  <aside class="sidebar" :class="{ collapsed }">

    <!-- Logo + toggle -->
    <div class="sidebar-logo">
      <img src="/Logo.png" alt="Acebedo Optical" class="logo-img" />
      <div v-if="!collapsed" class="logo-text">
        <span class="logo-name">ACEBEDO</span>
        <span class="logo-sub">Optical Clinic</span>
      </div>
      <button class="collapse-btn" @click="collapsed = !collapsed" :title="collapsed ? 'Expand' : 'Collapse'">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <polyline v-if="!collapsed" points="15 18 9 12 15 6"/>
          <polyline v-else points="9 18 15 12 9 6"/>
        </svg>
      </button>
    </div>

    <div class="sidebar-hr"/>

    <!-- User & notifications -->
    <div class="sidebar-user" :class="{ 'sidebar-user--col': collapsed }">
      <button class="icon-btn" title="Profile">
        <span v-html="icons.user"/>
      </button>
      <button class="icon-btn notif-btn" title="Notifications">
        <span v-html="icons.bell"/>
        <span class="notif-badge">9</span>
      </button>
    </div>

    <!-- Search -->
    <div v-if="!collapsed" class="sidebar-search">
      <span v-html="icons.search" class="search-svg"/>
      <input type="text" placeholder="Search for..." v-model="searchQuery" />
    </div>

    <div class="sidebar-hr"/>

    <!-- Nav links - Role-based filtering -->
    <nav class="sidebar-nav">
      <RouterLink
        v-for="item in filteredNavItems"
        :key="item.to"
        :to="item.to"
        class="nav-item"
        :class="{ active: isActive(item.to), 'nav-item--col': collapsed }"
        :title="collapsed ? item.label : ''"
      >
        <span class="nav-svg" v-html="item.svg"/>
        <span v-if="!collapsed" class="nav-label">{{ item.label }}</span>
        <span v-if="collapsed && isActive(item.to)" class="active-dot"/>
      </RouterLink>
    </nav>

    <div class="sidebar-spacer"/>
    <div class="sidebar-hr"/>

    <!-- User Info -->
    <div v-if="!collapsed" class="sidebar-user-info">
      <div class="user-avatar">{{ userInitial }}</div>
      <div class="user-details">
        <p class="user-name">{{ auth.user?.name || 'User' }}</p>
        <span class="user-role">{{ roleLabel }}</span>
      </div>
    </div>

    <!-- Logout -->
    <button
      class="nav-item logout-item"
      :class="{ 'nav-item--col': collapsed }"
      :title="collapsed ? 'Logout' : ''"
      @click="handleLogout"
    >
      <span class="nav-svg logout-svg" v-html="icons.logout"/>
      <span v-if="!collapsed" class="nav-label">Logout</span>
    </button>

  </aside>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth        = useAuthStore()
const route       = useRoute()
const router      = useRouter()
const collapsed   = ref(false)
const searchQuery = ref('')

const isActive     = (path) => route.path.startsWith(path)
const userInitial = computed(() => (auth.user?.name?.charAt(0) || 'U').toUpperCase())
const roleLabel   = computed(() => ({
  admin: 'Administrator',
  receptionist: 'Receptionist',
  optometrist: 'Optometrist',
  inventory_staff: 'Inventory Staff',
}[auth.user?.role] || 'User'))

const handleLogout = async () => {
  try {
    // Call auth store logout method if it exists
    if (auth && typeof auth.logout === 'function') {
      await auth.logout()
    } else {
      // Fallback: clear localStorage manually
      localStorage.removeItem('acebedo_token')
      localStorage.clear()
    }
  } catch (error) {
    console.error('Logout error:', error)
    localStorage.removeItem('acebedo_token')
  } finally {
    // Always redirect to login
    router.push('/login')
  }
}

/* Filter nav items by user role - show all for admin if role not defined */
const filteredNavItems = computed(() => {
  // If no role defined yet, show all items (will be filtered by router guards)
  if (!auth.user?.role) {
    return allNavItems
  }
  return allNavItems.filter(item => item.roles.includes(auth.user.role))
})
const icons = {
  user: `<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>`,
  bell: `<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>`,
  search: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>`,
  logout: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>`,
}

/* All available nav items with role restrictions */
const allNavItems = [
  {
    to: '/dashboard',
    label: 'Dashboard',
    roles: ['admin', 'receptionist', 'optometrist', 'inventory_staff'],
    svg: `<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>`
  },
  {
    to: '/patients',
    label: 'Patients',
    roles: ['admin', 'receptionist', 'optometrist'],
    svg: `<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>`
  },
  {
    to: '/appointments',
    label: 'Appointments',
    roles: ['admin', 'receptionist', 'optometrist'],
    svg: `<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>`
  },
  {
    to: '/prescriptions',
    label: 'Prescriptions',
    roles: ['admin', 'receptionist', 'optometrist'],
    svg: `<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>`
  },
  {
    to: '/inventory',
    label: 'Inventory',
    roles: ['admin', 'inventory_staff'],
    svg: `<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>`
  },
  {
    to: '/stock-movements',
    label: 'Stock Movements',
    roles: ['admin', 'inventory_staff'],
    svg: `<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>`
  },
  {
    to: '/sales',
    label: 'Sales',
    roles: ['admin', 'receptionist'],
    svg: `<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>`
  },
  {
    to: '/reports',
    label: 'Reports',
    roles: ['admin', 'inventory_staff'],
    svg: `<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>`
  },
  {
    to: '/analytics',
    label: 'Analytics',
    roles: ['admin', 'inventory_staff'],
    svg: `<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>`
  },
  {
    to: '/accounts',
    label: 'Accounts',
    roles: ['admin'],
    svg: `<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`
  },
]
</script>

<style scoped>
.sidebar {
  width: 220px;
  min-height: 100vh;
  background: #fff;
  display: flex;
  flex-direction: column;
  border-right: 1px solid var(--border);
  flex-shrink: 0;
  transition: width 0.26s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
}
.sidebar.collapsed { width: 72px; }

/* Logo */
.sidebar-logo {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 14px 12px;
  min-height: 66px;
  overflow: hidden;
}
.logo-img  { width: 38px; height: 38px; object-fit: contain; border-radius: 6px; flex-shrink: 0; }
.logo-text { display: flex; flex-direction: column; flex: 1; overflow: hidden; white-space: nowrap; }
.logo-name { font-size: 12px; font-weight: 900; color: var(--navy); letter-spacing: 1px; }
.logo-sub  { font-size: 9px; color: var(--slate); font-weight: 500; }

.collapse-btn {
  width: 24px; height: 24px; border-radius: 50%;
  border: 1.5px solid var(--border); background: #fff;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; color: var(--slate); flex-shrink: 0;
  transition: all var(--duration) var(--ease); margin-left: auto;
}
.collapse-btn:hover { background: var(--teal-bg); border-color: var(--teal); color: var(--teal-dark); }
.collapsed .collapse-btn { margin-left: 0; }

/* Divider */
.sidebar-hr { height: 1px; background: var(--border); margin: 2px 10px; opacity: 0.7; flex-shrink: 0; }

/* User row */
.sidebar-user {
  display: flex; align-items: center; gap: 6px;
  padding: 8px 12px; flex-shrink: 0;
}
.sidebar-user--col { flex-direction: column; align-items: center; gap: 8px; padding: 10px 8px; }

.icon-btn {
  width: 34px; height: 34px; border-radius: 50%;
  border: 1.5px solid var(--border); background: #fff;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; color: var(--slate); flex-shrink: 0;
  transition: all var(--duration) var(--ease);
}
.icon-btn:hover { background: var(--teal-bg); color: var(--teal-dark); border-color: var(--teal); }
.notif-btn { position: relative; }
.notif-badge {
  position: absolute; top: -4px; right: -4px;
  background: var(--danger); color: #fff; font-size: 9px; font-weight: 800;
  width: 16px; height: 16px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center; border: 2px solid #fff;
}

/* Search */
.sidebar-search {
  margin: 6px 10px 8px; display: flex; align-items: center; gap: 7px;
  background: var(--bg); border: 1.5px solid var(--border);
  border-radius: 10px; padding: 7px 10px;
  transition: border-color var(--duration) var(--ease); flex-shrink: 0;
}
.sidebar-search:focus-within { border-color: var(--teal); background: #fff; }
.search-svg { display: flex; align-items: center; color: var(--muted); flex-shrink: 0; }
.sidebar-search input {
  border: none; background: transparent; outline: none;
  font-family: var(--font-main); font-size: 13px; color: var(--navy); width: 100%;
}
.sidebar-search input::placeholder { color: var(--muted); }

/* Nav */
.sidebar-nav {
  display: flex; flex-direction: column; gap: 2px;
  padding: 8px 8px 4px; flex: 1;
  overflow-y: auto; min-height: 0;
}

.nav-item {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 12px; border-radius: 10px;
  font-size: 14px; font-weight: 600; color: var(--slate);
  text-decoration: none; transition: all var(--duration) var(--ease);
  flex-shrink: 0; position: relative;
  border: none; background: none;
  font-family: var(--font-main); cursor: pointer; width: 100%;
  box-sizing: border-box; text-align: left;
}
.nav-item--col {
  justify-content: center;
  padding: 11px 0;
}
.nav-item:hover  { background: var(--teal-bg); color: var(--teal-dark); }
.nav-item.active { background: var(--navy); color: #fff; }

/* SVG icon — always visible */
.nav-svg {
  display: flex; align-items: center; justify-content: center;
  width: 22px; height: 22px; flex-shrink: 0;
  opacity: 0.8;
}
.nav-item:hover .nav-svg,
.nav-item.active .nav-svg { opacity: 1; }

.nav-label { flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* Active dot when collapsed */
.active-dot {
  position: absolute; right: 7px; top: 50%; transform: translateY(-50%);
  width: 6px; height: 6px; border-radius: 50%; background: var(--teal);
}



/* User Info */
.sidebar-user-info {
  display: flex; align-items: center; gap: 10px;
  padding: 12px 12px;
  background: var(--bg);
  border-radius: 10px;
  margin: 0 8px;
  flex-shrink: 0;
}

.user-avatar {
  width: 32px; height: 32px;
  border-radius: 50%;
  background: var(--teal);
  color: #fff;
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 700;
  flex-shrink: 0;
}

.user-details {
  flex: 1; min-width: 0;
}

.user-name {
  font-size: 12px; font-weight: 700; color: var(--navy);
  margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}

.user-role {
  font-size: 10px; font-weight: 600;
  color: var(--teal-dark); display: block;
  margin-top: 2px;
}

/* Logout */
.logout-item  { color: var(--danger) !important; margin: 4px 8px 6px; width: auto !important; flex-shrink: 0; }
.logout-item:hover { background: #FEE2E2 !important; color: var(--danger-dark) !important; }
.logout-svg   { color: var(--danger); }
.collapsed .logout-item { margin: 4px 4px 6px; }

/* Scrollbar */
.sidebar-nav::-webkit-scrollbar {
  width: 4px;
}

.sidebar-nav::-webkit-scrollbar-track {
  background: transparent;
}

.sidebar-nav::-webkit-scrollbar-thumb {
  background: var(--border);
  border-radius: 2px;
}

.sidebar-nav::-webkit-scrollbar-thumb:hover {
  background: var(--slate);
}
</style>