<template>
  <div class="sales-page fade-up">

    <!-- ── Header ─────────────────────────────────────────────── -->
    <div class="page-header">
      <div class="flex items-center gap-3">
        <div class="header-icon">
          <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 2.5 2 2.5-2 2.5 2 2.5-2 3.5 2z"/>
          </svg>
        </div>
        <div>
          <h2 class="page-title">Transactions</h2>
          <p class="page-sub">{{ pagination.total ?? 0 }} total transactions</p>
        </div>
      </div>
      <button @click="openSaleModal" class="btn-add">
        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
        </svg>
        New Sale
      </button>
    </div>

    <!-- ── Stats Row ──────────────────────────────────────────── -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-icon emerald">
          <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 2.5 2 2.5-2 2.5 2 2.5-2 3.5 2z"/>
          </svg>
        </div>
        <div>
          <p class="stat-num">{{ pagination.total ?? 0 }}</p>
          <p class="stat-lbl">Total Sales</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon green">
          <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <div>
          <p class="stat-num green">₱{{ fmt(saleStats.total_revenue ?? 0) }}</p>
          <p class="stat-lbl">Total Revenue</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon blue">
          <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
          </svg>
        </div>
        <div>
          <p class="stat-num blue">{{ saleStats.cash_count ?? 0 }}</p>
          <p class="stat-lbl">Cash Sales</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon purple">
          <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 18h.01M8 21h8a2 2 0 002-2v-2a2 2 0 00-2-2H8a2 2 0 00-2 2v2a2 2 0 002 2zM12 3v4m0 4v.01"/>
          </svg>
        </div>
        <div>
          <p class="stat-num purple">{{ saleStats.digital_count ?? 0 }}</p>
          <p class="stat-lbl">Digital Payments</p>
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
      <div class="filter-search">
        <svg class="search-icon" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
        </svg>
        <input v-model="search" @input="debouncedFetch" placeholder="Search receipt number..." class="search-input" />
      </div>
      <div class="date-group">
        <label class="date-lbl">From</label>
        <input v-model="dateFrom" @change="fetchPage(1)" type="date" class="date-input" />
      </div>
      <div class="date-group">
        <label class="date-lbl">To</label>
        <input v-model="dateTo" @change="fetchPage(1)" type="date" class="date-input" />
      </div>
      <button v-if="search || dateFrom || dateTo" @click="clearFilters" class="clear-btn">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
        Clear
      </button>
    </div>

    <!-- ── Sale Cards ─────────────────────────────────────────── -->
    <div v-if="loading" class="flex items-center justify-center py-24">
      <div class="animate-spin w-9 h-9 border-4 border-emerald-400 border-t-transparent rounded-full"></div>
    </div>

    <template v-else>
      <div v-if="!sales.length" class="empty-state">
        <div class="empty-icon">
          <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 2.5 2 2.5-2 2.5 2 2.5-2 3.5 2z"/>
          </svg>
        </div>
        <p class="empty-title">No sales found</p>
        <p class="empty-sub">Try adjusting your filters or create a new sale</p>
      </div>

      <div v-else class="sale-list">
        <div v-for="s in sales" :key="s.id" class="sale-card">
          <div :class="methodStripe(s.payment_method)" class="sale-stripe"></div>

          <!-- Receipt & patient -->
          <div class="sale-left">
            <p class="receipt-num">{{ s.receipt_number }}</p>
            <div class="patient-row">
              <div class="patient-avatar">
                {{ patientInitials(s.patient) }}
              </div>
              <div>
                <p class="patient-name">{{ s.patient ? s.patient.first_name + ' ' + s.patient.last_name : 'Walk-in Customer' }}</p>
                <p class="cashier-name">Cashier: {{ s.cashier?.name ?? '—' }}</p>
              </div>
            </div>
          </div>

          <!-- Items -->
          <div class="sale-mid">
            <div class="items-badge">
              <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
              </svg>
              {{ s.items?.length ?? 0 }} item{{ (s.items?.length ?? 0) !== 1 ? 's' : '' }}
            </div>
            <span :class="methodPill(s.payment_method)" class="method-pill">
              {{ s.payment_method }}
            </span>
          </div>

          <!-- Amount & date -->
          <div class="sale-right">
            <p class="sale-amount">₱{{ fmt(s.total_amount) }}</p>
            <p class="sale-date">{{ fmtDate(s.created_at) }}</p>
          </div>

          <!-- Action -->
          <div class="sale-action">
            <button @click="viewSale(s)" class="view-btn">
              <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
              View
            </button>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="pagination">
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

  <!-- ── New Sale Modal ──────────────────────────────────────── -->
  <Teleport to="body">
    <div v-if="showSaleModal" class="modal-backdrop" @click.self="closeSaleModal">
      <div class="modal-box modal-lg">
        <div class="modal-header">
          <div class="flex items-center gap-3">
            <div class="modal-icon">
              <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 2.5 2 2.5-2 2.5 2 2.5-2 3.5 2z"/>
              </svg>
            </div>
            <h3 class="modal-title">New Sale / Billing</h3>
          </div>
          <button @click="closeSaleModal" class="modal-close">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <div class="modal-body">
          <!-- Patient & Payment -->
          <div class="section-label">Transaction Info</div>
          <div class="form-grid-2">
            <div class="fg">
              <label class="fl">Patient (optional)</label>
              <select v-model="saleForm.patient_id" class="fi">
                <option value="">Walk-in Customer</option>
                <option v-for="p in patients" :key="p.id" :value="p.id">{{ p.first_name }} {{ p.last_name }}</option>
              </select>
            </div>
            <div class="fg">
              <label class="fl">Payment Method *</label>
              <select v-model="saleForm.payment_method" class="fi" required>
                <option value="cash">Cash</option>
                <option value="card">Card</option>
                <option value="gcash">GCash</option>
                <option value="maya">Maya</option>
                <option value="other">Other</option>
              </select>
            </div>
          </div>

          <!-- Items -->
          <div class="section-label" style="margin-top:20px;">
            Items
            <button @click="addItem" type="button" class="add-item-btn">
              <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
              </svg>
              Add Item
            </button>
          </div>

          <div class="items-list">
            <div v-if="saleForm.items.length === 0" class="no-items">
              No items added yet. Click "Add Item" to start.
            </div>
            <div v-for="(item, idx) in saleForm.items" :key="idx" class="item-row">
              <div class="item-product">
                <label class="fl">Product</label>
                <select v-model="item.product_id" @change="fillPrice(item)" class="fi">
                  <option value="">Select product...</option>
                  <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} — ₱{{ fmt(p.selling_price) }}</option>
                </select>
              </div>
              <div class="item-qty">
                <label class="fl">Qty</label>
                <input v-model.number="item.quantity" type="number" min="1" class="fi"
                  :class="{ 'fi-warn': item.max_stock != null && item.quantity > item.max_stock }"
                  @input="recalc" />
                <span v-if="item.max_stock != null && item.quantity > item.max_stock" class="stock-warn">
                  Only {{ item.max_stock }} in stock
                </span>
              </div>
              <div class="item-price">
                <label class="fl">Unit Price</label>
                <input v-model="item.unit_price" type="number" step="0.01" class="fi" @input="recalc" />
              </div>
              <div class="item-disc">
                <label class="fl">Discount</label>
                <input v-model="item.discount" type="number" step="0.01" min="0" class="fi" @input="recalc" />
              </div>
              <div class="item-sub">
                <label class="fl">Subtotal</label>
                <p class="item-subtotal-val">₱{{ fmt(itemSubtotal(item)) }}</p>
              </div>
              <div class="item-remove">
                <label class="fl">&nbsp;</label>
                <button @click="removeItem(idx)" type="button" class="remove-btn">
                  <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                </button>
              </div>
            </div>
          </div>

          <!-- Totals -->
          <div class="totals-box">
            <div class="totals-row">
              <span class="totals-lbl">Subtotal</span>
              <span class="totals-val">₱{{ fmt(totals.subtotal) }}</span>
            </div>
            <div class="totals-row">
              <span class="totals-lbl">Overall Discount</span>
              <input v-model="saleForm.discount_amount" type="number" step="0.01" min="0" class="fi totals-input" @input="recalc" placeholder="0.00" />
            </div>
            <div class="totals-divider"></div>
            <div class="totals-row totals-total">
              <span>Total</span>
              <span class="total-amount">₱{{ fmt(totals.total) }}</span>
            </div>
            <div class="totals-row">
              <span class="totals-lbl">Amount Paid</span>
              <input v-model="saleForm.amount_paid" type="number" step="0.01" min="0" class="fi totals-input" @input="recalc" placeholder="0.00" />
            </div>
            <div class="totals-row change-row">
              <span>Change</span>
              <span class="change-amount">₱{{ fmt(totals.change) }}</span>
            </div>
          </div>

          <div v-if="saleError" class="error-box">{{ saleError }}</div>
        </div>

        <div class="modal-footer">
          <button @click="closeSaleModal" class="btn-cancel">Cancel</button>
          <button @click="submitSale" class="btn-submit" :disabled="saving">
            <svg v-if="!saving" width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            {{ saving ? 'Processing...' : 'Complete Sale' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>

  <!-- ── View Sale Modal ────────────────────────────────────── -->
  <Teleport to="body">
    <div v-if="viewingSale" class="modal-backdrop" @click.self="viewingSale = null">
      <div class="modal-box modal-sm">
        <div class="modal-header">
          <div>
            <p class="receipt-label">Receipt</p>
            <h3 class="receipt-title">{{ viewingSale.receipt_number }}</h3>
          </div>
          <button @click="viewingSale = null" class="modal-close">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <div class="modal-body">
          <!-- Patient / cashier / date -->
          <div class="receipt-meta">
            <div class="meta-row">
              <span class="meta-lbl">Patient</span>
              <span class="meta-val">{{ viewingSale.patient ? viewingSale.patient.first_name + ' ' + viewingSale.patient.last_name : 'Walk-in Customer' }}</span>
            </div>
            <div class="meta-row">
              <span class="meta-lbl">Cashier</span>
              <span class="meta-val">{{ viewingSale.cashier?.name ?? '—' }}</span>
            </div>
            <div class="meta-row">
              <span class="meta-lbl">Date</span>
              <span class="meta-val">{{ fmtDate(viewingSale.created_at) }}</span>
            </div>
            <div class="meta-row">
              <span class="meta-lbl">Payment</span>
              <span :class="methodPill(viewingSale.payment_method)" class="method-pill">{{ viewingSale.payment_method }}</span>
            </div>
          </div>

          <!-- Items -->
          <div class="receipt-items-header">Items Purchased</div>
          <div class="receipt-items">
            <div v-for="item in viewingSale.items" :key="item.id" class="receipt-item">
              <div>
                <p class="item-name">{{ item.product?.name }}</p>
                <p class="item-detail">{{ item.quantity }} × ₱{{ fmt(item.unit_price) }}</p>
              </div>
              <p class="item-line-total">₱{{ fmt(item.subtotal) }}</p>
            </div>
          </div>

          <!-- Totals -->
          <div class="receipt-totals">
            <div class="rt-row">
              <span>Subtotal</span>
              <span>₱{{ fmt(viewingSale.subtotal) }}</span>
            </div>
            <div v-if="Number(viewingSale.discount_amount) > 0" class="rt-row discount">
              <span>Discount</span>
              <span>−₱{{ fmt(viewingSale.discount_amount) }}</span>
            </div>
            <div class="rt-divider"></div>
            <div class="rt-row rt-total">
              <span>Total</span>
              <span>₱{{ fmt(viewingSale.total_amount) }}</span>
            </div>
            <div v-if="Number(viewingSale.amount_paid) > 0" class="rt-row rt-paid">
              <span>Amount Paid</span>
              <span>₱{{ fmt(viewingSale.amount_paid) }}</span>
            </div>
            <div v-if="Number(viewingSale.change_amount) > 0" class="rt-row rt-change">
              <span>Change</span>
              <span>₱{{ fmt(viewingSale.change_amount) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'

const sales      = ref([])
const patients   = ref([])
const products   = ref([])
const pagination = ref({})
const loading    = ref(true)
const search     = ref('')
const dateFrom   = ref('')
const dateTo     = ref('')
const showSaleModal = ref(false)
const viewingSale   = ref(null)
const saving     = ref(false)
const saleError  = ref('')

const saleForm = ref({ patient_id: '', payment_method: 'cash', discount_amount: 0, amount_paid: 0, items: [] })
const totals   = ref({ subtotal: 0, total: 0, change: 0 })

const saleStats = ref({})

async function fetchStats() {
  try { const { data } = await api.get('/sales/stats'); saleStats.value = data } catch { /* */ }
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

function itemSubtotal(item) { return (Number(item.unit_price) * Number(item.quantity)) - (Number(item.discount) || 0) }

function recalc() {
  const sub   = saleForm.value.items.reduce((s, i) => s + itemSubtotal(i), 0)
  const total = sub - (Number(saleForm.value.discount_amount) || 0)
  totals.value = { subtotal: sub, total, change: Math.max(0, (Number(saleForm.value.amount_paid) || 0) - total) }
}

function addItem()       { saleForm.value.items.push({ product_id: '', quantity: 1, unit_price: 0, discount: 0 }); recalc() }
function removeItem(idx) { saleForm.value.items.splice(idx, 1); recalc() }

function fillPrice(item) {
  const prod = products.value.find(p => p.id === item.product_id)
  if (prod) {
    item.unit_price  = prod.selling_price
    item.max_stock   = prod.stock_quantity
    recalc()
  }
}

function openSaleModal()  { showSaleModal.value = true }
function closeSaleModal() {
  showSaleModal.value = false
  saleForm.value = { patient_id: '', payment_method: 'cash', discount_amount: 0, amount_paid: 0, items: [] }
  totals.value   = { subtotal: 0, total: 0, change: 0 }
  saleError.value = ''
}

function viewSale(sale) { viewingSale.value = sale }

function clearFilters() { search.value = ''; dateFrom.value = ''; dateTo.value = ''; fetchPage(1) }

async function submitSale() {
  saleError.value = ''

  const overStock = saleForm.value.items.find(i => i.max_stock != null && i.quantity > i.max_stock)
  if (overStock) {
    const prod = products.value.find(p => p.id === overStock.product_id)
    saleError.value = `Insufficient stock for "${prod?.name ?? 'item'}". Available: ${overStock.max_stock}, requested: ${overStock.quantity}.`
    return
  }

  saving.value = true
  try {
    await api.post('/sales', saleForm.value)
    closeSaleModal(); fetchPage(1); fetchStats()
  } catch (e) {
    saleError.value = Object.values(e.response?.data?.errors ?? {}).flat().join(' ') || e.response?.data?.message || 'Failed to process sale.'
  } finally { saving.value = false }
}

let debounceTimer
function debouncedFetch() { clearTimeout(debounceTimer); debounceTimer = setTimeout(() => fetchPage(1), 400) }

async function fetchPage(page = 1) {
  loading.value = true
  try {
    const { data } = await api.get('/sales', {
      params: { page, search: search.value, date_from: dateFrom.value || undefined, date_to: dateTo.value || undefined },
    })
    sales.value = data.data
    pagination.value = { current_page: data.current_page, last_page: data.last_page, total: data.total }
  } catch { /* backend unavailable */ }
  finally { loading.value = false }
}

function fmt(v)     { return Number(v || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }
function fmtDate(d) { return d ? new Date(d).toLocaleString('en-PH', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—' }

function patientInitials(patient) {
  if (!patient) return 'W'
  return (patient.first_name?.[0] ?? '') + (patient.last_name?.[0] ?? '')
}

function methodStripe(m) {
  return { cash: 'stripe-green', card: 'stripe-blue', gcash: 'stripe-sky', maya: 'stripe-purple', other: 'stripe-gray' }[m] ?? 'stripe-gray'
}

function methodPill(m) {
  return { cash: 'pill-green', card: 'pill-blue', gcash: 'pill-sky', maya: 'pill-purple', other: 'pill-gray' }[m] ?? 'pill-gray'
}

onMounted(async () => {
  try {
    const [pts, prods] = await Promise.all([
      api.get('/patients', { params: { per_page: 200 } }),
      api.get('/products', { params: { per_page: 200 } }),
    ])
    patients.value = pts.data.data
    products.value = prods.data.data
    fetchPage(); fetchStats()
  } catch { loading.value = false }
})
</script>

<style scoped>
.sales-page { padding: 28px 32px 40px; }

/* Header */
.page-header  { display: flex; align-items: center; justify-content: space-between; margin-bottom: 28px; }
.header-icon  { width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, #10b981, #059669); display: flex; align-items: center; justify-content: center; color: #fff; box-shadow: 0 4px 12px rgba(16,185,129,.35); flex-shrink: 0; }
.page-title   { font-size: 1.25rem; font-weight: 800; color: #111827; margin: 0; }
.page-sub     { font-size: 12px; color: #6b7280; margin: 2px 0 0; }
.btn-add      { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 11px; border: none; background: linear-gradient(135deg, #10b981, #059669); color: #fff; font-size: 13px; font-weight: 700; font-family: inherit; cursor: pointer; box-shadow: 0 4px 12px rgba(16,185,129,.35); transition: all .2s; }
.btn-add:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(16,185,129,.45); }

/* Stats */
.stats-row  { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 20px; }
.stat-icon.emerald { background: #ecfdf5; color: #059669; }
.stat-icon.green   { background: #f0fdf4; color: #16a34a; }
.stat-icon.blue    { background: #eff6ff; color: #2563eb; }
.stat-icon.purple  { background: #f5f3ff; color: #7c3aed; }
.stat-num   { font-size: 1.4rem; font-weight: 800; color: #111827; line-height: 1; }
.stat-num.green  { color: #16a34a; font-size: 1.1rem; }
.stat-num.blue   { color: #2563eb; }
.stat-num.purple { color: #7c3aed; }
.stat-lbl   { font-size: 11px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: .5px; margin-top: 4px; }

/* Filter bar */
.filter-bar    { display: flex; align-items: center; gap: 12px; background: #fff; border: 1.5px solid #f3f4f6; border-radius: 14px; box-shadow: 0 2px 8px rgba(0,0,0,.04); padding: 14px 18px; margin-bottom: 20px; flex-wrap: wrap; }
.filter-icon   { color: #9ca3af; flex-shrink: 0; }
.filter-search { position: relative; flex: 1; min-width: 200px; }
.search-icon   { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: #9ca3af; pointer-events: none; }
.search-input  { width: 100%; padding: 8px 12px 8px 32px; border: 1.5px solid #e5e7eb; border-radius: 9px; font-size: 13px; color: #374151; font-family: inherit; background: #f9fafb; outline: none; transition: border-color .2s; }
.search-input:focus { border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,.1); }
.date-group  { display: flex; align-items: center; gap: 6px; }
.date-lbl    { font-size: 11px; font-weight: 700; color: #6b7280; white-space: nowrap; }
.date-input  { padding: 8px 10px; border: 1.5px solid #e5e7eb; border-radius: 9px; font-size: 13px; color: #374151; font-family: inherit; background: #f9fafb; outline: none; transition: border-color .2s; }
.date-input:focus { border-color: #10b981; }
.clear-btn   { display: flex; align-items: center; gap: 5px; padding: 8px 14px; border: 1.5px solid #bbf7d0; border-radius: 9px; background: #f0fdf4; color: #15803d; font-size: 12px; font-weight: 600; font-family: inherit; cursor: pointer; transition: all .2s; white-space: nowrap; }
.clear-btn:hover { background: #dcfce7; }

/* Empty state */
.empty-state { padding: 64px 20px; text-align: center; background: #fff; border-radius: 16px; border: 1.5px solid #f3f4f6; }
.empty-icon  { width: 56px; height: 56px; border-radius: 14px; background: #ecfdf5; color: #10b981; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; }
.empty-title { font-size: 15px; font-weight: 700; color: #374151; margin-bottom: 6px; }
.empty-sub   { font-size: 13px; color: #9ca3af; }

/* Sale list */
.sale-list { display: flex; flex-direction: column; gap: 14px; margin-bottom: 24px; }
.sale-card {
  display: flex; align-items: center; gap: 0;
  background: #fff; border-radius: 14px; border: 1.5px solid #f3f4f6;
  box-shadow: 0 2px 8px rgba(0,0,0,.04);
  overflow: hidden; transition: box-shadow .2s, transform .2s;
}
.sale-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,.09); transform: translateY(-1px); }

.sale-stripe { width: 4px; align-self: stretch; flex-shrink: 0; }
.stripe-green  { background: #10b981; }
.stripe-blue   { background: #3b82f6; }
.stripe-sky    { background: #0ea5e9; }
.stripe-purple { background: #8b5cf6; }
.stripe-gray   { background: #9ca3af; }

.sale-left    { flex: 1; padding: 18px 20px; min-width: 0; }
.receipt-num  { font-size: 11px; font-weight: 700; color: #10b981; font-family: 'Courier New', monospace; letter-spacing: .5px; margin-bottom: 10px; }
.patient-row  { display: flex; align-items: center; gap: 10px; }
.patient-avatar { width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, #10b981, #059669); color: #fff; font-size: 11px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.patient-name   { font-size: 14px; font-weight: 700; color: #111827; }
.cashier-name   { font-size: 11px; color: #9ca3af; margin-top: 2px; }

.sale-mid  { padding: 18px 16px; display: flex; flex-direction: column; gap: 8px; align-items: flex-start; flex-shrink: 0; }
.items-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 20px; background: #f3f4f6; color: #6b7280; font-size: 11px; font-weight: 600; }

.method-pill { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: capitalize; }
.pill-green  { background: #dcfce7; color: #15803d; }
.pill-blue   { background: #dbeafe; color: #1d4ed8; }
.pill-sky    { background: #e0f2fe; color: #0369a1; }
.pill-purple { background: #ede9fe; color: #6d28d9; }
.pill-gray   { background: #f3f4f6; color: #4b5563; }

.sale-right   { padding: 18px 20px; text-align: right; flex-shrink: 0; }
.sale-amount  { font-size: 1.15rem; font-weight: 800; color: #059669; }
.sale-date    { font-size: 11px; color: #9ca3af; margin-top: 4px; }

.sale-action  { padding: 18px 20px; flex-shrink: 0; }
.view-btn     { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border: 1.5px solid #d1fae5; border-radius: 9px; background: #ecfdf5; color: #059669; font-size: 12px; font-weight: 700; font-family: inherit; cursor: pointer; transition: all .2s; }
.view-btn:hover { background: #d1fae5; border-color: #10b981; }

/* Pagination */
.pagination  { display: flex; align-items: center; justify-content: space-between; margin-top: 8px; flex-wrap: wrap; gap: 12px; }
.pag-info    { font-size: 12px; color: #9ca3af; }
.pag-btns    { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.pag-btn     { display: inline-flex; align-items: center; gap: 5px; padding: 7px 13px; border: 1.5px solid #e5e7eb; border-radius: 8px; background: #fff; font-family: inherit; font-size: 12px; font-weight: 600; color: #4b5563; cursor: pointer; transition: all .2s; }
.pag-btn:hover:not(:disabled):not(.ellipsis) { background: #ecfdf5; border-color: #10b981; color: #059669; }
.pag-btn.active   { background: #1f2937; border-color: #1f2937; color: #fff; }
.pag-btn:disabled { opacity: .4; cursor: not-allowed; }
.pag-btn.ellipsis { border: none; cursor: default; }

/* Modal shared */
.modal-backdrop { position: fixed; inset: 0; z-index: 9999; background: rgba(0,0,0,.5); display: flex; align-items: center; justify-content: center; padding: 16px; overflow-y: auto; }
.modal-box      { background: #fff; border-radius: 16px; box-shadow: 0 24px 64px rgba(0,0,0,.18); width: 100%; margin: auto; display: flex; flex-direction: column; }
.modal-lg { max-width: 780px; }
.modal-sm { max-width: 480px; }
.modal-header { display: flex; align-items: center; justify-content: space-between; padding: 20px 24px; border-bottom: 1.5px solid #f3f4f6; }
.modal-icon   { width: 34px; height: 34px; border-radius: 9px; background: linear-gradient(135deg, #10b981, #059669); color: #fff; display: flex; align-items: center; justify-content: center; }
.modal-title  { font-size: 16px; font-weight: 700; color: #111827; margin: 0; }
.modal-close  { background: none; border: none; color: #9ca3af; cursor: pointer; padding: 4px; border-radius: 6px; transition: color .2s; }
.modal-close:hover { color: #374151; }
.modal-body   { padding: 24px; overflow-y: auto; max-height: 70vh; }
.modal-footer { display: flex; justify-content: flex-end; gap: 12px; padding: 16px 24px; border-top: 1.5px solid #f3f4f6; }

/* New Sale form */
.section-label { display: flex; align-items: center; justify-content: space-between; font-size: 11px; font-weight: 700; color: #059669; text-transform: uppercase; letter-spacing: .8px; border-bottom: 1.5px solid #d1fae5; padding-bottom: 8px; margin-bottom: 14px; }
.form-grid-2   { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.fg  { display: flex; flex-direction: column; gap: 5px; }
.fl  { font-size: 12px; font-weight: 700; color: #374151; }
.fi  { padding: 9px 12px; border: 1.5px solid #e5e7eb; border-radius: 9px; font-family: inherit; font-size: 13px; color: #1f2937; background: #fff; outline: none; width: 100%; transition: border-color .2s; }
.fi:focus { border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,.1); }

.add-item-btn { display: inline-flex; align-items: center; gap: 5px; padding: 5px 12px; border: 1.5px solid #d1fae5; border-radius: 7px; background: #ecfdf5; color: #059669; font-size: 11px; font-weight: 700; font-family: inherit; cursor: pointer; transition: all .2s; }
.add-item-btn:hover { background: #d1fae5; }

.items-list   { display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px; }
.no-items     { text-align: center; padding: 20px; color: #9ca3af; font-size: 13px; background: #f9fafb; border-radius: 10px; border: 1.5px dashed #e5e7eb; }
.item-row     { display: grid; grid-template-columns: 1fr 70px 110px 100px 90px 36px; gap: 10px; align-items: end; background: #f9fafb; border-radius: 10px; padding: 12px; }
.item-product { grid-column: 1; }
.item-qty, .item-price, .item-disc { }
.item-sub     { }
.item-subtotal-val { font-size: 14px; font-weight: 700; color: #059669; padding-top: 6px; }
.item-remove  { }
.remove-btn   { width: 32px; height: 32px; border-radius: 7px; border: 1.5px solid #fecaca; background: #fef2f2; color: #ef4444; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all .2s; }
.remove-btn:hover { background: #fee2e2; }

.totals-box    { background: #f0fdf4; border: 1.5px solid #bbf7d0; border-radius: 12px; padding: 16px 20px; display: flex; flex-direction: column; gap: 10px; }
.totals-row    { display: flex; align-items: center; justify-content: space-between; font-size: 13px; color: #374151; }
.totals-lbl    { color: #6b7280; }
.totals-val    { font-weight: 600; }
.totals-input  { width: 130px; text-align: right; background: #fff; }
.totals-divider { border-top: 1.5px solid #86efac; margin: 2px 0; }
.totals-total  { font-size: 15px; font-weight: 800; color: #111827; }
.total-amount  { color: #059669; font-size: 18px; }
.change-row    { font-weight: 700; color: #15803d; }
.change-amount { font-size: 16px; font-weight: 800; }

.fi-warn    { border-color: #f59e0b !important; background: #fffbeb !important; }
.stock-warn { display: block; font-size: 11px; color: #d97706; font-weight: 600; margin-top: 3px; }
.error-box  { background: #fef2f2; border: 1.5px solid #fecaca; border-radius: 9px; padding: 10px 14px; font-size: 13px; color: #dc2626; margin-top: 12px; }

.btn-cancel { padding: 10px 20px; border: 1.5px solid #e5e7eb; border-radius: 9px; background: #fff; color: #374151; font-size: 13px; font-weight: 600; font-family: inherit; cursor: pointer; transition: all .2s; }
.btn-cancel:hover { background: #f9fafb; }
.btn-submit { display: inline-flex; align-items: center; gap: 7px; padding: 10px 22px; border: none; border-radius: 9px; background: linear-gradient(135deg, #10b981, #059669); color: #fff; font-size: 13px; font-weight: 700; font-family: inherit; cursor: pointer; box-shadow: 0 4px 12px rgba(16,185,129,.3); transition: all .2s; }
.btn-submit:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(16,185,129,.4); }
.btn-submit:disabled { opacity: .6; cursor: not-allowed; }

/* View Sale (receipt) */
.receipt-label  { font-size: 11px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: .5px; margin: 0; }
.receipt-title  { font-size: 17px; font-weight: 800; color: #111827; font-family: 'Courier New', monospace; margin: 2px 0 0; }
.receipt-meta   { display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1.5px solid #f3f4f6; }
.meta-row       { display: flex; align-items: center; justify-content: space-between; font-size: 13px; }
.meta-lbl       { color: #9ca3af; font-size: 12px; }
.meta-val       { font-weight: 600; color: #374151; }
.receipt-items-header { font-size: 11px; font-weight: 700; color: #059669; text-transform: uppercase; letter-spacing: .7px; margin-bottom: 10px; }
.receipt-items  { display: flex; flex-direction: column; gap: 8px; margin-bottom: 16px; }
.receipt-item   { display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; background: #f9fafb; border-radius: 9px; }
.item-name      { font-size: 13px; font-weight: 600; color: #111827; }
.item-detail    { font-size: 11px; color: #9ca3af; margin-top: 2px; }
.item-line-total { font-size: 13px; font-weight: 700; color: #374151; }
.receipt-totals { background: #f0fdf4; border: 1.5px solid #bbf7d0; border-radius: 12px; padding: 14px 16px; display: flex; flex-direction: column; gap: 8px; }
.rt-row         { display: flex; justify-content: space-between; font-size: 13px; color: #374151; }
.rt-divider     { border-top: 1.5px solid #86efac; }
.discount       { color: #dc2626; }
.rt-total       { font-size: 15px; font-weight: 800; color: #059669; }
.rt-paid        { font-weight: 600; }
.rt-change      { font-weight: 700; color: #15803d; }

@media (max-width: 1024px) { .stats-row { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 768px)  { .sale-card { flex-wrap: wrap; } .item-row { grid-template-columns: 1fr 70px 110px; } }
@media (max-width: 640px)  { .stats-row { grid-template-columns: 1fr; } .sales-page { padding: 16px; } .form-grid-2 { grid-template-columns: 1fr; } }
</style>
