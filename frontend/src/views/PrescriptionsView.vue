<template>
  <div class="rx-page fade-up">

    <!-- ── Header ─────────────────────────────────────────────── -->
    <div class="page-header">
      <div class="flex items-center gap-3">
        <!-- <div class="header-icon">
          <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        </div>
        <div>
          <h2 class="page-title">Prescriptions</h2>
          <p class="page-sub">{{ pagination.total ?? 0 }} total records</p>
        </div> -->
      </div>
      <button v-if="auth.can('admin','optometrist')" @click="openModal()" class="btn-new">
        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
        New Prescription
      </button>
    </div>

    <!-- ── Stats ──────────────────────────────────────────────── -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-icon purple">
          <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        </div>
        <div>
          <p class="stat-num">{{ rxStats.total ?? 0 }}</p>
          <p class="stat-lbl">Total Records</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green">
          <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
          <p class="stat-num">{{ activeCount }}</p>
          <p class="stat-lbl">Active / Valid</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon red">
          <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
          <p class="stat-num">{{ expiredCount }}</p>
          <p class="stat-lbl">Expired</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon blue">
          <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </div>
        <div>
          <p class="stat-num">{{ thisMonthCount }}</p>
          <p class="stat-lbl">This Month</p>
        </div>
      </div>
    </div>

    <!-- ── Filters ────────────────────────────────────────────── -->
    <div class="filter-bar">
      <div class="filter-field flex-1" style="max-width: 320px;">
        <svg class="filter-icon" width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input v-model="search" @input="debouncedFetch" placeholder="Search by patient name…" class="filter-input" />
      </div>
      <div class="filter-field">
        <svg class="filter-icon" width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <input v-model="filterDate" type="date" class="filter-input" @change="applyFilters" />
      </div>
      <button v-if="search || filterDate" @click="clearFilters" class="clear-btn">Clear filters</button>
    </div>

    <!-- ── Prescription Cards ─────────────────────────────────── -->
    <div class="rx-list">
      <div v-if="loading" class="flex items-center justify-center py-20">
        <div class="animate-spin w-8 h-8 border-4 border-purple-500 border-t-transparent rounded-full"></div>
      </div>

      <template v-else-if="prescriptions.length">
        <div v-for="rx in prescriptions" :key="rx.id" class="rx-card" :class="{ 'card-highlighted': rx.id === highlightId }" :data-rx-id="rx.id">

          <!-- Patient -->
          <div class="rx-patient">
            <div class="patient-avatar">{{ rx.patient?.first_name?.charAt(0) }}{{ rx.patient?.last_name?.charAt(0) }}</div>
            <div>
              <RouterLink :to="`/patients/${rx.patient?.id}`" class="patient-name">
                {{ rx.patient?.first_name }} {{ rx.patient?.last_name }}
              </RouterLink>
              <p class="patient-code">{{ rx.patient?.patient_code }}</p>
            </div>
          </div>

          <!-- Dates -->
          <div class="rx-dates">
            <div>
              <p class="date-label">Exam Date</p>
              <p class="date-val">{{ fmtDate(rx.exam_date) }}</p>
            </div>
            <div>
              <p class="date-label">Valid Until</p>
              <span v-if="rx.valid_until" class="validity-badge" :class="isExpired(rx.valid_until) ? 'expired' : 'valid'">
                {{ fmtDate(rx.valid_until) }}
              </span>
              <span v-else class="date-val text-gray-300">—</span>
            </div>
          </div>

          <!-- Optical Values -->
          <div class="rx-values">
            <div class="eye-row">
              <span class="eye-label od">OD</span>
              <span class="eye-data">{{ fmt(rx.od_sphere) }} / {{ fmt(rx.od_cylinder) }} × {{ fmt(rx.od_axis) }}</span>
              <span v-if="rx.od_add" class="eye-add">Add {{ fmt(rx.od_add) }}</span>
            </div>
            <div class="eye-row">
              <span class="eye-label os">OS</span>
              <span class="eye-data">{{ fmt(rx.os_sphere) }} / {{ fmt(rx.os_cylinder) }} × {{ fmt(rx.os_axis) }}</span>
              <span v-if="rx.os_add" class="eye-add">Add {{ fmt(rx.os_add) }}</span>
            </div>
          </div>

          <!-- Doctor -->
          <div class="rx-doctor">
            <p class="date-label">Optometrist</p>
            <p class="doctor-name">Dr. {{ rx.optometrist?.name ?? '—' }}</p>
          </div>

          <!-- Actions -->
          <div class="rx-actions">
            <button @click="openView(rx)" class="act-btn act-purple">
              <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
              View
            </button>
            <button @click="printPrescription(rx)" class="act-btn act-blue">
              <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
              Print
            </button>
            <button v-if="auth.can('admin','optometrist')" @click="openModal(rx)" class="act-btn act-gray">
              <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
              Edit
            </button>
            <button v-if="auth.can('admin')" @click="deletePrescription(rx)" class="act-btn act-red">
              <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              Delete
            </button>
          </div>
        </div>
      </template>

      <div v-else class="empty-state">
        <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="empty-icon"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        <p class="empty-text">No prescriptions found</p>
        <p class="empty-sub">Try adjusting your filters or create a new prescription</p>
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

  <!-- ── Add / Edit Modal ────────────────────────────────────── -->
  <Teleport to="body">
    <div v-if="showModal" class="modal-backdrop" @click.self="closeModal">
      <div class="modal-box modal-lg">
        <div class="modal-header">
          <div class="flex items-center gap-3">
            <div class="modal-icon">
              <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            <h3 class="modal-title">{{ editingId ? 'Edit Prescription' : 'New Prescription' }}</h3>
          </div>
          <button @click="closeModal" class="modal-close">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <form @submit.prevent="save" class="modal-body">
          <!-- Patient + Dates -->
          <div class="form-row-3">
            <div class="fg" style="grid-column: span 1;">
              <label class="fl">Patient *</label>
              <select v-model="form.patient_id" class="fi" required :disabled="!!editingId">
                <option value="">Select patient</option>
                <option v-for="p in patients" :key="p.id" :value="p.id">{{ p.first_name }} {{ p.last_name }}</option>
              </select>
            </div>
            <div class="fg">
              <label class="fl">Exam Date *</label>
              <input v-model="form.exam_date" type="date" class="fi" required />
            </div>
            <div class="fg">
              <label class="fl">Valid Until</label>
              <input v-model="form.valid_until" type="date" class="fi" />
            </div>
          </div>

          <!-- OD -->
          <div class="eye-section od-section">
            <div class="eye-section-label">
              <span class="eye-tag od-tag">OD</span>
              <span class="eye-section-title">Right Eye</span>
            </div>
            <div class="form-row-5">
              <div class="fg"><label class="fl">Sphere</label><input v-model="form.od_sphere" type="number" step="0.25" class="fi mono" placeholder="0.00" /></div>
              <div class="fg"><label class="fl">Cylinder</label><input v-model="form.od_cylinder" type="number" step="0.25" class="fi mono" placeholder="0.00" /></div>
              <div class="fg"><label class="fl">Axis</label><input v-model="form.od_axis" type="number" step="1" min="0" max="180" class="fi mono" placeholder="0" /></div>
              <div class="fg"><label class="fl">Add</label><input v-model="form.od_add" type="number" step="0.25" class="fi mono" placeholder="0.00" /></div>
              <div class="fg"><label class="fl">PD</label><input v-model="form.od_pd" type="number" step="0.5" class="fi mono" placeholder="0.0" /></div>
            </div>
          </div>

          <!-- OS -->
          <div class="eye-section os-section">
            <div class="eye-section-label">
              <span class="eye-tag os-tag">OS</span>
              <span class="eye-section-title">Left Eye</span>
            </div>
            <div class="form-row-5">
              <div class="fg"><label class="fl">Sphere</label><input v-model="form.os_sphere" type="number" step="0.25" class="fi mono" placeholder="0.00" /></div>
              <div class="fg"><label class="fl">Cylinder</label><input v-model="form.os_cylinder" type="number" step="0.25" class="fi mono" placeholder="0.00" /></div>
              <div class="fg"><label class="fl">Axis</label><input v-model="form.os_axis" type="number" step="1" min="0" max="180" class="fi mono" placeholder="0" /></div>
              <div class="fg"><label class="fl">Add</label><input v-model="form.os_add" type="number" step="0.25" class="fi mono" placeholder="0.00" /></div>
              <div class="fg"><label class="fl">PD</label><input v-model="form.os_pd" type="number" step="0.5" class="fi mono" placeholder="0.0" /></div>
            </div>
          </div>

          <!-- Visual Acuity -->
          <div class="eye-section va-section">
            <div class="eye-section-label">
              <span class="eye-section-title">Visual Acuity</span>
            </div>
            <div class="form-row-2">
              <div class="fg"><label class="fl">VA — OD (Right)</label><input v-model="form.visual_acuity_od" type="number" step="0.01" class="fi mono" placeholder="e.g. 0.80" /></div>
              <div class="fg"><label class="fl">VA — OS (Left)</label><input v-model="form.visual_acuity_os" type="number" step="0.01" class="fi mono" placeholder="e.g. 0.80" /></div>
            </div>
          </div>

          <!-- Notes -->
          <div class="fg">
            <label class="fl">Notes / Remarks</label>
            <textarea v-model="form.notes" class="fi fi--ta" rows="3" placeholder="Lens type, frame recommendation, additional remarks…"></textarea>
          </div>

          <div v-if="formError" class="error-msg">{{ formError }}</div>

          <div class="modal-footer">
            <button type="button" @click="closeModal" class="btn-cancel">Cancel</button>
            <button type="submit" class="btn-save" :disabled="saving">{{ saving ? 'Saving…' : (editingId ? 'Save Changes' : 'Save Prescription') }}</button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>

  <!-- ── View Detail Modal ───────────────────────────────────── -->
  <Teleport to="body">
    <div v-if="viewing" class="modal-backdrop" @click.self="viewing = null">
      <div class="modal-box modal-md">
        <div class="modal-header">
          <div>
            <h3 class="modal-title">Prescription Detail</h3>
            <p class="modal-sub">Exam: {{ fmtDate(viewing.exam_date) }}</p>
          </div>
          <button @click="viewing = null" class="modal-close">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <div class="modal-body space-y-5">
          <!-- Patient + Doctor info -->
          <div class="view-meta">
            <div class="view-meta-row">
              <span class="view-key">Patient</span>
              <span class="view-val font-semibold">{{ viewing.patient?.first_name }} {{ viewing.patient?.last_name }}</span>
            </div>
            <div class="view-meta-row">
              <span class="view-key">Optometrist</span>
              <span class="view-val">Dr. {{ viewing.optometrist?.name }}</span>
            </div>
            <div class="view-meta-row">
              <span class="view-key">Valid Until</span>
              <span v-if="viewing.valid_until" class="validity-badge" :class="isExpired(viewing.valid_until) ? 'expired' : 'valid'">
                {{ fmtDate(viewing.valid_until) }}
              </span>
              <span v-else class="view-val text-gray-300">Not set</span>
            </div>
          </div>

          <!-- Rx Table -->
          <div class="rx-table-wrap">
            <table class="rx-table">
              <thead>
                <tr>
                  <th>Eye</th><th>Sphere</th><th>Cylinder</th><th>Axis</th><th>Add</th><th>PD</th><th>VA</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td class="eye-cell od-cell">OD (R)</td>
                  <td>{{ viewing.od_sphere ?? '—' }}</td>
                  <td>{{ viewing.od_cylinder ?? '—' }}</td>
                  <td>{{ viewing.od_axis ?? '—' }}</td>
                  <td>{{ viewing.od_add ?? '—' }}</td>
                  <td>{{ viewing.od_pd ?? '—' }}</td>
                  <td>{{ viewing.visual_acuity_od ?? '—' }}</td>
                </tr>
                <tr>
                  <td class="eye-cell os-cell">OS (L)</td>
                  <td>{{ viewing.os_sphere ?? '—' }}</td>
                  <td>{{ viewing.os_cylinder ?? '—' }}</td>
                  <td>{{ viewing.os_axis ?? '—' }}</td>
                  <td>{{ viewing.os_add ?? '—' }}</td>
                  <td>{{ viewing.os_pd ?? '—' }}</td>
                  <td>{{ viewing.visual_acuity_os ?? '—' }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="viewing.notes" class="notes-box">
            <p class="notes-label">Notes / Remarks</p>
            <p class="notes-text">{{ viewing.notes }}</p>
          </div>

          <div class="modal-footer">
            <button @click="viewing = null" class="btn-cancel">Close</button>
            <button @click="printPrescription(viewing)" class="btn-print">
              <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
              Print Prescription
            </button>
            <button v-if="auth.can('admin','optometrist')" @click="openModal(viewing); viewing = null" class="btn-save">Edit Prescription</button>
          </div>
        </div>
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

const prescriptions = ref([])
const patients      = ref([])
const pagination    = ref({})
const loading       = ref(true)
const search        = ref('')
const filterDate    = ref('')
const showModal     = ref(false)
const viewing       = ref(null)
const editingId     = ref(null)
const saving        = ref(false)
const formError     = ref('')

const emptyForm = () => ({
  patient_id: '', exam_date: new Date().toISOString().split('T')[0], valid_until: '',
  od_sphere: '', od_cylinder: '', od_axis: '', od_add: '', od_pd: '',
  os_sphere: '', os_cylinder: '', os_axis: '', os_add: '', os_pd: '',
  visual_acuity_od: '', visual_acuity_os: '', notes: '',
})
const form = ref(emptyForm())

const rxStats        = ref({})
const activeCount    = computed(() => rxStats.value.active     ?? 0)
const expiredCount   = computed(() => rxStats.value.expired    ?? 0)
const thisMonthCount = computed(() => rxStats.value.this_month ?? 0)

async function fetchStats() {
  try {
    const { data } = await api.get('/prescriptions/stats', {
      params: { date: filterDate.value || undefined },
    })
    rxStats.value = data
  } catch { /* */ }
}

function applyFilters() { fetchPage(1); fetchStats() }
let debounceTimer
function debouncedFetch() { clearTimeout(debounceTimer); debounceTimer = setTimeout(() => applyFilters(), 400) }
function clearFilters() { search.value = ''; filterDate.value = ''; applyFilters() }

async function fetchPage(page = 1) {
  loading.value = true
  try {
    const { data } = await api.get('/prescriptions', {
      params: { page, search: search.value || undefined, date: filterDate.value || undefined }
    })
    prescriptions.value = data.data
    pagination.value    = { current_page: data.current_page, last_page: data.last_page, total: data.total }
  } catch { /* backend unavailable */ }
  finally { loading.value = false }
}

function openModal(rx = null) {
  formError.value = ''
  if (rx) {
    editingId.value = rx.id
    form.value = {
      patient_id: rx.patient_id, exam_date: rx.exam_date, valid_until: rx.valid_until ?? '',
      od_sphere: rx.od_sphere ?? '', od_cylinder: rx.od_cylinder ?? '', od_axis: rx.od_axis ?? '',
      od_add: rx.od_add ?? '', od_pd: rx.od_pd ?? '',
      os_sphere: rx.os_sphere ?? '', os_cylinder: rx.os_cylinder ?? '', os_axis: rx.os_axis ?? '',
      os_add: rx.os_add ?? '', os_pd: rx.os_pd ?? '',
      visual_acuity_od: rx.visual_acuity_od ?? '', visual_acuity_os: rx.visual_acuity_os ?? '',
      notes: rx.notes ?? '',
    }
  } else {
    editingId.value = null
    form.value = emptyForm()
  }
  showModal.value = true
}

function closeModal() { showModal.value = false }
function openView(rx) { viewing.value = rx }

async function save() {
  formError.value = ''; saving.value = true
  try {
    if (editingId.value) await api.put(`/prescriptions/${editingId.value}`, form.value)
    else await api.post('/prescriptions', form.value)
    closeModal(); fetchPage(pagination.value.current_page ?? 1); fetchStats()
  } catch (e) {
    formError.value = Object.values(e.response?.data?.errors ?? {}).flat().join(' ') || 'Failed to save prescription.'
  } finally { saving.value = false }
}

async function deletePrescription(rx) {
  if (!confirm(`Delete prescription for ${rx.patient?.first_name} on ${fmtDate(rx.exam_date)}?`)) return
  try { await api.delete(`/prescriptions/${rx.id}`); fetchPage(pagination.value.current_page ?? 1); fetchStats() } catch { /* */ }
}

function fmt(v) { return v != null && v !== '' ? Number(v).toFixed(2) : '—' }
function fmtDate(d) { return d ? new Date(d).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '—' }
function isExpired(d) { return d && new Date(d) < new Date() }

function printPrescription(rx) {
  const patient = rx.patient
    ? `${rx.patient.first_name} ${rx.patient.last_name} · ${rx.patient.patient_code ?? ''}`
    : '—'

  const validityClass = rx.valid_until ? (isExpired(rx.valid_until) ? 'expired' : 'valid') : ''
  const validityLabel = rx.valid_until ? fmtDate(rx.valid_until) : 'Not specified'

  const html = `<!DOCTYPE html><html><head><meta charset="utf-8">
<title>Prescription — ${patient}</title>
<style>
  *{margin:0;padding:0;box-sizing:border-box}
  body{font-family:Arial,sans-serif;font-size:12px;color:#111;padding:32px 40px;max-width:700px;margin:0 auto}
  .header{display:flex;align-items:center;justify-content:space-between;border-bottom:2.5px solid #1e3a5f;padding-bottom:14px;margin-bottom:22px}
  .clinic{font-size:22px;font-weight:900;color:#1e3a5f;letter-spacing:1px}
  .clinic-sub{font-size:11px;color:#666;margin-top:3px}
  .rx-sym{font-size:48px;font-weight:900;color:#1e3a5f;opacity:.12;line-height:1}
  .sec{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#1e3a5f;border-bottom:1px solid #dce6f0;padding-bottom:5px;margin:18px 0 10px}
  .info-grid{display:grid;grid-template-columns:1fr 1fr;gap:7px 24px;margin-bottom:4px}
  .ir{display:flex;gap:6px;align-items:baseline}
  .ik{font-size:10px;color:#888;font-weight:600;min-width:84px;flex-shrink:0}
  .iv{font-size:12px;font-weight:600;color:#111}
  table{width:100%;border-collapse:collapse;margin:10px 0 18px}
  th{background:#1e3a5f;color:#fff;font-size:10px;font-weight:700;text-transform:uppercase;padding:8px 10px;text-align:center}
  th:first-child{text-align:left}
  td{padding:9px 10px;text-align:center;border-bottom:1px solid #e5e7eb;font-size:12px;font-weight:600}
  td:first-child{text-align:left}
  .od{color:#1e3a5f;font-weight:800;font-size:13px}
  .os{color:#0f766e;font-weight:800;font-size:13px}
  .valid{background:#dcfce7;color:#15803d;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:700}
  .expired{background:#fee2e2;color:#991b1b;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:700}
  .notes{background:#f8fafc;border-left:3px solid #1e3a5f;padding:10px 14px;border-radius:0 6px 6px 0;margin-bottom:20px}
  .nl{font-size:10px;font-weight:700;text-transform:uppercase;color:#888;margin-bottom:4px}
  .nt{font-size:12px;color:#374151}
  .sigs{display:grid;grid-template-columns:1fr 1fr;gap:48px;margin-top:40px}
  .sig{border-top:1px solid #374151;padding-top:6px;text-align:center;font-size:10px;color:#666}
  .foot{margin-top:28px;border-top:1px solid #e5e7eb;padding-top:10px;font-size:10px;color:#aaa;text-align:center}
  @media print{body{padding:20px 24px}}
</style></head><body>
<div class="header">
  <div>
    <div class="clinic">ACEBEDO OPTICAL</div>
    <div class="clinic-sub">Your Vision, Our Mission · Cebu City, Philippines</div>
  </div>
  <div class="rx-sym">Rx</div>
</div>

<div class="sec">Patient Information</div>
<div class="info-grid">
  <div class="ir"><span class="ik">Patient</span><span class="iv">${patient}</span></div>
  <div class="ir"><span class="ik">Exam Date</span><span class="iv">${fmtDate(rx.exam_date)}</span></div>
  <div class="ir"><span class="ik">Optometrist</span><span class="iv">Dr. ${rx.optometrist?.name ?? '—'}</span></div>
  <div class="ir"><span class="ik">Valid Until</span><span class="iv"><span class="${validityClass}">${validityLabel}</span></span></div>
</div>

<div class="sec">Prescription</div>
<table>
  <thead>
    <tr><th>Eye</th><th>Sphere</th><th>Cylinder</th><th>Axis</th><th>Add</th><th>PD</th><th>VA</th></tr>
  </thead>
  <tbody>
    <tr>
      <td class="od">OD (Right)</td>
      <td>${rx.od_sphere ?? '—'}</td><td>${rx.od_cylinder ?? '—'}</td><td>${rx.od_axis ?? '—'}</td>
      <td>${rx.od_add ?? '—'}</td><td>${rx.od_pd ?? '—'}</td><td>${rx.visual_acuity_od ?? '—'}</td>
    </tr>
    <tr>
      <td class="os">OS (Left)</td>
      <td>${rx.os_sphere ?? '—'}</td><td>${rx.os_cylinder ?? '—'}</td><td>${rx.os_axis ?? '—'}</td>
      <td>${rx.os_add ?? '—'}</td><td>${rx.os_pd ?? '—'}</td><td>${rx.visual_acuity_os ?? '—'}</td>
    </tr>
  </tbody>
</table>

${rx.notes ? `<div class="notes"><div class="nl">Notes / Remarks</div><div class="nt">${rx.notes}</div></div>` : ''}

<div class="sigs">
  <div class="sig">Patient Signature &nbsp;/&nbsp; Date</div>
  <div class="sig">Dr. ${rx.optometrist?.name ?? '—'}<br>Licensed Optometrist</div>
</div>

<div class="foot">This prescription is valid for one (1) year from the date of examination unless otherwise stated. · Acebedo Optical Clinic · Cebu City, Philippines</div>
</body></html>`

  const w = window.open('', '_blank', 'width=760,height=700')
  w.document.write(html)
  w.document.close()
  w.focus()
  setTimeout(() => { w.print(); w.close() }, 400)
}

async function activateHighlight(id) {
  highlightId.value = id
  search.value      = ''
  filterDate.value  = ''
  loading.value = true
  try {
    const { data } = await api.get('/prescriptions', { params: { highlight_id: id } })
    prescriptions.value = data.data
    pagination.value    = { current_page: data.current_page, last_page: data.last_page, total: data.total }
  } catch {}
  finally { loading.value = false }

  await nextTick()
  await new Promise(r => setTimeout(r, 80))
  const el = document.querySelector(`[data-rx-id="${id}"]`)
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
    const { data } = await api.get('/patients', { params: { per_page: 500 } })
    patients.value = data.data
  } catch {}

  fetchStats()
  if (route.query.highlight) {
    activateHighlight(Number(route.query.highlight))
  } else {
    fetchPage()
  }
})
</script>

<style scoped>
.rx-page { padding: 28px 32px 48px; }

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
  background: linear-gradient(135deg, #7c3aed, #6d28d9);
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.header-icon svg { color: white; }
.page-title { font-size: 20px; font-weight: 800; color: #111827; margin: 0; }
.page-sub   { font-size: 13px; color: #9ca3af; margin: 2px 0 0; }

.btn-new {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 10px 20px; background: linear-gradient(135deg, #7c3aed, #6d28d9);
  color: white; font-size: 13px; font-weight: 700; border-radius: 10px;
  border: none; cursor: pointer; transition: all 0.2s;
  box-shadow: 0 4px 12px rgba(124,58,237,0.35);
}
.btn-new:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(124,58,237,0.45); }

/* ── Stats ──────────────────────────────────────────── */
.stats-row {
  display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px;
}
.stat-icon svg { color: white; }
.stat-icon.purple { background: #7c3aed; }
.stat-icon.green  { background: #10b981; }
.stat-icon.red    { background: #ef4444; }
.stat-icon.blue   { background: #3b82f6; }
.stat-num { font-size: 22px; font-weight: 800; color: #111827; line-height: 1; }
.stat-lbl { font-size: 11px; font-weight: 600; color: #9ca3af; margin-top: 3px; }

/* ── Filters ────────────────────────────────────────── */
.filter-bar {
  display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
  background: white; border: 1.5px solid #f3f4f6; border-radius: 14px;
  padding: 16px 20px; margin-bottom: 20px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}
.filter-field {
  display: flex; align-items: center; gap: 8px;
  background: #f9fafb; border: 1.5px solid #e5e7eb; border-radius: 10px; padding: 0 12px;
}
.filter-icon  { color: #9ca3af; flex-shrink: 0; }
.filter-input {
  padding: 9px 4px; background: transparent; border: none; outline: none;
  font-size: 13px; font-weight: 500; color: #374151; font-family: inherit; min-width: 130px;
}
.clear-btn {
  padding: 9px 16px; background: #f3e8ff; color: #7c3aed;
  font-size: 12px; font-weight: 700; border: none; border-radius: 8px; cursor: pointer; transition: background 0.2s;
}
.clear-btn:hover { background: #ede9fe; }

/* ── Rx Cards ───────────────────────────────────────── */
.rx-list { display: flex; flex-direction: column; gap: 12px; margin-bottom: 28px; }

.rx-card {
  display: flex; align-items: center; gap: 0;
  background: white; border-radius: 14px; border: 1.5px solid #f3f4f6;
  box-shadow: 0 1px 6px rgba(0,0,0,0.05);
  padding: 0; overflow: hidden; transition: box-shadow 0.2s, transform 0.2s;
}
.rx-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.09); transform: translateY(-1px); }

/* Left purple stripe */
.rx-card::before {
  content: ''; width: 5px; min-height: 80px; align-self: stretch;
  background: linear-gradient(180deg, #7c3aed, #a78bfa);
  flex-shrink: 0;
}

.rx-patient {
  display: flex; align-items: center; gap: 12px;
  padding: 18px 20px; min-width: 210px; flex: 1.2;
}
.patient-avatar {
  width: 40px; height: 40px; border-radius: 10px;
  background: linear-gradient(135deg, #ede9fe, #ddd6fe);
  color: #6d28d9; font-size: 13px; font-weight: 800;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.patient-name {
  font-size: 14px; font-weight: 700; color: #111827; text-decoration: none;
  transition: color 0.2s;
}
.patient-name:hover { color: #7c3aed; }
.patient-code { font-size: 11px; color: #9ca3af; font-family: monospace; margin-top: 2px; }

.rx-dates {
  padding: 18px 16px; display: flex; flex-direction: column; gap: 8px;
  min-width: 160px; flex-shrink: 0;
}
.date-label   { font-size: 10px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; }
.date-val     { font-size: 13px; font-weight: 600; color: #374151; margin-top: 2px; }

.validity-badge {
  display: inline-block; font-size: 11px; font-weight: 700;
  padding: 3px 10px; border-radius: 99px; margin-top: 2px;
}
.validity-badge.valid   { background: #dcfce7; color: #166534; }
.validity-badge.expired { background: #fee2e2; color: #991b1b; }

.rx-values {
  padding: 18px 16px; flex: 2; min-width: 260px;
  display: flex; flex-direction: column; gap: 6px;
}
.eye-row  { display: flex; align-items: center; gap: 8px; }
.eye-label {
  font-size: 10px; font-weight: 800; padding: 2px 7px; border-radius: 4px; flex-shrink: 0;
}
.eye-label.od { background: #dbeafe; color: #1e40af; }
.eye-label.os { background: #d1fae5; color: #065f46; }
.eye-data { font-size: 12px; font-family: monospace; color: #374151; font-weight: 600; }
.eye-add  { font-size: 11px; color: #9ca3af; }

.rx-doctor {
  padding: 18px 16px; min-width: 150px; flex-shrink: 0;
}
.doctor-name { font-size: 13px; font-weight: 600; color: #374151; margin-top: 3px; }

.rx-actions {
  display: flex; align-items: center; gap: 8px;
  padding: 18px 20px; flex-shrink: 0; margin-left: auto;
}
.act-btn {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 7px 14px; border-radius: 8px; border: none; cursor: pointer;
  font-size: 12px; font-weight: 700; font-family: inherit; transition: all 0.15s;
}
.act-purple { background: #f3e8ff; color: #6d28d9; }
.act-purple:hover { background: #ede9fe; }
.act-gray   { background: #f3f4f6; color: #374151; }
.act-gray:hover   { background: #e5e7eb; }
.act-red    { background: #fee2e2; color: #991b1b; }
.act-red:hover    { background: #fecaca; }
.act-blue   { background: #eff6ff; color: #2563eb; }
.act-blue:hover   { background: #dbeafe; }
.btn-print  { display: inline-flex; align-items: center; gap: 7px; padding: 10px 20px; border: none; border-radius: 9px; background: linear-gradient(135deg, #3b82f6, #2563eb); color: #fff; font-size: 13px; font-weight: 700; font-family: inherit; cursor: pointer; transition: all .2s; }
.btn-print:hover { opacity: .9; transform: translateY(-1px); }

/* ── Empty State ─────────────────────────────────────── */
.empty-state {
  text-align: center; padding: 60px 20px;
  background: white; border: 1.5px solid #f3f4f6; border-radius: 14px;
}
.empty-icon { color: #d1d5db; margin: 0 auto 12px; }
.empty-text { font-size: 15px; font-weight: 700; color: #374151; margin: 0; }
.empty-sub  { font-size: 13px; color: #9ca3af; margin-top: 6px; }

/* ── Pagination ──────────────────────────────────────── */
.pagination { display: flex; align-items: center; justify-content: center; gap: 12px; }
.page-btn {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 10px 18px; background: white; border: 1.5px solid #e5e7eb;
  border-radius: 10px; font-size: 13px; font-weight: 600; color: #374151;
  cursor: pointer; transition: all 0.2s; font-family: inherit;
}
.page-btn:hover:not(:disabled) { border-color: #7c3aed; color: #6d28d9; background: #f5f3ff; }
.page-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.page-info { font-size: 13px; color: #6b7280; font-weight: 500; }

/* ── Modals ──────────────────────────────────────────── */
.modal-backdrop {
  position: fixed; inset: 0; z-index: 9999;
  background: rgba(0,0,0,0.5);
  display: flex; align-items: center; justify-content: center;
  padding: 16px; overflow-y: auto;
}
.modal-box {
  background: white; border-radius: 16px;
  box-shadow: 0 24px 64px rgba(0,0,0,0.18);
  width: 100%; margin: auto;
}
.modal-md { max-width: 540px; }
.modal-lg { max-width: 680px; }

.modal-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 20px 24px; border-bottom: 1.5px solid #f3f4f6;
}
.modal-icon {
  width: 36px; height: 36px; border-radius: 10px;
  background: linear-gradient(135deg, #7c3aed, #6d28d9);
  display: flex; align-items: center; justify-content: center;
}
.modal-icon svg { color: white; }
.modal-title { font-size: 16px; font-weight: 700; color: #111827; margin: 0; }
.modal-sub   { font-size: 12px; color: #9ca3af; margin-top: 2px; }
.modal-close {
  color: #9ca3af; background: none; border: none; cursor: pointer;
  padding: 4px; border-radius: 6px; transition: color 0.2s;
}
.modal-close:hover { color: #374151; }
.modal-body   { padding: 24px; }
.modal-footer {
  display: flex; justify-content: flex-end; gap: 10px;
  margin-top: 24px; padding-top: 16px; border-top: 1.5px solid #f3f4f6;
}

/* Form */
.form-row-3 { display: grid; grid-template-columns: 1.5fr 1fr 1fr; gap: 14px; margin-bottom: 16px; }
.form-row-5 { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; }
.form-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.fg  { display: flex; flex-direction: column; gap: 6px; }
.fl  { font-size: 12px; font-weight: 700; color: #374151; }
.fi {
  padding: 10px 12px; border: 1.5px solid #e5e7eb; border-radius: 10px;
  font-family: inherit; font-size: 13px; color: #111827;
  background: #fff; outline: none; width: 100%; transition: border-color 0.2s;
}
.fi:focus  { border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,0.1); }
.fi--ta    { resize: vertical; min-height: 80px; }
.fi.mono   { font-family: monospace; }
.fi:disabled { background: #f9fafb; color: #9ca3af; cursor: not-allowed; }

/* Eye sections */
.eye-section {
  border-radius: 12px; padding: 16px; margin-bottom: 14px;
}
.od-section { background: #eff6ff; border: 1.5px solid #dbeafe; }
.os-section { background: #f0fdf4; border: 1.5px solid #d1fae5; }
.va-section { background: #f9fafb; border: 1.5px solid #e5e7eb; }
.eye-section-label {
  display: flex; align-items: center; gap: 10px; margin-bottom: 12px;
}
.eye-tag {
  font-size: 11px; font-weight: 800; padding: 3px 10px; border-radius: 99px;
}
.od-tag { background: #dbeafe; color: #1e40af; }
.os-tag { background: #d1fae5; color: #065f46; }
.eye-section-title { font-size: 12px; font-weight: 700; color: #374151; }

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
  padding: 10px 24px; background: linear-gradient(135deg, #7c3aed, #6d28d9);
  color: white; border: none; border-radius: 10px;
  font-size: 13px; font-weight: 700; cursor: pointer;
  transition: all 0.2s; font-family: inherit;
}
.btn-save:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(124,58,237,0.4); }
.btn-save:disabled { opacity: 0.6; cursor: not-allowed; }

/* View detail modal */
.view-meta { display: flex; flex-direction: column; gap: 10px; }
.view-meta-row {
  display: flex; align-items: center; justify-content: space-between;
  padding: 8px 0; border-bottom: 1px solid #f3f4f6;
}
.view-key { font-size: 13px; color: #9ca3af; font-weight: 500; }
.view-val { font-size: 13px; color: #111827; }

.rx-table-wrap { border: 1.5px solid #e5e7eb; border-radius: 12px; overflow: hidden; }
.rx-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.rx-table th {
  text-align: center; font-size: 11px; font-weight: 700; color: #6b7280;
  padding: 10px 12px; background: #f9fafb; border-bottom: 1.5px solid #e5e7eb;
}
.rx-table td {
  text-align: center; padding: 12px; font-family: monospace; font-weight: 600;
  color: #374151; border-bottom: 1px solid #f3f4f6;
}
.rx-table tr:last-child td { border-bottom: none; }
.eye-cell { font-family: inherit !important; font-weight: 800 !important; text-align: left !important; padding-left: 16px !important; }
.od-cell  { color: #1e40af; }
.os-cell  { color: #065f46; }

.notes-box { background: #f5f3ff; border: 1.5px solid #ddd6fe; border-radius: 10px; padding: 14px; }
.notes-label { font-size: 11px; font-weight: 700; color: #7c3aed; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
.notes-text  { font-size: 13px; color: #374151; line-height: 1.6; white-space: pre-wrap; }

/* Responsive */
@media (max-width: 1100px) {
  .stats-row { grid-template-columns: repeat(2, 1fr); }
  .rx-doctor { display: none; }
}
@media (max-width: 768px) {
  .rx-page    { padding: 16px 16px 32px; }
  .stats-row  { grid-template-columns: repeat(2, 1fr); }
  .rx-values  { display: none; }
  .form-row-3 { grid-template-columns: 1fr; }
  .form-row-5 { grid-template-columns: repeat(3, 1fr); }
}
</style>
