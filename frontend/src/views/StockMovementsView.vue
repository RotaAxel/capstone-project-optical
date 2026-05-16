<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-base font-semibold text-gray-800">Stock Movements</h2>
        <p class="text-xs text-gray-500">History of all stock-ins and adjustments</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="card p-4 flex gap-3 flex-wrap items-center">
      <select v-model="filterType" @change="fetchPage(1)" class="input w-44">
        <option value="">All Types</option>
        <option value="stock_in">Stock In</option>
        <option value="sale">Sale</option>
        <option value="adjustment">Adjustment</option>
        <option value="return">Return</option>
      </select>
      <input v-model="filterProduct" @input="debouncedFetch" placeholder="Search product..." class="input w-52" />
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="card text-center">
        <p class="text-xs text-gray-500">Total Movements</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ pagination.total ?? 0 }}</p>
      </div>
      <div class="card text-center">
        <p class="text-xs text-gray-500">Stock Ins</p>
        <p class="text-2xl font-bold text-green-600 mt-1">{{ summary.stock_in ?? 0 }}</p>
      </div>
      <div class="card text-center">
        <p class="text-xs text-gray-500">Sales (Deductions)</p>
        <p class="text-2xl font-bold text-blue-600 mt-1">{{ summary.sale ?? 0 }}</p>
      </div>
      <div class="card text-center">
        <p class="text-xs text-gray-500">Adjustments</p>
        <p class="text-2xl font-bold text-yellow-600 mt-1">{{ summary.adjustment ?? 0 }}</p>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
      <div v-if="loading" class="flex items-center justify-center py-16">
        <div class="animate-spin w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full"></div>
      </div>
      <table v-else class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr>
            <th class="table-th">Date</th>
            <th class="table-th">Product</th>
            <th class="table-th">Type</th>
            <th class="table-th">Qty Change</th>
            <th class="table-th">Before</th>
            <th class="table-th">After</th>
            <th class="table-th">Reference</th>
            <th class="table-th">By</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <tr v-for="m in movements" :key="m.id" class="hover:bg-gray-50 transition-colors">
            <td class="table-td text-xs text-gray-400 whitespace-nowrap">{{ fmtDate(m.created_at) }}</td>
            <td class="table-td">
              <p class="text-sm font-medium text-gray-900">{{ m.product?.name }}</p>
              <p class="text-xs text-gray-400 font-mono">{{ m.product?.sku }}</p>
            </td>
            <td class="table-td">
              <span :class="{
                'badge-green':  m.type === 'stock_in' || m.type === 'return',
                'badge-blue':   m.type === 'sale',
                'badge-yellow': m.type === 'adjustment',
                'badge-gray':   !m.type,
              }" class="capitalize text-xs">{{ m.type?.replace('_', ' ') }}</span>
            </td>
            <td class="table-td text-sm font-semibold"
              :class="['stock_in','return','adjustment'].includes(m.type) ? 'text-green-600' : 'text-red-600'">
              {{ ['stock_in','return','adjustment'].includes(m.type) ? '+' : '-' }}{{ m.quantity }}
            </td>
            <td class="table-td text-sm text-gray-500">{{ m.quantity_before ?? '—' }}</td>
            <td class="table-td text-sm text-gray-800 font-medium">{{ m.quantity_after ?? '—' }}</td>
            <td class="table-td text-xs text-gray-400 font-mono">{{ m.reference_number ?? '—' }}</td>
            <td class="table-td text-sm text-gray-600">{{ m.user?.name ?? '—' }}</td>
          </tr>
          <tr v-if="!movements.length">
            <td colspan="8" class="text-center py-12 text-gray-400 text-sm">No stock movements found.</td>
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
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'

const movements  = ref([])
const pagination = ref({})
const summary    = ref({})
const loading    = ref(true)
const filterType = ref('')
const filterProduct = ref('')

let debounceTimer
function debouncedFetch() { clearTimeout(debounceTimer); debounceTimer = setTimeout(() => fetchPage(1), 400) }

async function fetchPage(page = 1) {
  loading.value = true
  try {
    const { data } = await api.get('/stock-movements', {
      params: { page, type: filterType.value || undefined, search: filterProduct.value || undefined },
    })
    movements.value  = data.data
    pagination.value = { current_page: data.current_page, last_page: data.last_page, total: data.total }
  } catch { /* backend unavailable */ }
  finally { loading.value = false }
}

async function fetchSummary() {
  try {
    const { data } = await api.get('/stock-movements/summary')
    summary.value = data
  } catch { /* summary not critical */ }
}

function fmtDate(d) {
  return d ? new Date(d).toLocaleString('en-PH', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—'
}

onMounted(() => { fetchPage(); fetchSummary() })
</script>
