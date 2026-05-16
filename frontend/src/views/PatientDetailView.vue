<template>
  <div v-if="patient" class="space-y-6">
    <!-- Back + Header -->
    <div class="flex items-center gap-4">
      <RouterLink to="/patients" class="btn-secondary btn-sm">← Back</RouterLink>
      <div>
        <h2 class="text-base font-semibold text-gray-800">{{ patient.first_name }} {{ patient.last_name }}</h2>
        <p class="text-xs text-gray-500 font-mono">{{ patient.patient_code }}</p>
      </div>
    </div>

    <!-- Top Row: Profile + Latest Rx + Stats -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Patient Info -->
      <div class="card space-y-3">
        <div class="flex items-center gap-3 pb-3 border-b border-gray-100">
          <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold text-lg shrink-0">
            {{ patient.first_name?.charAt(0) }}{{ patient.last_name?.charAt(0) }}
          </div>
          <div>
            <p class="font-semibold text-gray-900">{{ patient.first_name }} {{ patient.last_name }}</p>
            <span class="badge-blue capitalize text-xs">{{ patient.gender }}</span>
          </div>
        </div>
        <div v-for="info in patientInfo" :key="info.label" class="flex justify-between text-sm gap-2">
          <span class="text-gray-500 shrink-0">{{ info.label }}</span>
          <span class="font-medium text-gray-800 text-right truncate" :title="info.value">{{ info.value }}</span>
        </div>
      </div>

      <!-- Latest Prescription -->
      <div class="card">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-semibold text-gray-800">Latest Prescription</h3>
          <span v-if="latestRx" class="text-xs text-gray-400">{{ fmtDate(latestRx.exam_date) }}</span>
        </div>
        <div v-if="latestRx" class="space-y-3">
          <div class="overflow-x-auto">
            <table class="w-full text-xs">
              <thead>
                <tr class="text-gray-500"><th class="py-1 text-left">Eye</th><th>Sphere</th><th>Cylinder</th><th>Axis</th><th>Add</th></tr>
              </thead>
              <tbody class="font-mono">
                <tr class="border-t">
                  <td class="py-1.5 font-sans font-semibold text-gray-700">OD (R)</td>
                  <td class="text-center">{{ latestRx.od_sphere ?? '—' }}</td>
                  <td class="text-center">{{ latestRx.od_cylinder ?? '—' }}</td>
                  <td class="text-center">{{ latestRx.od_axis ?? '—' }}</td>
                  <td class="text-center">{{ latestRx.od_add ?? '—' }}</td>
                </tr>
                <tr class="border-t">
                  <td class="py-1.5 font-sans font-semibold text-gray-700">OS (L)</td>
                  <td class="text-center">{{ latestRx.os_sphere ?? '—' }}</td>
                  <td class="text-center">{{ latestRx.os_cylinder ?? '—' }}</td>
                  <td class="text-center">{{ latestRx.os_axis ?? '—' }}</td>
                  <td class="text-center">{{ latestRx.os_add ?? '—' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <p v-if="latestRx.notes" class="text-xs text-gray-500 bg-gray-50 rounded p-2">{{ latestRx.notes }}</p>
          <p class="text-xs text-gray-400">Dr. {{ latestRx.optometrist?.name }}</p>
        </div>
        <p v-else class="text-sm text-gray-400 text-center py-6">No prescription on record</p>
      </div>

      <!-- Summary Stats -->
      <div class="card space-y-4">
        <h3 class="text-sm font-semibold text-gray-800">Summary</h3>
        <div v-for="s in patientStats" :key="s.label" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
          <span class="text-xs text-gray-600">{{ s.label }}</span>
          <span class="font-bold text-gray-900 text-sm">{{ s.value }}</span>
        </div>
      </div>
    </div>

    <!-- History Tabs -->
    <div>
      <div class="flex gap-1 bg-gray-100 rounded-xl p-1 w-fit mb-5">
        <button v-for="tab in historyTabs" :key="tab.id" @click="activeTab = tab.id"
          class="px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2"
          :class="activeTab === tab.id ? 'bg-white text-blue-700 shadow-sm' : 'text-gray-500 hover:text-gray-700'">
          {{ tab.label }}
          <span v-if="tab.count > 0" class="text-xs rounded-full px-1.5 py-0.5 font-semibold"
            :class="activeTab === tab.id ? 'bg-blue-100 text-blue-700' : 'bg-gray-200 text-gray-600'">
            {{ tab.count }}
          </span>
        </button>
      </div>

      <!-- Prescriptions Tab -->
      <div v-if="activeTab === 'prescriptions'" class="card">
        <h3 class="text-sm font-semibold text-gray-800 mb-4">Prescription History</h3>
        <div v-if="patient.prescriptions?.length" class="space-y-4">
          <div v-for="rx in patient.prescriptions" :key="rx.id"
            class="border border-gray-100 rounded-xl p-4 hover:border-blue-200 transition-colors">
            <div class="flex items-center justify-between mb-3">
              <div>
                <p class="text-sm font-semibold text-gray-900">Exam: {{ fmtDate(rx.exam_date) }}</p>
                <p class="text-xs text-gray-400 mt-0.5">Dr. {{ rx.optometrist?.name }} · Valid until {{ rx.valid_until ? fmtDate(rx.valid_until) : 'Not set' }}</p>
              </div>
              <span v-if="rx === latestRx" class="badge-blue text-xs">Latest</span>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-xs">
                <thead>
                  <tr class="text-gray-500 bg-gray-50 rounded">
                    <th class="px-3 py-1.5 text-left">Eye</th>
                    <th class="px-3 py-1.5">Sphere</th>
                    <th class="px-3 py-1.5">Cylinder</th>
                    <th class="px-3 py-1.5">Axis</th>
                    <th class="px-3 py-1.5">Add</th>
                    <th class="px-3 py-1.5">PD</th>
                    <th class="px-3 py-1.5">VA</th>
                  </tr>
                </thead>
                <tbody class="font-mono divide-y divide-gray-50">
                  <tr>
                    <td class="px-3 py-2 font-sans font-semibold text-gray-700">OD (R)</td>
                    <td class="px-3 py-2 text-center">{{ rx.od_sphere ?? '—' }}</td>
                    <td class="px-3 py-2 text-center">{{ rx.od_cylinder ?? '—' }}</td>
                    <td class="px-3 py-2 text-center">{{ rx.od_axis ?? '—' }}</td>
                    <td class="px-3 py-2 text-center">{{ rx.od_add ?? '—' }}</td>
                    <td class="px-3 py-2 text-center">{{ rx.od_pd ?? '—' }}</td>
                    <td class="px-3 py-2 text-center">{{ rx.od_va ?? '—' }}</td>
                  </tr>
                  <tr>
                    <td class="px-3 py-2 font-sans font-semibold text-gray-700">OS (L)</td>
                    <td class="px-3 py-2 text-center">{{ rx.os_sphere ?? '—' }}</td>
                    <td class="px-3 py-2 text-center">{{ rx.os_cylinder ?? '—' }}</td>
                    <td class="px-3 py-2 text-center">{{ rx.os_axis ?? '—' }}</td>
                    <td class="px-3 py-2 text-center">{{ rx.os_add ?? '—' }}</td>
                    <td class="px-3 py-2 text-center">{{ rx.os_pd ?? '—' }}</td>
                    <td class="px-3 py-2 text-center">{{ rx.os_va ?? '—' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p v-if="rx.notes" class="text-xs text-gray-500 bg-gray-50 rounded p-2 mt-2">{{ rx.notes }}</p>
          </div>
        </div>
        <p v-else class="text-sm text-gray-400 py-8 text-center">No prescriptions recorded.</p>
      </div>

      <!-- Appointments Tab -->
      <div v-if="activeTab === 'appointments'" class="card">
        <h3 class="text-sm font-semibold text-gray-800 mb-4">Appointment History</h3>
        <div v-if="patient.appointments?.length" class="divide-y divide-gray-100">
          <div v-for="appt in sortedAppointments" :key="appt.id"
            class="flex items-center gap-4 py-3">
            <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
              <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 capitalize">{{ appt.type?.replace('_', ' ') }}</p>
              <p class="text-xs text-gray-400">{{ fmtDateTime(appt.appointment_date) }} · Dr. {{ appt.optometrist?.name ?? '—' }}</p>
              <p v-if="appt.reason" class="text-xs text-gray-500 mt-0.5 truncate">{{ appt.reason }}</p>
            </div>
            <span :class="{
              'badge-blue':  appt.status === 'scheduled',
              'badge-green': appt.status === 'completed',
              'badge-red':   appt.status === 'cancelled',
              'badge-gray':  appt.status === 'no_show',
            }" class="capitalize text-xs shrink-0">{{ appt.status?.replace('_', ' ') }}</span>
          </div>
        </div>
        <p v-else class="text-sm text-gray-400 py-8 text-center">No appointments recorded.</p>
      </div>

      <!-- Purchases Tab -->
      <div v-if="activeTab === 'purchases'" class="card">
        <h3 class="text-sm font-semibold text-gray-800 mb-4">Purchase History</h3>
        <div v-if="patient.sales?.length" class="space-y-3">
          <div v-for="s in patient.sales" :key="s.id"
            class="border border-gray-100 rounded-xl overflow-hidden hover:border-green-200 transition-colors">
            <!-- Sale Header -->
            <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-100">
              <div>
                <p class="text-sm font-semibold text-blue-700 font-mono">{{ s.receipt_number }}</p>
                <p class="text-xs text-gray-400">{{ fmtDateTime(s.created_at) }} · {{ s.payment_method }} · by {{ s.cashier?.name }}</p>
              </div>
              <p class="font-bold text-green-700">₱{{ fmt(s.total_amount) }}</p>
            </div>
            <!-- Sale Items -->
            <div v-if="s.items?.length" class="divide-y divide-gray-50">
              <div v-for="item in s.items" :key="item.id" class="flex items-center justify-between px-4 py-2">
                <div>
                  <p class="text-sm text-gray-800">{{ item.product?.name }}</p>
                  <p class="text-xs text-gray-400 font-mono">{{ item.product?.sku }}</p>
                </div>
                <div class="text-right">
                  <p class="text-xs text-gray-500">{{ item.quantity }} × ₱{{ fmt(item.unit_price) }}</p>
                  <p class="text-sm font-medium text-gray-800">₱{{ fmt(item.subtotal) }}</p>
                </div>
              </div>
            </div>
            <div v-if="s.discount_amount > 0" class="flex justify-between px-4 py-2 text-xs text-red-600 bg-red-50">
              <span>Discount</span><span>-₱{{ fmt(s.discount_amount) }}</span>
            </div>
          </div>
        </div>
        <p v-else class="text-sm text-gray-400 py-8 text-center">No purchase history.</p>
      </div>

      <!-- Medical History Tab -->
      <div v-if="activeTab === 'medical'" class="card">
        <h3 class="text-sm font-semibold text-gray-800 mb-4">Medical History</h3>
        <div v-if="patient.medical_history" class="bg-gray-50 rounded-xl p-4 text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ patient.medical_history }}</div>
        <div class="mt-4 space-y-2">
          <div v-if="patient.emergency_contact_name" class="flex justify-between text-sm border-t pt-3">
            <span class="text-gray-500">Emergency Contact</span>
            <span class="font-medium">{{ patient.emergency_contact_name }}</span>
          </div>
          <div v-if="patient.emergency_contact_phone" class="flex justify-between text-sm">
            <span class="text-gray-500">Emergency Phone</span>
            <span class="font-medium">{{ patient.emergency_contact_phone }}</span>
          </div>
        </div>
        <p v-if="!patient.medical_history && !patient.emergency_contact_name" class="text-sm text-gray-400 py-8 text-center">No medical history recorded.</p>
      </div>
    </div>
  </div>

  <!-- Loading state -->
  <div v-else class="flex items-center justify-center py-20">
    <div class="animate-spin w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full"></div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'

const route   = useRoute()
const router  = useRouter()
const patient = ref(null)
const activeTab = ref('prescriptions')

const latestRx = computed(() => patient.value?.prescriptions?.[0] ?? null)

const sortedAppointments = computed(() =>
  [...(patient.value?.appointments ?? [])].sort((a, b) => new Date(b.appointment_date) - new Date(a.appointment_date))
)

const historyTabs = computed(() => [
  { id: 'prescriptions', label: 'Prescriptions', count: patient.value?.prescriptions?.length ?? 0 },
  { id: 'appointments',  label: 'Appointments',  count: patient.value?.appointments?.length ?? 0 },
  { id: 'purchases',     label: 'Purchases',      count: patient.value?.sales?.length ?? 0 },
  { id: 'medical',       label: 'Medical History',count: 0 },
])

const patientInfo = computed(() => patient.value ? [
  { label: 'Date of Birth', value: fmtDate(patient.value.date_of_birth) },
  { label: 'Age',           value: `${calcAge(patient.value.date_of_birth)} years old` },
  { label: 'Phone',         value: patient.value.phone ?? '—' },
  { label: 'Email',         value: patient.value.email ?? '—' },
  { label: 'Address',       value: patient.value.address ?? '—' },
  { label: 'Registered by', value: patient.value.createdBy?.name ?? '—' },
] : [])

const patientStats = computed(() => patient.value ? [
  { label: 'Total Prescriptions', value: patient.value.prescriptions?.length ?? 0 },
  { label: 'Total Appointments',  value: patient.value.appointments?.length ?? 0 },
  { label: 'Total Purchases',     value: patient.value.sales?.length ?? 0 },
  { label: 'Total Spent',         value: '₱' + fmt(patient.value.sales?.reduce((s, x) => s + +x.total_amount, 0) ?? 0) },
] : [])

function calcAge(dob) { return dob ? Math.floor((Date.now() - new Date(dob)) / 31557600000) : '—' }
function fmtDate(d) { return d ? new Date(d).toLocaleDateString('en-PH', { month: 'long', day: 'numeric', year: 'numeric' }) : '—' }
function fmtDateTime(d) { return d ? new Date(d).toLocaleString('en-PH', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—' }
function fmt(v) { return Number(v || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }

onMounted(async () => {
  try {
    const { data } = await api.get(`/patients/${route.params.id}`)
    patient.value = data
  } catch { router.push('/patients') }
})
</script>
