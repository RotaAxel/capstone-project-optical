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

    <!-- Nav links -->
    <nav class="sidebar-nav">
      <RouterLink
        v-for="item in navItems"
        :key="item.to"
        :to="item.to"
        class="nav-item"
        :class="{ active: isActive(item.to), 'nav-item--col': collapsed }"
        :title="collapsed ? item.label : ''"
      >
        <span v-if="item.icon" class="nav-svg" v-html="item.icon"/>
        <span v-if="!collapsed" class="nav-label">{{ item.label }}</span>
        <span v-if="collapsed && isActive(item.to)" class="active-dot"/>
      </RouterLink>
    </nav>

    <div class="sidebar-spacer"/>
    <div class="sidebar-hr"/>

    <!-- User Profile -->
    <div class="sidebar-profile" :class="{ 'sidebar-profile--col': collapsed }">
      <div class="user-avatar">{{ userInitial }}</div>
      <div v-if="!collapsed" class="user-details">
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

defineProps({
  navItems:      { type: Array,    default: () => [] },
  isActive:      { type: Function, default: () => false },
  roleLabel:     { type: String,   default: '' },
  roleBadgeColor:{ type: String,   default: '' },
})
defineEmits(['logout'])

const auth        = useAuthStore()
const route       = useRoute()
const router      = useRouter()
const collapsed   = ref(false)

const isActive    = (path) => route.path.startsWith(path)
const userInitial = computed(() => (auth.user?.name?.charAt(0) || 'U').toUpperCase())
const roleLabel   = computed(() => ({
  admin: 'Administrator', receptionist: 'Receptionist',
  optometrist: 'Optometrist', inventory_staff: 'Inventory Staff',
}[auth.user?.role] || 'User'))

const handleLogout = async () => {
  try { await auth.logout() } catch {}
  router.push('/login')
}

const icons = {
  logout: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>`,
}
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



/* User Profile */
.sidebar-profile {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 12px;
  background: linear-gradient(135deg, #f0fdfa, #e6fffa);
  border: 1px solid var(--teal-bg);
  border-radius: 12px;
  margin: 0 8px;
  flex-shrink: 0;
}
.sidebar-profile--col {
  justify-content: center;
  padding: 10px 0;
  background: transparent;
  border-color: transparent;
}

.user-avatar {
  width: 34px; height: 34px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--teal), #0891b2);
  color: #fff;
  display: flex; align-items: center; justify-content: center;
  font-size: 13px; font-weight: 800;
  flex-shrink: 0;
  box-shadow: 0 2px 8px rgba(91,200,192,.35);
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