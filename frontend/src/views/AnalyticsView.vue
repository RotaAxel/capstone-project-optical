<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-base font-semibold text-gray-800">Predictive Analytics</h2>
        <p class="text-xs text-gray-500">ARIMA · EOQ · ROP · FSN Analysis</p>
      </div>
      <button @click="runAnalytics" class="btn-primary" :disabled="running">
        <svg v-if="running" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
        {{ running ? 'Computing...' : 'Run Analytics' }}
      </button>
    </div>

    <!-- Model Info Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="card">
        <p class="text-xs font-semibold text-gray-500 uppercase">ARIMA</p>
        <p class="text-sm text-gray-700 mt-1">Demand Forecasting</p>
        <p class="text-xs text-gray-400 mt-1">Predicts next 30-day demand based on 90-day sales trend</p>
      </div>
      <div class="card">
        <p class="text-xs font-semibold text-gray-500 uppercase">EOQ</p>
        <p class="text-sm text-gray-700 mt-1">Economic Order Quantity</p>
        <p class="text-xs text-gray-400 mt-1">Optimal reorder qty to minimize holding & ordering costs</p>
      </div>
      <div class="card">
        <p class="text-xs font-semibold text-gray-500 uppercase">ROP</p>
        <p class="text-sm text-gray-700 mt-1">Reorder Point</p>
        <p class="text-xs text-gray-400 mt-1">Minimum stock level to trigger reorder (lead time: 7 days)</p>
      </div>
      <div class="card">
        <p class="text-xs font-semibold text-gray-500 uppercase">FSN</p>
        <p class="text-sm text-gray-700 mt-1">Fast-Slow-Non Moving</p>
        <p class="text-xs text-gray-400 mt-1">Classifies products by movement rate</p>
      </div>
    </div>

    <!-- FSN Summary Counters -->
    <div v-if="summary" class="grid grid-cols-3 gap-4">
      <div class="card text-center border-l-4 border-l-green-500">
        <p class="text-xs text-gray-500">Fast Moving</p>
        <p class="text-3xl font-bold text-green-600 mt-1">{{ summary.fsn_summary.fast }}</p>
      </div>
      <div class="card text-center border-l-4 border-l-yellow-400">
        <p class="text-xs text-gray-500">Slow Moving</p>
        <p class="text-3xl font-bold text-yellow-600 mt-1">{{ summary.fsn_summary.slow }}</p>
      </div>
      <div class="card text-center border-l-4 border-l-red-400">
        <p class="text-xs text-gray-500">Non-Moving</p>
        <p class="text-3xl font-bold text-red-600 mt-1">{{ summary.fsn_summary.nonMoving }}</p>
      </div>
    </div>

    <!-- ── Charts ──────────────────────────────────────────────────── -->
    <div v-if="results.length" class="space-y-6">

      <!-- Row 1: FSN Donut + Demand vs Stock -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- FSN Donut -->
        <div class="card flex flex-col">
          <h3 class="text-sm font-semibold text-gray-800 mb-4">FSN Distribution</h3>
          <div class="flex-1 flex items-center justify-center" style="min-height:220px">
            <Doughnut :data="fsnChartData" :options="doughnutOptions" />
          </div>
          <div class="flex justify-center gap-6 mt-4 text-xs text-gray-500">
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-green-500 inline-block"></span>Fast</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-yellow-400 inline-block"></span>Slow</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-red-400 inline-block"></span>Non-moving</span>
          </div>
        </div>

        <!-- Demand vs Current Stock -->
        <div class="card lg:col-span-2">
          <h3 class="text-sm font-semibold text-gray-800 mb-4">Predicted Demand vs Current Stock (30-day)</h3>
          <div style="height:260px">
            <Bar :data="demandStockData" :options="barOptions" />
          </div>
        </div>
      </div>

      <!-- Row 2: EOQ & ROP per product -->
      <div class="card">
        <h3 class="text-sm font-semibold text-gray-800 mb-4">EOQ & ROP per Product</h3>
        <div :style="{ height: eoqChartHeight }">
          <Bar :data="eoqRopData" :options="eoqRopOptions" />
        </div>
      </div>

      <!-- Row 3: Demand Forecast Trend (per-product line) -->
      <div class="card">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-semibold text-gray-800">Avg Daily Demand by Product</h3>
          <span class="text-xs text-gray-400">Based on 90-day sales history</span>
        </div>
        <div style="height:260px">
          <Bar :data="avgDailyData" :options="{ ...barOptions, indexAxis: 'y' }" />
        </div>
      </div>
    </div>

    <!-- Results Table -->
    <div v-if="results.length" class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="px-4 py-3 border-b bg-gray-50 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-gray-800">Analytics Results</h3>
        <p class="text-xs text-gray-400">Computed: {{ computedAt }}</p>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="table-th">Product</th>
              <th class="table-th">Current Stock</th>
              <th class="table-th">Predicted Demand (30d)</th>
              <th class="table-th">EOQ</th>
              <th class="table-th">ROP</th>
              <th class="table-th">FSN Class</th>
              <th class="table-th">Alert</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-for="r in results" :key="r.product.id"
              @click="openDetail(r)"
              class="hover:bg-blue-50 transition-colors cursor-pointer group">
              <td class="table-td">
                <p class="text-sm font-medium text-gray-900 group-hover:text-blue-700">{{ r.product.name }}</p>
                <p class="text-xs text-gray-400 font-mono">{{ r.product.sku }}</p>
              </td>
              <td class="table-td text-sm" :class="r.product.stock_quantity <= r.product.reorder_point ? 'text-red-600 font-semibold' : 'text-gray-700'">
                {{ r.product.stock_quantity }}
              </td>
              <td class="table-td text-sm text-blue-700 font-medium">{{ r.analytics?.predicted_demand ?? '—' }}</td>
              <td class="table-td text-sm text-purple-700 font-medium">{{ r.analytics?.eoq_value ?? '—' }}</td>
              <td class="table-td text-sm text-orange-600 font-medium">{{ r.analytics?.rop_value ?? '—' }}</td>
              <td class="table-td">
                <span :class="{
                  'badge-green':  r.analytics?.fsn_classification === 'fast',
                  'badge-yellow': r.analytics?.fsn_classification === 'slow',
                  'badge-red':    r.analytics?.fsn_classification === 'non_moving',
                  'badge-gray':   !r.analytics?.fsn_classification,
                }" class="capitalize">
                  {{ r.analytics?.fsn_classification?.replace('_', ' ') ?? '—' }}
                </span>
              </td>
              <td class="table-td">
                <div class="flex items-center gap-2">
                  <span v-if="r.product.stock_quantity <= r.product.reorder_point" class="badge-red text-xs">⚠ Reorder Now</span>
                  <span v-else-if="r.analytics?.predicted_demand > r.product.stock_quantity" class="badge-yellow text-xs">⚠ Stock Forecast</span>
                  <span v-else class="badge-green text-xs">OK</span>
                  <svg class="w-4 h-4 text-gray-300 group-hover:text-blue-400 transition-colors ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                  </svg>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ── Product Detail Slide-over ──────────────────────────────────── -->
    <Teleport to="body">
      <!-- Backdrop -->
      <div v-if="selected" class="fixed inset-0 bg-black/40 z-40" @click="selected = null"></div>

      <!-- Panel -->
      <transition enter-active-class="transition-transform duration-300 ease-out"
                  enter-from-class="translate-x-full"
                  enter-to-class="translate-x-0"
                  leave-active-class="transition-transform duration-200 ease-in"
                  leave-from-class="translate-x-0"
                  leave-to-class="translate-x-full">
        <div v-if="selected"
          class="fixed top-0 right-0 h-full w-full max-w-lg bg-white shadow-2xl z-50 flex flex-col overflow-hidden">

          <!-- Header -->
          <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50 shrink-0">
            <div>
              <p class="font-semibold text-gray-900">{{ selected.product.name }}</p>
              <p class="text-xs text-gray-400 font-mono mt-0.5">{{ selected.product.sku }}</p>
            </div>
            <button @click="selected = null" class="text-gray-400 hover:text-gray-700 p-1 rounded-lg hover:bg-gray-200 transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>

          <!-- Scrollable body -->
          <div class="flex-1 overflow-y-auto p-6 space-y-6">

            <!-- FSN Badge + status -->
            <div class="flex items-center gap-3">
              <span :class="{
                'bg-green-100 text-green-800':  selected.analytics?.fsn_classification === 'fast',
                'bg-yellow-100 text-yellow-800':selected.analytics?.fsn_classification === 'slow',
                'bg-red-100 text-red-800':      selected.analytics?.fsn_classification === 'non_moving',
                'bg-gray-100 text-gray-600':    !selected.analytics?.fsn_classification,
              }" class="px-3 py-1 rounded-full text-sm font-semibold capitalize">
                {{ fsnLabel(selected.analytics?.fsn_classification) }}
              </span>
              <span v-if="selected.product.stock_quantity <= selected.product.reorder_point"
                class="badge-red text-xs">⚠ Below Reorder Point</span>
              <span v-else class="badge-green text-xs">Stock OK</span>
            </div>

            <!-- Key Metrics -->
            <div class="grid grid-cols-2 gap-3">
              <div class="bg-gray-50 rounded-xl p-4 text-center">
                <p class="text-xs text-gray-500 mb-1">Current Stock</p>
                <p class="text-2xl font-bold" :class="selected.product.stock_quantity <= selected.product.reorder_point ? 'text-red-600' : 'text-gray-900'">
                  {{ selected.product.stock_quantity }}
                </p>
                <p class="text-xs text-gray-400 mt-0.5">units</p>
              </div>
              <div class="bg-blue-50 rounded-xl p-4 text-center">
                <p class="text-xs text-blue-600 mb-1">Predicted Demand (30d)</p>
                <p class="text-2xl font-bold text-blue-700">{{ selected.analytics?.predicted_demand ?? '—' }}</p>
                <p class="text-xs text-blue-400 mt-0.5">units</p>
              </div>
              <div class="bg-purple-50 rounded-xl p-4 text-center">
                <p class="text-xs text-purple-600 mb-1">EOQ</p>
                <p class="text-2xl font-bold text-purple-700">{{ selected.analytics?.eoq_value ?? '—' }}</p>
                <p class="text-xs text-purple-400 mt-0.5">units/order</p>
              </div>
              <div class="bg-orange-50 rounded-xl p-4 text-center">
                <p class="text-xs text-orange-600 mb-1">Reorder Point</p>
                <p class="text-2xl font-bold text-orange-700">{{ selected.analytics?.rop_value ?? '—' }}</p>
                <p class="text-xs text-orange-400 mt-0.5">units threshold</p>
              </div>
            </div>

            <!-- Stock vs Demand mini bar chart -->
            <div class="bg-white border border-gray-100 rounded-xl p-4">
              <p class="text-xs font-semibold text-gray-700 mb-3">Stock vs Demand vs ROP</p>
              <div style="height:160px">
                <Bar :data="detailChartData" :options="detailChartOptions" />
              </div>
            </div>

            <!-- ARIMA Section -->
            <div class="border border-gray-100 rounded-xl overflow-hidden">
              <div class="bg-blue-50 px-4 py-2.5 flex items-center gap-2">
                <span class="text-xs font-bold text-blue-700 uppercase tracking-wider">ARIMA</span>
                <span class="text-xs text-blue-500">Demand Forecast</span>
              </div>
              <div class="p-4 space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500">Sales last 90 days</span>
                  <span class="font-semibold">{{ selected.analytics?.result_data?.sales_90d ?? '—' }} units</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500">Avg daily demand</span>
                  <span class="font-semibold">{{ Number(selected.analytics?.result_data?.avg_daily ?? 0).toFixed(3) }} units/day</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500">Predicted next 30 days</span>
                  <span class="font-semibold text-blue-700">{{ selected.analytics?.predicted_demand ?? '—' }} units</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500">Days of stock remaining</span>
                  <span class="font-semibold" :class="daysRemaining(selected) < 14 ? 'text-red-600' : 'text-green-700'">
                    {{ daysRemaining(selected) }} days
                  </span>
                </div>
              </div>
            </div>

            <!-- EOQ Section -->
            <div class="border border-gray-100 rounded-xl overflow-hidden">
              <div class="bg-purple-50 px-4 py-2.5 flex items-center gap-2">
                <span class="text-xs font-bold text-purple-700 uppercase tracking-wider">EOQ</span>
                <span class="text-xs text-purple-500">Economic Order Quantity</span>
              </div>
              <div class="p-4 space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500">Annual demand (projected)</span>
                  <span class="font-semibold">{{ Math.round((selected.analytics?.result_data?.avg_daily ?? 0) * 365) }} units/yr</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500">Order cost (fixed)</span>
                  <span class="font-semibold">₱500.00</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500">Holding cost (20% of cost price)</span>
                  <span class="font-semibold">₱{{ holdingCost(selected) }}/unit/yr</span>
                </div>
                <div class="flex justify-between text-sm border-t pt-2 mt-1">
                  <span class="text-gray-700 font-medium">Optimal order qty (EOQ)</span>
                  <span class="font-bold text-purple-700">{{ selected.analytics?.eoq_value ?? '—' }} units</span>
                </div>
              </div>
            </div>

            <!-- ROP Section -->
            <div class="border border-gray-100 rounded-xl overflow-hidden">
              <div class="bg-orange-50 px-4 py-2.5 flex items-center gap-2">
                <span class="text-xs font-bold text-orange-700 uppercase tracking-wider">ROP</span>
                <span class="text-xs text-orange-500">Reorder Point</span>
              </div>
              <div class="p-4 space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500">Avg daily demand</span>
                  <span class="font-semibold">{{ Number(selected.analytics?.result_data?.avg_daily ?? 0).toFixed(3) }} units/day</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500">Lead time</span>
                  <span class="font-semibold">7 days</span>
                </div>
                <div class="flex justify-between text-sm border-t pt-2 mt-1">
                  <span class="text-gray-700 font-medium">Reorder when stock ≤</span>
                  <span class="font-bold text-orange-700">{{ selected.analytics?.rop_value ?? '—' }} units</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500">Current stock vs ROP</span>
                  <span class="font-semibold" :class="selected.product.stock_quantity <= (selected.analytics?.rop_value ?? 0) ? 'text-red-600' : 'text-green-700'">
                    {{ selected.product.stock_quantity <= (selected.analytics?.rop_value ?? 0) ? 'Order now!' : 'Sufficient' }}
                  </span>
                </div>
              </div>
            </div>

            <!-- FSN Section -->
            <div class="border border-gray-100 rounded-xl overflow-hidden">
              <div class="px-4 py-2.5 flex items-center gap-2"
                :class="{
                  'bg-green-50':  selected.analytics?.fsn_classification === 'fast',
                  'bg-yellow-50': selected.analytics?.fsn_classification === 'slow',
                  'bg-red-50':    selected.analytics?.fsn_classification === 'non_moving',
                  'bg-gray-50':   !selected.analytics?.fsn_classification,
                }">
                <span class="text-xs font-bold uppercase tracking-wider"
                  :class="{
                    'text-green-700':  selected.analytics?.fsn_classification === 'fast',
                    'text-yellow-700': selected.analytics?.fsn_classification === 'slow',
                    'text-red-700':    selected.analytics?.fsn_classification === 'non_moving',
                    'text-gray-600':   !selected.analytics?.fsn_classification,
                  }">FSN</span>
                <span class="text-xs text-gray-500">Classification</span>
              </div>
              <div class="p-4 space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500">Avg daily demand</span>
                  <span class="font-semibold">{{ Number(selected.analytics?.result_data?.avg_daily ?? 0).toFixed(3) }} units/day</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500">Classification threshold</span>
                  <span class="font-semibold text-gray-600">Fast ≥ 1/day · Slow ≥ 0.1/day · Non-moving &lt; 0.1/day</span>
                </div>
                <div class="flex justify-between text-sm border-t pt-2 mt-1">
                  <span class="text-gray-700 font-medium">Classification</span>
                  <span class="font-bold capitalize" :class="{
                    'text-green-700':  selected.analytics?.fsn_classification === 'fast',
                    'text-yellow-700': selected.analytics?.fsn_classification === 'slow',
                    'text-red-700':    selected.analytics?.fsn_classification === 'non_moving',
                  }">{{ fsnLabel(selected.analytics?.fsn_classification) }}</span>
                </div>
                <p class="text-xs text-gray-400 mt-1 leading-relaxed">{{ fsnDescription(selected.analytics?.fsn_classification) }}</p>
              </div>
            </div>

          </div>
        </div>
      </transition>
    </Teleport>

    <!-- Empty state -->
    <div v-if="!results.length && !running" class="card text-center py-12">
      <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
      </svg>
      <p class="text-gray-500 text-sm">Click <strong>Run Analytics</strong> to compute ARIMA, EOQ, ROP, and FSN for all products.</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Bar, Doughnut } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale, LinearScale, BarElement, ArcElement,
  Title, Tooltip, Legend
} from 'chart.js'
import api from '@/services/api'

ChartJS.register(CategoryScale, LinearScale, BarElement, ArcElement, Title, Tooltip, Legend)

const results    = ref([])
const summary    = ref(null)
const running    = ref(false)
const computedAt = ref('')
const selected   = ref(null)

function openDetail(r) { selected.value = r }

function fsnLabel(cls) {
  return { fast: 'Fast Moving', slow: 'Slow Moving', non_moving: 'Non-Moving' }[cls] ?? '—'
}

function fsnDescription(cls) {
  return {
    fast:       'This product moves quickly (≥1 unit/day). Prioritize stock replenishment and maintain higher safety stock.',
    slow:       'This product moves slowly (0.1–1 unit/day). Monitor stock levels and avoid over-ordering.',
    non_moving: 'This product has little or no movement (<0.1 unit/day). Review for dead stock, consider promotions or liquidation.',
  }[cls] ?? ''
}

function daysRemaining(r) {
  const avg = r.analytics?.result_data?.avg_daily ?? 0
  if (!avg) return '∞'
  return Math.max(0, Math.round(r.product.stock_quantity / avg))
}

function holdingCost(r) {
  return Number((r.product?.cost_price ?? 0) * 0.20).toLocaleString('en-PH', { minimumFractionDigits: 2 })
}

const detailChartData = computed(() => {
  if (!selected.value) return { labels: [], datasets: [] }
  const r = selected.value
  return {
    labels: ['Current Stock', 'Predicted Demand (30d)', 'EOQ', 'ROP'],
    datasets: [{
      label: r.product.name,
      data: [
        r.product.stock_quantity,
        r.analytics?.predicted_demand ?? 0,
        r.analytics?.eoq_value ?? 0,
        r.analytics?.rop_value ?? 0,
      ],
      backgroundColor: ['#22c55e', '#3b82f6', '#a855f7', '#f97316'],
      borderColor:     ['#16a34a', '#2563eb', '#9333ea', '#ea580c'],
      borderWidth: 1,
      borderRadius: 6,
    }],
  }
})

const detailChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false }, tooltip: { mode: 'index' } },
  scales: {
    x: { ticks: { font: { size: 10 } }, grid: { display: false } },
    y: { beginAtZero: true, ticks: { font: { size: 10 } }, grid: { color: '#f3f4f6' } },
  },
}

// ── Derived chart datasets ──────────────────────────────────────────

const productLabels = computed(() => results.value.map(r => r.product.sku ?? r.product.name))

const fsnChartData = computed(() => ({
  labels: ['Fast Moving', 'Slow Moving', 'Non-Moving'],
  datasets: [{
    data: [
      summary.value?.fsn_summary?.fast ?? 0,
      summary.value?.fsn_summary?.slow ?? 0,
      summary.value?.fsn_summary?.nonMoving ?? 0,
    ],
    backgroundColor: ['#22c55e', '#facc15', '#f87171'],
    borderColor: ['#16a34a', '#ca8a04', '#ef4444'],
    borderWidth: 2,
    hoverOffset: 6,
  }],
}))

const demandStockData = computed(() => ({
  labels: productLabels.value,
  datasets: [
    {
      label: 'Predicted Demand (30d)',
      data: results.value.map(r => r.analytics?.predicted_demand ?? 0),
      backgroundColor: 'rgba(59,130,246,0.75)',
      borderColor: '#3b82f6',
      borderWidth: 1,
      borderRadius: 4,
    },
    {
      label: 'Current Stock',
      data: results.value.map(r => r.product.stock_quantity ?? 0),
      backgroundColor: 'rgba(34,197,94,0.65)',
      borderColor: '#22c55e',
      borderWidth: 1,
      borderRadius: 4,
    },
  ],
}))

const eoqRopData = computed(() => ({
  labels: productLabels.value,
  datasets: [
    {
      label: 'EOQ',
      data: results.value.map(r => r.analytics?.eoq_value ?? 0),
      backgroundColor: 'rgba(168,85,247,0.75)',
      borderColor: '#a855f7',
      borderWidth: 1,
      borderRadius: 4,
    },
    {
      label: 'ROP',
      data: results.value.map(r => r.analytics?.rop_value ?? 0),
      backgroundColor: 'rgba(249,115,22,0.75)',
      borderColor: '#f97316',
      borderWidth: 1,
      borderRadius: 4,
    },
  ],
}))

const avgDailyData = computed(() => ({
  labels: productLabels.value,
  datasets: [{
    label: 'Avg Daily Demand',
    data: results.value.map(r => {
      const d = r.analytics?.result_data?.avg_daily ?? r.analytics?.predicted_demand / 30 ?? 0
      return Math.round(d * 100) / 100
    }),
    backgroundColor: results.value.map(r => {
      const cls = r.analytics?.fsn_classification
      if (cls === 'fast') return 'rgba(34,197,94,0.75)'
      if (cls === 'slow') return 'rgba(250,204,21,0.75)'
      return 'rgba(248,113,113,0.75)'
    }),
    borderColor: results.value.map(r => {
      const cls = r.analytics?.fsn_classification
      if (cls === 'fast') return '#16a34a'
      if (cls === 'slow') return '#ca8a04'
      return '#ef4444'
    }),
    borderWidth: 1,
    borderRadius: 4,
  }],
}))

const eoqChartHeight = computed(() => Math.max(260, results.value.length * 36) + 'px')

// ── Chart options ───────────────────────────────────────────────────

const barOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { position: 'top', labels: { font: { size: 11 }, boxWidth: 12, padding: 16 } },
    tooltip: { mode: 'index', intersect: false },
  },
  scales: {
    x: { ticks: { font: { size: 10 }, maxRotation: 35, minRotation: 0 }, grid: { display: false } },
    y: { beginAtZero: true, ticks: { font: { size: 10 } }, grid: { color: '#f3f4f6' } },
  },
}

const eoqRopOptions = {
  ...barOptions,
  indexAxis: 'y',
  scales: {
    y: { ticks: { font: { size: 10 } }, grid: { display: false } },
    x: { beginAtZero: true, ticks: { font: { size: 10 } }, grid: { color: '#f3f4f6' } },
  },
}

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: true,
  plugins: {
    legend: { display: false },
    tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed}` } },
  },
  cutout: '62%',
}

// ── Data fetching ───────────────────────────────────────────────────

async function runAnalytics() {
  running.value = true
  try {
    const { data } = await api.post('/analytics/run')
    results.value    = data.results
    computedAt.value = new Date(data.computed_at).toLocaleString('en-PH')
    await loadSummary()
  } finally { running.value = false }
}

async function loadSummary() {
  try {
    const { data } = await api.get('/analytics/summary')
    summary.value = data
    if (!results.value.length && data.items?.length) {
      results.value = data.items.map(a => ({ product: a.product, analytics: a }))
      computedAt.value = 'Last computed'
    }
  } catch {}
}

onMounted(loadSummary)
</script>
