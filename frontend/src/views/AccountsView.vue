<template>
  <div class="accounts fade-up">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-xl font-bold text-gray-900" style="margin: 0.2rem;">User Accounts</h2>
        <p class="text-sm text-gray-500 mt-1" style="margin: 0.2rem;">{{ users.length ?? 0 }} total users</p>
      </div>
      <button @click="openModal()" class="btn btn-primary" style="margin: 0.2rem;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add User
      </button>
    </div>

    <!-- Search -->
    <div class="card p-4" style="margin: 0.2rem;">
      <input v-model="search" @input="debouncedFetch" type="text" placeholder="🔍 Search by name, email, or role..." class="fi max-w-md" />
    </div>

    <!-- User List -->
    <div class="user-list mb-6">
      <div v-if="loading" class="flex items-center justify-center py-16">
        <div class="animate-spin w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full"></div>
      </div>

      <div v-for="u in filteredUsers" v-else :key="u.id" class="user-card card">
        <div class="flex items-center gap-4 flex-1">
          <!-- Avatar -->
          <div class="avatar">
            {{ u.name?.charAt(0)?.toUpperCase() }}
          </div>

          <!-- User Info -->
          <div class="flex-1">
            <h3 class="user-name">{{ u.name }}</h3>
            <p class="user-contact"><span class="lbl">Email:</span> {{ u.email }} {{ u.phone ? '• ' + u.phone : '' }}</p>
            <div class="badges-container">
              <span :class="getRoleBadgeClass(u.role)" class="badge">
                {{ u.role?.replace('_', ' ') }}
              </span>
              <span :class="u.is_active ? 'badge-success' : 'badge-danger'" class="badge">
                {{ u.is_active ? 'Active' : 'Inactive' }}
              </span>
            </div>
          </div>
        </div>

        <!-- Meta Info -->
        <div class="user-meta text-right">
          <span class="meta-label">Joined</span>
          <span class="meta-date">{{ formatDate(u.created_at) }}</span>
        </div>

        <!-- Action Buttons -->
        <div style="display: flex; flex-direction: column; gap: 8px; margin-left: 16px; width: 120px;">
          <button @click.stop="openModal(u)" class="btn btn-secondary btn-sm">Edit</button>
          <button @click.stop="toggleActive(u)" :class="u.is_active ? 'btn btn-danger btn-sm' : 'btn btn-success btn-sm'">
            {{ u.is_active ? 'Deactivate' : 'Activate' }}
          </button>
        </div>
      </div>

      <div v-if="!loading && !users.length" class="text-center py-12 text-gray-400 card">
        No users found.
      </div>
    </div>

    <!-- Add/Edit User Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-screen overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
          <h3 class="font-semibold text-gray-900">{{ editingId ? 'Edit User' : 'Add New User' }}</h3>
          <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <form @submit.prevent="save" class="px-6 py-5">
          <div class="form-grid">
            <!-- User Info -->
            <div class="fg">
              <label class="fl">Full Name *</label>
              <input v-model="form.name" class="fi" required />
            </div>
            <div class="fg">
              <label class="fl">Email *</label>
              <input v-model="form.email" type="email" class="fi" required />
            </div>
            <div class="fg">
              <label class="fl">Phone</label>
              <input v-model="form.phone" class="fi" />
            </div>
            <div class="fg">
              <label class="fl">Role *</label>
              <select v-model="form.role" class="fi" required>
                <option value="receptionist">Receptionist</option>
                <option value="optometrist">Optometrist</option>
                <option value="inventory_staff">Inventory Staff</option>
                <option value="admin">Admin</option>
              </select>
            </div>

            <!-- Password Section -->
            <div class="fg fg--full section-divider"><span>Authentication</span></div>
            <div class="fg">
              <label class="fl">Password {{ editingId ? '(leave blank to keep)' : '*' }}</label>
              <input v-model="form.password" type="password" class="fi" :required="!editingId" />
            </div>
            <div v-if="form.password" class="fg">
              <label class="fl">Confirm Password</label>
              <input v-model="form.password_confirmation" type="password" class="fi" />
            </div>

            <div v-if="formError" class="fg fg--full">
              <div class="text-sm text-red-600 bg-red-50 rounded-lg px-4 py-2">{{ formError }}</div>
            </div>
          </div>

          <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
            <button type="button" @click="closeModal" class="btn btn-secondary">Cancel</button>
            <button type="submit" class="btn btn-primary" :disabled="saving">{{ saving ? 'Saving...' : 'Save User' }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'

const users      = ref([])
const loading    = ref(true)
const search     = ref('')
const showModal  = ref(false)
const editingId  = ref(null)
const saving     = ref(false)
const formError  = ref('')

const emptyForm = () => ({ name: '', email: '', phone: '', role: 'receptionist', password: '', password_confirmation: '' })
const form = ref(emptyForm())

let debounceTimer

function debouncedFetch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => fetchUsers(), 400)
}

const filteredUsers = computed(() => {
  if (!search.value) return users.value
  const q = search.value.toLowerCase()
  return users.value.filter(u =>
    u.name?.toLowerCase().includes(q) ||
    u.email?.toLowerCase().includes(q) ||
    u.role?.toLowerCase().includes(q)
  )
})

function getRoleBadgeClass(role) {
  const classes = {
    'admin': 'badge-blue',
    'receptionist': 'badge-info',
    'optometrist': 'badge-warning',
    'inventory_staff': 'badge-info',
  }
  return classes[role] || 'badge-info'
}

async function fetchUsers() {
  loading.value = true
  try { users.value = (await api.get('/users')).data.data ?? [] }
  catch { /* backend unavailable */ }
  finally { loading.value = false }
}

function openModal(user = null) {
  formError.value = ''
  if (user) {
    editingId.value = user.id
    form.value = { ...user, password: '', password_confirmation: '' }
  } else {
    editingId.value = null
    form.value = emptyForm()
  }
  showModal.value = true
}

function closeModal() { showModal.value = false }

async function save() {
  formError.value = ''
  saving.value = true
  try {
    if (editingId.value) await api.put(`/users/${editingId.value}`, form.value)
    else await api.post('/users', form.value)
    closeModal()
    fetchUsers()
  } catch (e) {
    formError.value = Object.values(e.response?.data?.errors ?? {}).flat().join(' ') || 'Failed to save user.'
  } finally {
    saving.value = false
  }
}

async function toggleActive(user) {
  await api.put(`/users/${user.id}`, { is_active: !user.is_active })
  fetchUsers()
}

function formatDate(d) {
  return d ? new Date(d).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '—'
}

onMounted(fetchUsers)
</script>

<style scoped>
.accounts { padding: 28px 32px 40px; }
.user-list { display: flex; flex-direction: column; gap: 18px; margin: 10px; }
.user-card {
  display: flex; align-items: center; justify-content: space-between;
  padding: 24px 28px; cursor: pointer;
  background: rgba(255, 255, 255, 0.92); backdrop-filter: blur(6px);
  border-radius: 12px;
  transition: all 0.3s ease;
}
.user-card:hover { box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); transform: translateX(4px); }

.avatar {
  width: 48px; height: 48px; border-radius: 50%;
  background: #DBEAFE; display: flex; align-items: center;
  justify-content: center; color: #1D4ED8; font-size: 18px;
  font-weight: 700; flex-shrink: 0;
}

.user-name { font-size: 1rem; font-weight: 700; color: #1f2937; margin-bottom: 4px; margin: 0; }
.user-contact { font-size: 12px; color: #9ca3af; margin: 0; }
.lbl { font-weight: 600; color: #4b5563; }

.badges-container { display: flex; gap: 8px; margin-top: 8px; }
.badge {
  display: inline-block; padding: 4px 10px; border-radius: 6px;
  font-size: 11px; font-weight: 700; text-transform: capitalize;
}
.badge-success { background: #DCFCE7; color: #166534; }
.badge-danger  { background: #FEE2E2; color: #991B1B; }
.badge-blue    { background: #DBEAFE; color: #1E40AF; }
.badge-info    { background: #E0E7FF; color: #3730A3; }
.badge-warning { background: #FEF9C3; color: #854D0E; }

.user-meta { text-align: right; margin: 0 24px; }
.meta-label { display: block; font-size: 12px; color: #9ca3af; }
.meta-date { font-size: 13px; font-weight: 700; color: #1f2937; }

/* Form */
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px 18px; }
.fg { display: flex; flex-direction: column; gap: 6px; }
.fg--full { grid-column: 1 / -1; }
.fl { font-size: 12px; font-weight: 700; color: #1f2937; }
.fi {
  padding: 10px 14px; border: 1.5px solid #e5e7eb; border-radius: 10px;
  font-family: inherit; font-size: 13px; color: #1f2937;
  background: #fff; outline: none; width: 100%;
  transition: border-color 0.3s ease;
}
.fi:focus { border-color: #06b6d4; box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.12); }
.fi--ta { resize: vertical; min-height: 80px; }
.section-divider {
  font-size: 11px; font-weight: 700; color: #0e7490;
  text-transform: uppercase; letter-spacing: 1px;
  border-bottom: 1.5px solid #a5f3fc; padding-bottom: 6px;
  margin-top: 8px;
}

/* Animations */
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(16px); }
  to { opacity: 1; transform: translateY(0); }
}
.fade-up { animation: fadeUp 0.4s cubic-bezier(0.4, 0, 0.2, 1) both; }
</style>