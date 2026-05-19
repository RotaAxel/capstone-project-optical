<template>
  <div class="patients fade-up">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6" >
      <div>
        <h2 class="text-xl font-bold text-gray-900" style="margin:0.2rem;">Patient Records</h2>
        <p class="text-sm text-gray-500 mt-1" style="margin: 0.2rem;">{{ pagination.total ?? 0 }} total patients</p>
      </div>
      <button v-if="auth.can('admin','receptionist')" @click="openModal()" class="btn btn-success" style="margin: 0.2rem;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Patient
      </button>
    </div>

    <!-- Search -->
    <div class="card p-4 " style="margin: 0.2rem;">
      <input v-model="search" @input="debouncedFetch" type="text" placeholder="🔍 Search by name, patient code, or phone..." class="fi max-w-md" />
    </div>

    <!-- Patient List -->
    <div class="patient-list mb-6">
      <div v-if="loading" class="flex items-center justify-center py-16">
        <div class="animate-spin w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full"></div>
      </div>
      <div v-for="p in patients" v-else :key="p.id" class="patient-card card" @click="$router.push(`/patients/${p.id}`)">
        <div class="flex items-center gap-4 flex-1">
          <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 text-sm font-bold shrink-0">
            {{ p.first_name?.charAt(0) }}{{ p.last_name?.charAt(0) }}
          </div>
          <div>
            <h3 class="patient-name">{{ p.first_name }} {{ p.last_name }}</h3>
            <p class="patient-contact"><span class="lbl">Code:</span> {{ p.patient_code }} • <span class="lbl">Phone:</span> {{ p.phone ?? '—' }}</p>
          </div>
        </div>
        <div class="patient-meta text-right">
          <span class="meta-label">Registered</span>
          <span class="meta-date">{{ formatDate(p.created_at) }}</span>
        </div>
        <div v-if="auth.can('admin','receptionist')" style="display: flex; flex-direction: column; gap: 8px; margin-left: 16px; width: 100px;">
          <button @click.stop="openModal(p)" class="btn btn-secondary btn-sm">Edit</button>
          <button v-if="auth.can('admin')" @click.stop="deletePatient(p)" class="btn btn-danger btn-sm">Delete</button>
        </div>
      </div>
      <div v-if="!loading && !patients.length" class="text-center py-12 text-gray-400 card">
        No patients found.
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.last_page > 1" class="pagination">
      <button class="page-btn" :disabled="pagination.current_page === 1" @click="fetchPage(pagination.current_page - 1)">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg> Previous
      </button>
      <button v-for="p in visiblePages" :key="p" class="page-btn"
        :class="{ active: p === pagination.current_page, ellipsis: p === '...' }" :disabled="p === '...'"
        @click="p !== '...' && fetchPage(p)">{{ p }}</button>
      <button class="page-btn" :disabled="pagination.current_page === pagination.last_page" @click="fetchPage(pagination.current_page + 1)">
        Next <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
      </button>
    </div>

    <!-- Add/Edit Patient Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-screen overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
          <h3 class="font-semibold text-gray-900">{{ editingId ? 'Edit Patient' : 'Add New Patient' }}</h3>
          <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <form @submit.prevent="savePatient" class="px-6 py-5">
          <div class="form-grid">
            <!-- Patient Info -->
            <div class="fg">
              <label class="fl">First Name *</label>
              <input v-model="form.first_name" class="fi" required />
            </div>
            <div class="fg">
              <label class="fl">Last Name *</label>
              <input v-model="form.last_name" class="fi" required />
            </div>
            <div class="fg">
              <label class="fl">Contact Number *</label>
              <input v-model="form.phone" class="fi" placeholder="09XX XXX XXXX" required />
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

            <!-- Emergency Contact -->
            <div class="fg fg--full section-divider"><span>Emergency Contact</span></div>
            <div class="fg">
              <label class="fl">Contact Name</label>
              <input v-model="form.emergency_contact_name" class="fi" />
            </div>
            <div class="fg">
              <label class="fl">Contact Phone</label>
              <input v-model="form.emergency_contact_phone" class="fi" />
            </div>

            <!-- Medical History -->
            <div class="fg fg--full section-divider"><span>Medical Information (Optional)</span></div>
            <div class="fg fg--full">
              <label class="fl">Medical History / Notes</label>
              <textarea v-model="form.medical_history" class="fi fi--ta" placeholder="Allergies, conditions, medications..." rows="3"></textarea>
            </div>

            <div v-if="formError" class="fg fg--full">
              <div class="text-sm text-red-600 bg-red-50 rounded-lg px-4 py-2">{{ formError }}</div>
            </div>
          </div>

          <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
            <button type="button" @click="closeModal" class="btn btn-secondary">Cancel</button>
            <button type="submit" class="btn btn-success" :disabled="saving">{{ saving ? 'Saving...' : 'Save Patient' }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

const patients   = ref([])
const pagination = ref({})
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
}

function formatDate(d) { return d ? new Date(d).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '—' }

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

onMounted(() => fetchPage())
</script>

<style scoped>
.patients { padding: 28px 32px 40px; }
.patient-list { display: flex; flex-direction: column; gap: 18px; margin: 10px}
.patient-card {
  display: flex; align-items: center; justify-content: space-between;
  padding: 24px 28px; cursor: pointer;
  background: rgba(255, 255, 255, 0.92); backdrop-filter: blur(6px);
  border-radius: 12px;
  transition: all 0.3s ease;
}
.patient-card:hover { box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); transform: translateX(4px); }
.patient-name    { font-size: 1rem; font-weight: 700; color: #1f2937; margin-bottom: 4px; }
.patient-contact { font-size: 12px; color: #9ca3af; }
.lbl             { font-weight: 600; color: #4b5563; }
.patient-meta    { text-align: right; margin: 0 24px; }
.meta-label      { display: block; font-size: 12px; color: #9ca3af; }
.meta-date       { font-size: 13px; font-weight: 700; color: #1f2937; }
.pagination { display: flex; align-items: center; justify-content: center; gap: 8px; flex-wrap: wrap; margin-top: 28px; }
.page-btn {
  display: inline-flex; align-items: center; gap: 6px; padding: 10px 16px;
  border: 1.5px solid #e5e7eb; border-radius: 10px;
  background: rgba(255, 255, 255, 0.9); font-family: inherit;
  font-size: 13px; font-weight: 600; color: #4b5563; cursor: pointer;
  transition: all 0.3s ease;
}
.page-btn:hover:not(:disabled):not(.ellipsis) { background: #e0f2fe; border-color: #06b6d4; color: #0e7490; }
.page-btn.active  { background: #1f2937; border-color: #1f2937; color: #fff; }
.page-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.page-btn.ellipsis { border: none; }

/* Form */
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px 18px; }
.fg        { display: flex; flex-direction: column; gap: 6px; }
.fg--full  { grid-column: 1 / -1; }
.fl        { font-size: 12px; font-weight: 700; color: #1f2937; }
.fi {
  padding: 10px 14px; border: 1.5px solid #e5e7eb; border-radius: 10px;
  font-family: inherit; font-size: 13px; color: #1f2937;
  background: #fff; outline: none; width: 100%;
  transition: border-color 0.3s ease;
}
.fi:focus { border-color: #06b6d4; box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.12); }
.fi--ta   { resize: vertical; min-height: 80px; }
.section-divider {
  font-size: 11px; font-weight: 700; color: #0e7490;
  text-transform: uppercase; letter-spacing: 1px;
  border-bottom: 1.5px solid #a5f3fc; padding-bottom: 6px;
  margin-top: 8px;
}
</style>