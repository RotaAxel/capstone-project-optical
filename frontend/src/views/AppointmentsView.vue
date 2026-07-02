<template>
  <div class="appts-page fade-up">

    <!-- ── Header ─────────────────────────────────────────────── -->
    <div class="page-header">
      <div class="flex items-center gap-3">
        <div class="header-icon">
          <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </div>
        <div>
          <h2 class="page-title">Appointments</h2>
          <p class="page-sub">Schedule &amp; track patient consultations</p>
        </div>
      </div>
      <button v-if="auth.can('admin','receptionist')" @click="openModal()" class="btn-book">
        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
        Book Appointment
      </button>
    </div>

    <!-- ── Stats ──────────────────────────────────────────────── -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-icon blue">
          <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </div>
        <div>
          <p class="stat-num">{{ apptStats.total ?? 0 }}</p>
          <p class="stat-lbl">Total</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon amber">
          <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
          <p class="stat-num">{{ apptStats.scheduled ?? 0 }}</p>
          <p class="stat-lbl">Scheduled</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green">
          <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
          <p class="stat-num">{{ apptStats.completed ?? 0 }}</p>
          <p class="stat-lbl">Completed</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon red">
          <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
          <p class="stat-num">{{ apptStats.cancelled_no_show ?? 0 }}</p>
          <p class="stat-lbl">Cancelled / No-show</p>
        </div>
      </div>
    </div>

    <!-- ── Filters ────────────────────────────────────────────── -->
    <div class="filter-bar">
      <div class="filter-field">
        <svg class="filter-icon" width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <input v-model="filterDate" type="date" class="filter-input" @change="applyFilters" />
      </div>
      <div class="filter-field">
        <svg class="filter-icon" width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/></svg>
        <select v-model="filterStatus" class="filter-input" @change="applyFilters">
          <option value="">All Status</option>
          <option value="scheduled">Scheduled</option>
          <option value="completed">Completed</option>
          <option value="cancelled">Cancelled</option>
          <option value="no_show">No Show</option>
        </select>
      </div>
      <button v-if="filterDate || filterStatus" @click="clearFilters" class="clear-btn">
        Clear filters
      </button>
    </div>

    <!-- ── Appointment List ───────────────────────────────────── -->
    <div class="appt-list">
      <div v-if="loading" class="flex items-center justify-center py-20">
        <div class="animate-spin w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full"></div>
      </div>

      <template v-else-if="appointments.length">
        <div v-for="a in appointments" :key="a.id" class="appt-card" :class="[statusBorder(a.status), { 'card-highlighted': a.id === highlightId }]" :data-appt-id="a.id">
          <!-- Status stripe -->
          <div class="status-stripe" :class="statusStripe(a.status)"></div>

          <!-- Patient -->
          <div class="appt-patient">
            <div class="patient-avatar">{{ a.patient?.first_name?.charAt(0) }}{{ a.patient?.last_name?.charAt(0) }}</div>
            <div>
              <p class="patient-name">{{ a.patient?.first_name }} {{ a.patient?.last_name }}</p>
              <p class="patient-sub">{{ a.patient?.patient_code ?? '—' }}</p>
            </div>
          </div>

          <!-- Date & Time -->
          <div class="appt-datetime">
            <p class="appt-date">{{ fmtDate(a.appointment_date) }}</p>
            <p class="appt-time">{{ fmtTime(a.appointment_date) }}</p>
          </div>

          <!-- Doctor -->
          <div class="appt-doctor">
            <p class="doctor-label">Optometrist</p>
            <p class="doctor-name">{{ a.optometrist?.name ?? '—' }}</p>
          </div>

          <!-- Type -->
          <div class="appt-type">
            <span class="type-badge capitalize">{{ a.type?.replace(/_/g, ' ') }}</span>
          </div>

          <!-- Status -->
          <div class="appt-status-col">
            <span class="status-pill" :class="statusPill(a.status)">{{ a.status?.replace(/_/g, ' ') }}</span>
          </div>

          <!-- Actions -->
          <div class="appt-actions">
            <button v-if="a.status === 'scheduled'" @click="updateStatus(a, 'completed')" class="act-btn act-green">
              <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
              Done
            </button>
            <button v-if="a.status === 'scheduled' && auth.can('admin','receptionist')" @click="updateStatus(a, 'cancelled')" class="act-btn act-red">
              <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
              Cancel
            </button>
            <button v-if="auth.can('admin','receptionist')" @click="openModal(a)" class="act-btn act-gray">
              <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
              Edit
            </button>
          </div>
        </div>
      </template>

      <div v-else class="empty-state">
        <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="empty-icon"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <p class="empty-text">No appointments found</p>
        <p class="empty-sub">Try adjusting your filters or book a new appointment</p>
      </div>
    </div>

    <!-- ── Pagination ─────────────────────────────────────────── -->
    <div v-if="pagination.last_page > 1" class="pagination">
      <button class="page-btn" :disabled="pagination.current_page === 1" @click="fetchPage(pagination.current_page - 1)">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg> Prev
      </button>
      <span class="page-info">Page {{ pagination.current_page }} of {{ pagination.last_page }}</span>
      <button class="page-btn" :disabled="pagination.current_page === pagination.last_page" @click="fetchPage(pagination.current_page + 1)">
        Next <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
      </button>
    </div>

  </div>

  <!-- ── Book / Edit Modal (Teleported) ─────────────────────── -->
  <Teleport to="body">
    <div v-if="showModal" class="modal-backdrop" @click.self="closeModal">
      <div class="modal-box">
        <div class="modal-header">
          <div class="flex items-center gap-3">
            <div class="header-icon small">
              <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <h3 class="modal-title">{{ editingId ? 'Edit Appointment' : 'Book Appointment' }}</h3>
          </div>
          <button @click="closeModal" class="modal-close">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <form @submit.prevent="save" class="modal-body">
          <div class="form-grid">
            <div class="fg fg--full">
              <label class="fl">Patient *</label>
              <select v-model="form.patient_id" class="fi" required>
                <option value="">Select patient</option>
                <option v-for="p in patients" :key="p.id" :value="p.id">{{ p.first_name }} {{ p.last_name }}</option>
              </select>
            </div>
            <div class="fg fg--full">
              <label class="fl">Optometrist *</label>
              <select v-model="form.optometrist_id" class="fi" required>
                <option value="">Select optometrist</option>
                <option v-for="u in optometrists" :key="u.id" :value="u.id">{{ u.name }}</option>
              </select>
            </div>
            <div class="fg">
              <label class="fl">Date &amp; Time *</label>
              <input v-model="form.appointment_date" type="datetime-local" class="fi" required />
            </div>
            <div class="fg">
              <label class="fl">Type *</label>
              <select v-model="form.type" class="fi" required>
                <option value="eye_exam">Eye Exam</option>
                <option value="follow_up">Follow-up</option>
                <option value="fitting">Fitting</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div class="fg fg--full">
              <label class="fl">Reason / Notes</label>
              <textarea v-model="form.reason" class="fi fi--ta" rows="3" placeholder="Reason for visit..."></textarea>
            </div>
          </div>

          <div v-if="formError" class="error-msg">{{ formError }}</div>

          <div class="modal-footer">
            <button type="button" @click="closeModal" class="btn-cancel">Cancel</button>
            <button type="submit" class="btn-save" :disabled="saving">{{ saving ? 'Saving...' : (editingId ? 'Save Changes' : 'Book Appointment') }}</button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const auth   = useAuthStore()
const route  = useRoute()
const router = useRouter()
const highlightId = ref(null)

const appointments = ref([])
const patients     = ref([])
const optometrists = ref([])
const pagination   = ref({})
const apptStats    = ref({})
const loading      = ref(true)
const filterDate   = ref('')
const filterStatus = ref('')
const showModal    = ref(false)
const editingId    = ref(null)
const saving       = ref(false)
const formError    = ref('')

const emptyForm = () => ({ patient_id: '', optometrist_id: '', appointment_date: '', type: 'eye_exam', reason: '' })
const form = ref(emptyForm())

async function fetchStats() {
  try {
    const { data } = await api.get('/appointments/stats', {
      params: { date: filterDate.value || undefined, status: filterStatus.value || undefined },
    })
    apptStats.value = data
  } catch { /* */ }
}

function applyFilters() { fetchPage(1); fetchStats() }
function clearFilters() { filterDate.value = ''; filterStatus.value = ''; applyFilters() }

async function fetchPage(page = 1) {
  loading.value = true
  try {
    const { data } = await api.get('/appointments', {
      params: { page, date: filterDate.value || undefined, status: filterStatus.value || undefined }
    })
    appointments.value = data.data
    pagination.value   = { current_page: data.current_page, last_page: data.last_page, total: data.total }
  } catch { /* backend unavailable */ }
  finally { loading.value = false }
}

function openModal(appt = null) {
  formError.value = ''
  if (appt) {
    editingId.value = appt.id
    form.value = { ...appt, appointment_date: appt.appointment_date?.replace(' ', 'T').slice(0, 16) }
  } else {
    editingId.value = null
    form.value = emptyForm()
  }
  showModal.value = true
}

function closeModal() { showModal.value = false }

async function save() {
  formError.value = ''; saving.value = true
  try {
    if (editingId.value) await api.put(`/appointments/${editingId.value}`, form.value)
    else await api.post('/appointments', form.value)
    closeModal(); fetchPage(pagination.value.current_page ?? 1); fetchStats()
  } catch (e) {
    formError.value = Object.values(e.response?.data?.errors ?? {}).flat().join(' ') || 'Failed to save appointment.'
  } finally { saving.value = false }
}

async function updateStatus(appt, status) {
  await api.put(`/appointments/${appt.id}`, { status })
  fetchPage(pagination.value.current_page ?? 1); fetchStats()
}

function statusBorder(s) {
  return { 'border-l-amber-400': s === 'scheduled', 'border-l-green-400': s === 'completed', 'border-l-red-400': s === 'cancelled', 'border-l-gray-300': s === 'no_show' }
}
function statusStripe(s) {
  return { 'bg-amber-400': s === 'scheduled', 'bg-green-400': s === 'completed', 'bg-red-400': s === 'cancelled', 'bg-gray-300': s === 'no_show' }
}
function statusPill(s) {
  return { 'pill-amber': s === 'scheduled', 'pill-green': s === 'completed', 'pill-red': s === 'cancelled', 'pill-gray': s === 'no_show' }
}

function fmtDate(d) { return d ? new Date(d).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '—' }
function fmtTime(d) { return d ? new Date(d).toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' }) : '' }

async function activateHighlight(id) {
  highlightId.value  = id
  filterDate.value   = ''
  filterStatus.value = ''
  loading.value = true
  try {
    const { data } = await api.get('/appointments', { params: { highlight_id: id } })
    appointments.value = data.data
    pagination.value   = { current_page: data.current_page, last_page: data.last_page, total: data.total }
  } catch {}
  finally { loading.value = false }

  await nextTick()
  await new Promise(r => setTimeout(r, 80))
  const el = document.querySelector(`[data-appt-id="${id}"]`)
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'center' })
    setTimeout(() => {
      highlightId.value = null
      router.replace({ path: route.path, query: {} })
    }, 950)
  }
}

watch(() => route.query.highlight, (newId) => {
  if (newId) activateHighlight(Number(newId))
})

onMounted(async () => {
  try {
    const [pts, opts] = await Promise.all([
      api.get('/patients', { params: { per_page: 200 } }),
      api.get('/optometrists'),
    ])
    patients.value     = pts.data.data
    optometrists.value = opts.data
  } catch { loading.value = false }

  fetchStats()
  if (route.query.highlight) {
    activateHighlight(Number(route.query.highlight))
  } else {
    fetchPage()
  }
})
</script>

<style scoped>
.appts-page { padding: 28px 32px 48px; }

@keyframes card-blink {
  0%, 100% { box-shadow: 0 0 0 0 rgba(245,158,11,0); }
  50%       { box-shadow: 0 0 0 7px rgba(245,158,11,.9); background-color: rgba(255,251,235,.6); }
}
.card-highlighted {
  outline: 2px solid #f59e0b;
  animation: card-blink 0.3s ease-in-out 3;
  position: relative; z-index: 1;
}

/* ── Header ─────────────────────────────────────────── */
.page-header {
  display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;
}
.header-icon {
  width: 44px; height: 44px; border-radius: 12px;
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.header-icon svg { color: white; }
.header-icon.small { width: 36px; height: 36px; border-radius: 10px; }

.page-title { font-size: 20px; font-weight: 800; color: #111827; margin: 0; }
.page-sub   { font-size: 13px; color: #9ca3af; margin: 2px 0 0; }

.btn-book {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 10px 20px; background: linear-gradient(135deg, #3b82f6, #2563eb);
  color: white; font-size: 13px; font-weight: 700; border-radius: 10px;
  border: none; cursor: pointer; transition: all 0.2s;
  box-shadow: 0 4px 12px rgba(59,130,246,0.35);
}
.btn-book:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(59,130,246,0.45); }

/* ── Stats ──────────────────────────────────────────── */
.stats-row {
  display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px;
}
.stat-icon svg { color: white; }
.stat-icon.blue  { background: #3b82f6; }
.stat-icon.amber { background: #f59e0b; }
.stat-icon.green { background: #10b981; }
.stat-icon.red   { background: #ef4444; }
.stat-num  { font-size: 22px; font-weight: 800; color: #111827; line-height: 1; }
.stat-lbl  { font-size: 11px; font-weight: 600; color: #9ca3af; margin-top: 3px; }

/* ── Filters ────────────────────────────────────────── */
.filter-bar {
  display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
  background: white; border: 1.5px solid #f3f4f6; border-radius: 14px;
  padding: 16px 20px; margin-bottom: 20px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}
.filter-field {
  display: flex; align-items: center; gap: 8px;
  background: #f9fafb; border: 1.5px solid #e5e7eb; border-radius: 10px;
  padding: 0 12px;
}
.filter-icon { color: #9ca3af; flex-shrink: 0; }
.filter-input {
  padding: 9px 4px; background: transparent; border: none; outline: none;
  font-size: 13px; font-weight: 500; color: #374151; font-family: inherit;
  min-width: 130px;
}
.clear-btn {
  padding: 9px 16px; background: #fee2e2; color: #dc2626;
  font-size: 12px; font-weight: 700; border: none; border-radius: 8px;
  cursor: pointer; transition: background 0.2s;
}
.clear-btn:hover { background: #fecaca; }

/* ── Appointment Cards ───────────────────────────────── */
.appt-list { display: flex; flex-direction: column; gap: 12px; margin-bottom: 28px; }

.appt-card {
  display: flex; align-items: center; gap: 0;
  background: white; border-radius: 14px; border: 1.5px solid #f3f4f6;
  box-shadow: 0 1px 6px rgba(0,0,0,0.05); overflow: hidden;
  transition: box-shadow 0.2s, transform 0.2s;
}
.appt-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.09); transform: translateY(-1px); }

.status-stripe { width: 5px; min-height: 80px; align-self: stretch; flex-shrink: 0; }

.appt-patient {
  display: flex; align-items: center; gap: 12px;
  padding: 18px 20px; min-width: 200px; flex: 1.5;
}
.patient-avatar {
  width: 40px; height: 40px; border-radius: 10px;
  background: linear-gradient(135deg, #dbeafe, #bfdbfe);
  color: #1d4ed8; font-size: 13px; font-weight: 800;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.patient-name { font-size: 14px; font-weight: 700; color: #111827; margin: 0; }
.patient-sub  { font-size: 11px; color: #9ca3af; font-family: monospace; margin-top: 2px; }

.appt-datetime {
  padding: 18px 16px; min-width: 130px; flex-shrink: 0;
}
.appt-date { font-size: 13px; font-weight: 700; color: #111827; }
.appt-time { font-size: 12px; color: #3b82f6; font-weight: 600; margin-top: 3px; }

.appt-doctor {
  padding: 18px 16px; min-width: 150px; flex-shrink: 0;
}
.doctor-label { font-size: 10px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; }
.doctor-name  { font-size: 13px; font-weight: 600; color: #374151; margin-top: 3px; }

.appt-type { padding: 18px 12px; flex-shrink: 0; }
.type-badge {
  font-size: 11px; font-weight: 700; background: #f3f4f6; color: #374151;
  padding: 5px 12px; border-radius: 99px; white-space: nowrap;
}

.appt-status-col { padding: 18px 12px; flex-shrink: 0; min-width: 110px; }
.status-pill {
  font-size: 11px; font-weight: 700; padding: 5px 12px;
  border-radius: 99px; white-space: nowrap; text-transform: capitalize;
}
.pill-amber { background: #fef3c7; color: #92400e; }
.pill-green { background: #dcfce7; color: #166534; }
.pill-red   { background: #fee2e2; color: #991b1b; }
.pill-gray  { background: #f3f4f6; color: #4b5563; }

.appt-actions {
  display: flex; align-items: center; gap: 8px; padding: 18px 20px;
  flex-shrink: 0; margin-left: auto;
}
.act-btn {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 7px 14px; border-radius: 8px; border: none; cursor: pointer;
  font-size: 12px; font-weight: 700; font-family: inherit; transition: all 0.15s;
}
.act-green { background: #dcfce7; color: #166534; }
.act-green:hover { background: #bbf7d0; }
.act-red   { background: #fee2e2; color: #991b1b; }
.act-red:hover   { background: #fecaca; }
.act-gray  { background: #f3f4f6; color: #374151; }
.act-gray:hover  { background: #e5e7eb; }

/* ── Empty State ─────────────────────────────────────── */
.empty-state {
  text-align: center; padding: 60px 20px;
  background: white; border: 1.5px solid #f3f4f6; border-radius: 14px;
}
.empty-icon { color: #d1d5db; margin: 0 auto 12px; }
.empty-text { font-size: 15px; font-weight: 700; color: #374151; margin: 0; }
.empty-sub  { font-size: 13px; color: #9ca3af; margin-top: 6px; }

/* ── Pagination ──────────────────────────────────────── */
.pagination {
  display: flex; align-items: center; justify-content: center; gap: 12px;
}
.page-btn {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 10px 18px; background: white; border: 1.5px solid #e5e7eb;
  border-radius: 10px; font-size: 13px; font-weight: 600; color: #374151;
  cursor: pointer; transition: all 0.2s; font-family: inherit;
}
.page-btn:hover:not(:disabled) { border-color: #3b82f6; color: #2563eb; background: #eff6ff; }
.page-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.page-info { font-size: 13px; color: #6b7280; font-weight: 500; }

/* ── Modal ───────────────────────────────────────────── */
.modal-backdrop {
  position: fixed; inset: 0; z-index: 9999;
  background: rgba(0,0,0,0.5);
  display: flex; align-items: center; justify-content: center;
  padding: 16px; overflow-y: auto;
}
.modal-box {
  background: white; border-radius: 16px;
  box-shadow: 0 24px 64px rgba(0,0,0,0.18);
  width: 100%; max-width: 560px; margin: auto;
}
.modal-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 20px 24px; border-bottom: 1.5px solid #f3f4f6;
}
.modal-title { font-size: 16px; font-weight: 700; color: #111827; margin: 0; }
.modal-close {
  color: #9ca3af; background: none; border: none; cursor: pointer;
  padding: 4px; border-radius: 6px; transition: color 0.2s;
}
.modal-close:hover { color: #374151; }
.modal-body { padding: 24px; }
.modal-footer {
  display: flex; justify-content: flex-end; gap: 10px;
  margin-top: 24px; padding-top: 16px; border-top: 1.5px solid #f3f4f6;
}

/* Form */
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.fg        { display: flex; flex-direction: column; gap: 6px; }
.fg--full  { grid-column: 1 / -1; }
.fl        { font-size: 12px; font-weight: 700; color: #374151; }
.fi {
  padding: 10px 14px; border: 1.5px solid #e5e7eb; border-radius: 10px;
  font-family: inherit; font-size: 13px; color: #111827;
  background: #fff; outline: none; width: 100%; transition: border-color 0.2s;
}
.fi:focus  { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.12); }
.fi--ta    { resize: vertical; min-height: 80px; }

.error-msg {
  margin-top: 12px; padding: 10px 14px;
  background: #fef2f2; border: 1px solid #fecaca;
  border-radius: 8px; font-size: 13px; color: #dc2626;
}

.btn-cancel {
  padding: 10px 20px; background: #f9fafb; border: 1.5px solid #e5e7eb;
  border-radius: 10px; font-size: 13px; font-weight: 600; color: #374151;
  cursor: pointer; transition: all 0.2s; font-family: inherit;
}
.btn-cancel:hover { background: #f3f4f6; }
.btn-save {
  padding: 10px 24px;
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  color: white; border: none; border-radius: 10px;
  font-size: 13px; font-weight: 700; cursor: pointer;
  transition: all 0.2s; font-family: inherit;
}
.btn-save:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(59,130,246,0.4); }
.btn-save:disabled { opacity: 0.6; cursor: not-allowed; }

/* Responsive */
@media (max-width: 1100px) {
  .stats-row { grid-template-columns: repeat(2, 1fr); }
  .appt-doctor, .appt-type { display: none; }
}
@media (max-width: 768px) {
  .appts-page { padding: 16px 16px 32px; }
  .stats-row  { grid-template-columns: repeat(2, 1fr); }
  .appt-patient { min-width: unset; }
  .appt-datetime { min-width: unset; }
  .form-grid { grid-template-columns: 1fr; }
}
</style>
