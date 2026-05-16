<template>
  <div class="space-y-5">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-base font-semibold text-gray-800">Inventory Management</h2>
        <p class="text-xs text-gray-500 mt-0.5">{{ pagination.total ?? 0 }} total products</p>
      </div>
      <div class="flex gap-2">
        <button @click="openStockIn()" class="btn-success">Stock In</button>
        <button @click="openModal()" class="btn-primary">Add Product</button>
      </div>
    </div>

    <!-- Out-of-stock / Low-stock Alert Banner -->
    <div v-if="stockAlerts.length" class="space-y-2">
      <div v-if="outOfStockAlerts.length" class="flex items-start gap-3 bg-red-50 border border-red-200 rounded-xl px-5 py-4">
        <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <div class="flex-1">
          <p class="text-sm font-semibold text-red-800">Out of Stock — {{ outOfStockAlerts.length }} item(s)</p>
          <p class="text-xs text-red-600 mt-0.5">{{ outOfStockAlerts.map(a => a.product?.name).join(', ') }}</p>
        </div>
        <button @click="filterLowStock = true; fetchPage(1)" class="btn-danger btn-sm shrink-0">Show</button>
      </div>
      <div v-if="lowStockWarnings.length" class="flex items-start gap-3 bg-amber-50 border border-amber-200 rounded-xl px-5 py-4">
        <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <div class="flex-1">
          <p class="text-sm font-semibold text-amber-800">Low Stock — {{ lowStockWarnings.length }} item(s) at or below reorder point</p>
          <p class="text-xs text-amber-600 mt-0.5">{{ lowStockWarnings.map(a => `${a.product?.name} (${a.product?.stock} left)`).join(', ') }}</p>
        </div>
        <button @click="filterLowStock = true; fetchPage(1)" class="ml-auto shrink-0 text-xs font-medium text-amber-700 hover:text-amber-900 underline">Filter</button>
      </div>
    </div>

    <!-- Filters -->
    <div class="card p-4 flex items-center gap-3">
      <input v-model="search" @input="debouncedFetch" placeholder="Search products..." class="input max-w-xs" />
      <select v-model="filterCategory" @change="fetchPage(1)" class="input w-48">
        <option value="">All Categories</option>
        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
      </select>
      <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
        <input v-model="filterLowStock" @change="fetchPage(1)" type="checkbox" class="rounded" />
        Low Stock Only
      </label>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-3 gap-4">
      <div class="card text-center">
        <p class="text-xs text-gray-500">Total Products</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ pagination.total ?? 0 }}</p>
      </div>
      <div class="card text-center">
        <p class="text-xs text-gray-500">Low Stock Items</p>
        <p class="text-2xl font-bold text-red-600 mt-1">{{ lowStockCount }}</p>
      </div>
      <div class="card text-center">
        <p class="text-xs text-gray-500">Total Stock Value</p>
        <p class="text-2xl font-bold text-green-700 mt-1">₱{{ formatAmount(stockValue) }}</p>
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
            <th class="table-th">SKU</th>
            <th class="table-th">Product</th>
            <th class="table-th">Category</th>
            <th class="table-th">Stock</th>
            <th class="table-th">Cost</th>
            <th class="table-th">Price</th>
            <th class="table-th">Status</th>
            <th class="table-th">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <tr v-for="p in products" :key="p.id" class="hover:bg-gray-50 transition-colors">
            <td class="table-td font-mono text-xs text-blue-700">{{ p.sku }}</td>
            <td class="table-td">
              <p class="font-medium text-gray-900 text-sm">{{ p.name }}</p>
              <p class="text-xs text-gray-400">{{ p.brand }} {{ p.model }}</p>
            </td>
            <td class="table-td text-sm">{{ p.category?.name }}</td>
            <td class="table-td">
              <div class="flex items-center gap-2">
                <span class="font-semibold text-sm" :class="p.stock_quantity <= p.reorder_point ? 'text-red-600' : 'text-gray-900'">
                  {{ p.stock_quantity }}
                </span>
                <span class="text-xs text-gray-400">/ ROP: {{ p.reorder_point }}</span>
              </div>
            </td>
            <td class="table-td text-sm">₱{{ formatAmount(p.cost_price) }}</td>
            <td class="table-td text-sm font-medium">₱{{ formatAmount(p.selling_price) }}</td>
            <td class="table-td">
              <span v-if="p.stock_quantity <= p.reorder_point" class="badge-red">Low Stock</span>
              <span v-else class="badge-green">OK</span>
            </td>
            <td class="table-td">
              <div class="flex items-center gap-1.5">
                <button @click="openStockIn(p)" class="btn-success btn-sm">Stock In</button>
                <button @click="openModal(p)" class="btn-secondary btn-sm">Edit</button>
              </div>
            </td>
          </tr>
          <tr v-if="!products.length">
            <td colspan="8" class="text-center py-12 text-gray-400 text-sm">No products found.</td>
          </tr>
        </tbody>
      </table>

      <div v-if="pagination.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-100">
        <p class="text-xs text-gray-500">Page {{ pagination.current_page }} of {{ pagination.last_page }}</p>
        <div class="flex gap-2">
          <button @click="fetchPage(pagination.current_page - 1)" :disabled="pagination.current_page === 1" class="btn-secondary btn-sm">Prev</button>
          <button @click="fetchPage(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page" class="btn-secondary btn-sm">Next</button>
        </div>
      </div>
    </div>

    <!-- Add/Edit Product Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-screen overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b">
          <h3 class="font-semibold text-gray-900">{{ editingId ? 'Edit Product' : 'Add New Product' }}</h3>
          <button @click="closeModal" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <form @submit.prevent="saveProduct" class="px-6 py-5 grid grid-cols-2 gap-4">
          <div class="col-span-2">
            <label class="label">Product Name *</label>
            <input v-model="form.name" class="input" required />
          </div>
          <div>
            <label class="label">Category *</label>
            <select v-model="form.category_id" class="input" required>
              <option value="">Select category</option>
              <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>
          <div>
            <label class="label">Supplier</label>
            <select v-model="form.supplier_id" class="input">
              <option value="">Select supplier</option>
              <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
          </div>
          <div>
            <label class="label">Brand</label>
            <input v-model="form.brand" class="input" />
          </div>
          <div>
            <label class="label">Model</label>
            <input v-model="form.model" class="input" />
          </div>
          <div>
            <label class="label">Color</label>
            <input v-model="form.color" class="input" />
          </div>
          <div>
            <label class="label">Size</label>
            <input v-model="form.size" class="input" />
          </div>
          <div>
            <label class="label">Cost Price (₱) *</label>
            <input v-model="form.cost_price" type="number" step="0.01" min="0" class="input" required />
          </div>
          <div>
            <label class="label">Selling Price (₱) *</label>
            <input v-model="form.selling_price" type="number" step="0.01" min="0" class="input" required />
          </div>
          <div v-if="!editingId">
            <label class="label">Initial Stock Qty *</label>
            <input v-model="form.stock_quantity" type="number" min="0" class="input" required />
          </div>
          <div>
            <label class="label">Reorder Point *</label>
            <input v-model="form.reorder_point" type="number" min="0" class="input" required />
          </div>
          <div>
            <label class="label">Reorder Quantity *</label>
            <input v-model="form.reorder_quantity" type="number" min="1" class="input" required />
          </div>
          <div v-if="formError" class="col-span-2 text-sm text-red-600 bg-red-50 rounded-lg px-4 py-2">{{ formError }}</div>
          <div class="col-span-2 flex justify-end gap-3">
            <button type="button" @click="closeModal" class="btn-secondary">Cancel</button>
            <button type="submit" class="btn-primary" :disabled="saving">{{ saving ? 'Saving...' : 'Save Product' }}</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Stock In Modal -->
    <div v-if="showStockInModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-4 border-b">
          <h3 class="font-semibold text-gray-900">Stock In — {{ stockInProduct?.name ?? 'Select Product' }}</h3>
          <button @click="showStockInModal = false" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <form @submit.prevent="doStockIn" class="px-6 py-5 space-y-4">
          <div v-if="!stockInProduct">
            <label class="label">Product *</label>
            <select v-model="stockInForm.product_id" class="input" required>
              <option value="">Select product</option>
              <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} ({{ p.sku }})</option>
            </select>
          </div>
          <div>
            <label class="label">Quantity to Add *</label>
            <input v-model="stockInForm.quantity" type="number" min="1" class="input" required />
          </div>
          <div>
            <label class="label">Unit Cost (₱)</label>
            <input v-model="stockInForm.unit_cost" type="number" step="0.01" min="0" class="input" />
          </div>
          <div>
            <label class="label">Reference / DR No.</label>
            <input v-model="stockInForm.reference_number" class="input" placeholder="e.g. DR-001" />
          </div>
          <div>
            <label class="label">Notes</label>
            <textarea v-model="stockInForm.notes" class="input" rows="2"></textarea>
          </div>
          <div class="flex justify-end gap-3">
            <button type="button" @click="showStockInModal = false" class="btn-secondary">Cancel</button>
            <button type="submit" class="btn-success" :disabled="saving">{{ saving ? 'Processing...' : 'Confirm Stock In' }}</button>
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
