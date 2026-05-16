<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div><h2 class="text-base font-semibold text-gray-800">Appointments</h2><p class="text-xs text-gray-500">Schedule & track patient consultations</p></div>
      <button v-if="auth.can('admin','receptionist')" @click="openModal()" class="btn-primary">Book Appointment</button>
    </div>

    <div class="card p-4 flex gap-3 flex-wrap">
      <input v-model="filterDate" type="date" class="input w-40" @change="fetchPage(1)" />
      <select v-model="filterStatus" class="input w-40" @change="fetchPage(1)">
        <option value="">All Status</option>
        <option value="scheduled">Scheduled</option>
        <option value="completed">Completed</option>
        <option value="cancelled">Cancelled</option>
        <option value="no_show">No Show</option>
      </select>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
      <div v-if="loading" class="flex items-center justify-center py-16">
        <div class="animate-spin w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full"></div>
      </div>
      <table v-else class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr><th class="table-th">Patient</th><th class="table-th">Optometrist</th><th class="table-th">Date & Time</th><th class="table-th">Type</th><th class="table-th">Status</th><th class="table-th">Actions</th></tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <tr v-for="a in appointments" :key="a.id" class="hover:bg-gray-50 transition-colors">
            <td class="table-td"><p class="text-sm font-medium">{{ a.patient?.first_name }} {{ a.patient?.last_name }}</p></td>
            <td class="table-td text-sm">{{ a.optometrist?.name }}</td>
            <td class="table-td text-sm">{{ fmtDateTime(a.appointment_date) }}</td>
            <td class="table-td"><span class="badge-blue capitalize text-xs">{{ a.type?.replace('_', ' ') }}</span></td>
            <td class="table-td">
              <span :class="{
                'badge-blue': a.status === 'scheduled',
                'badge-green': a.status === 'completed',
                'badge-red': a.status === 'cancelled',
                'badge-gray': a.status === 'no_show',
              }" class="capitalize text-xs">{{ a.status?.replace('_', ' ') }}</span>
            </td>
            <td class="table-td">
              <div class="flex gap-1.5">
                <button v-if="a.status === 'scheduled'" @click="updateStatus(a, 'completed')" class="btn-success btn-sm">Done</button>
                <button v-if="a.status === 'scheduled' && auth.can('admin','receptionist')" @click="updateStatus(a, 'cancelled')" class="btn-danger btn-sm">Cancel</button>
                <button v-if="auth.can('admin','receptionist')" @click="openModal(a)" class="btn-secondary btn-sm">Edit</button>
              </div>
            </td>
          </tr>
          <tr v-if="!appointments.length"><td colspan="6" class="text-center py-12 text-gray-400 text-sm">No appointments found.</td></tr>
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

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b"><h3 class="font-semibold">{{ editingId ? 'Edit Appointment' : 'Book Appointment' }}</h3><button @click="closeModal" class="text-gray-400 hover:text-gray-600">✕</button></div>
        <form @submit.prevent="save" class="px-6 py-5 space-y-4">
          <div>
            <label class="label">Patient *</label>
            <select v-model="form.patient_id" class="input" required>
              <option value="">Select patient</option>
              <option v-for="p in patients" :key="p.id" :value="p.id">{{ p.first_name }} {{ p.last_name }}</option>
            </select>
          </div>
          <div>
            <label class="label">Optometrist *</label>
            <select v-model="form.optometrist_id" class="input" required>
              <option value="">Select optometrist</option>
              <option v-for="u in optometrists" :key="u.id" :value="u.id">{{ u.name }}</option>
            </select>
          </div>
          <div>
            <label class="label">Date & Time *</label>
            <input v-model="form.appointment_date" type="datetime-local" class="input" required />
          </div>
          <div>
            <label class="label">Type *</label>
            <select v-model="form.type" class="input" required>
              <option value="eye_exam">Eye Exam</option>
              <option value="follow_up">Follow-up</option>
              <option value="fitting">Fitting</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div>
            <label class="label">Reason</label>
            <textarea v-model="form.reason" class="input" rows="2"></textarea>
          </div>
          <div v-if="formError" class="text-sm text-red-600 bg-red-50 rounded px-3 py-2">{{ formError }}</div>
          <div class="flex justify-end gap-3">
            <button type="button" @click="closeModal" class="btn-secondary">Cancel</button>
            <button type="submit" class="btn-primary" :disabled="saving">{{ saving ? 'Saving...' : 'Save' }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

const appointments = ref([])
const patients     = ref([])
const optometrists = ref([])
const pagination   = ref({})
const loading      = ref(true)
const filterDate   = ref('')
const filterStatus = ref('')
const showModal    = ref(false)
const editingId    = ref(null)
const saving       = ref(false)
const formError    = ref('')

const emptyForm = () => ({ patient_id: '', optometrist_id: '', appointment_date: '', type: 'eye_exam', reason: '' })
const form = ref(emptyForm())

async function fetchPage(page = 1) {
  loading.value = true
  try {
    const { data } = await api.get('/appointments', { params: { page, date: filterDate.value || undefined, status: filterStatus.value || undefined } })
    appointments.value = data.data; pagination.value = { current_page: data.current_page, last_page: data.last_page }
  } catch { /* backend unavailable */ }
  finally { loading.value = false }
}

function openModal(appt = null) {
  formError.value = ''
  if (appt) {
    editingId.value = appt.id
    form.value = { ...appt, appointment_date: appt.appointment_date?.replace(' ', 'T').slice(0, 16) }
  } else { editingId.value = null; form.value = emptyForm() }
  showModal.value = true
}

function closeModal() { showModal.value = false }

async function save() {
  formError.value = ''; saving.value = true
  try {
    if (editingId.value) await api.put(`/appointments/${editingId.value}`, form.value)
    else await api.post('/appointments', form.value)
    closeModal(); fetchPage(pagination.value.current_page ?? 1)
  } catch (e) {
    formError.value = Object.values(e.response?.data?.errors ?? {}).flat().join(' ') || 'Failed to save.'
  } finally { saving.value = false }
}

async function updateStatus(appt, status) {
  await api.put(`/appointments/${appt.id}`, { status })
  fetchPage(pagination.value.current_page ?? 1)
}

function fmtDateTime(d) { return d ? new Date(d).toLocaleString('en-PH', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—' }

onMounted(async () => {
  try {
    const [pts, opts] = await Promise.all([
      api.get('/patients', { params: { per_page: 200 } }),
      api.get('/optometrists'),
    ])
    patients.value     = pts.data.data
    optometrists.value = opts.data
    fetchPage()
  } catch { loading.value = false }
})
</script>
