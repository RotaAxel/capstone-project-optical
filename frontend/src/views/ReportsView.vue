<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h2 class="text-base font-semibold text-gray-800">Reports</h2>
    </div>

    <!-- Error Banner -->
    <div v-if="error" class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700 flex items-center gap-2">
      <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
      {{ error }}
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center gap-2 text-sm text-gray-500">
      <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
      Generating report...
    </div>

    <!-- Tab Navigation -->
    <div class="flex gap-1 bg-gray-100 rounded-xl p-1 w-fit">
      <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id"
        class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
        :class="activeTab === tab.id ? 'bg-white text-blue-700 shadow-sm' : 'text-gray-500 hover:text-gray-700'">
        {{ tab.label }}
      </button>
    </div>

    <!-- Daily Sales Report -->
    <div v-if="activeTab === 'daily'" class="space-y-4">
      <div class="card p-4 flex items-center gap-3">
        <label class="label mb-0 text-sm">Date:</label>
        <input v-model="dailyDate" type="date" class="input w-40" @change="loadDaily" />
        <button @click="loadDaily" class="btn-primary btn-sm">Generate</button>
      </div>
      <div v-if="dailyData" class="space-y-4">
        <div class="grid grid-cols-3 gap-4">
          <div class="card text-center"><p class="text-xs text-gray-500">Transactions</p><p class="text-2xl font-bold text-gray-900 mt-1">{{ dailyData.total_transactions }}</p></div>
          <div class="card text-center"><p class="text-xs text-gray-500">Revenue</p><p class="text-2xl font-bold text-green-700 mt-1">₱{{ fmt(dailyData.total_revenue) }}</p></div>
          <div class="card text-center"><p class="text-xs text-gray-500">Discounts Given</p><p class="text-2xl font-bold text-red-600 mt-1">₱{{ fmt(dailyData.total_discount) }}</p></div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
          <table class="w-full">
            <thead class="bg-gray-50 border-b"><tr><th class="table-th">Receipt #</th><th class="table-th">Patient</th><th class="table-th">Items</th><th class="table-th">Total</th><th class="table-th">Payment</th></tr></thead>
            <tbody class="divide-y divide-gray-50">
              <tr v-for="s in dailyData.sales" :key="s.id" class="hover:bg-gray-50">
                <td class="table-td font-mono text-blue-700 text-xs">{{ s.receipt_number }}</td>
                <td class="table-td text-sm">{{ s.patient ? s.patient.first_name + ' ' + s.patient.last_name : 'Walk-in' }}</td>
                <td class="table-td text-sm">{{ s.items?.length }}</td>
                <td class="table-td font-semibold text-green-700">₱{{ fmt(s.total_amount) }}</td>
                <td class="table-td"><span class="badge-blue capitalize text-xs">{{ s.payment_method }}</span></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Monthly Sales Report -->
    <div v-if="activeTab === 'monthly'" class="space-y-4">
      <div class="card p-4 flex items-center gap-3">
        <div class="flex items-center gap-2">
          <label class="label mb-0 text-sm">Month:</label>
          <select v-model="monthlyMonth" class="input w-32">
            <option v-for="(m, i) in months" :key="i" :value="i + 1">{{ m }}</option>
          </select>
        </div>
        <div class="flex items-center gap-2">
          <label class="label mb-0 text-sm">Year:</label>
          <input v-model="monthlyYear" type="number" class="input w-24" min="2020" />
        </div>
        <button @click="loadMonthly" class="btn-primary btn-sm">Generate</button>
      </div>
      <div v-if="monthlyData" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div class="card text-center"><p class="text-xs text-gray-500">Total Transactions</p><p class="text-2xl font-bold mt-1">{{ monthlyData.total_transactions }}</p></div>
          <div class="card text-center"><p class="text-xs text-gray-500">Total Revenue</p><p class="text-2xl font-bold text-green-700 mt-1">₱{{ fmt(monthlyData.total_revenue) }}</p></div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
          <table class="w-full"><thead class="bg-gray-50 border-b"><tr><th class="table-th">Date</th><th class="table-th">Transactions</th><th class="table-th">Revenue</th></tr></thead>
            <tbody class="divide-y divide-gray-50">
              <tr v-for="d in monthlyData.daily_breakdown" :key="d.date" class="hover:bg-gray-50">
                <td class="table-td text-sm">{{ fmtDate(d.date) }}</td>
                <td class="table-td text-sm">{{ d.transactions }}</td>
                <td class="table-td font-semibold text-green-700">₱{{ fmt(d.revenue) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Inventory Report -->
    <div v-if="activeTab === 'inventory'" class="space-y-4">
      <button @click="loadInventory" class="btn-primary btn-sm">Refresh Report</button>
      <div v-if="inventoryData" class="space-y-4">
        <div class="grid grid-cols-3 gap-4">
          <div class="card text-center"><p class="text-xs text-gray-500">Total Products</p><p class="text-2xl font-bold mt-1">{{ inventoryData.total_products }}</p></div>
          <div class="card text-center"><p class="text-xs text-gray-500">Low Stock Items</p><p class="text-2xl font-bold text-red-600 mt-1">{{ inventoryData.low_stock_count }}</p></div>
          <div class="card text-center"><p class="text-xs text-gray-500">Total Stock Value</p><p class="text-2xl font-bold text-green-700 mt-1">₱{{ fmt(inventoryData.total_stock_value) }}</p></div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
          <table class="w-full"><thead class="bg-gray-50 border-b"><tr><th class="table-th">SKU</th><th class="table-th">Name</th><th class="table-th">Category</th><th class="table-th">Stock</th><th class="table-th">ROP</th><th class="table-th">Status</th><th class="table-th">Stock Value</th></tr></thead>
            <tbody class="divide-y divide-gray-50">
              <tr v-for="p in inventoryData.products" :key="p.id" class="hover:bg-gray-50">
                <td class="table-td font-mono text-xs text-blue-700">{{ p.sku }}</td>
                <td class="table-td text-sm font-medium">{{ p.name }}</td>
                <td class="table-td text-sm">{{ p.category }}</td>
                <td class="table-td text-sm">{{ p.stock_quantity }}</td>
                <td class="table-td text-sm text-gray-400">{{ p.reorder_point }}</td>
                <td class="table-td"><span :class="p.is_low_stock ? 'badge-red' : 'badge-green'">{{ p.is_low_stock ? 'Low Stock' : 'OK' }}</span></td>
                <td class="table-td text-sm font-medium">₱{{ fmt(p.stock_value) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Top Products -->
    <div v-if="activeTab === 'top_products'" class="space-y-4">
      <div class="card p-4 flex gap-3 items-end">
        <div><label class="label">From</label><input v-model="topFrom" type="date" class="input w-40" /></div>
        <div><label class="label">To</label><input v-model="topTo" type="date" class="input w-40" /></div>
        <button @click="loadTop" class="btn-primary btn-sm">Generate</button>
      </div>
      <div v-if="topData" class="bg-white rounded-xl border border-gray-100 overflow-hidden">
        <table class="w-full"><thead class="bg-gray-50 border-b"><tr><th class="table-th">#</th><th class="table-th">Product</th><th class="table-th">Category</th><th class="table-th">Qty Sold</th><th class="table-th">Revenue</th></tr></thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-for="(p, i) in topData.products" :key="p.product_id" class="hover:bg-gray-50">
              <td class="table-td font-bold text-blue-700">{{ i + 1 }}</td>
              <td class="table-td text-sm font-medium">{{ p.product?.name }}</td>
              <td class="table-td text-sm text-gray-400">{{ p.product?.category?.name }}</td>
              <td class="table-td text-sm">{{ p.total_qty }}</td>
              <td class="table-td text-sm font-semibold text-green-700">₱{{ fmt(p.total_revenue) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

const allTabs = [
  { id: 'daily',        label: 'Daily Sales',    roles: ['admin'] },
  { id: 'monthly',      label: 'Monthly Sales',  roles: ['admin'] },
  { id: 'inventory',    label: 'Inventory',      roles: ['admin', 'inventory_staff'] },
  { id: 'top_products', label: 'Top Products',   roles: ['admin', 'inventory_staff'] },
]
const tabs = computed(() => allTabs.filter(t => t.roles.includes(auth.user?.role)))
const activeTab = ref('')

// Auto-load data whenever the active tab changes
watch(activeTab, (tab) => {
  if (tab === 'daily'        && !dailyData.value)     loadDaily()
  if (tab === 'monthly'      && !monthlyData.value)   loadMonthly()
  if (tab === 'inventory'    && !inventoryData.value)  loadInventory()
  if (tab === 'top_products' && !topData.value)        loadTop()
})

onMounted(() => {
  activeTab.value = tabs.value[0]?.id ?? 'inventory'
})
const months = ['January','February','March','April','May','June','July','August','September','October','November','December']

const dailyDate    = ref(new Date().toISOString().split('T')[0])
const dailyData    = ref(null)
const monthlyMonth = ref(new Date().getMonth() + 1)
const monthlyYear  = ref(new Date().getFullYear())
const monthlyData  = ref(null)
const inventoryData = ref(null)
const topFrom      = ref(new Date(new Date().setDate(1)).toISOString().split('T')[0])
const topTo        = ref(new Date().toISOString().split('T')[0])
const topData      = ref(null)
const error        = ref('')
const loading      = ref(false)

async function loadDaily() {
  error.value = ''; loading.value = true
  try { dailyData.value = (await api.get('/reports/sales/daily', { params: { date: dailyDate.value } })).data }
  catch (e) { error.value = e.response?.data?.message || 'Could not load report. Make sure the backend is running.' }
  finally { loading.value = false }
}
async function loadMonthly() {
  error.value = ''; loading.value = true
  try { monthlyData.value = (await api.get('/reports/sales/monthly', { params: { month: monthlyMonth.value, year: monthlyYear.value } })).data }
  catch (e) { error.value = e.response?.data?.message || 'Could not load report. Make sure the backend is running.' }
  finally { loading.value = false }
}
async function loadInventory() {
  error.value = ''; loading.value = true
  try { inventoryData.value = (await api.get('/reports/inventory')).data }
  catch (e) { error.value = e.response?.data?.message || 'Could not load report. Make sure the backend is running.' }
  finally { loading.value = false }
}
async function loadTop() {
  error.value = ''; loading.value = true
  try { topData.value = (await api.get('/reports/top-products', { params: { date_from: topFrom.value, date_to: topTo.value } })).data }
  catch (e) { error.value = e.response?.data?.message || 'Could not load report. Make sure the backend is running.' }
  finally { loading.value = false }
}

function fmt(v) { return Number(v || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }
function fmtDate(d) { return d ? new Date(d).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '—' }
</script>
