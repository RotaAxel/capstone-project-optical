<template>
  <div v-if="patient" class="patient-detail fade-up">
    <!-- Header -->
    <div class="flex flex-col items-center justify-center gap-4 mb-8">
      <RouterLink to="/patients" class="btn-back">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
        Back to Patients
      </RouterLink>
      <div class="text-center">
        <h2 class="text-3xl font-bold text-gray-900">{{ patient.first_name }} {{ patient.last_name }}</h2>
        <p class="text-sm text-gray-500 font-mono mt-2">{{ patient.patient_code }}</p>
      </div>
    </div>

    <!-- Patient Header Card -->
    <div class="patient-header-card card mb-6">
      <div class="flex items-start justify-between">
        <div class="flex items-center gap-4">
          <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold text-xl shrink-0">
            {{ patient.first_name?.charAt(0) }}{{ patient.last_name?.charAt(0) }}
          </div>
          <div>
            <h3 class="text-lg font-bold text-gray-900">{{ patient.first_name }} {{ patient.last_name }}</h3>
            <p class="text-sm text-gray-600 mt-1"><span class="font-semibold">Contact:</span> {{ patient.phone ?? '—' }}</p>
          </div>
        </div>
        <div class="text-right space-y-2">
          <div>
            <p class="text-xs text-gray-500">Registered</p>
            <p class="text-sm font-bold text-gray-900">{{ formatDate(patient.created_at) }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500">Patient ID</p>
            <p class="text-sm font-bold text-gray-900">{{ patient.patient_code }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Prescription & Diagnosis Grid -->
    <div class="rx-diag-grid mb-6">
      <!-- Prescription Card -->
      <div class="card rx-card">
        <h3 class="section-title mb-4">Latest Prescription</h3>
        <div v-if="latestRx" class="overflow-x-auto">
          <table class="rx-table">
            <thead>
              <tr>
                <th>Eye</th>
                <th>Sphere</th>
                <th>Cyl</th>
                <th>Axis</th>
                <th>Add</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="eye-cell">OD</td>
                <td>{{ latestRx.od_sphere ?? '—' }}</td>
                <td>{{ latestRx.od_cylinder ?? '—' }}</td>
                <td>{{ latestRx.od_axis ?? '—' }}</td>
                <td>{{ latestRx.od_add ?? '—' }}</td>
              </tr>
              <tr>
                <td class="eye-cell">OS</td>
                <td>{{ latestRx.os_sphere ?? '—' }}</td>
                <td>{{ latestRx.os_cylinder ?? '—' }}</td>
                <td>{{ latestRx.os_axis ?? '—' }}</td>
                <td>{{ latestRx.os_add ?? '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-else class="text-sm text-gray-400 text-center py-6">No prescription on record</p>
        <p v-if="latestRx?.notes" class="text-xs text-gray-500 bg-gray-50 rounded p-2 mt-2">{{ latestRx.notes }}</p>
      </div>

      <!-- Diagnosis Card -->
      <div class="card diag-card">
        <h3 class="section-title mb-4">Medical History</h3>
        <p v-if="patient.medical_history" class="diag-text">{{ patient.medical_history }}</p>
        <p v-else class="text-sm text-gray-400 text-center py-6">No medical history recorded</p>
      </div>
    </div>

    <!-- History Tabs -->
    <div class="mb-6">
      <div class="flex gap-2 bg-gray-100 rounded-xl p-1.5 w-fit mb-6">
        <button v-for="tab in historyTabs" :key="tab.id" @click="activeTab = tab.id"
          class="tab-btn"
          :class="activeTab === tab.id ? 'tab-btn-active' : 'tab-btn-inactive'">
          {{ tab.label }}
          <span v-if="tab.count > 0" class="text-xs rounded-full px-2 py-0.5 font-semibold ml-1"
            :class="activeTab === tab.id ? 'bg-blue-200 text-blue-800' : 'bg-gray-300 text-gray-700'">
            {{ tab.count }}
          </span>
        </button>
      </div>

      <!-- Appointments Tab -->
      <div v-if="activeTab === 'appointments'" class="card tab-content-card">
        <h3 class="section-title mb-4">Visit History</h3>
        <div v-if="sortedAppointments.length" class="visit-list space-y-3">
          <div v-for="appt in sortedAppointments" :key="appt.id" class="visit-row">
            <span class="visit-date">{{ formatDate(appt.appointment_date) }}</span>
            <div class="flex-1">
              <p class="text-sm font-semibold text-gray-900 capitalize">{{ appt.type?.replace('_', ' ') }}</p>
              <p class="text-xs text-gray-500 mt-0.5">Dr. {{ appt.optometrist?.name ?? '—' }} • {{ appt.reason ?? '—' }}</p>
            </div>
          </div>
        </div>
        <p v-else class="text-sm text-gray-400 text-center py-8">No appointments recorded.</p>
      </div>

      <!-- Prescriptions Tab -->
      <div v-if="activeTab === 'prescriptions'" class="card tab-content-card">
        <h3 class="section-title mb-4">Prescription History</h3>
        <div v-if="patient.prescriptions?.length" class="space-y-4">
          <div v-for="rx in patient.prescriptions" :key="rx.id" class="border border-gray-100 rounded-xl p-4 hover:border-blue-200 transition-colors">
            <div class="flex items-center justify-between mb-3">
              <div>
                <p class="text-sm font-semibold text-gray-900">Exam: {{ formatDate(rx.exam_date) }}</p>
                <p class="text-xs text-gray-400 mt-0.5">Dr. {{ rx.optometrist?.name }} • Valid until {{ rx.valid_until ? formatDate(rx.valid_until) : 'Not set' }}</p>
              </div>
              <span v-if="rx === latestRx" class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full font-semibold">Latest</span>
            </div>
            <div class="overflow-x-auto">
              <table class="rx-table-full">
                <thead>
                  <tr>
                    <th>Eye</th>
                    <th>Sphere</th>
                    <th>Cyl</th>
                    <th>Axis</th>
                    <th>Add</th>
                    <th>PD</th>
                    <th>VA</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td class="eye-cell">OD (R)</td>
                    <td>{{ rx.od_sphere ?? '—' }}</td>
                    <td>{{ rx.od_cylinder ?? '—' }}</td>
                    <td>{{ rx.od_axis ?? '—' }}</td>
                    <td>{{ rx.od_add ?? '—' }}</td>
                    <td>{{ rx.od_pd ?? '—' }}</td>
                    <td>{{ rx.od_va ?? '—' }}</td>
                  </tr>
                  <tr>
                    <td class="eye-cell">OS (L)</td>
                    <td>{{ rx.os_sphere ?? '—' }}</td>
                    <td>{{ rx.os_cylinder ?? '—' }}</td>
                    <td>{{ rx.os_axis ?? '—' }}</td>
                    <td>{{ rx.os_add ?? '—' }}</td>
                    <td>{{ rx.os_pd ?? '—' }}</td>
                    <td>{{ rx.os_va ?? '—' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p v-if="rx.notes" class="text-xs text-gray-500 bg-gray-50 rounded p-2 mt-2">{{ rx.notes }}</p>
          </div>
        </div>
        <p v-else class="text-sm text-gray-400 py-8 text-center">No prescriptions recorded.</p>
      </div>

      <!-- Purchases Tab -->
      <div v-if="activeTab === 'purchases'" class="card tab-content-card">
        <h3 class="section-title mb-4">Purchase History</h3>
        <div v-if="patient.sales?.length" class="space-y-3">
          <div v-for="s in patient.sales" :key="s.id" class="border border-gray-100 rounded-xl overflow-hidden hover:border-green-200 transition-colors">
            <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-100">
              <div>
                <p class="text-sm font-semibold text-blue-700 font-mono">{{ s.receipt_number }}</p>
                <p class="text-xs text-gray-400">{{ formatDateTime(s.created_at) }} • {{ s.payment_method }} • by {{ s.cashier?.name }}</p>
              </div>
              <p class="font-bold text-green-700">₱{{ formatAmount(s.total_amount) }}</p>
            </div>
            <div v-if="s.items?.length" class="divide-y divide-gray-50">
              <div v-for="item in s.items" :key="item.id" class="flex items-center justify-between px-4 py-2">
                <div>
                  <p class="text-sm text-gray-800">{{ item.product?.name }}</p>
                  <p class="text-xs text-gray-400 font-mono">{{ item.product?.sku }}</p>
                </div>
                <div class="text-right">
                  <p class="text-xs text-gray-500">{{ item.quantity }} × ₱{{ formatAmount(item.unit_price) }}</p>
                  <p class="text-sm font-medium text-gray-800">₱{{ formatAmount(item.subtotal) }}</p>
                </div>
              </div>
            </div>
            <div v-if="s.discount_amount > 0" class="flex justify-between px-4 py-2 text-xs text-red-600 bg-red-50">
              <span>Discount</span><span>-₱{{ formatAmount(s.discount_amount) }}</span>
            </div>
          </div>
        </div>
        <p v-else class="text-sm text-gray-400 py-8 text-center">No purchase history.</p>
      </div>

      <!-- Medical Info Tab -->
      <div v-if="activeTab === 'medical'" class="card tab-content-card">
        <h3 class="section-title mb-4">Additional Medical Info</h3>
        <div class="space-y-4">
          <div v-if="patient.medical_history" class="bg-gray-50 rounded-xl p-4">
            <p class="text-xs text-gray-500 font-semibold mb-2">Medical History</p>
            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ patient.medical_history }}</p>
          </div>
          <div class="space-y-3 pt-4 border-t border-gray-100">
            <div v-if="patient.emergency_contact_name" class="flex justify-between text-sm">
              <span class="text-gray-500">Emergency Contact</span>
              <span class="font-medium text-gray-900">{{ patient.emergency_contact_name }}</span>
            </div>
            <div v-if="patient.emergency_contact_phone" class="flex justify-between text-sm">
              <span class="text-gray-500">Emergency Phone</span>
              <span class="font-medium text-gray-900">{{ patient.emergency_contact_phone }}</span>
            </div>
          </div>
          <p v-if="!patient.medical_history && !patient.emergency_contact_name" class="text-sm text-gray-400 py-6 text-center">No additional information recorded.</p>
        </div>
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
const activeTab = ref('appointments')

const latestRx = computed(() => patient.value?.prescriptions?.[0] ?? null)

const sortedAppointments = computed(() =>
  [...(patient.value?.appointments ?? [])].sort((a, b) => new Date(b.appointment_date) - new Date(a.appointment_date))
)

const historyTabs = computed(() => [
  { id: 'appointments',  label: 'Visits',         count: patient.value?.appointments?.length ?? 0 },
  { id: 'prescriptions', label: 'Prescriptions',  count: patient.value?.prescriptions?.length ?? 0 },
  { id: 'purchases',     label: 'Purchases',      count: patient.value?.sales?.length ?? 0 },
  { id: 'medical',       label: 'Medical Info',   count: 0 },
])

function formatDate(d) { return d ? new Date(d).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '—' }
function formatDateTime(d) { return d ? new Date(d).toLocaleString('en-PH', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—' }
function formatAmount(v) { return Number(v || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }

onMounted(async () => {
  try {
    const { data } = await api.get(`/patients/${route.params.id}`)
    patient.value = data
  } catch { router.push('/patients') }
})
</script>

<style scoped>
.patient-detail { padding: 28px 32px 40px; }

/* Back Button */
.btn-back {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 14px 28px;
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
  font-weight: 600;
  font-size: 15px;
  border-radius: 12px;
  transition: all 0.3s ease;
  cursor: pointer;
  text-decoration: none;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.btn-back:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
}

.btn-back:active {
  transform: translateY(0);
}

/* Tab Buttons Container */
.flex.gap-2.bg-gray-100 {
  gap: 12px !important;
  padding: 8px !important;
}

/* Tab Buttons */
.tab-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 14px 24px;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  transition: all 0.3s ease;
  cursor: pointer;
  border: none;
  background: transparent;
}

.tab-btn-active {
  background: white;
  color: #2563eb;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.tab-btn-inactive {
  color: #6b7280;
  background: transparent;
}

.tab-btn-inactive:hover {
  color: #1f2937;
  background: rgba(255, 255, 255, 0.6);
}

/* Tab Content Card */
.tab-content-card {
  padding: 32px !important;
  background: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(6px);
  border-radius: 12px;
}

/* Header Card */
.patient-header-card {
  padding: 28px;
  background: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(6px);
  border-radius: 12px;
  margin-bottom: 24px !important;
}

/* Rx & Diagnosis Grid */
.rx-diag-grid {
  display: grid;
  grid-template-columns: 1fr 1.5fr;
  gap: 24px;
  margin-bottom: 28px !important;
}

.rx-card,
.diag-card {
  padding: 24px;
  background: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(6px);
  border-radius: 12px;
}

.section-title {
  font-size: 15px;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 16px !important;
}

/* Tables */
.rx-table,
.rx-table-full {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}

.rx-table th,
.rx-table-full th {
  text-align: left;
  font-size: 12px;
  font-weight: 700;
  color: #9ca3af;
  padding: 10px 12px;
  border-bottom: 1.5px solid #e5e7eb;
}

.rx-table td,
.rx-table-full td {
  padding: 12px;
  color: #1f2937;
  font-weight: 600;
  border-bottom: 1px solid #f3f4f6;
}

.eye-cell {
  font-weight: 800;
  color: #0891b2;
}

.diag-text {
  font-size: 14px;
  color: #4b5563;
  line-height: 1.8;
}

/* Visit History */
.visit-list {
  display: flex;
  flex-direction: column;
  gap: 16px !important;
}

.visit-row {
  display: flex;
  gap: 28px;
  align-items: flex-start;
  background: #f9fafb;
  border-radius: 12px;
  padding: 18px 22px;
  transition: all 0.3s ease;
}

.visit-row:hover {
  background: #f3f4f6;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.visit-date {
  font-size: 13px;
  font-weight: 700;
  color: #1f2937;
  min-width: 150px;
  flex-shrink: 0;
}

/* Prescription History Cards */
.border.border-gray-100 {
  margin-bottom: 16px;
  padding: 18px 20px !important;
  transition: all 0.3s ease;
}

.border.border-gray-100:hover {
  border-color: #dbeafe;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
}

/* Purchase History Cards */
.space-y-3 {
  gap: 16px !important;
}

@media (max-width: 1024px) {
  .rx-diag-grid {
    grid-template-columns: 1fr;
    gap: 20px;
  }

  .tab-btn {
    padding: 12px 20px;
  }

  .tab-content-card {
    padding: 24px !important;
  }
}

@media (max-width: 768px) {
  .patient-detail { 
    padding: 20px 16px 30px; 
  }

  .patient-header-card {
    padding: 20px;
  }

  .rx-card,
  .diag-card {
    padding: 18px;
  }

  .visit-row {
    gap: 16px;
    padding: 14px 16px;
  }

  .tab-content-card {
    padding: 20px !important;
  }
}
</style>

