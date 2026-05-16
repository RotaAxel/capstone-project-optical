<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-base font-semibold text-gray-800">Sales & Billing</h2>
        <p class="text-xs text-gray-500">{{ pagination.total ?? 0 }} total transactions</p>
      </div>
      <button @click="showSaleModal = true" class="btn-primary">New Sale</button>
    </div>

    <!-- Filters -->
    <div class="card p-4 flex gap-3 flex-wrap">
      <input v-model="search" @input="debouncedFetch" placeholder="Search receipt #..." class="input w-52" />
      <input v-model="dateFrom" @change="fetchPage(1)" type="date" class="input w-40" />
      <input v-model="dateTo"   @change="fetchPage(1)" type="date" class="input w-40" />
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
      <div v-if="loading" class="flex items-center justify-center py-16">
        <div class="animate-spin w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full"></div>
      </div>
      <table v-else class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr>
            <th class="table-th">Receipt #</th>
            <th class="table-th">Patient</th>
            <th class="table-th">Cashier</th>
            <th class="table-th">Items</th>
            <th class="table-th">Total</th>
            <th class="table-th">Payment</th>
            <th class="table-th">Date</th>
            <th class="table-th">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <tr v-for="s in sales" :key="s.id" class="hover:bg-gray-50 transition-colors">
            <td class="table-td font-mono text-blue-700 font-medium text-xs">{{ s.receipt_number }}</td>
            <td class="table-td text-sm">{{ s.patient ? s.patient.first_name + ' ' + s.patient.last_name : 'Walk-in' }}</td>
            <td class="table-td text-sm">{{ s.cashier?.name }}</td>
            <td class="table-td text-sm">{{ s.items?.length ?? 0 }} item(s)</td>
            <td class="table-td font-semibold text-green-700">₱{{ fmt(s.total_amount) }}</td>
            <td class="table-td"><span class="badge-blue capitalize">{{ s.payment_method }}</span></td>
            <td class="table-td text-sm text-gray-400">{{ fmtDate(s.created_at) }}</td>
            <td class="table-td"><button @click="viewSale(s)" class="btn-secondary btn-sm">View</button></td>
          </tr>
          <tr v-if="!sales.length"><td colspan="8" class="text-center py-12 text-gray-400 text-sm">No sales found.</td></tr>
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

    <!-- New Sale Modal -->
    <div v-if="showSaleModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-screen overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b">
          <h3 class="font-semibold text-gray-900">New Sale / Billing</h3>
          <button @click="closeSaleModal" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <div class="px-6 py-5 space-y-5">
          <!-- Patient & Payment -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="label">Patient (optional)</label>
              <select v-model="saleForm.patient_id" class="input">
                <option value="">Walk-in</option>
                <option v-for="p in patients" :key="p.id" :value="p.id">{{ p.first_name }} {{ p.last_name }}</option>
              </select>
            </div>
            <div>
              <label class="label">Payment Method *</label>
              <select v-model="saleForm.payment_method" class="input" required>
                <option value="cash">Cash</option>
                <option value="card">Card</option>
                <option value="gcash">GCash</option>
                <option value="maya">Maya</option>
                <option value="other">Other</option>
              </select>
            </div>
          </div>

          <!-- Items -->
          <div>
            <div class="flex items-center justify-between mb-2">
              <label class="label mb-0">Items *</label>
              <button @click="addItem" type="button" class="btn-secondary btn-sm">+ Add Item</button>
            </div>
            <div class="space-y-2">
              <div v-for="(item, idx) in saleForm.items" :key="idx" class="flex gap-2 items-end">
                <div class="flex-1">
                  <label class="label text-xs">Product</label>
                  <select v-model="item.product_id" @change="fillPrice(item)" class="input">
                    <option value="">Select product</option>
                    <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} (₱{{ fmt(p.selling_price) }})</option>
                  </select>
                </div>
                <div class="w-20">
                  <label class="label text-xs">Qty</label>
                  <input v-model="item.quantity" type="number" min="1" class="input" @input="recalc" />
                </div>
                <div class="w-28">
                  <label class="label text-xs">Unit Price</label>
                  <input v-model="item.unit_price" type="number" step="0.01" class="input" @input="recalc" />
                </div>
                <div class="w-24">
                  <label class="label text-xs">Discount</label>
                  <input v-model="item.discount" type="number" step="0.01" min="0" class="input" @input="recalc" />
                </div>
                <div class="w-24">
                  <label class="label text-xs">Subtotal</label>
                  <p class="text-sm font-semibold text-gray-800 pt-2">₱{{ fmt(itemSubtotal(item)) }}</p>
                </div>
                <button @click="removeItem(idx)" type="button" class="btn-danger btn-sm mb-0.5">✕</button>
              </div>
            </div>
          </div>

          <!-- Totals -->
          <div class="bg-gray-50 rounded-xl p-4 space-y-2">
            <div class="flex justify-between text-sm"><span class="text-gray-600">Subtotal:</span><span class="font-medium">₱{{ fmt(totals.subtotal) }}</span></div>
            <div class="flex justify-between text-sm items-center">
              <span class="text-gray-600">Discount:</span>
              <input v-model="saleForm.discount_amount" type="number" step="0.01" min="0" class="input w-32 text-right" @input="recalc" />
            </div>
            <div class="flex justify-between text-base font-bold border-t pt-2 mt-1"><span>Total:</span><span class="text-blue-700">₱{{ fmt(totals.total) }}</span></div>
            <div class="flex justify-between text-sm items-center">
              <span class="text-gray-600">Amount Paid:</span>
              <input v-model="saleForm.amount_paid" type="number" step="0.01" min="0" class="input w-32 text-right" @input="recalc" />
            </div>
            <div class="flex justify-between text-sm font-semibold text-green-700"><span>Change:</span><span>₱{{ fmt(totals.change) }}</span></div>
          </div>

          <div v-if="saleError" class="text-sm text-red-600 bg-red-50 rounded-lg px-4 py-2">{{ saleError }}</div>

          <div class="flex justify-end gap-3">
            <button @click="closeSaleModal" class="btn-secondary">Cancel</button>
            <button @click="submitSale" class="btn-primary" :disabled="saving">{{ saving ? 'Processing...' : 'Complete Sale' }}</button>
          </div>
        </div>
      </div>
    </div>

    <!-- View Sale Modal -->
    <div v-if="viewingSale" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b">
          <h3 class="font-semibold">Receipt: {{ viewingSale.receipt_number }}</h3>
          <button @click="viewingSale = null" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <div class="px-6 py-5 space-y-3">
          <div class="flex justify-between text-sm"><span class="text-gray-500">Patient:</span><span>{{ viewingSale.patient ? viewingSale.patient.first_name + ' ' + viewingSale.patient.last_name : 'Walk-in' }}</span></div>
          <div class="flex justify-between text-sm"><span class="text-gray-500">Cashier:</span><span>{{ viewingSale.cashier?.name }}</span></div>
          <div class="flex justify-between text-sm"><span class="text-gray-500">Date:</span><span>{{ fmtDate(viewingSale.created_at) }}</span></div>
          <hr />
          <div v-for="item in viewingSale.items" :key="item.id" class="flex justify-between text-sm">
            <span>{{ item.product?.name }} × {{ item.quantity }}</span>
            <span>₱{{ fmt(item.subtotal) }}</span>
          </div>
          <hr />
          <div class="flex justify-between text-sm"><span>Subtotal</span><span>₱{{ fmt(viewingSale.subtotal) }}</span></div>
          <div class="flex justify-between text-sm text-red-600"><span>Discount</span><span>-₱{{ fmt(viewingSale.discount_amount) }}</span></div>
          <div class="flex justify-between font-bold"><span>Total</span><span class="text-blue-700">₱{{ fmt(viewingsale?.total_amount ?? viewingScale?.total_amount ?? 0) }}</span></div>
          <div class="flex justify-between text-sm text-green-700"><span>Payment Method</span><span class="capitalize">{{ viewingScale?.payment_method ?? viewingale?.payment_method }}</span></div>
        </div>
      </div>
    </div>
  </div>
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
const viewingScale  = ref(null)
const saving     = ref(false)
const saleError  = ref('')

// alias fix
const viewingale  = ref(null)
const viewingSale = ref(null)

const saleForm = ref({ patient_id: '', payment_method: 'cash', discount_amount: 0, amount_paid: 0, items: [] })
const totals   = ref({ subtotal: 0, total: 0, change: 0 })

function itemSubtotal(item) { return (item.unit_price * item.quantity) - (item.discount || 0) }

function recalc() {
  const sub = saleForm.value.items.reduce((s, i) => s + itemSubtotal(i), 0)
  const total = sub - (saleForm.value.discount_amount || 0)
  totals.value = { subtotal: sub, total, change: Math.max(0, (saleForm.value.amount_paid || 0) - total) }
}

function addItem() { saleForm.value.items.push({ product_id: '', quantity: 1, unit_price: 0, discount: 0 }); recalc() }
function removeItem(idx) { saleForm.value.items.splice(idx, 1); recalc() }
function fillPrice(item) {
  const prod = products.value.find(p => p.id === item.product_id)
  if (prod) { item.unit_price = prod.selling_price; recalc() }
}

function closeSaleModal() { showSaleModal.value = false; saleForm.value = { patient_id: '', payment_method: 'cash', discount_amount: 0, amount_paid: 0, items: [] }; totals.value = { subtotal: 0, total: 0, change: 0 }; saleError.value = '' }

async function submitSale() {
  saleError.value = ''; saving.value = true
  try {
    await api.post('/sales', saleForm.value)
    closeSaleModal(); fetchPage(1)
  } catch (e) {
    saleError.value = Object.values(e.response?.data?.errors ?? {}).flat().join(' ') || e.response?.data?.message || 'Failed to process sale.'
  } finally { saving.value = false }
}

function viewSale(sale) { viewingScale.value = sale; viewingale.value = sale; viewingSale.value = sale }

let debounceTimer
function debouncedFetch() { clearTimeout(debounceTimer); debounceTimer = setTimeout(() => fetchPage(1), 400) }

async function fetchPage(page = 1) {
  loading.value = true
  try {
    const { data } = await api.get('/sales', { params: { page, search: search.value, date_from: dateFrom.value || undefined, date_to: dateTo.value || undefined } })
    sales.value = data.data; pagination.value = { current_page: data.current_page, last_page: data.last_page, total: data.total }
  } catch { /* backend unavailable — show empty state */ }
  finally { loading.value = false }
}

function fmt(v) { return Number(v || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }
function fmtDate(d) { return d ? new Date(d).toLocaleString('en-PH', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—' }

onMounted(async () => {
  try {
    const [pts, prods] = await Promise.all([
      api.get('/patients', { params: { per_page: 200 } }),
      api.get('/products', { params: { per_page: 200 } }),
    ])
    patients.value = pts.data.data
    products.value = prods.data.data
    fetchPage()
  } catch { loading.value = false }
})
</script>
