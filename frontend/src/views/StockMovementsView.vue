<template>
  <div class="sm-page fade-up">

    <!-- ── Header ─────────────────────────────────────────────── -->
    <div class="page-header">
      <div class="flex items-center gap-3">
        <div class="header-icon">
          <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/>
          </svg>
        </div>
        <div>
          <h2 class="page-title">Stock Movements</h2>
          <p class="page-sub">History of all stock-ins, sales, and adjustments</p>
        </div>
      </div>
    </div>

    <!-- ── Stats Row ──────────────────────────────────────────── -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-icon orange">
          <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
          </svg>
        </div>
        <div>
          <p class="stat-num">{{ pagination.total ?? 0 }}</p>
          <p class="stat-lbl">Total Movements</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon green">
          <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 4v16m8-8H4"/>
          </svg>
        </div>
        <div>
          <p class="stat-num green">{{ summary.stock_in ?? 0 }}</p>
          <p class="stat-lbl">Stock Ins</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon blue">
          <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
          </svg>
        </div>
        <div>
          <p class="stat-num blue">{{ summary.sale ?? 0 }}</p>
          <p class="stat-lbl">Sales</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon amber">
          <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
          </svg>
        </div>
        <div>
          <p class="stat-num amber">{{ summary.adjustment ?? 0 }}</p>
          <p class="stat-lbl">Adjustments</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon red">
          <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
          </svg>
        </div>
        <div>
          <p class="stat-num red">{{ (summary.damage ?? 0) + (summary.loss ?? 0) }}</p>
          <p class="stat-lbl">Damage &amp; Loss</p>
        </div>
      </div>
    </div>

    <!-- ── Filter Bar ─────────────────────────────────────────── -->
    <div class="filter-bar">
      <div class="filter-icon">
        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
        </svg>
      </div>
      <select v-model="filterType" @change="fetchPage(1)" class="filter-select">
        <option value="">All Types</option>
        <option value="stock_in">Stock In</option>
        <option value="sale">Sale</option>
        <option value="adjustment">Adjustment</option>
        <option value="damage">Damage</option>
        <option value="loss">Loss</option>
        <option value="return">Return</option>
      </select>
      <div class="filter-search">
        <svg class="search-icon" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
        </svg>
        <input v-model="filterProduct" @input="debouncedFetch" placeholder="Search product name or SKU..." class="search-input" />
      </div>
      <button v-if="filterType || filterProduct" @click="clearFilters" class="clear-btn">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
        Clear
      </button>
    </div>

    <!-- ── Table ──────────────────────────────────────────────── -->
    <div class="table-card">
      <div v-if="loading" class="flex items-center justify-center py-20">
        <div class="animate-spin w-9 h-9 border-4 border-orange-400 border-t-transparent rounded-full"></div>
      </div>

      <template v-else>
        <!-- Empty state -->
        <div v-if="!movements.length" class="empty-state">
          <div class="empty-icon">
            <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/>
            </svg>
          </div>
          <p class="empty-title">No stock movements found</p>
          <p class="empty-sub">Try adjusting your filters</p>
        </div>

        <!-- Table -->
        <div v-else class="table-wrap">
          <table class="mv-table">
            <thead>
              <tr>
                <th>Date & Time</th>
                <th>Product</th>
                <th>Type</th>
                <th>Qty Change</th>
                <th>Before</th>
                <th>After</th>
                <th>Reference</th>
                <th>Recorded By</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="m in movements" :key="m.id">
                <td>
                  <p class="mv-date">{{ fmtDay(m.created_at) }}</p>
                  <p class="mv-time">{{ fmtTime(m.created_at) }}</p>
                </td>
                <td>
                  <p class="mv-product">{{ m.product?.name ?? '—' }}</p>
                  <p class="mv-sku">{{ m.product?.sku ?? '' }}</p>
                </td>
                <td>
                  <span :class="typePill(m.type)" class="type-pill">
                    <span class="type-dot"></span>
                    {{ typeLabel(m.type) }}
                  </span>
                </td>
                <td>
                  <span :class="qtyClass(m.type)" class="qty-val">
                    {{ qtySign(m.type) }}{{ m.quantity }}
                  </span>
                </td>
                <td class="mv-num">{{ m.quantity_before ?? '—' }}</td>
                <td>
                  <span class="mv-after">{{ m.quantity_after ?? '—' }}</span>
                </td>
                <td class="mv-ref">{{ m.reference_number ?? '—' }}</td>
                <td>
                  <div class="mv-user">
                    <div class="user-avatar">{{ userInitials(m.user?.name) }}</div>
                    <span class="user-name">{{ m.user?.name ?? '—' }}</span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.last_page > 1" class="pag-bar">
          <span class="pag-info">Page {{ pagination.current_page }} of {{ pagination.last_page }}  ·  {{ pagination.total }} records</span>
          <div class="pag-btns">
            <button @click="fetchPage(pagination.current_page - 1)" :disabled="pagination.current_page === 1" class="pag-btn">
              <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
              Prev
            </button>
            <button v-for="p in visiblePages" :key="p"
              class="pag-btn" :class="{ active: p === pagination.current_page, ellipsis: p === '...' }"
              :disabled="p === '...'"
              @click="p !== '...' && fetchPage(p)">{{ p }}</button>
            <button @click="fetchPage(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page" class="pag-btn">
              Next
              <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
          </div>
        </div>
      </template>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'

const movements   = ref([])
const pagination  = ref({})
const summary     = ref({})
const loading     = ref(true)
const filterType    = ref('')
const filterProduct = ref('')

let debounceTimer
function debouncedFetch() { clearTimeout(debounceTimer); debounceTimer = setTimeout(() => fetchPage(1), 400) }

function clearFilters() { filterType.value = ''; filterProduct.value = ''; fetchPage(1) }

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

function fmtDay(d)  { return d ? new Date(d).toLocaleDateString('en-PH',  { month: 'short', day: 'numeric', year: 'numeric' }) : '—' }
function fmtTime(d) { return d ? new Date(d).toLocaleTimeString('en-PH',  { hour: '2-digit', minute: '2-digit' }) : '' }

function typeLabel(t) {
  return {
    stock_in:   'Stock In',
    sale:       'Sale',
    adjustment: 'Adjustment',
    damage:     'Damage',
    loss:       'Loss',
    return:     'Return',
  }[t] ?? t ?? '—'
}

function typePill(t) {
  return {
    stock_in:   'pill-green',
    return:     'pill-green',
    sale:       'pill-blue',
    adjustment: 'pill-amber',
    damage:     'pill-red',
    loss:       'pill-red',
  }[t] ?? 'pill-gray'
}

function qtySign(t)  { return ['stock_in', 'return'].includes(t) ? '+' : '−' }
function qtyClass(t) { return ['stock_in', 'return'].includes(t) ? 'qty-pos' : 'qty-neg' }

function userInitials(name) {
  if (!name) return '?'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

const visiblePages = computed(() => {
  const pages = [], total = pagination.value.last_page ?? 1, cur = pagination.value.current_page ?? 1
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1)
  pages.push(1)
  if (cur > 3) pages.push('...')
  for (let i = Math.max(2, cur - 1); i <= Math.min(total - 1, cur + 1); i++) pages.push(i)
  if (cur < total - 2) pages.push('...')
  pages.push(total)
  return pages
})

onMounted(() => { fetchPage(); fetchSummary() })
</script>

<style scoped>
.sm-page { padding: 28px 32px 40px; }

/* Header */
.page-header  { display: flex; align-items: center; justify-content: space-between; margin-bottom: 28px; }
.header-icon  { width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, #f97316, #ea580c); display: flex; align-items: center; justify-content: center; color: #fff; box-shadow: 0 4px 12px rgba(249,115,22,.35); flex-shrink: 0; }
.page-title   { font-size: 1.25rem; font-weight: 800; color: #111827; margin: 0; }
.page-sub     { font-size: 12px; color: #6b7280; margin: 2px 0 0; }

/* Stats */
.stats-row  { display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px; margin-bottom: 20px; }
.stat-icon.orange { background: #fff7ed; color: #ea580c; }
.stat-icon.green  { background: #f0fdf4; color: #16a34a; }
.stat-icon.blue   { background: #eff6ff; color: #2563eb; }
.stat-icon.amber  { background: #fffbeb; color: #d97706; }
.stat-icon.red    { background: #fef2f2; color: #dc2626; }
.stat-num   { font-size: 1.5rem; font-weight: 800; color: #111827; line-height: 1; }
.stat-num.green  { color: #16a34a; }
.stat-num.blue   { color: #2563eb; }
.stat-num.amber  { color: #d97706; }
.stat-num.red    { color: #dc2626; }
.stat-lbl   { font-size: 11px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: .5px; margin-top: 4px; }

/* Filter bar */
.filter-bar    { display: flex; align-items: center; gap: 12px; background: #fff; border: 1.5px solid #f3f4f6; border-radius: 14px; box-shadow: 0 2px 8px rgba(0,0,0,.04); padding: 14px 18px; margin-bottom: 20px; flex-wrap: wrap; }
.filter-icon   { color: #9ca3af; flex-shrink: 0; }
.filter-select { border: 1.5px solid #e5e7eb; border-radius: 9px; padding: 8px 12px; font-size: 13px; color: #374151; font-family: inherit; background: #f9fafb; outline: none; cursor: pointer; transition: border-color .2s; }
.filter-select:focus { border-color: #f97316; }
.filter-search { position: relative; flex: 1; min-width: 200px; }
.search-icon   { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: #9ca3af; pointer-events: none; }
.search-input  { width: 100%; padding: 8px 12px 8px 32px; border: 1.5px solid #e5e7eb; border-radius: 9px; font-size: 13px; color: #374151; font-family: inherit; background: #f9fafb; outline: none; transition: border-color .2s; }
.search-input:focus { border-color: #f97316; box-shadow: 0 0 0 3px rgba(249,115,22,.1); }
.clear-btn     { display: flex; align-items: center; gap: 5px; padding: 8px 14px; border: 1.5px solid #fde68a; border-radius: 9px; background: #fffbeb; color: #92400e; font-size: 12px; font-weight: 600; font-family: inherit; cursor: pointer; transition: all .2s; white-space: nowrap; }
.clear-btn:hover { background: #fef3c7; }

.table-wrap { overflow-x: auto; }

/* Empty state */
.empty-state  { padding: 64px 20px; text-align: center; }
.empty-icon   { width: 56px; height: 56px; border-radius: 14px; background: #fff7ed; color: #f97316; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; }
.empty-title  { font-size: 15px; font-weight: 700; color: #374151; margin-bottom: 6px; }
.empty-sub    { font-size: 13px; color: #9ca3af; }

/* Table */
.mv-table { width: 100%; border-collapse: collapse; }
.mv-table thead tr { background: #f9fafb; border-bottom: 2px solid #f3f4f6; }
.mv-table thead th { padding: 13px 16px; text-align: left; font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: .6px; white-space: nowrap; }
.mv-table tbody tr { border-bottom: 1px solid #f9fafb; transition: background .15s; }
.mv-table tbody tr:last-child { border-bottom: none; }
.mv-table tbody tr:hover { background: #fffaf5; }
.mv-table td { padding: 14px 16px; vertical-align: middle; }

.mv-date    { font-size: 13px; font-weight: 600; color: #374151; }
.mv-time    { font-size: 11px; color: #9ca3af; margin-top: 2px; }
.mv-product { font-size: 13px; font-weight: 700; color: #111827; }
.mv-sku     { font-size: 11px; color: #9ca3af; font-family: 'Courier New', monospace; margin-top: 2px; }
.mv-num     { font-size: 13px; color: #6b7280; }
.mv-after   { font-size: 13px; font-weight: 700; color: #111827; }
.mv-ref     { font-size: 11px; color: #9ca3af; font-family: 'Courier New', monospace; }

/* Type pill */
.type-pill { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: capitalize; white-space: nowrap; }
.type-dot  { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
.pill-green { background: #f0fdf4; color: #15803d; }
.pill-blue  { background: #eff6ff; color: #1d4ed8; }
.pill-amber { background: #fffbeb; color: #b45309; }
.pill-red   { background: #fee2e2; color: #b91c1c; }
.pill-gray  { background: #f9fafb; color: #4b5563; }

/* Qty change */
.qty-val { font-size: 14px; font-weight: 800; }
.qty-pos { color: #16a34a; }
.qty-neg { color: #dc2626; }

/* User */
.mv-user    { display: flex; align-items: center; gap: 8px; }
.user-avatar { width: 28px; height: 28px; border-radius: 50%; background: linear-gradient(135deg, #f97316, #ea580c); color: #fff; font-size: 10px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.user-name  { font-size: 13px; color: #374151; font-weight: 500; }

/* Pagination */
.pag-bar   { display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; border-top: 1.5px solid #f3f4f6; flex-wrap: wrap; gap: 12px; }
.pag-info  { font-size: 12px; color: #9ca3af; }
.pag-btns  { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.pag-btn   { display: inline-flex; align-items: center; gap: 5px; padding: 7px 13px; border: 1.5px solid #e5e7eb; border-radius: 8px; background: #fff; font-family: inherit; font-size: 12px; font-weight: 600; color: #4b5563; cursor: pointer; transition: all .2s; }
.pag-btn:hover:not(:disabled):not(.ellipsis) { background: #fff7ed; border-color: #f97316; color: #ea580c; }
.pag-btn.active   { background: #1f2937; border-color: #1f2937; color: #fff; }
.pag-btn:disabled { opacity: .4; cursor: not-allowed; }
.pag-btn.ellipsis { border: none; cursor: default; }

@media (max-width: 1200px) { .stats-row { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 1024px) { .stats-row { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 640px)  { .stats-row { grid-template-columns: 1fr; } .sm-page { padding: 16px; } }
</style>
