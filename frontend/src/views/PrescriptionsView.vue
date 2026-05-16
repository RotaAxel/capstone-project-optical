<template>
  <div class="space-y-5">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-base font-semibold text-gray-800">Prescriptions</h2>
        <p class="text-xs text-gray-500 mt-0.5">{{ pagination.total ?? 0 }} total records</p>
      </div>
      <button v-if="auth.can('admin','optometrist')" @click="openModal()" class="btn-primary">
        + New Prescription
      </button>
    </div>

    <!-- Filters -->
    <div class="card p-4 flex gap-3 flex-wrap items-center">
      <input v-model="search" @input="debouncedFetch" placeholder="Search patient..." class="input max-w-xs" />
      <input v-model="filterDate" type="date" @change="fetchPage(1)" class="input w-40" />
      <button @click="search=''; filterDate=''; fetchPage(1)" class="btn-secondary btn-sm">Clear</button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
      <div v-if="loading" class="flex items-center justify-center py-16">
        <div class="animate-spin w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full"></div>
      </div>
      <table v-else class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr>
            <th class="table-th">Patient</th>
            <th class="table-th">Exam Date</th>
            <th class="table-th">OD (Right Eye)</th>
            <th class="table-th">OS (Left Eye)</th>
            <th class="table-th">VA</th>
            <th class="table-th">Optometrist</th>
            <th class="table-th">Valid Until</th>
            <th class="table-th">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <tr v-for="rx in prescriptions" :key="rx.id" class="hover:bg-gray-50 transition-colors">
            <td class="table-td">
              <RouterLink :to="`/patients/${rx.patient?.id}`" class="text-sm font-medium text-blue-700 hover:underline">
                {{ rx.patient?.first_name }} {{ rx.patient?.last_name }}
              </RouterLink>
              <p class="text-xs text-gray-400 font-mono">{{ rx.patient?.patient_code }}</p>
            </td>
            <td class="table-td text-sm">{{ fmtDate(rx.exam_date) }}</td>
            <td class="table-td text-xs font-mono text-gray-700">
              <span>{{ fmt(rx.od_sphere) }} / {{ fmt(rx.od_cylinder) }} × {{ fmt(rx.od_axis) }}</span>
              <span v-if="rx.od_add" class="ml-1 text-gray-400">Add: {{ fmt(rx.od_add) }}</span>
            </td>
            <td class="table-td text-xs font-mono text-gray-700">
              <span>{{ fmt(rx.os_sphere) }} / {{ fmt(rx.os_cylinder) }} × {{ fmt(rx.os_axis) }}</span>
              <span v-if="rx.os_add" class="ml-1 text-gray-400">Add: {{ fmt(rx.os_add) }}</span>
            </td>
            <td class="table-td text-xs text-gray-500">
              <div v-if="rx.visual_acuity_od || rx.visual_acuity_os">
                <p>OD: {{ rx.visual_acuity_od ?? '—' }}</p>
                <p>OS: {{ rx.visual_acuity_os ?? '—' }}</p>
              </div>
              <span v-else class="text-gray-300">—</span>
            </td>
            <td class="table-td text-sm text-gray-600">Dr. {{ rx.optometrist?.name }}</td>
            <td class="table-td text-sm">
              <span v-if="rx.valid_until" :class="isExpired(rx.valid_until) ? 'badge-red' : 'badge-green'" class="text-xs">
                {{ fmtDate(rx.valid_until) }}
              </span>
              <span v-else class="text-gray-300 text-xs">—</span>
            </td>
            <td class="table-td">
              <div class="flex gap-1.5">
                <button @click="openView(rx)" class="btn-secondary btn-sm">View</button>
                <button v-if="auth.can('admin','optometrist')" @click="openModal(rx)" class="btn-secondary btn-sm">Edit</button>
                <button v-if="auth.can('admin')" @click="deletePrescription(rx)" class="btn-danger btn-sm">Delete</button>
              </div>
            </td>
          </tr>
          <tr v-if="!prescriptions.length">
            <td colspan="8" class="text-center py-12 text-gray-400 text-sm">No prescriptions found.</td>
          </tr>
        </tbody>
      </table>

      <div v-if="pagination.last_page > 1" class="flex justify-between items-center px-4 py-3 border-t text-xs text-gray-500">
        <span>Page {{ pagination.current_page }} of {{ pagination.last_page }}</span>
        <div class="flex gap-2">
          <button @click="fetchPage(pagination.current_page - 1)" :disabled="pagination.current_page === 1" class="btn-secondary btn-sm">Prev</button>
          <button @click="fetchPage(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page" class="btn-secondary btn-sm">Next</button>
        </div>
      </div>
    </div>

    <!-- Add / Edit Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-screen overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b">
          <h3 class="font-semibold text-gray-900">{{ editingId ? 'Edit Prescription' : 'New Prescription' }}</h3>
          <button @click="closeModal" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <form @submit.prevent="save" class="px-6 py-5 space-y-5">

          <!-- Patient + Dates -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="sm:col-span-1">
              <label class="label">Patient *</label>
              <select v-model="form.patient_id" class="input" required :disabled="!!editingId">
                <option value="">Select patient</option>
                <option v-for="p in patients" :key="p.id" :value="p.id">{{ p.first_name }} {{ p.last_name }}</option>
              </select>
            </div>
            <div>
              <label class="label">Exam Date *</label>
              <input v-model="form.exam_date" type="date" class="input" required />
            </div>
            <div>
              <label class="label">Valid Until</label>
              <input v-model="form.valid_until" type="date" class="input" />
            </div>
          </div>

          <!-- OD -->
          <div>
            <p class="text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">OD — Right Eye</p>
            <div class="grid grid-cols-5 gap-3">
              <div><label class="label text-xs">Sphere</label><input v-model="form.od_sphere" type="number" step="0.25" class="input" placeholder="0.00" /></div>
              <div><label class="label text-xs">Cylinder</label><input v-model="form.od_cylinder" type="number" step="0.25" class="input" placeholder="0.00" /></div>
              <div><label class="label text-xs">Axis</label><input v-model="form.od_axis" type="number" step="1" min="0" max="180" class="input" placeholder="0" /></div>
              <div><label class="label text-xs">Add</label><input v-model="form.od_add" type="number" step="0.25" class="input" placeholder="0.00" /></div>
              <div><label class="label text-xs">PD</label><input v-model="form.od_pd" type="number" step="0.5" class="input" placeholder="0.0" /></div>
            </div>
          </div>

          <!-- OS -->
          <div>
            <p class="text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">OS — Left Eye</p>
            <div class="grid grid-cols-5 gap-3">
              <div><label class="label text-xs">Sphere</label><input v-model="form.os_sphere" type="number" step="0.25" class="input" placeholder="0.00" /></div>
              <div><label class="label text-xs">Cylinder</label><input v-model="form.os_cylinder" type="number" step="0.25" class="input" placeholder="0.00" /></div>
              <div><label class="label text-xs">Axis</label><input v-model="form.os_axis" type="number" step="1" min="0" max="180" class="input" placeholder="0" /></div>
              <div><label class="label text-xs">Add</label><input v-model="form.os_add" type="number" step="0.25" class="input" placeholder="0.00" /></div>
              <div><label class="label text-xs">PD</label><input v-model="form.os_pd" type="number" step="0.5" class="input" placeholder="0.0" /></div>
            </div>
          </div>

          <!-- Visual Acuity -->
          <div>
            <p class="text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Visual Acuity</p>
            <div class="grid grid-cols-2 gap-4">
              <div><label class="label text-xs">VA — OD (Right)</label><input v-model="form.visual_acuity_od" type="number" step="0.01" class="input" placeholder="e.g. 0.80" /></div>
              <div><label class="label text-xs">VA — OS (Left)</label><input v-model="form.visual_acuity_os" type="number" step="0.01" class="input" placeholder="e.g. 0.80" /></div>
            </div>
          </div>

          <!-- Notes -->
          <div>
            <label class="label">Notes / Remarks</label>
            <textarea v-model="form.notes" class="input" rows="3" placeholder="Additional remarks, lens type, frame recommendation..."></textarea>
          </div>

          <div v-if="formError" class="text-sm text-red-600 bg-red-50 rounded px-3 py-2">{{ formError }}</div>
          <div class="flex justify-end gap-3">
            <button type="button" @click="closeModal" class="btn-secondary">Cancel</button>
            <button type="submit" class="btn-primary" :disabled="saving">{{ saving ? 'Saving...' : 'Save Prescription' }}</button>
          </div>
        </form>
      </div>
    </div>

    <!-- View Detail Modal -->
    <div v-if="viewing" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b">
          <div>
            <h3 class="font-semibold text-gray-900">Prescription Detail</h3>
            <p class="text-xs text-gray-400 mt-0.5">Exam: {{ fmtDate(viewing.exam_date) }}</p>
          </div>
          <button @click="viewing = null" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <div class="px-6 py-5 space-y-4">
          <div class="flex justify-between text-sm"><span class="text-gray-500">Patient</span><span class="font-semibold">{{ viewing.patient?.first_name }} {{ viewing.patient?.last_name }}</span></div>
          <div class="flex justify-between text-sm"><span class="text-gray-500">Optometrist</span><span>Dr. {{ viewing.optometrist?.name }}</span></div>
          <div class="flex justify-between text-sm"><span class="text-gray-500">Valid Until</span><span>{{ viewing.valid_until ? fmtDate(viewing.valid_until) : 'Not set' }}</span></div>

          <div class="overflow-x-auto rounded-xl border border-gray-100">
            <table class="w-full text-sm">
              <thead class="bg-gray-50">
                <tr>
                  <th class="table-th">Eye</th>
                  <th class="table-th">Sphere</th>
                  <th class="table-th">Cylinder</th>
                  <th class="table-th">Axis</th>
                  <th class="table-th">Add</th>
                  <th class="table-th">PD</th>
                  <th class="table-th">VA</th>
                </tr>
              </thead>
              <tbody class="font-mono divide-y divide-gray-50">
                <tr>
                  <td class="table-td font-sans font-semibold text-gray-700">OD (R)</td>
                  <td class="table-td text-center">{{ viewing.od_sphere ?? '—' }}</td>
                  <td class="table-td text-center">{{ viewing.od_cylinder ?? '—' }}</td>
                  <td class="table-td text-center">{{ viewing.od_axis ?? '—' }}</td>
                  <td class="table-td text-center">{{ viewing.od_add ?? '—' }}</td>
                  <td class="table-td text-center">{{ viewing.od_pd ?? '—' }}</td>
                  <td class="table-td text-center">{{ viewing.visual_acuity_od ?? '—' }}</td>
                </tr>
                <tr>
                  <td class="table-td font-sans font-semibold text-gray-700">OS (L)</td>
                  <td class="table-td text-center">{{ viewing.os_sphere ?? '—' }}</td>
                  <td class="table-td text-center">{{ viewing.os_cylinder ?? '—' }}</td>
                  <td class="table-td text-center">{{ viewing.os_axis ?? '—' }}</td>
                  <td class="table-td text-center">{{ viewing.os_add ?? '—' }}</td>
                  <td class="table-td text-center">{{ viewing.os_pd ?? '—' }}</td>
                  <td class="table-td text-center">{{ viewing.visual_acuity_os ?? '—' }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="viewing.notes" class="bg-gray-50 rounded-xl p-4 text-sm text-gray-700 whitespace-pre-wrap">{{ viewing.notes }}</div>

          <div class="flex justify-end gap-3 pt-2">
            <button @click="viewing = null" class="btn-secondary">Close</button>
            <button v-if="auth.can('admin','optometrist')" @click="openModal(viewing); viewing = null" class="btn-primary">Edit</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

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

let debounceTimer
function debouncedFetch() { clearTimeout(debounceTimer); debounceTimer = setTimeout(() => fetchPage(1), 400) }

async function fetchPage(page = 1) {
  loading.value = true
  try {
    const { data } = await api.get('/prescriptions', {
      params: { page, search: search.value || undefined, date: filterDate.value || undefined }
    })
    prescriptions.value = data.data
    pagination.value = { current_page: data.current_page, last_page: data.last_page, total: data.total }
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
    closeModal()
    fetchPage(pagination.value.current_page ?? 1)
  } catch (e) {
    formError.value = Object.values(e.response?.data?.errors ?? {}).flat().join(' ') || 'Failed to save.'
  } finally { saving.value = false }
}

async function deletePrescription(rx) {
  if (!confirm(`Delete prescription for ${rx.patient?.first_name} on ${fmtDate(rx.exam_date)}?`)) return
  try {
    await api.delete(`/prescriptions/${rx.id}`)
    fetchPage(pagination.value.current_page ?? 1)
  } catch { /* error */ }
}

function fmt(v) { return v != null ? Number(v).toFixed(2) : '—' }
function fmtDate(d) { return d ? new Date(d).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '—' }
function isExpired(d) { return d && new Date(d) < new Date() }

onMounted(async () => {
  try {
    const { data } = await api.get('/patients', { params: { per_page: 500 } })
    patients.value = data.data
  } catch { /* no patients */ }
  fetchPage()
})
</script>
