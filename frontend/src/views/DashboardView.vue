<template>
  <div class="dashboard">
    <!-- Loading state -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="animate-spin w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full"></div>
    </div>

    <template v-else>

      <!-- ── Page Header ─────────────────────────────────────── -->
      <!-- <div class="page-header">
        <div class="flex items-center gap-3">
          <div class="header-icon">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><rect x="14" y="3" width="7" height="7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><rect x="3" y="14" width="7" height="7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><rect x="14" y="14" width="7" height="7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </div>
          <div>
            <h2 class="page-title">Dashboard</h2>
            <p class="page-sub">{{ dashSubtitle }}</p>
          </div>
        </div>
      </div> -->

      <!-- ══════════════════════════════════════════════════════ ADMIN -->
      <template v-if="role === 'admin'">

        <!-- Low Stock Alert -->
        <div v-if="data.stats?.low_stock_count > 0" class="alert-banner">
          <div class="alert-icon-wrap">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
          </div>
          <div class="alert-content">
            <p class="alert-title">Low Stock Alert</p>
            <p class="alert-message">{{ data.stats?.low_stock_count }} product(s) at or below reorder point</p>
          </div>
          <RouterLink to="/inventory?low_stock=1" class="alert-action">View Items →</RouterLink>
        </div>

        <!-- Stat Cards -->
        <div class="admin-stats-grid">
          <div class="admin-stat-card">
            <div class="asc-icon green">
              <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="asc-body">
              <p class="asc-label">Sales Today</p>
              <p class="asc-value">₱{{ fmt(data.stats?.sales_today) }}</p>
              <p class="asc-sub">{{ data.stats?.transactions_today ?? 0 }} transaction(s)</p>
            </div>
          </div>
          <div class="admin-stat-card">
            <div class="asc-icon blue">
              <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </div>
            <div class="asc-body">
              <p class="asc-label">Sales This Month</p>
              <p class="asc-value">₱{{ fmt(data.stats?.sales_this_month) }}</p>
              <p class="asc-sub">Current month total</p>
            </div>
          </div>
          <div class="admin-stat-card">
            <div class="asc-icon purple">
              <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-5.477-3.713M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <div class="asc-body">
              <p class="asc-label">Total Patients</p>
              <p class="asc-value">{{ data.stats?.total_patients ?? 0 }}</p>
              <p class="asc-sub">+{{ data.stats?.new_patients_today ?? 0 }} new today</p>
            </div>
          </div>
          <div class="admin-stat-card">
            <div class="asc-icon orange">
              <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <div class="asc-body">
              <p class="asc-label">Appointments Today</p>
              <p class="asc-value">{{ data.stats?.appointments_today ?? 0 }}</p>
              <p class="asc-sub">Scheduled for today</p>
            </div>
          </div>
        </div>

        <!-- Charts Row 1 -->
        <div class="admin-chart-row">
          <!-- Best Selling -->
          <div class="card top-frames">
            <h3 class="card-title">Best-Selling Products This Month</h3>
            <div class="frame-list">
              <div v-for="(frame, i) in topSellingFrames" :key="i" class="frame-row">
                <span class="frame-rank">{{ i + 1 }}</span>
                <div class="frame-info">
                  <span class="frame-name">{{ frame.name }}</span>
                  <span class="frame-value">{{ frame.value }} sold</span>
                </div>
                <span class="frame-badge" :class="frame.change >= 0 ? 'pos' : 'neg'">
                  {{ frame.change >= 0 ? '+' : '' }}{{ frame.change }}%
                </span>
              </div>
              <p v-if="!topSellingFrames.length" class="empty-state">No sales data this month</p>
            </div>
          </div>

          <!-- Total Earnings -->
          <div class="card chart-card">
            <h3 class="card-title">Total Earnings Per Month</h3>
            <div class="chart-wrap">
              <canvas ref="stockCanvas"></canvas>
            </div>
          </div>
        </div>

        <!-- Charts Row 2 -->
        <div class="admin-chart-row">
          <div class="card chart-card">
            <h3 class="card-title">Products Running Low on Stock</h3>
            <div class="chart-wrap">
              <canvas ref="deadStockCanvas"></canvas>
            </div>
          </div>
          <div class="card chart-card">
            <h3 class="card-title">Monthly Sales Performance</h3>
            <div class="chart-wrap">
              <canvas ref="stockLevelCanvas"></canvas>
            </div>
          </div>
        </div>

        <!-- Bottom Row -->
        <div class="admin-bottom-row">
          <div class="card">
            <div class="section-header">
              <h2 class="section-title">Recent Sales</h2>
              <RouterLink to="/transactions" class="view-link">View all →</RouterLink>
            </div>
            <div class="sale-list">
              <div v-for="sale in data.recent_sales?.slice(0, 5)" :key="sale.id" class="sale-item">
                <div>
                  <p class="sale-receipt">{{ sale.receipt_number }}</p>
                  <p class="sale-meta">{{ sale.patient?.first_name ?? 'Walk-in' }} · {{ sale.cashier?.name }}</p>
                </div>
                <p class="sale-amount">₱{{ fmt(sale.total_amount) }}</p>
              </div>
              <p v-if="!data.recent_sales?.length" class="empty-state">No sales yet</p>
            </div>
          </div>

          <div class="card">
            <div class="section-header">
              <h2 class="section-title">Upcoming Appointments</h2>
              <RouterLink to="/appointments" class="view-link">View all →</RouterLink>
            </div>
            <div class="appointment-list">
              <div v-for="appt in data.upcoming_appointments?.slice(0, 5)" :key="appt.id" class="appointment-item">
                <div class="appointment-badge">
                  <svg class="badge-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <div class="appointment-info">
                  <p class="appointment-name">{{ appt.patient?.first_name }} {{ appt.patient?.last_name }}</p>
                  <p class="appointment-time">{{ fmtDateTime(appt.appointment_date) }}</p>
                </div>
                <span class="appointment-type">{{ appt.type?.replace('_', ' ') }}</span>
              </div>
              <p v-if="!data.upcoming_appointments?.length" class="empty-state">No upcoming appointments</p>
            </div>
          </div>
        </div>

      </template>

      <!-- ══════════════════════════════════════════════════════ RECEPTIONIST -->
      <template v-else-if="role === 'receptionist'">
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon blue"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></div>
            <div><p class="stat-label">Appointments Today</p><p class="stat-value">{{ data.stats?.appointments_today ?? 0 }}</p></div>
          </div>
          <div class="stat-card">
            <div class="stat-icon green"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
            <div><p class="stat-label">Completed Today</p><p class="stat-value">{{ data.stats?.completed_today ?? 0 }}</p></div>
          </div>
          <div class="stat-card">
            <div class="stat-icon purple"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg></div>
            <div><p class="stat-label">Total Patients</p><p class="stat-value">{{ data.stats?.total_patients ?? 0 }}</p></div>
          </div>
          <div class="stat-card">
            <div class="stat-icon red"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></div>
            <div><p class="stat-label">Cancelled Today</p><p class="stat-value">{{ data.stats?.cancelled_today ?? 0 }}</p></div>
          </div>
        </div>

        <div class="content-grid">
          <div class="card">
            <div class="section-header">
              <h2 class="section-title">Today's Schedule</h2>
              <RouterLink to="/appointments" class="view-link">View all →</RouterLink>
            </div>
            <div v-if="data.today_appointments?.length" class="schedule-list">
              <div v-for="appt in data.today_appointments" :key="appt.id" class="schedule-item">
                <div class="time-badge">{{ fmtTime(appt.appointment_date) }}</div>
                <div class="schedule-details">
                  <p class="schedule-name">{{ appt.patient?.first_name }} {{ appt.patient?.last_name }}</p>
                  <p class="schedule-meta">{{ appt.type?.replace('_', ' ') }} · Dr. {{ appt.optometrist?.name ?? '—' }}</p>
                </div>
                <span class="status-badge" :class="appt.status">{{ appt.status?.replace('_', ' ') }}</span>
              </div>
            </div>
            <p v-else class="empty-state">No appointments scheduled today</p>
          </div>

          <div class="card">
            <div class="section-header">
              <h2 class="section-title">Recently Registered</h2>
              <RouterLink to="/patients" class="view-link">View all →</RouterLink>
            </div>
            <div v-if="data.recent_patients?.length" class="patient-list">
              <div v-for="p in data.recent_patients?.slice(0, 5)" :key="p.id" class="patient-item">
                <div class="patient-avatar">{{ p.first_name?.charAt(0) }}</div>
                <div>
                  <p class="patient-name">{{ p.first_name }} {{ p.last_name }}</p>
                  <p class="patient-code">{{ p.patient_code }}</p>
                </div>
                <p class="patient-date">{{ fmtDateShort(p.created_at) }}</p>
              </div>
            </div>
            <p v-else class="empty-state">No patients yet</p>
          </div>
        </div>
      </template>

      <!-- ══════════════════════════════════════════════════════ OPTOMETRIST -->
      <template v-else-if="role === 'optometrist'">
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon blue"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></div>
            <div><p class="stat-label">My Appointments Today</p><p class="stat-value">{{ data.stats?.my_appointments_today ?? 0 }}</p></div>
          </div>
          <div class="stat-card">
            <div class="stat-icon green"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
            <div><p class="stat-label">Completed Today</p><p class="stat-value">{{ data.stats?.completed_today ?? 0 }}</p></div>
          </div>
          <div class="stat-card">
            <div class="stat-icon purple"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg></div>
            <div><p class="stat-label">Prescriptions This Week</p><p class="stat-value">{{ data.stats?.prescriptions_this_week ?? 0 }}</p></div>
          </div>
          <div class="stat-card">
            <div class="stat-icon orange"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
            <div><p class="stat-label">Pending Today</p><p class="stat-value">{{ data.stats?.scheduled_today ?? 0 }}</p></div>
          </div>
        </div>

        <div class="content-grid">
          <div class="card">
            <div class="section-header">
              <h2 class="section-title">My Schedule Today</h2>
              <RouterLink to="/appointments" class="view-link">View all →</RouterLink>
            </div>
            <div v-if="data.today_appointments?.length" class="schedule-list">
              <div v-for="appt in data.today_appointments" :key="appt.id" class="schedule-item">
                <div class="time-badge">{{ fmtTime(appt.appointment_date) }}</div>
                <div class="schedule-details">
                  <p class="schedule-name">{{ appt.patient?.first_name }} {{ appt.patient?.last_name }}</p>
                  <p class="schedule-meta">{{ appt.type?.replace('_', ' ') }} · {{ appt.reason || '—' }}</p>
                </div>
                <span class="status-badge" :class="appt.status">{{ appt.status }}</span>
              </div>
            </div>
            <p v-else class="empty-state">No appointments assigned to you today</p>
          </div>

          <div class="card">
            <div class="section-header">
              <h2 class="section-title">Recent Prescriptions</h2>
            </div>
            <div v-if="data.recent_prescriptions?.length" class="prescription-list">
              <div v-for="rx in data.recent_prescriptions?.slice(0, 5)" :key="rx.id" class="prescription-item">
                <div>
                  <p class="prescription-patient">{{ rx.patient?.first_name }} {{ rx.patient?.last_name }}</p>
                  <p class="prescription-date">{{ fmtDateShort(rx.exam_date) }}</p>
                </div>
                <div class="prescription-values">
                  <p>OD {{ rx.od_sphere ?? '—' }} / {{ rx.od_cylinder ?? '—' }}</p>
                  <p>OS {{ rx.os_sphere ?? '—' }} / {{ rx.os_cylinder ?? '—' }}</p>
                </div>
              </div>
            </div>
            <p v-else class="empty-state">No recent prescriptions</p>
          </div>
        </div>
      </template>

      <!-- ══════════════════════════════════════════════════════ INVENTORY STAFF -->
      <template v-else-if="role === 'inventory_staff'">
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon blue"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></div>
            <div><p class="stat-label">Total Products</p><p class="stat-value">{{ data.stats?.total_products ?? 0 }}</p></div>
          </div>
          <div class="stat-card">
            <div class="stat-icon red"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></div>
            <div><p class="stat-label">Out of Stock</p><p class="stat-value red">{{ data.stats?.out_of_stock ?? 0 }}</p></div>
          </div>
          <div class="stat-card">
            <div class="stat-icon yellow"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg></div>
            <div><p class="stat-label">Low Stock</p><p class="stat-value yellow">{{ data.stats?.low_stock_count ?? 0 }}</p></div>
          </div>
          <div class="stat-card">
            <div class="stat-icon green"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
            <div><p class="stat-label">Stock Value</p><p class="stat-value green">₱{{ fmt(data.stats?.total_stock_value) }}</p></div>
          </div>
        </div>

        <div class="content-grid">
          <div class="card">
            <div class="section-header">
              <h2 class="section-title">Stock Alerts</h2>
              <RouterLink to="/inventory?low_stock=1" class="view-link">View inventory →</RouterLink>
            </div>
            <div v-if="data.low_stock_products?.length" class="alert-list">
              <div v-for="p in data.low_stock_products?.slice(0, 5)" :key="p.id" class="alert-item">
                <div class="alert-dot" :class="p.stock_quantity === 0 ? 'red' : 'yellow'"></div>
                <div>
                  <p class="alert-product">{{ p.name }}</p>
                  <p class="alert-meta">{{ p.category?.name }} · ROP: {{ p.reorder_point }}</p>
                </div>
                <span class="stock-badge" :class="p.stock_quantity === 0 ? 'red' : 'yellow'">
                  {{ p.stock_quantity === 0 ? 'Out' : p.stock_quantity + ' left' }}
                </span>
              </div>
            </div>
            <p v-else class="empty-state">All stocks are sufficient</p>
          </div>

          <div class="card">
            <div class="section-header">
              <h2 class="section-title">Recent Stock Movements</h2>
              <RouterLink to="/stock-movements" class="view-link">View all →</RouterLink>
            </div>
            <div v-if="data.recent_stock_movements?.length" class="movement-list">
              <div v-for="m in data.recent_stock_movements?.slice(0, 5)" :key="m.id" class="movement-item">
                <span class="movement-type" :class="m.type">{{ m.type?.replace('_', ' ') }}</span>
                <div class="movement-details">
                  <p class="movement-product">{{ m.product?.name }}</p>
                  <p class="movement-meta">{{ fmtDateShort(m.created_at) }} · {{ m.user?.name }}</p>
                </div>
                <span class="movement-qty" :class="['stock_in','return'].includes(m.type) ? 'green' : 'red'">
                  {{ ['stock_in','return'].includes(m.type) ? '+' : '-' }}{{ m.quantity }}
                </span>
              </div>
            </div>
            <p v-else class="empty-state">No recent movements</p>
          </div>
        </div>
      </template>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { Chart, registerables } from 'chart.js'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

Chart.register(...registerables)

const auth = useAuthStore()
const role = computed(() => auth.user?.role)
const dashSubtitle = computed(() => ({
  admin:           'Full system overview — sales, stock & appointments',
  receptionist:    'Daily appointments and patient schedule',
  optometrist:     'Your appointments and recent prescriptions',
  inventory_staff: 'Stock levels, alerts and movements',
}[role.value] ?? 'Welcome back'))
const data = ref({})
const loading = ref(true)
let refreshTimer = null

const stockCanvas = ref(null)
const deadStockCanvas = ref(null)
const stockLevelCanvas = ref(null)

let stockChart = null
let deadStockChart = null
let stockLevelChart = null

const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']

const topSellingFrames = computed(() => {
  const products = data.value.top_selling_products || []
  return products.slice(0, 5).map(p => ({
    name: p.name,
    value: p.total_sold?.toLocaleString() || '0',
    change: p.change_percent || 0
  }))
})

const chartDefaults = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top',
      labels: { font: { family: 'var(--font-body)', size: 12 }, boxWidth: 14, padding: 16 },
    },
  },
  scales: {
    x: { grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { family: 'var(--font-body)', size: 11 } } },
    y: { grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { family: 'var(--font-body)', size: 11 } } },
  },
}

function fmt(v) { return Number(v || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }
function fmtDateTime(v) { return v ? new Date(v).toLocaleString('en-PH', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—' }
function fmtTime(v) { return v ? new Date(v).toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' }) : '—' }
function fmtDateShort(v) { return v ? new Date(v).toLocaleDateString('en-PH', { month: 'short', day: 'numeric' }) : '—' }

async function fetchDashboard() {
  try {
    data.value = (await api.get('/dashboard')).data
    if (role.value === 'admin' && !loading.value) {
      await nextTick()
      initCharts()
    }
  } catch (e) {
    console.error('Dashboard fetch error:', e)
  }
}

function initCharts() {
  if (!stockCanvas.value || !deadStockCanvas.value || !stockLevelCanvas.value) return

  stockChart?.destroy()
  deadStockChart?.destroy()
  stockLevelChart?.destroy()

  // Build monthly sales arrays from API data (months 1–12)
  const monthlySalesRaw = data.value.monthly_sales || []
  const salesByMonth = Array(12).fill(0)
  monthlySalesRaw.forEach(row => { salesByMonth[row.month - 1] = parseFloat(row.total) || 0 })
  const currentMonth = new Date().getMonth() // 0-indexed, only show up to current month
  const displayLabels = months.slice(0, currentMonth + 1)
  const displaySales = salesByMonth.slice(0, currentMonth + 1)

  const currencyTick = (v) => v >= 1000 ? '₱' + (v / 1000).toFixed(0) + 'K' : '₱' + v

  // Chart 1 – Monthly Revenue line
  stockChart = new Chart(stockCanvas.value, {
    type: 'line',
    data: {
      labels: displayLabels,
      datasets: [{
        label: 'Revenue (₱)',
        data: displaySales,
        borderColor: '#3B82F6',
        backgroundColor: 'rgba(59,130,246,0.10)',
        tension: 0.4,
        fill: true,
        pointBackgroundColor: '#fff',
        pointBorderColor: '#3B82F6',
        pointRadius: 4,
        borderWidth: 2,
      }],
    },
    options: {
      ...chartDefaults,
      scales: {
        ...chartDefaults.scales,
        y: { ...chartDefaults.scales.y, min: 0, ticks: { ...chartDefaults.scales.y.ticks, callback: currencyTick } },
      },
    },
  })

  // Chart 2 – Low Stock vs Reorder Point horizontal bar
  const lowProds = (data.value.low_stock_products || []).slice(0, 8)
  const lowLabels = lowProds.map(p => p.name.length > 18 ? p.name.slice(0, 18) + '…' : p.name)
  const stockQty = lowProds.map(p => p.stock_quantity)
  const ropQty   = lowProds.map(p => p.reorder_point)

  deadStockChart = new Chart(deadStockCanvas.value, {
    type: 'bar',
    data: {
      labels: lowLabels.length ? lowLabels : ['(all stocks OK)'],
      datasets: [
        {
          label: 'Current Stock',
          data: stockQty.length ? stockQty : [0],
          backgroundColor: stockQty.map(q => q === 0 ? '#EF4444' : '#F59E0B'),
          borderRadius: 3,
        },
        {
          label: 'Reorder Point',
          data: ropQty.length ? ropQty : [0],
          backgroundColor: 'rgba(203,213,225,0.7)',
          borderRadius: 3,
        },
      ],
    },
    options: {
      ...chartDefaults,
      indexAxis: 'y',
      scales: {
        x: { ...chartDefaults.scales.x },
        y: { grid: { display: false }, ticks: { font: { family: 'var(--font-body)', size: 10 } } },
      },
    },
  })

  // Chart 3 – Monthly Sales bar + trend line
  stockLevelChart = new Chart(stockLevelCanvas.value, {
    type: 'bar',
    data: {
      labels: displayLabels,
      datasets: [
        {
          type: 'bar',
          label: 'Monthly Sales',
          data: displaySales,
          backgroundColor: displaySales.map((_, i) => i === displaySales.length - 1 ? '#3B82F6' : 'rgba(59,130,246,0.45)'),
          borderRadius: 4,
          order: 1,
        },
        {
          type: 'line',
          label: 'Trend',
          data: displaySales,
          borderColor: '#1A2744',
          backgroundColor: 'transparent',
          tension: 0.4,
          pointRadius: 0,
          borderWidth: 2,
          order: 0,
        },
      ],
    },
    options: {
      ...chartDefaults,
      scales: {
        ...chartDefaults.scales,
        y: { ...chartDefaults.scales.y, min: 0, ticks: { ...chartDefaults.scales.y.ticks, callback: currencyTick } },
      },
    },
  })
}

onMounted(async () => {
  await fetchDashboard()
  loading.value = false
  if (role.value === 'admin') {
    await nextTick()
    initCharts()
  }
  refreshTimer = setInterval(fetchDashboard, 60000)
})

onUnmounted(() => { if (refreshTimer) clearInterval(refreshTimer) })
</script>

<style scoped>
.dashboard {
  padding: 28px 32px 40px;
}

.page-header {
  display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;
}
.header-icon {
  width: 44px; height: 44px; border-radius: 12px;
  background: linear-gradient(135deg, #1a2744, #2d4a8a);
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.header-icon svg { color: white; }
.page-title { font-size: 20px; font-weight: 800; color: #111827; margin: 0; }
.page-sub   { font-size: 13px; color: #9ca3af; margin: 2px 0 0; }

/* Admin stat cards */
.admin-stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 18px;
}

.admin-stat-card {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 18px 20px;
  background: rgba(255,255,255,0.92);
  backdrop-filter: blur(6px);
  border: 1.5px solid rgba(226,232,240,0.5);
  border-top-width: 4px;
  border-top-style: solid;
  border-top-color: transparent;
  border-radius: 14px;
}

/* Accent top border matching admin icon colours */
.admin-stat-card:has(.asc-icon.blue)   { border-top-color: #3b82f6; }
.admin-stat-card:has(.asc-icon.green)  { border-top-color: #10b981; }
.admin-stat-card:has(.asc-icon.purple) { border-top-color: #a855f7; }
.admin-stat-card:has(.asc-icon.orange) { border-top-color: #f97316; }
.admin-stat-card:has(.asc-icon.teal)   { border-top-color: #0d9488; }
.admin-stat-card:has(.asc-icon.yellow) { border-top-color: #f59e0b; }

.asc-icon {
  width: 46px; height: 46px; border-radius: 12px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
}
.asc-icon.green  { background: #dcfce7; color: #16a34a; }
.asc-icon.blue   { background: #dbeafe; color: #2563eb; }
.asc-icon.purple { background: #ede9fe; color: #7c3aed; }
.asc-icon.orange { background: #ffedd5; color: #ea580c; }

.asc-body { flex: 1; min-width: 0; }
.asc-label { font-size: 11px; font-weight: 600; color: var(--muted); margin: 0; text-transform: uppercase; letter-spacing: 0.4px; }
.asc-value { font-size: 20px; font-weight: 800; color: var(--navy); margin: 2px 0; line-height: 1.2; }
.asc-sub   { font-size: 11px; color: var(--muted); margin: 0; }

/* Admin chart rows */
.admin-chart-row {
  display: grid;
  grid-template-columns: 280px 1fr;
  gap: 18px;
  margin-bottom: 18px;
}

.admin-bottom-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 18px;
}

.chart-card {
  padding: 20px;
  background: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(6px);
}

.alert-banner {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 14px 20px;
  background: #FEF2F2;
  border: 1.5px solid #FECACA;
  border-left: 5px solid #DC2626;
  border-radius: 12px;
  margin-bottom: 20px;
  animation: alertPulse 2s ease-in-out infinite;
}

@keyframes alertPulse {
  0%, 100% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0); }
  50%       { box-shadow: 0 0 0 5px rgba(220, 38, 38, 0.08); }
}

.alert-icon-wrap {
  width: 40px; height: 40px; border-radius: 10px;
  background: #FEE2E2;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; color: #DC2626;
}

.alert-content {
  flex: 1;
}

.alert-title {
  font-size: 13px;
  font-weight: 800;
  color: #991B1B;
  margin: 0;
}

.alert-message {
  font-size: 12px;
  color: #7F1D1D;
  margin: 2px 0 0;
}

.alert-action {
  font-size: 12px;
  color: #DC2626;
  text-decoration: none;
  font-weight: 700;
  white-space: nowrap;
  padding: 7px 14px;
  border: 1.5px solid #FECACA;
  border-radius: 8px;
  background: #fff;
  transition: all 0.2s;
}

.alert-action:hover {
  background: #DC2626;
  color: #fff;
  border-color: #DC2626;
}

.card-title {
  font-size: 13px;
  font-weight: 700;
  color: var(--navy);
  margin: 0 0 14px;
}

.frame-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.frame-row {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  background: var(--bg);
  border-radius: 8px;
}

.frame-rank { width: 20px; height: 20px; border-radius: 50%; background: var(--navy); color: #fff; font-size: 10px; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.frame-info { display: flex; flex-direction: column; flex: 1; min-width: 0; }
.frame-name { font-size: 11px; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.frame-value { font-size: 13px; font-weight: 800; color: var(--navy); margin-top: 2px; }

.frame-badge {
  font-size: 12px;
  font-weight: 700;
  padding: 3px 10px;
  border-radius: 99px;
}

.frame-badge.pos { background: #DCFCE7; color: #166534; }
.frame-badge.neg { background: var(--navy); color: #fff; }

.chart-wrap {
  position: relative;
  height: 240px;
}

/* Section styles */
.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}

.section-title {
  font-size: 13px;
  font-weight: 700;
  color: var(--navy);
  margin: 0;
}

.view-link {
  font-size: 12px;
  color: var(--teal);
  text-decoration: none;
  font-weight: 600;
  transition: color var(--duration) var(--ease);
}

.view-link:hover { color: var(--teal-dark); }

/* Sales list */
.sale-list,
.appointment-list,
.patient-list,
.alert-list,
.movement-list,
.schedule-list,
.prescription-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.sale-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 12px;
  background: var(--bg);
  border-radius: 8px;
}

.sale-receipt { font-size: 12px; font-weight: 600; color: var(--navy); margin: 0; }
.sale-meta { font-size: 11px; color: var(--muted); margin: 2px 0 0; }
.sale-amount { font-size: 13px; font-weight: 700; color: #10B981; }

.appointment-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  background: var(--bg);
  border-radius: 8px;
}

.appointment-badge {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  background: #DBEAFE;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.badge-icon { width: 18px; height: 18px; color: #3B82F6; }

.appointment-info { flex: 1; min-width: 0; }
.appointment-name { font-size: 12px; font-weight: 600; color: var(--navy); margin: 0; }
.appointment-time { font-size: 11px; color: var(--muted); margin: 2px 0 0; }
.appointment-type { font-size: 11px; font-weight: 600; color: var(--teal); white-space: nowrap; }

/* Stats grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.stat-card {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 16px;
  background: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(6px);
  border-radius: 14px;
  border: 1.5px solid rgba(226, 232, 240, 0.5);
  border-top-width: 4px;
  border-top-style: solid;
  border-top-color: transparent;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.stat-icon {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.stat-icon svg { width: 20px; height: 20px; color: #fff; }
.stat-icon.blue { background: #3B82F6; }
.stat-icon.green { background: #10B981; }
.stat-icon.purple { background: #A855F7; }
.stat-icon.red { background: #EF4444; }
.stat-icon.yellow { background: #FBBF24; }
.stat-icon.orange { background: #F97316; }

.stat-label { font-size: 11px; color: var(--muted); font-weight: 600; margin: 0; }
.stat-value { font-size: 24px; font-weight: 800; color: var(--navy); margin: 4px 0 0; }
.stat-value.red { color: #EF4444; }
.stat-value.green { color: #10B981; }
.stat-value.yellow { color: #FBBF24; }

/* Content grid */
.content-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 18px;
}

.schedule-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  border: 1px solid var(--border);
  border-radius: 8px;
  transition: border-color var(--duration) var(--ease);
}

.schedule-item:hover { border-color: var(--teal); }

.time-badge {
  width: 48px;
  text-align: center;
  font-size: 12px;
  font-weight: 700;
  color: var(--teal);
  flex-shrink: 0;
}

.schedule-details { flex: 1; min-width: 0; }
.schedule-name { font-size: 12px; font-weight: 600; color: var(--navy); margin: 0; }
.schedule-meta { font-size: 11px; color: var(--muted); margin: 2px 0 0; }

.status-badge {
  font-size: 11px;
  font-weight: 600;
  padding: 4px 8px;
  border-radius: 4px;
  white-space: nowrap;
  flex-shrink: 0;
}

.status-badge.scheduled { background: #DBEAFE; color: #1E40AF; }
.status-badge.completed { background: #DCFCE7; color: #166534; }
.status-badge.cancelled { background: #FEE2E2; color: #991B1B; }
.status-badge.no_show { background: #F3F4F6; color: #374151; }

.patient-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  background: var(--bg);
  border-radius: 8px;
}

.patient-avatar {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: #A855F7;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 11px;
  flex-shrink: 0;
}

.patient-name { font-size: 12px; font-weight: 600; color: var(--navy); margin: 0; }
.patient-code { font-size: 10px; color: var(--muted); margin: 2px 0 0; font-family: monospace; }
.patient-date { font-size: 11px; color: var(--muted); margin-left: auto; flex-shrink: 0; }

.alert-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px;
  background: var(--bg);
  border-radius: 8px;
}

.alert-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  flex-shrink: 0;
}

.alert-dot.red { background: #EF4444; }
.alert-dot.yellow { background: #FBBF24; }

.alert-product { font-size: 12px; font-weight: 600; color: var(--navy); margin: 0; }
.alert-meta { font-size: 11px; color: var(--muted); margin: 2px 0 0; }

.stock-badge {
  font-size: 11px;
  font-weight: 600;
  padding: 4px 8px;
  border-radius: 4px;
  flex-shrink: 0;
}

.stock-badge.red { background: #FEE2E2; color: #991B1B; }
.stock-badge.yellow { background: #FEF3C7; color: #92400E; }

.movement-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  background: var(--bg);
  border-radius: 8px;
}

.movement-type {
  font-size: 10px;
  font-weight: 700;
  padding: 4px 8px;
  border-radius: 4px;
  text-transform: capitalize;
  flex-shrink: 0;
}

.movement-type.stock_in,
.movement-type.return { background: #DCFCE7; color: #166534; }

.movement-type.sale { background: #DBEAFE; color: #1E40AF; }
.movement-type.adjustment { background: #FEF3C7; color: #92400E; }

.movement-details { flex: 1; min-width: 0; }
.movement-product { font-size: 12px; font-weight: 600; color: var(--navy); margin: 0; }
.movement-meta { font-size: 11px; color: var(--muted); margin: 2px 0 0; }

.movement-qty {
  font-size: 13px;
  font-weight: 700;
  flex-shrink: 0;
}

.movement-qty.green { color: #10B981; }
.movement-qty.red { color: #EF4444; }

.prescription-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 12px;
  background: var(--bg);
  border-radius: 8px;
}

.prescription-patient { font-size: 12px; font-weight: 600; color: var(--navy); margin: 0; }
.prescription-date { font-size: 11px; color: var(--muted); margin: 2px 0 0; }

.prescription-values {
  text-align: right;
  font-size: 10px;
  color: var(--muted);
  font-family: monospace;
  line-height: 1.4;
}

.prescription-values p { margin: 0; }

.empty-state {
  text-align: center;
  padding: 24px 16px;
  font-size: 13px;
  color: var(--muted);
  margin: 0;
}

/* Responsive */
@media (max-width: 1200px) {
  .dash-grid {
    grid-template-columns: 1fr;
  }
  
  .top-frames,
  .chart-card {
    grid-column: 1 !important;
  }
  
  .alert-banner {
    grid-column: 1 !important;
  }

  .content-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .dashboard {
    padding: 16px 16px 24px;
  }

  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .content-grid {
    grid-template-columns: 1fr;
  }
}
</style>
