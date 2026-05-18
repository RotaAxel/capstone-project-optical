<template>
  <div class="inventory fade-up">
    <!-- Header with Actions -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-xl font-bold text-gray-900">Inventory Management</h2>
        <p class="text-sm text-gray-500 mt-1">{{ pagination.total ?? 0 }} total products</p>
      </div>
      <button @click="openModal()" class="btn btn-success">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Item
      </button>
    </div>

    <!-- Out-of-stock / Low-stock Alert Banner -->
    <div v-if="stockAlerts.length" class="space-y-3 mb-6">
      <div v-if="outOfStockAlerts.length" class="flex items-start gap-3 bg-red-50 border border-red-200 rounded-lg px-4 py-3">
        <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <div class="flex-1">
          <p class="text-sm font-semibold text-red-800">Out of Stock — {{ outOfStockAlerts.length }} item(s)</p>
          <p class="text-xs text-red-600 mt-1">{{ outOfStockAlerts.map(a => a.product?.name).join(', ') }}</p>
        </div>
        <button @click="filterLowStock = true; fetchPage(1)" class="btn btn-danger btn-sm shrink-0">Show</button>
      </div>
      <div v-if="lowStockWarnings.length" class="flex items-start gap-3 bg-amber-50 border border-amber-200 rounded-lg px-4 py-3">
        <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <div class="flex-1">
          <p class="text-sm font-semibold text-amber-800">Low Stock — {{ lowStockWarnings.length }} item(s)</p>
          <p class="text-xs text-amber-600 mt-1">{{ lowStockWarnings.map(a => `${a.product?.name} (${a.product?.stock} left)`).join(', ') }}</p>
        </div>
        <button @click="filterLowStock = true; fetchPage(1)" class="ml-auto shrink-0 text-xs font-medium text-amber-700 hover:text-amber-900 underline">Filter</button>
      </div>
    </div>

    <!-- Filters -->
    <div class="filter-bar card mb-6">
      <input v-model="search" @input="debouncedFetch" placeholder="🔍 Search items..." class="fi" />
      <select v-model="filterCategory" @change="fetchPage(1)" class="fi fi--sel">
        <option value="">All Categories</option>
        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
      </select>
      <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
        <input v-model="filterLowStock" @change="fetchPage(1)" type="checkbox" class="rounded" />
        Low Stock Only
      </label>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-3 gap-8 mb-10">
      <div class="card summary-card text-center">
        <p class="text-xs text-gray-500 font-medium">Total Products</p>
        <p class="text-3xl font-bold text-gray-900 mt-3">{{ pagination.total ?? 0 }}</p>
      </div>
      <div class="card summary-card text-center">
        <p class="text-xs text-gray-500 font-medium">Low Stock Items</p>
        <p class="text-3xl font-bold text-red-600 mt-3">{{ lowStockCount }}</p>
      </div>
      <div class="card summary-card text-center">
        <p class="text-xs text-gray-500 font-medium">Total Stock Value</p>
        <p class="text-3xl font-bold text-green-700 mt-3">₱{{ formatAmount(stockValue) }}</p>
      </div>
    </div>

    <!-- Items Grid -->
    <div class="items-grid">
      <div v-if="loading" class="col-span-4 flex items-center justify-center py-16">
        <div class="animate-spin w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full"></div>
      </div>
      <div v-for="p in products" v-else :key="p.id" class="item-card card">
        <div class="item-image">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/>
            <polyline points="21 15 16 10 5 21"/>
          </svg>
        </div>
        <p class="text-xs text-blue-600 font-mono mb-2">{{ p.sku }}</p>
        <h4 class="item-name">{{ p.name }}</h4>
        <p class="item-desc">{{ p.brand }} {{ p.model }}</p>
        <p class="text-xs text-gray-500 mb-3">₱{{ formatAmount(p.cost_price) }} - ₱{{ formatAmount(p.selling_price) }}</p>
        <button class="stock-btn" :class="p.stock_quantity <= p.reorder_point ? 'btn-danger' : 'btn-success'">
          Stock: {{ p.stock_quantity }} / ROP: {{ p.reorder_point }}
        </button>
        <div class="flex gap-2 mt-3 w-full">
          <button @click="openStockIn(p)" class="btn btn-success btn-sm flex-1">Stock In</button>
          <button @click="openModal(p)" class="btn btn-secondary btn-sm flex-1">Edit</button>
        </div>
      </div>
      <div v-if="!loading && !products.length" class="col-span-4 text-center py-12 text-gray-400">
        No products found.
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.last_page > 1" class="flex items-center justify-between mt-6 px-4 py-3">
      <p class="text-xs text-gray-500">Page {{ pagination.current_page }} of {{ pagination.last_page }}</p>
      <div class="flex gap-2">
        <button @click="fetchPage(pagination.current_page - 1)" :disabled="pagination.current_page === 1" class="btn btn-secondary btn-sm">Prev</button>
        <button @click="fetchPage(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page" class="btn btn-secondary btn-sm">Next</button>
      </div>
    </div>

    <!-- Add/Edit Product Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-screen overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
          <h3 class="font-semibold text-gray-900">{{ editingId ? 'Edit Product' : 'Add New Item' }}</h3>
          <button @click="closeModal" class="text-gray-400 hover:text-gray-600 text-xl">✕</button>
        </div>
        <form @submit.prevent="saveProduct" class="px-6 py-5">
          <div class="form-grid">
            <div class="fg fg--full">
              <label class="fl">Item Name *</label>
              <input v-model="form.name" class="fi" required />
            </div>
            <div class="fg">
              <label class="fl">Category *</label>
              <select v-model="form.category_id" class="fi fi--sel" required>
                <option value="">Select category</option>
                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>
            <div class="fg">
              <label class="fl">Brand</label>
              <input v-model="form.brand" class="fi" />
            </div>
            <div class="fg">
              <label class="fl">Model</label>
              <input v-model="form.model" class="fi" />
            </div>
            <div class="fg">
              <label class="fl">Color</label>
              <input v-model="form.color" class="fi" />
            </div>
            <div class="fg">
              <label class="fl">Size</label>
              <input v-model="form.size" class="fi" />
            </div>
            <div class="fg">
              <label class="fl">Supplier</label>
              <select v-model="form.supplier_id" class="fi fi--sel">
                <option value="">Select supplier</option>
                <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
              </select>
            </div>
            <div class="fg">
              <label class="fl">Cost Price (₱) *</label>
              <input v-model="form.cost_price" type="number" step="0.01" min="0" class="fi" required />
            </div>
            <div class="fg">
              <label class="fl">Selling Price (₱) *</label>
              <input v-model="form.selling_price" type="number" step="0.01" min="0" class="fi" required />
            </div>
            <div v-if="!editingId" class="fg">
              <label class="fl">Initial Stock Qty *</label>
              <input v-model="form.stock_quantity" type="number" min="0" class="fi" required />
            </div>
            <div class="fg">
              <label class="fl">Reorder Point *</label>
              <input v-model="form.reorder_point" type="number" min="0" class="fi" required />
            </div>
            <div class="fg">
              <label class="fl">Reorder Quantity *</label>
              <input v-model="form.reorder_quantity" type="number" min="1" class="fi" required />
            </div>
            <div v-if="formError" class="fg fg--full">
              <div class="text-sm text-red-600 bg-red-50 rounded-lg px-4 py-2">{{ formError }}</div>
            </div>
          </div>
          <div class="flex justify-end gap-3 mt-6">
            <button type="button" @click="closeModal" class="btn btn-secondary">Cancel</button>
            <button type="submit" class="btn btn-primary" :disabled="saving">{{ saving ? 'Saving...' : 'Save Product' }}</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Stock In Modal -->
    <div v-if="showStockInModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
          <h3 class="font-semibold text-gray-900">Stock In — {{ stockInProduct?.name ?? 'Select Product' }}</h3>
          <button @click="showStockInModal = false" class="text-gray-400 hover:text-gray-600 text-xl">✕</button>
        </div>
        <form @submit.prevent="doStockIn" class="px-6 py-5 space-y-4">
          <div v-if="!stockInProduct" class="fg">
            <label class="fl">Product *</label>
            <select v-model="stockInForm.product_id" class="fi fi--sel" required>
              <option value="">Select product</option>
              <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} ({{ p.sku }})</option>
            </select>
          </div>
          <div class="fg">
            <label class="fl">Quantity to Add *</label>
            <input v-model="stockInForm.quantity" type="number" min="1" class="fi" required />
          </div>
          <div class="fg">
            <label class="fl">Unit Cost (₱)</label>
            <input v-model="stockInForm.unit_cost" type="number" step="0.01" min="0" class="fi" />
          </div>
          <div class="fg">
            <label class="fl">Reference / DR No.</label>
            <input v-model="stockInForm.reference_number" class="fi" placeholder="e.g. DR-001" />
          </div>
          <div class="fg">
            <label class="fl">Notes</label>
            <textarea v-model="stockInForm.notes" class="fi fi--ta" rows="2"></textarea>
          </div>
          <div class="flex justify-end gap-3 mt-6">
            <button type="button" @click="showStockInModal = false" class="btn btn-secondary">Cancel</button>
            <button type="submit" class="btn btn-success" :disabled="saving">{{ saving ? 'Processing...' : 'Confirm Stock In' }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'
import { useAlerts } from '@/composables/useAlerts'

const { alerts } = useAlerts(false)

const stockAlerts      = computed(() => alerts.value.filter(a => ['out_of_stock', 'low_stock'].includes(a.type)))
const outOfStockAlerts = computed(() => alerts.value.filter(a => a.type === 'out_of_stock'))
const lowStockWarnings = computed(() => alerts.value.filter(a => a.type === 'low_stock'))

const products      = ref([])
const categories    = ref([])
const suppliers     = ref([])
const pagination    = ref({})
const loading       = ref(true)
const search        = ref('')
const filterCategory = ref('')
const filterLowStock = ref(false)
const showModal     = ref(false)
const showStockInModal = ref(false)
const editingId     = ref(null)
const saving        = ref(false)
const formError     = ref('')
const stockInProduct = ref(null)

const emptyForm = () => ({ name: '', category_id: '', supplier_id: '', brand: '', model: '', color: '', size: '', cost_price: 0, selling_price: 0, stock_quantity: 0, reorder_point: 5, reorder_quantity: 20 })
const form = ref(emptyForm())
const stockInForm = ref({ product_id: '', quantity: 1, unit_cost: null, reference_number: '', notes: '' })

const lowStockCount = computed(() => products.value.filter(p => p.stock_quantity <= p.reorder_point).length)
const stockValue    = computed(() => products.value.reduce((sum, p) => sum + (p.stock_quantity * p.cost_price), 0))

let debounceTimer
function debouncedFetch() { clearTimeout(debounceTimer); debounceTimer = setTimeout(() => fetchPage(1), 400) }

async function fetchPage(page = 1) {
  loading.value = true
  try {
    const { data } = await api.get('/products', { params: { page, search: search.value, category_id: filterCategory.value || undefined, low_stock: filterLowStock.value ? 1 : undefined } })
    products.value   = data.data
    pagination.value = { current_page: data.current_page, last_page: data.last_page, total: data.total }
  } catch { /* backend unavailable */ }
  finally { loading.value = false }
}

function openModal(product = null) {
  formError.value = ''
  if (product) { editingId.value = product.id; form.value = { ...product } }
  else { editingId.value = null; form.value = emptyForm() }
  showModal.value = true
}

function closeModal() { showModal.value = false }

async function saveProduct() {
  formError.value = ''; saving.value = true
  try {
    if (editingId.value) await api.put(`/products/${editingId.value}`, form.value)
    else await api.post('/products', form.value)
    closeModal(); fetchPage(pagination.value.current_page ?? 1)
  } catch (e) {
    formError.value = Object.values(e.response?.data?.errors ?? {}).flat().join(' ') || 'Failed to save.'
  } finally { saving.value = false }
}

function openStockIn(product = null) {
  stockInProduct.value = product
  stockInForm.value = { product_id: product?.id ?? '', quantity: 1, unit_cost: null, reference_number: '', notes: '' }
  showStockInModal.value = true
}

async function doStockIn() {
  saving.value = true
  try {
    const pid = stockInProduct.value?.id ?? stockInForm.value.product_id
    await api.post(`/products/${pid}/stock-in`, stockInForm.value)
    showStockInModal.value = false; fetchPage(pagination.value.current_page ?? 1)
  } finally { saving.value = false }
}

function formatAmount(v) { return Number(v || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }

onMounted(async () => {
  try {
    const [cats, sups] = await Promise.all([
      api.get('/product-categories'),
      api.get('/suppliers', { params: { per_page: 100 } }),
    ])
    categories.value = cats.data
    suppliers.value  = sups.data.data ?? sups.data
    fetchPage()
  } catch { loading.value = false }
})
</script>

<style scoped>
.inventory { padding: 28px 32px 40px; }

.filter-bar {
  display: flex; gap: 16px; padding: 16px 20px;
  background: rgba(255, 255, 255, 0.92); backdrop-filter: blur(6px);
  border-radius: 12px;
  margin-bottom: 28px;
  align-items: center;
}

/* Summary Cards */
.summary-card {
  padding: 28px 24px !important;
  background: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(6px);
  border-radius: 12px;
  transition: all 0.3s ease;
  margin: 15px; 
}

.summary-card:hover {
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
  transform: translateY(-2px);
}

.items-grid { 
  display: grid; grid-template-columns: repeat(4, 1fr); gap: 28px;
  margin-bottom: 36px;
}

.item-card {
  display: flex; flex-direction: column; align-items: center;
  padding: 28px 22px; text-align: center;
  background: rgba(255, 255, 255, 0.92); backdrop-filter: blur(6px);
  border-radius: 12px;
  transition: all 0.3s ease;
}

.item-card:hover { 
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); 
  transform: translateY(-3px); 
}

.item-image {
  width: 70px; height: 70px; background: #f3f4f6; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 16px; color: #9ca3af;
}

.item-name  { font-size: 14px; font-weight: 700; color: #1f2937; margin-bottom: 8px; }
.item-desc  { font-size: 11px; color: #9ca3af; line-height: 1.6; margin-bottom: 10px; }
.item-price { font-size: 15px; font-weight: 800; color: #0891b2; margin-bottom: 18px; }

.stock-btn  { 
  width: 100%; justify-content: center; padding: 10px 12px; 
  font-size: 12px; border-radius: 8px; border: none; 
  margin-bottom: 16px; font-weight: 600;
}

/* Form */
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px 22px; }
.fg        { display: flex; flex-direction: column; gap: 8px; }
.fg--full  { grid-column: 1 / -1; }
.fl        { font-size: 12px; font-weight: 700; color: #1f2937; }

.fi {
  padding: 11px 15px; border: 1.5px solid #e5e7eb; border-radius: 10px;
  font-family: inherit; font-size: 13px; color: #1f2937;
  background: #fff; outline: none; width: 100%;
  transition: border-color 0.3s ease;
}

.fi:focus { border-color: #06b6d4; box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.12); }
.fi--sel  { cursor: pointer; }
.fi--ta   { resize: vertical; min-height: 90px; }

@media (max-width: 1400px) {
  .items-grid { grid-template-columns: repeat(3, 1fr); gap: 26px; }
}

@media (max-width: 1024px) {
  .items-grid { grid-template-columns: repeat(2, 1fr); gap: 24px; }
}

@media (max-width: 768px) {
  .inventory { padding: 20px 16px 30px; }
  .items-grid { grid-template-columns: 1fr; gap: 20px; margin-bottom: 30px; }
  .filter-bar { gap: 14px; padding: 16px 18px; margin-bottom: 24px; }
  .item-card { padding: 22px 18px; }
  .summary-card { padding: 26px 22px !important; }
}
</style>
