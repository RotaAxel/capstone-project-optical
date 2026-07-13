<template>
  <div class="patients fade-up">

    <!-- Header -->
    <div class="page-header">
      <div class="flex items-center gap-3">
        <!-- <div class="header-icon">
          <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M23 21v-2a4 4 0 0 0-3-3.87"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div>
          <h2 class="page-title">Patient Records</h2>
          <p class="page-sub">{{ pagination.total ?? 0 }} total patients registered</p>
        </div> -->
      </div>
      <button v-if="auth.can('admin','receptionist')" @click="openModal()" class="btn-add">
        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
        Add Patient
      </button>
    </div>

    <!-- Stats Row -->
    <div class="stats-row">
      <div class="stat-pill">
        <div class="sp-icon blue">
          <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        </div>
        <div><p class="sp-val">{{ globalStats.total ?? 0 }}</p><p class="sp-lbl">Total</p></div>
      </div>
      <div class="stat-pill">
        <div class="sp-icon indigo">
          <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        </div>
        <div><p class="sp-val">{{ maleCount }}</p><p class="sp-lbl">Male</p></div>
      </div>
      <div class="stat-pill">
        <div class="sp-icon pink">
          <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        </div>
        <div><p class="sp-val">{{ femaleCount }}</p><p class="sp-lbl">Female</p></div>
      </div>
      <div class="stat-pill">
        <div class="sp-icon green">
          <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        </div>
        <div><p class="sp-val">{{ newThisMonth }}</p><p class="sp-lbl">New This Month</p></div>
      </div>
    </div>

    <!-- Search Bar -->
    <div class="search-bar">
      <div class="search-wrap">
        <svg class="search-icon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
        <input v-model="search" @input="debouncedFetch" type="text"
          placeholder="Search by name, patient code, or phone..." class="search-input" />
        <button v-if="search" @click="search = ''; debouncedFetch()" class="search-clear">
          <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>
      <p class="results-note" v-if="search">Showing results for "{{ search }}"</p>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading-wrap">
      <div class="spinner"></div>
      <p>Loading patients…</p>
    </div>

    <!-- Patient Cards -->
    <div v-else class="patient-list">
      <div v-for="p in patients" :key="p.id" class="patient-card" @click="$router.push(`/patients/${p.id}`)">

        <!-- Avatar -->
        <div class="p-avatar" :class="avatarColor(p.gender)">
          {{ p.first_name?.charAt(0) }}{{ p.last_name?.charAt(0) }}
        </div>

        <!-- Main Info -->
        <div class="p-main">
          <div class="p-name-row">
            <span class="p-name">{{ p.first_name }} {{ p.last_name }}</span>
            <span class="p-code">{{ p.patient_code }}</span>
            <span class="gender-badge" :class="p.gender">{{ p.gender }}</span>
          </div>
          <div class="p-details">
            <span class="p-detail-item">
              <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
              {{ p.phone ?? '—' }}
            </span>
            <span class="p-detail-item" v-if="p.email">
              <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
              {{ p.email }}
            </span>
            <span class="p-detail-item" v-if="p.address">
              <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
              {{ p.address }}
            </span>
          </div>

          <!-- Clinical summary row -->
          <div class="p-clinical">
            <span class="cl-tag cl-visit">
              <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
              {{ p.appointments_count ?? 0 }} {{ (p.appointments_count ?? 0) === 1 ? 'visit' : 'visits' }}
            </span>
            <span class="cl-tag cl-rx">
              <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
              {{ p.prescriptions_count ?? 0 }} {{ (p.prescriptions_count ?? 0) === 1 ? 'prescription' : 'prescriptions' }}
            </span>
            <span v-if="p.last_visit" class="cl-tag cl-last">
              <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              Last visit: {{ formatDate(p.last_visit) }}
            </span>
            <span v-if="p.emergency_contact_name" class="cl-tag cl-emg" :title="p.emergency_contact_name + ' · ' + (p.emergency_contact_phone ?? '')">
              <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
              Emergency contact
            </span>
            <span v-if="p.medical_history" class="cl-tag cl-med" :title="p.medical_history">
              <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
              Medical history
            </span>
          </div>
        </div>

        <!-- Age & Date -->
        <div class="p-meta">
          <div class="p-age">{{ calcAge(p.date_of_birth) }}<span>yrs</span></div>
          <div class="p-reg">
            <span class="reg-label">Registered</span>
            <span class="reg-date">{{ formatDate(p.created_at) }}</span>
          </div>
        </div>

        <!-- Actions -->
        <div v-if="auth.can('admin','receptionist')" class="p-actions" @click.stop>
          <button @click="openModal(p)" class="act-btn edit">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Edit
          </button>
          <button v-if="auth.can('admin')" @click="deletePatient(p)" class="act-btn delete">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            Delete
          </button>
        </div>

        <!-- Arrow -->
        <svg class="p-arrow" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
      </div>

      <!-- Empty -->
      <div v-if="!patients.length" class="empty-state">
        <div class="empty-icon">
          <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        </div>
        <p class="empty-title">No patients found</p>
        <p class="empty-sub">{{ search ? 'Try a different search term.' : 'Add your first patient to get started.' }}</p>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.last_page > 1" class="pagination">
      <button class="page-btn" :disabled="pagination.current_page === 1" @click="fetchPage(pagination.current_page - 1)">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg> Previous
      </button>
      <button v-for="pg in visiblePages" :key="pg" class="page-btn"
        :class="{ active: pg === pagination.current_page, ellipsis: pg === '...' }" :disabled="pg === '...'"
        @click="pg !== '...' && fetchPage(pg)">{{ pg }}</button>
      <button class="page-btn" :disabled="pagination.current_page === pagination.last_page" @click="fetchPage(pagination.current_page + 1)">
        Next <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
      </button>
    </div>

  </div>

  <!-- Add/Edit Patient Modal — teleported to body to escape parent transform/animation -->
  <Teleport to="body">
    <div v-if="showModal" class="modal-backdrop" @click.self="closeModal">
      <div class="modal-box">
        <div class="modal-header">
          <h3 class="modal-title">{{ editingId ? 'Edit Patient' : 'Add New Patient' }}</h3>
          <button @click="closeModal" class="modal-close">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <form @submit.prevent="savePatient" class="modal-body">
          <div class="form-grid">
            <div class="fg">
              <label class="fl">First Name *</label>
              <input v-model="form.first_name" class="fi" required />
            </div>
            <div class="fg">
              <label class="fl">Last Name *</label>
              <input v-model="form.last_name" class="fi" required />
            </div>
            <div class="fg">
              <label class="fl">Contact Number</label>
              <input v-model="form.phone" class="fi" placeholder="09XX XXX XXXX" />
            </div>
            <div class="fg">
              <label class="fl">Email Address</label>
              <input v-model="form.email" type="email" class="fi" />
            </div>
            <div class="fg">
              <label class="fl">Date of Birth *</label>
              <input v-model="form.date_of_birth" type="date" class="fi" required />
            </div>
            <div class="fg">
              <label class="fl">Gender *</label>
              <select v-model="form.gender" class="fi" required>
                <option value="">Select gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div class="fg fg--full">
              <label class="fl">Address</label>
              <input v-model="form.address" class="fi" placeholder="Street, City, Province" />
            </div>

            <div class="fg fg--full section-divider"><span>Emergency Contact</span></div>
            <div class="fg">
              <label class="fl">Contact Name</label>
              <input v-model="form.emergency_contact_name" class="fi" />
            </div>
            <div class="fg">
              <label class="fl">Contact Phone</label>
              <input v-model="form.emergency_contact_phone" class="fi" />
            </div>

            <div class="fg fg--full section-divider"><span>Medical Information (Optional)</span></div>
            <div class="fg fg--full">
              <label class="fl">Medical History / Notes</label>
              <textarea v-model="form.medical_history" class="fi fi--ta" placeholder="Allergies, conditions, medications..." rows="3"></textarea>
            </div>

            <div v-if="formError" class="fg fg--full">
              <div class="text-sm text-red-600 bg-red-50 rounded-lg px-4 py-2">{{ formError }}</div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" @click="closeModal" class="btn btn-secondary">Cancel</button>
            <button type="submit" class="btn btn-success" :disabled="saving">{{ saving ? 'Saving...' : 'Save Patient' }}</button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

const patients   = ref([])
const pagination = ref({})
const globalStats = ref({ total: 0, male_count: 0, female_count: 0, new_this_month: 0 })
const loading    = ref(true)
const search     = ref('')
const showModal  = ref(false)
const editingId  = ref(null)
const saving     = ref(false)
const formError  = ref('')

const emptyForm = () => ({ first_name: '', last_name: '', date_of_birth: '', gender: '', phone: '', email: '', address: '', emergency_contact_name: '', emergency_contact_phone: '', medical_history: '' })
const form = ref(emptyForm())

let debounceTimer

function debouncedFetch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => fetchPage(1), 400)
}

async function fetchStats() {
  try {
    const { data } = await api.get('/patients/stats')
    globalStats.value = data
  } catch {}
}

async function fetchPage(page = 1) {
  loading.value = true
  try {
    const { data } = await api.get('/patients', { params: { page, search: search.value } })
    patients.value   = data.data
    pagination.value = { current_page: data.current_page, last_page: data.last_page, total: data.total }
  } catch { /* backend unavailable */ }
  finally { loading.value = false }
}

function openModal(patient = null) {
  formError.value = ''
  if (patient) {
    editingId.value = patient.id
    form.value = { ...patient, date_of_birth: patient.date_of_birth?.split('T')[0] }
  } else {
    editingId.value = null
    form.value = emptyForm()
  }
  showModal.value = true
}

function closeModal() { showModal.value = false }

async function savePatient() {
  formError.value = ''
  saving.value = true
  try {
    if (editingId.value) {
      await api.put(`/patients/${editingId.value}`, form.value)
    } else {
      await api.post('/patients', form.value)
    }
    closeModal()
    fetchPage(pagination.value.current_page ?? 1)
    fetchStats()
  } catch (e) {
    formError.value = Object.values(e.response?.data?.errors ?? {}).flat().join(' ') || 'Failed to save patient.'
  } finally {
    saving.value = false
  }
}

async function deletePatient(patient) {
  if (!confirm(`Delete patient ${patient.first_name} ${patient.last_name}?`)) return
  await api.delete(`/patients/${patient.id}`)
  fetchPage(pagination.value.current_page ?? 1)
  fetchStats()
}

function formatDate(d) { return d ? new Date(d).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '—' }

function calcAge(dob) {
  if (!dob) return '—'
  const today = new Date(), birth = new Date(dob)
  let age = today.getFullYear() - birth.getFullYear()
  if (today.getMonth() < birth.getMonth() || (today.getMonth() === birth.getMonth() && today.getDate() < birth.getDate())) age--
  return age
}

function avatarColor(gender) {
  return { male: 'av-blue', female: 'av-pink', other: 'av-green' }[gender] ?? 'av-gray'
}

const maleCount    = computed(() => globalStats.value.male_count    ?? 0)
const femaleCount  = computed(() => globalStats.value.female_count  ?? 0)
const newThisMonth = computed(() => globalStats.value.new_this_month ?? 0)

const visiblePages = computed(() => {
  const pages = [], total = pagination.value.last_page ?? 1, cur = pagination.value.current_page ?? 1
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1)
  pages.push(1)
  if (cur > 3) pages.push('...')
  for (let i = Math.max(2, cur - 1); i <= Math.min(total - 1, cur + 1); i++) pages.push(i)
  if (cur < total - 2) pages.push('...')
  pages.push(total)
  return pages
})

onMounted(() => { fetchPage(); fetchStats() })
</script>

<style scoped>
.patients { padding: 28px 32px 40px; }

/* ── Header ──────────────────────────────────────────────── */
.page-header {
  display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;
}
.header-icon {
  width: 44px; height: 44px; border-radius: 12px;
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
  color: #fff;
}
.page-title { font-size: 20px; font-weight: 800; color: #111827; margin: 0; }
.page-sub   { font-size: 13px; color: #9ca3af; margin: 2px 0 0; }
.btn-add {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 10px 20px; border: none; border-radius: 10px;
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  color: #fff; font-size: 13px; font-weight: 700; font-family: inherit;
  cursor: pointer; box-shadow: 0 4px 12px rgba(59,130,246,.3);
  transition: opacity .2s, transform .2s;
}
.btn-add:hover { opacity: .9; transform: translateY(-1px); }

/* ── Stats Row ───────────────────────────────────────────── */
.stats-row {
  display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap;
}
.stat-pill {
  display: flex; align-items: center; gap: 12px;
  background: rgba(255,255,255,0.9); backdrop-filter: blur(6px);
  border: 1.5px solid #f3f4f6; border-radius: 12px;
  padding: 12px 18px; flex: 1; min-width: 130px;
}
.sp-icon {
  width: 36px; height: 36px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.sp-icon.blue   { background: #eff6ff; color: #3b82f6; }
.sp-icon.indigo { background: #eef2ff; color: #6366f1; }
.sp-icon.pink   { background: #fdf2f8; color: #ec4899; }
.sp-icon.green  { background: #f0fdf4; color: #22c55e; }
.sp-val { font-size: 20px; font-weight: 800; color: #111827; margin: 0; line-height: 1.1; }
.sp-lbl { font-size: 11px; color: #9ca3af; font-weight: 600; margin: 0; }

/* ── Search Bar ──────────────────────────────────────────── */
.search-bar { margin-bottom: 16px; }
.search-wrap {
  position: relative; display: flex; align-items: center;
}
.search-icon {
  position: absolute; left: 14px; color: #9ca3af; pointer-events: none;
}
.search-input {
  width: 100%; padding: 12px 42px; border: 1.5px solid #e5e7eb; border-radius: 12px;
  font-family: inherit; font-size: 14px; color: #1f2937; background: rgba(255,255,255,0.9);
  outline: none; transition: border-color .2s, box-shadow .2s;
}
.search-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.12); }
.search-clear {
  position: absolute; right: 12px; background: #f3f4f6; border: none; border-radius: 50%;
  width: 22px; height: 22px; display: flex; align-items: center; justify-content: center;
  cursor: pointer; color: #6b7280; transition: background .2s;
}
.search-clear:hover { background: #e5e7eb; }
.results-note { font-size: 12px; color: #6b7280; margin: 6px 0 0 4px; }

/* ── Loading ─────────────────────────────────────────────── */
.loading-wrap {
  display: flex; flex-direction: column; align-items: center; gap: 14px;
  padding: 64px 0; color: #9ca3af; font-size: 14px;
}
.spinner {
  width: 36px; height: 36px; border: 3px solid #e5e7eb;
  border-top-color: #3b82f6; border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Patient Cards ───────────────────────────────────────── */
.patient-list { display: flex; flex-direction: column; gap: 10px; }

.patient-card {
  display: flex; align-items: center; gap: 16px;
  padding: 16px 20px; cursor: pointer;
  background: rgba(255,255,255,0.92); backdrop-filter: blur(6px);
  border: 1.5px solid #f3f4f6; border-radius: 14px;
  transition: box-shadow .2s, transform .2s, border-color .2s;
}
.patient-card:hover {
  box-shadow: 0 8px 24px rgba(0,0,0,.09);
  transform: translateX(3px);
  border-color: #dbeafe;
}

/* Avatar */
.p-avatar {
  width: 48px; height: 48px; border-radius: 14px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 15px; font-weight: 800; letter-spacing: -.5px;
}
.av-blue  { background: #dbeafe; color: #1d4ed8; }
.av-pink  { background: #fce7f3; color: #be185d; }
.av-green { background: #dcfce7; color: #15803d; }
.av-gray  { background: #f3f4f6; color: #4b5563; }

/* Main info block — grows to fill space */
.p-main { flex: 1; min-width: 0; }
.p-name-row {
  display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 5px;
}
.p-name  { font-size: 14px; font-weight: 700; color: #111827; }
.p-code  {
  font-size: 11px; font-weight: 700; color: #6366f1;
  background: #eef2ff; border-radius: 6px; padding: 2px 7px;
  letter-spacing: .3px;
}
.gender-badge {
  font-size: 10px; font-weight: 700; border-radius: 5px;
  padding: 2px 7px; text-transform: capitalize;
}
.gender-badge.male   { background: #dbeafe; color: #1d4ed8; }
.gender-badge.female { background: #fce7f3; color: #be185d; }
.gender-badge.other  { background: #dcfce7; color: #15803d; }

.p-details { display: flex; gap: 14px; flex-wrap: wrap; }
.p-detail-item {
  display: flex; align-items: center; gap: 5px;
  font-size: 12px; color: #6b7280;
  max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}

/* Clinical summary chips */
.p-clinical { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 7px; }
.cl-tag {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 11px; font-weight: 600; border-radius: 6px;
  padding: 3px 8px; white-space: nowrap;
}
.cl-visit { background: #eff6ff; color: #2563eb; }
.cl-rx    { background: #f0fdf4; color: #15803d; }
.cl-last  { background: #fefce8; color: #854d0e; }
.cl-emg   { background: #fff1f2; color: #be123c; cursor: default; }
.cl-med   { background: #f5f3ff; color: #6d28d9; cursor: default; }

/* Age + Registration */
.p-meta { text-align: right; flex-shrink: 0; }
.p-age  {
  font-size: 22px; font-weight: 800; color: #111827; line-height: 1;
}
.p-age span { font-size: 11px; font-weight: 600; color: #9ca3af; margin-left: 2px; }
.p-reg  { margin-top: 4px; }
.reg-label { display: block; font-size: 10px; color: #9ca3af; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
.reg-date  { font-size: 12px; color: #6b7280; font-weight: 600; }

/* Action buttons */
.p-actions { display: flex; gap: 8px; flex-shrink: 0; }
.act-btn {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 7px 12px; border-radius: 8px; border: 1.5px solid transparent;
  font-family: inherit; font-size: 12px; font-weight: 700; cursor: pointer;
  transition: all .2s;
}
.act-btn.edit   { background: #eff6ff; color: #2563eb; border-color: #bfdbfe; }
.act-btn.edit:hover   { background: #dbeafe; }
.act-btn.delete { background: #fff1f2; color: #be123c; border-color: #fecdd3; }
.act-btn.delete:hover { background: #ffe4e6; }

.p-arrow { color: #d1d5db; flex-shrink: 0; transition: color .2s; }
.patient-card:hover .p-arrow { color: #3b82f6; }

/* ── Empty State ─────────────────────────────────────────── */
.empty-state {
  display: flex; flex-direction: column; align-items: center;
  padding: 72px 0; color: #9ca3af;
}
.empty-icon {
  width: 64px; height: 64px; border-radius: 20px;
  background: #f3f4f6; display: flex; align-items: center; justify-content: center;
  margin-bottom: 16px; color: #d1d5db;
}
.empty-title { font-size: 16px; font-weight: 700; color: #4b5563; margin: 0 0 4px; }
.empty-sub   { font-size: 13px; color: #9ca3af; margin: 0; }

/* ── Pagination ──────────────────────────────────────────── */
.pagination { display: flex; align-items: center; justify-content: center; gap: 8px; flex-wrap: wrap; margin-top: 28px; }
.page-btn {
  display: inline-flex; align-items: center; gap: 6px; padding: 10px 16px;
  border: 1.5px solid #e5e7eb; border-radius: 10px;
  background: rgba(255,255,255,0.9); font-family: inherit;
  font-size: 13px; font-weight: 600; color: #4b5563; cursor: pointer;
  transition: all .2s;
}
.page-btn:hover:not(:disabled):not(.ellipsis) { background: #eff6ff; border-color: #3b82f6; color: #2563eb; }
.page-btn.active   { background: #1f2937; border-color: #1f2937; color: #fff; }
.page-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.page-btn.ellipsis { border: none; }

/* ── Modal ───────────────────────────────────────────────── */
.modal-backdrop {
  position: fixed; inset: 0; z-index: 9999;
  background: rgba(0,0,0,0.5);
  display: flex; align-items: center; justify-content: center;
  padding: 16px; overflow-y: auto;
}
.modal-box {
  background: #fff; border-radius: 14px;
  box-shadow: 0 24px 64px rgba(0,0,0,.18);
  width: 100%; max-width: 680px;
  margin: auto; display: flex; flex-direction: column;
}
.modal-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 20px 24px; border-bottom: 1.5px solid #f3f4f6;
}
.modal-title { font-size: 16px; font-weight: 700; color: #111827; margin: 0; }
.modal-close {
  color: #9ca3af; background: none; border: none; cursor: pointer;
  padding: 4px; border-radius: 6px; transition: color .2s;
}
.modal-close:hover { color: #374151; }
.modal-body { padding: 24px; }
.modal-footer {
  display: flex; justify-content: flex-end; gap: 12px;
  margin-top: 24px; padding-top: 16px; border-top: 1.5px solid #f3f4f6;
}

/* ── Form ────────────────────────────────────────────────── */
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px 18px; }
.fg        { display: flex; flex-direction: column; gap: 6px; }
.fg--full  { grid-column: 1 / -1; }
.fl        { font-size: 12px; font-weight: 700; color: #1f2937; }
.fi {
  padding: 10px 14px; border: 1.5px solid #e5e7eb; border-radius: 10px;
  font-family: inherit; font-size: 13px; color: #1f2937;
  background: #fff; outline: none; width: 100%;
  transition: border-color .2s;
}
.fi:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.12); }
.fi--ta   { resize: vertical; min-height: 80px; }
.section-divider {
  font-size: 11px; font-weight: 700; color: #2563eb;
  text-transform: uppercase; letter-spacing: 1px;
  border-bottom: 1.5px solid #bfdbfe; padding-bottom: 6px;
  margin-top: 8px;
}

/* ── Shared buttons ──────────────────────────────────────── */
.btn {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 10px 20px; border-radius: 10px; border: none;
  font-family: inherit; font-size: 13px; font-weight: 700; cursor: pointer;
  transition: opacity .2s, transform .2s;
}
.btn-secondary { background: #f3f4f6; color: #374151; }
.btn-secondary:hover { background: #e5e7eb; }
.btn-success { background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff; box-shadow: 0 4px 12px rgba(34,197,94,.3); }
.btn-success:hover:not(:disabled) { opacity: .9; transform: translateY(-1px); }
.btn-success:disabled { opacity: .6; cursor: not-allowed; }
</style>