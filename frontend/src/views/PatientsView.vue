<template>
  <div class="space-y-5">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-base font-semibold text-gray-800">Patient Records</h2>
        <p class="text-xs text-gray-500 mt-0.5">{{ pagination.total ?? 0 }} total patients</p>
      </div>
      <button v-if="auth.can('admin','receptionist')" @click="openModal()" class="btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Patient
      </button>
    </div>

    <!-- Search -->
    <div class="card p-4">
      <input v-model="search" @input="debouncedFetch" type="text" placeholder="Search by name, patient code, or phone..." class="input max-w-md" />
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
      <div v-if="loading" class="flex items-center justify-center py-16">
        <div class="animate-spin w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full"></div>
      </div>
      <table v-else class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr>
            <th class="table-th">Patient Code</th>
            <th class="table-th">Name</th>
            <th class="table-th">Age / Gender</th>
            <th class="table-th">Contact</th>
            <th class="table-th">Registered</th>
            <th class="table-th">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <tr v-for="p in patients" :key="p.id" class="hover:bg-gray-50 transition-colors">
            <td class="table-td font-mono text-blue-700 font-medium">{{ p.patient_code }}</td>
            <td class="table-td">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 text-xs font-bold shrink-0">
                  {{ p.first_name?.charAt(0) }}{{ p.last_name?.charAt(0) }}
                </div>
                <div>
                  <p class="font-medium text-gray-900 text-sm">{{ p.first_name }} {{ p.last_name }}</p>
                  <p class="text-xs text-gray-400">{{ p.email ?? '—' }}</p>
                </div>
              </div>
            </td>
            <td class="table-td">
              <p class="text-sm">{{ calcAge(p.date_of_birth) }} yrs</p>
              <p class="text-xs text-gray-400 capitalize">{{ p.gender }}</p>
            </td>
            <td class="table-td text-sm">{{ p.phone ?? '—' }}</td>
            <td class="table-td text-sm text-gray-400">{{ formatDate(p.created_at) }}</td>
            <td class="table-td">
              <div class="flex items-center gap-2">
                <RouterLink :to="'/patients/' + p.id" class="btn-secondary btn-sm">View</RouterLink>
                <button v-if="auth.can('admin','receptionist')" @click="openModal(p)" class="btn-secondary btn-sm">Edit</button>
                <button v-if="auth.can('admin')" @click="deletePatient(p)" class="btn-danger btn-sm">Del</button>
              </div>
            </td>
          </tr>
          <tr v-if="!patients.length">
            <td colspan="6" class="text-center py-12 text-gray-400 text-sm">No patients found.</td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-100">
        <p class="text-xs text-gray-500">Page {{ pagination.current_page }} of {{ pagination.last_page }}</p>
        <div class="flex gap-2">
          <button @click="fetchPage(pagination.current_page - 1)" :disabled="pagination.current_page === 1" class="btn-secondary btn-sm">Prev</button>
          <button @click="fetchPage(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page" class="btn-secondary btn-sm">Next</button>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-screen overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
          <h3 class="font-semibold text-gray-900">{{ editingId ? 'Edit Patient' : 'Add New Patient' }}</h3>
          <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <form @submit.prevent="savePatient" class="px-6 py-5 grid grid-cols-2 gap-4">
          <div>
            <label class="label">First Name *</label>
            <input v-model="form.first_name" class="input" required />
          </div>
          <div>
            <label class="label">Last Name *</label>
            <input v-model="form.last_name" class="input" required />
          </div>
          <div>
            <label class="label">Date of Birth *</label>
            <input v-model="form.date_of_birth" type="date" class="input" required />
          </div>
          <div>
            <label class="label">Gender *</label>
            <select v-model="form.gender" class="input" required>
              <option value="">Select</option>
              <option value="male">Male</option>
              <option value="female">Female</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div>
            <label class="label">Phone</label>
            <input v-model="form.phone" class="input" placeholder="09XXXXXXXXX" />
          </div>
          <div>
            <label class="label">Email</label>
            <input v-model="form.email" type="email" class="input" />
          </div>
          <div class="col-span-2">
            <label class="label">Address</label>
            <input v-model="form.address" class="input" />
          </div>
          <div>
            <label class="label">Emergency Contact Name</label>
            <input v-model="form.emergency_contact_name" class="input" />
          </div>
          <div>
            <label class="label">Emergency Contact Phone</label>
            <input v-model="form.emergency_contact_phone" class="input" />
          </div>
          <div class="col-span-2">
            <label class="label">Medical History</label>
            <textarea v-model="form.medical_history" class="input" rows="3" placeholder="Allergies, conditions, medications..."></textarea>
          </div>

          <div v-if="formError" class="col-span-2 text-sm text-red-600 bg-red-50 rounded-lg px-4 py-2">{{ formError }}</div>

          <div class="col-span-2 flex justify-end gap-3 pt-2">
            <button type="button" @click="closeModal" class="btn-secondary">Cancel</button>
            <button type="submit" class="btn-primary" :disabled="saving">{{ saving ? 'Saving...' : 'Save Patient' }}</button>
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

function calcAge(dob) { return dob ? Math.floor((Date.now() - new Date(dob)) / 31557600000) : '—' }
function formatDate(d) { return d ? new Date(d).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '—' }

onMounted(() => fetchPage())
</script>
