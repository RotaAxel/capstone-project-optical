<template>
  <div class="patient-detail fade-up">

    <!-- Back nav -->
    <div class="mb-6">
      <RouterLink to="/patients" class="btn-back">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
        Back to Patients
      </RouterLink>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="animate-spin w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full"></div>
    </div>

    <!-- Not found -->
    <div v-else-if="!patient" class="text-center py-20 card">
      <svg class="mx-auto mb-3 text-gray-300" width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/></svg>
      <p class="text-gray-400 text-sm">Patient not found.</p>
      <RouterLink to="/patients" class="text-blue-600 text-sm mt-2 inline-block font-semibold">Go back to patients</RouterLink>
    </div>

    <template v-else>
      <!-- ── Identity Header Card ───────────────────────────────── -->
      <div class="header-card card mb-5">
        <div class="flex items-center gap-5">
          <div class="avatar">{{ patient.first_name?.charAt(0) }}{{ patient.last_name?.charAt(0) }}</div>
          <div class="flex-1 min-w-0">
            <h2 class="patient-fullname">{{ patient.first_name }} {{ patient.last_name }}</h2>
            <p class="patient-code">{{ patient.patient_code }}</p>
            <div class="flex flex-wrap gap-2 mt-2">
              <span class="badge-pill">{{ ageLabel }}</span>
              <span class="badge-pill capitalize">{{ patient.gender ?? '—' }}</span>
              <span v-if="patient.phone" class="badge-pill">{{ patient.phone }}</span>
              <span v-if="patient.email" class="badge-pill truncate max-w-xs">{{ patient.email }}</span>
            </div>
          </div>
        </div>

        <!-- Quick Stats -->
        <div class="quick-stats">
          <div class="stat-item">
            <span class="stat-val">{{ patient.appointments?.length ?? 0 }}</span>
            <span class="stat-lbl">Total Visits</span>
          </div>
          <div class="stat-divider"></div>
          <div class="stat-item">
            <span class="stat-val">{{ patient.prescriptions?.length ?? 0 }}</span>
            <span class="stat-lbl">Prescriptions</span>
          </div>
          <div class="stat-divider"></div>
          <div class="stat-item">
            <span class="stat-val">{{ patient.sales?.length ?? 0 }}</span>
            <span class="stat-lbl">Purchases</span>
          </div>
          <div class="stat-divider"></div>
          <div class="stat-item">
            <span class="stat-val text-green-700">₱{{ totalSpent }}</span>
            <span class="stat-lbl">Total Spent</span>
          </div>
          <div class="stat-divider"></div>
          <div class="stat-item">
            <span class="stat-val">{{ lastVisitLabel }}</span>
            <span class="stat-lbl">Last Visit</span>
          </div>
        </div>
      </div>

      <!-- ── Info Grid ──────────────────────────────────────────── -->
      <div class="info-grid mb-5">

        <!-- Patient Demographics -->
        <div class="card info-card">
          <h3 class="info-title">Patient Information</h3>
          <div class="info-rows">
            <div class="info-row"><span class="info-key">Full Name</span><span class="info-val">{{ patient.first_name }} {{ patient.last_name }}</span></div>
            <div class="info-row"><span class="info-key">Date of Birth</span><span class="info-val">{{ formatDate(patient.date_of_birth) }}</span></div>
            <div class="info-row"><span class="info-key">Age</span><span class="info-val">{{ ageLabel }}</span></div>
            <div class="info-row"><span class="info-key">Gender</span><span class="info-val capitalize">{{ patient.gender ?? '—' }}</span></div>
            <div class="info-row"><span class="info-key">Phone</span><span class="info-val">{{ patient.phone ?? '—' }}</span></div>
            <div class="info-row"><span class="info-key">Email</span><span class="info-val">{{ patient.email ?? '—' }}</span></div>
            <div class="info-row no-border"><span class="info-key">Address</span><span class="info-val">{{ patient.address ?? '—' }}</span></div>
          </div>
        </div>

        <!-- Emergency Contact + Medical History stacked -->
        <div class="flex flex-col gap-4">
          <div class="card info-card">
            <h3 class="info-title">Emergency Contact</h3>
            <div class="info-rows">
              <div class="info-row"><span class="info-key">Name</span><span class="info-val">{{ patient.emergency_contact_name ?? '—' }}</span></div>
              <div class="info-row no-border"><span class="info-key">Phone</span><span class="info-val">{{ patient.emergency_contact_phone ?? '—' }}</span></div>
            </div>
          </div>
          <div class="card info-card flex-1">
            <h3 class="info-title">Medical History</h3>
            <p v-if="patient.medical_history" class="text-sm text-gray-600 leading-relaxed">{{ patient.medical_history }}</p>
            <p v-else class="text-sm text-gray-400 text-center py-2">No medical history recorded</p>
          </div>
        </div>

        <!-- Latest Prescription -->
        <div class="card info-card">
          <h3 class="info-title">Latest Prescription</h3>
          <div v-if="latestRx">
            <p class="text-xs text-gray-400 mb-3">
              Exam: {{ formatDate(latestRx.exam_date) }} &nbsp;·&nbsp; Dr. {{ latestRx.optometrist?.name ?? '—' }}<br/>
              Valid until: <span class="font-semibold text-gray-700">{{ latestRx.valid_until ? formatDate(latestRx.valid_until) : 'Not specified' }}</span>
            </p>
            <div class="overflow-x-auto">
              <table class="rx-table">
                <thead>
                  <tr><th>Eye</th><th>Sphere</th><th>Cyl</th><th>Axis</th><th>Add</th><th>PD</th></tr>
                </thead>
                <tbody>
                  <tr>
                    <td class="eye-cell">OD</td>
                    <td>{{ latestRx.od_sphere ?? '—' }}</td>
                    <td>{{ latestRx.od_cylinder ?? '—' }}</td>
                    <td>{{ latestRx.od_axis ?? '—' }}</td>
                    <td>{{ latestRx.od_add ?? '—' }}</td>
                    <td>{{ latestRx.od_pd ?? '—' }}</td>
                  </tr>
                  <tr>
                    <td class="eye-cell">OS</td>
                    <td>{{ latestRx.os_sphere ?? '—' }}</td>
                    <td>{{ latestRx.os_cylinder ?? '—' }}</td>
                    <td>{{ latestRx.os_axis ?? '—' }}</td>
                    <td>{{ latestRx.os_add ?? '—' }}</td>
                    <td>{{ latestRx.os_pd ?? '—' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p v-if="latestRx.notes" class="text-xs text-gray-500 bg-gray-50 rounded-lg p-2 mt-3">{{ latestRx.notes }}</p>
          </div>
          <p v-else class="text-sm text-gray-400 text-center py-6">No prescription on record</p>
        </div>
      </div>

      <!-- ── History Tabs ────────────────────────────────────────── -->
      <div class="card tab-wrapper">
        <div class="tab-nav">
          <button v-for="tab in historyTabs" :key="tab.id" @click="activeTab = tab.id"
            class="tab-btn" :class="activeTab === tab.id ? 'tab-active' : 'tab-inactive'">
            {{ tab.label }}
            <span v-if="tab.count > 0" class="tab-badge" :class="activeTab === tab.id ? 'tab-badge-active' : ''">{{ tab.count }}</span>
          </button>
        </div>

        <!-- Visit History -->
        <div v-if="activeTab === 'appointments'" class="tab-body">
          <div v-if="sortedAppointments.length" class="space-y-2">
            <div v-for="appt in sortedAppointments" :key="appt.id" class="appt-row">
              <div class="appt-date-col">
                <p class="appt-date">{{ formatDate(appt.appointment_date) }}</p>
                <p class="appt-time">{{ formatTime(appt.appointment_date) }}</p>
              </div>
              <div class="flex-1">
                <p class="text-sm font-semibold text-gray-900 capitalize">{{ appt.type?.replace(/_/g, ' ') }}</p>
                <p class="text-xs text-gray-500 mt-0.5">Dr. {{ appt.optometrist?.name ?? '—' }}</p>
                <p v-if="appt.reason" class="text-xs text-gray-400 mt-0.5 italic">{{ appt.reason }}</p>
              </div>
              <span class="appt-status" :class="statusClass(appt.status)">{{ appt.status?.replace(/_/g, ' ') }}</span>
            </div>
          </div>
          <p v-else class="empty-state">No visits recorded.</p>
        </div>

        <!-- Prescriptions -->
        <div v-if="activeTab === 'prescriptions'" class="tab-body space-y-4">
          <div v-if="patient.prescriptions?.length">
            <div v-for="rx in patient.prescriptions" :key="rx.id" class="rx-card">
              <div class="flex items-start justify-between mb-3">
                <div>
                  <p class="text-sm font-semibold text-gray-900">Exam: {{ formatDate(rx.exam_date) }}</p>
                  <p class="text-xs text-gray-400 mt-0.5">Dr. {{ rx.optometrist?.name ?? '—' }} &nbsp;·&nbsp; Valid until {{ rx.valid_until ? formatDate(rx.valid_until) : 'Not set' }}</p>
                </div>
                <span v-if="rx === latestRx" class="latest-badge">Latest</span>
              </div>
              <div class="overflow-x-auto">
                <table class="rx-table">
                  <thead>
                    <tr><th>Eye</th><th>Sphere</th><th>Cyl</th><th>Axis</th><th>Add</th><th>PD</th><th>VA</th></tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td class="eye-cell">OD (R)</td>
                      <td>{{ rx.od_sphere ?? '—' }}</td><td>{{ rx.od_cylinder ?? '—' }}</td><td>{{ rx.od_axis ?? '—' }}</td>
                      <td>{{ rx.od_add ?? '—' }}</td><td>{{ rx.od_pd ?? '—' }}</td><td>{{ rx.od_va ?? '—' }}</td>
                    </tr>
                    <tr>
                      <td class="eye-cell">OS (L)</td>
                      <td>{{ rx.os_sphere ?? '—' }}</td><td>{{ rx.os_cylinder ?? '—' }}</td><td>{{ rx.os_axis ?? '—' }}</td>
                      <td>{{ rx.os_add ?? '—' }}</td><td>{{ rx.os_pd ?? '—' }}</td><td>{{ rx.os_va ?? '—' }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <p v-if="rx.notes" class="text-xs text-gray-500 bg-gray-50 rounded-lg p-2 mt-2">{{ rx.notes }}</p>
            </div>
          </div>
          <p v-else class="empty-state">No prescriptions recorded.</p>
        </div>

        <!-- Purchases -->
        <div v-if="activeTab === 'purchases'" class="tab-body space-y-3">
          <div v-if="patient.sales?.length">
            <div v-for="s in patient.sales" :key="s.id" class="purchase-card">
              <div class="purchase-header">
                <div>
                  <p class="text-sm font-bold text-blue-700 font-mono">{{ s.receipt_number }}</p>
                  <p class="text-xs text-gray-400 mt-0.5">
                    {{ formatDateTime(s.created_at) }} &nbsp;·&nbsp;
                    <span class="capitalize">{{ s.payment_method }}</span> &nbsp;·&nbsp;
                    by {{ s.cashier?.name ?? '—' }}
                  </p>
                </div>
                <p class="text-base font-bold text-green-700">₱{{ formatAmount(s.total_amount) }}</p>
              </div>
              <div v-if="s.items?.length" class="divide-y divide-gray-50">
                <div v-for="item in s.items" :key="item.id" class="purchase-item">
                  <div>
                    <p class="text-sm text-gray-800 font-medium">{{ item.product?.name }}</p>
                    <p class="text-xs text-gray-400 font-mono">{{ item.product?.sku }}</p>
                  </div>
                  <div class="text-right">
                    <p class="text-xs text-gray-500">{{ item.quantity }} × ₱{{ formatAmount(item.unit_price) }}</p>
                    <p class="text-sm font-semibold text-gray-800">₱{{ formatAmount(item.subtotal) }}</p>
                  </div>
                </div>
              </div>
              <div v-if="Number(s.discount_amount) > 0" class="flex justify-between px-4 py-2 text-xs text-red-600 bg-red-50 border-t border-red-100">
                <span>Discount applied</span><span>-₱{{ formatAmount(s.discount_amount) }}</span>
              </div>
            </div>
          </div>
          <p v-else class="empty-state">No purchase history.</p>
        </div>

        <!-- Medical Info -->
        <div v-if="activeTab === 'medical'" class="tab-body space-y-4">
          <div v-if="patient.medical_history" class="bg-blue-50 border border-blue-100 rounded-xl p-4">
            <p class="text-xs font-bold text-blue-700 uppercase tracking-wide mb-2">Medical History</p>
            <p class="text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ patient.medical_history }}</p>
          </div>
          <div v-if="patient.emergency_contact_name || patient.emergency_contact_phone" class="border border-gray-100 rounded-xl p-4 space-y-3">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wide">Emergency Contact</p>
            <div v-if="patient.emergency_contact_name" class="flex justify-between text-sm">
              <span class="text-gray-500">Name</span>
              <span class="font-semibold text-gray-900">{{ patient.emergency_contact_name }}</span>
            </div>
            <div v-if="patient.emergency_contact_phone" class="flex justify-between text-sm">
              <span class="text-gray-500">Phone</span>
              <span class="font-semibold text-gray-900">{{ patient.emergency_contact_phone }}</span>
            </div>
          </div>
          <p v-if="!patient.medical_history && !patient.emergency_contact_name" class="empty-state">
            No additional medical information recorded.
          </p>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'

const route   = useRoute()
const router  = useRouter()
const patient = ref(null)
const loading = ref(true)
const activeTab = ref('appointments')

const latestRx = computed(() => patient.value?.prescriptions?.[0] ?? null)

const sortedAppointments = computed(() =>
  [...(patient.value?.appointments ?? [])].sort((a, b) => new Date(b.appointment_date) - new Date(a.appointment_date))
)

const ageLabel = computed(() => {
  if (!patient.value?.date_of_birth) return '—'
  const dob = new Date(patient.value.date_of_birth)
  const now = new Date()
  let age = now.getFullYear() - dob.getFullYear()
  const m = now.getMonth() - dob.getMonth()
  if (m < 0 || (m === 0 && now.getDate() < dob.getDate())) age--
  return `${age} yrs old`
})

const totalSpent = computed(() => {
  const sum = (patient.value?.sales ?? []).reduce((acc, s) => acc + parseFloat(s.total_amount ?? 0), 0)
  return Number(sum).toLocaleString('en-PH', { minimumFractionDigits: 2 })
})

const lastVisitLabel = computed(() => {
  const appts = sortedAppointments.value
  return appts.length ? formatDate(appts[0].appointment_date) : 'None'
})

const historyTabs = computed(() => [
  { id: 'appointments',  label: 'Visit History',  count: patient.value?.appointments?.length ?? 0 },
  { id: 'prescriptions', label: 'Prescriptions',  count: patient.value?.prescriptions?.length ?? 0 },
  { id: 'purchases',     label: 'Purchases',      count: patient.value?.sales?.length ?? 0 },
  { id: 'medical',       label: 'Medical Info',   count: 0 },
])

function statusClass(status) {
  return {
    scheduled: 'status-scheduled',
    completed:  'status-completed',
    cancelled:  'status-cancelled',
    no_show:    'status-noshow',
  }[status] ?? 'status-scheduled'
}

function formatDate(d)     { return d ? new Date(d).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '—' }
function formatTime(d)     { return d ? new Date(d).toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' }) : '' }
function formatDateTime(d) { return d ? new Date(d).toLocaleString('en-PH', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—' }
function formatAmount(v)   { return Number(v || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }

onMounted(async () => {
  try {
    const { data } = await api.get(`/patients/${route.params.id}`)
    patient.value = data
  } catch {
    // patient not found — template shows not-found state
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.patient-detail { padding: 28px 32px 48px; }

/* Back button */
.btn-back {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 9px 18px; background: white;
  border: 1.5px solid #e5e7eb; border-radius: 10px;
  color: #374151; font-size: 13px; font-weight: 600;
  text-decoration: none; transition: all 0.2s;
}
.btn-back:hover { border-color: #3b82f6; color: #2563eb; background: #eff6ff; }

/* Header card */
.header-card { padding: 28px 32px; }

.avatar {
  width: 68px; height: 68px; border-radius: 18px;
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  color: white; font-size: 24px; font-weight: 800;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; letter-spacing: -1px;
}

.patient-fullname { font-size: 22px; font-weight: 800; color: #111827; margin: 0; }
.patient-code     { font-size: 12px; color: #9ca3af; font-family: monospace; margin-top: 2px; }

.badge-pill {
  font-size: 12px; font-weight: 600; color: #374151;
  background: #f3f4f6; border-radius: 99px; padding: 4px 12px;
}

/* Quick stats */
.quick-stats {
  display: flex; align-items: center;
  border-top: 1.5px solid #f3f4f6; margin-top: 24px; padding-top: 20px;
}
.stat-item {
  flex: 1; display: flex; flex-direction: column; align-items: center; gap: 3px; padding: 8px 0;
}
.stat-divider { width: 1.5px; height: 36px; background: #f3f4f6; flex-shrink: 0; }
.stat-val  { font-size: 20px; font-weight: 800; color: #111827; }
.stat-lbl  { font-size: 11px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; }

/* Info Grid */
.info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 18px;
  align-items: start;
}
.info-card { padding: 22px; }

.info-title {
  font-size: 13px; font-weight: 700; color: #111827;
  margin-bottom: 14px; padding-bottom: 10px;
  border-bottom: 1.5px solid #f3f4f6;
}
.info-rows { display: flex; flex-direction: column; }
.info-row {
  display: flex; justify-content: space-between; align-items: flex-start;
  gap: 12px; padding: 8px 0; border-bottom: 1px solid #f9fafb;
}
.info-row.no-border { border-bottom: none; }
.info-key { font-size: 12px; font-weight: 600; color: #9ca3af; flex-shrink: 0; min-width: 80px; }
.info-val { font-size: 13px; color: #111827; font-weight: 500; text-align: right; }

/* Tabs */
.tab-wrapper { padding: 0; overflow: hidden; }

.tab-nav {
  display: flex; border-bottom: 1.5px solid #f3f4f6; padding: 0 8px; overflow-x: auto;
}
.tab-btn {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 15px 22px; font-size: 13px; font-weight: 600;
  border: none; background: transparent; cursor: pointer;
  border-bottom: 2px solid transparent; margin-bottom: -2px;
  transition: all 0.2s; color: #9ca3af; white-space: nowrap;
}
.tab-active   { color: #2563eb; border-bottom-color: #2563eb; }
.tab-inactive:hover { color: #374151; background: #f9fafb; }

.tab-badge { font-size: 11px; font-weight: 700; background: #e5e7eb; color: #6b7280; border-radius: 99px; padding: 2px 8px; }
.tab-badge-active { background: #dbeafe; color: #1d4ed8; }

.tab-body { padding: 24px; }

/* Appointment rows */
.appt-row {
  display: flex; align-items: flex-start; gap: 20px;
  background: #f9fafb; border-radius: 10px; padding: 14px 18px;
  margin-bottom: 8px; transition: background 0.2s;
}
.appt-row:hover { background: #f3f4f6; }
.appt-date-col { min-width: 120px; flex-shrink: 0; }
.appt-date { font-size: 13px; font-weight: 700; color: #111827; }
.appt-time { font-size: 11px; color: #9ca3af; margin-top: 2px; }
.appt-status {
  font-size: 11px; font-weight: 700; padding: 4px 10px;
  border-radius: 6px; white-space: nowrap; flex-shrink: 0; text-transform: capitalize;
}
.status-scheduled { background: #dbeafe; color: #1e40af; }
.status-completed  { background: #dcfce7; color: #166534; }
.status-cancelled  { background: #fee2e2; color: #991b1b; }
.status-noshow     { background: #f3f4f6; color: #374151; }

/* Prescription cards */
.rx-card {
  border: 1.5px solid #e5e7eb; border-radius: 12px; padding: 18px 20px;
  margin-bottom: 12px; transition: border-color 0.2s;
}
.rx-card:hover { border-color: #bfdbfe; }

.latest-badge {
  font-size: 11px; font-weight: 700; background: #dbeafe; color: #1d4ed8;
  padding: 4px 12px; border-radius: 99px; white-space: nowrap;
}

/* Rx table */
.rx-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.rx-table th {
  text-align: left; font-size: 11px; font-weight: 700; color: #9ca3af;
  padding: 8px 12px; border-bottom: 1.5px solid #e5e7eb;
}
.rx-table td { padding: 10px 12px; color: #111827; font-weight: 600; border-bottom: 1px solid #f3f4f6; }
.eye-cell { font-weight: 800; color: #0891b2; }

/* Purchase cards */
.purchase-card {
  border: 1.5px solid #e5e7eb; border-radius: 12px; overflow: hidden;
  margin-bottom: 12px; transition: border-color 0.2s;
}
.purchase-card:hover { border-color: #bbf7d0; }
.purchase-header {
  display: flex; align-items: flex-start; justify-content: space-between;
  padding: 14px 18px; background: #f9fafb; border-bottom: 1px solid #f3f4f6;
}
.purchase-item {
  display: flex; align-items: center; justify-content: space-between; padding: 10px 18px;
}

.empty-state {
  text-align: center; padding: 36px 16px; font-size: 13px; color: #9ca3af;
}

/* Responsive */
@media (max-width: 1100px) {
  .info-grid { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 768px) {
  .patient-detail { padding: 16px 16px 32px; }
  .header-card { padding: 20px; }
  .info-grid { grid-template-columns: 1fr; }
  .quick-stats { flex-wrap: wrap; gap: 0; }
  .stat-item { min-width: 50%; border-bottom: 1.5px solid #f3f4f6; }
  .stat-divider { display: none; }
}
</style>
