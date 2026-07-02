<template>
  <div class="inv-page fade-up">

    <!-- ── Header ─────────────────────────────────────────────── -->
    <div class="page-header">
      <div class="flex items-center gap-3">
        <div class="header-icon">
          <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
        </div>
        <div>
          <h2 class="page-title">Inventory</h2>
          <p class="page-sub">{{ pagination.total ?? 0 }} total products</p>
        </div>
      </div>
      <button @click="openModal()" class="btn-add">
        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
        Add Item
      </button>
    </div>

    <!-- ── Stats Row ──────────────────────────────────────────── -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-icon teal">
          <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
        </div>
        <div>
          <p class="stat-num">{{ invStats.total ?? 0 }}</p>
          <p class="stat-lbl">Total Products</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon amber">
          <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </div>
        <div>
          <p class="stat-num text-amber-600">{{ lowStockCount }}</p>
          <p class="stat-lbl">Low Stock</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon red">
          <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
          <p class="stat-num text-red-600">{{ outOfStockCount }}</p>
          <p class="stat-lbl">Out of Stock</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green">
          <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
          <p class="stat-num text-green-700">₱{{ fmtAmt(stockValue) }}</p>
          <p class="stat-lbl">Stock Value</p>
        </div>
      </div>
    </div>

    <!-- ── Alert Banners ──────────────────────────────────────── -->
    <div v-if="outOfStockAlerts.length || lowStockWarnings.length" class="alerts-wrap">
      <div v-if="outOfStockAlerts.length" class="alert-banner alert-red">
        <div class="alert-dot red"></div>
        <div class="flex-1">
          <p class="alert-title">Out of Stock — {{ outOfStockAlerts.length }} item(s)</p>
          <p class="alert-body">{{ outOfStockAlerts.map(a => a.product?.name).join(', ') }}</p>
        </div>
        <button @click="filterLowStock = true; fetchPage(1)" class="alert-action red">View Items</button>
      </div>
      <div v-if="lowStockWarnings.length" class="alert-banner alert-amber">
        <div class="alert-dot amber"></div>
        <div class="flex-1">
          <p class="alert-title">Low Stock — {{ lowStockWarnings.length }} item(s) at or below reorder point</p>
          <p class="alert-body">{{ lowStockWarnings.map(a => a.product?.name).slice(0, 5).join(', ') }}{{ lowStockWarnings.length > 5 ? '…' : '' }}</p>
        </div>
        <button @click="filterLowStock = true; fetchPage(1)" class="alert-action amber">Filter</button>
      </div>
    </div>

    <!-- ── Filters ────────────────────────────────────────────── -->
    <div class="filter-bar">
      <div class="filter-field" style="flex: 1; max-width: 320px;">
        <svg class="filter-icon" width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input v-model="search" @input="debouncedFetch" placeholder="Search products…" class="filter-input" />
      </div>
      <div class="filter-field">
        <svg class="filter-icon" width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
        <select v-model="filterCategory" @change="fetchPage(1)" class="filter-input">
          <option value="">All Categories</option>
          <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
      </div>
      <label class="toggle-wrap">
        <input v-model="filterLowStock" @change="fetchPage(1)" type="checkbox" class="toggle-cb" />
        <span class="toggle-track" :class="filterLowStock ? 'on' : ''">
          <span class="toggle-thumb"></span>
        </span>
        <span class="toggle-label">Low Stock Only</span>
      </label>
      <button v-if="search || filterCategory || filterLowStock" @click="clearFilters" class="clear-btn">Clear</button>
    </div>

    <!-- ── Product Grid ───────────────────────────────────────── -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="animate-spin w-8 h-8 border-4 border-teal-500 border-t-transparent rounded-full"></div>
    </div>

    <div v-else-if="products.length" class="products-grid">
      <div v-for="p in products" :key="p.id" class="product-card" :class="{ 'card-highlighted': p.id === highlightId }" :data-product-id="p.id">
        <!-- Top status stripe -->
        <div class="card-stripe" :class="stockStripe(p)"></div>

        <!-- Product Image / Placeholder -->
        <div class="product-img-area">
          <img v-if="p.image_url" :src="p.image_url" :alt="p.name" class="product-img" />
          <div v-else class="product-img-placeholder" :class="iconBg(p)">
            <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            <span class="placeholder-label">No Image</span>
          </div>
          <span class="sku-badge-overlay">{{ p.sku }}</span>
        </div>

        <!-- Name + Brand -->
        <h4 class="product-name">{{ p.name }}</h4>
        <p class="product-brand">{{ [p.brand, p.model, p.color].filter(Boolean).join(' · ') || '—' }}</p>

        <!-- Category -->
        <span class="category-badge">{{ p.category?.name ?? '—' }}</span>

        <!-- Price -->
        <div class="price-row">
          <div>
            <p class="price-label">Cost</p>
            <p class="price-val">₱{{ fmtAmt(p.cost_price) }}</p>
          </div>
          <div class="text-right">
            <p class="price-label">Selling</p>
            <p class="price-val selling">₱{{ fmtAmt(p.selling_price) }}</p>
          </div>
        </div>

        <!-- Stock level -->
        <div class="stock-section">
          <div class="stock-row">
            <span class="stock-label">Stock</span>
            <span class="stock-num" :class="stockNumClass(p)">{{ p.stock_quantity }}</span>
          </div>
          <div class="stock-bar-bg">
            <div class="stock-bar-fill" :class="stockBarClass(p)" :style="{ width: stockBarWidth(p) }"></div>
          </div>
          <div class="stock-row mt-1">
            <span class="stock-rop">ROP: {{ p.reorder_point }}</span>
            <span class="stock-status" :class="stockStatusClass(p)">{{ stockStatusLabel(p) }}</span>
          </div>
        </div>

        <!-- Actions -->
        <div class="card-actions">
          <button @click="openStockIn(p)" class="action-btn btn-stockin">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            Stock In
          </button>
          <button @click="openAdjust(p)" class="action-btn btn-adjust">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            Adjust
          </button>
          <button @click="openModal(p)" class="action-btn btn-edit">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Edit
          </button>
        </div>
      </div>
    </div>

    <div v-else class="empty-state">
      <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="empty-icon"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
      <p class="empty-text">No products found</p>
      <p class="empty-sub">Try adjusting your filters or add a new item</p>
    </div>

    <!-- ── Pagination ─────────────────────────────────────────── -->
    <div v-if="pagination.last_page > 1" class="pagination">
      <button class="page-btn" :disabled="pagination.current_page === 1" @click="fetchPage(pagination.current_page - 1)">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg> Prev
      </button>
      <span class="page-info">Page {{ pagination.current_page }} of {{ pagination.last_page }}</span>
      <button class="page-btn" :disabled="pagination.current_page === pagination.last_page" @click="fetchPage(pagination.current_page + 1)">
        Next <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
      </button>
    </div>

  </div>

  <!-- ── Add / Edit Product Modal ───────────────────────────── -->
  <Teleport to="body">
    <div v-if="showModal" class="modal-backdrop" @click.self="closeModal">
      <div class="modal-box modal-lg">
        <div class="modal-header">
          <div class="flex items-center gap-3">
            <div class="modal-icon">
              <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
            <h3 class="modal-title">{{ editingId ? 'Edit Product' : 'Add New Item' }}</h3>
          </div>
          <button @click="closeModal" class="modal-close">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
        <form @submit.prevent="saveProduct" class="modal-body">

          <!-- Image upload -->
          <div class="fg fg--full" style="margin-bottom:16px;">
            <label class="fl">Product Image</label>
            <div class="img-upload-zone" :class="{ 'has-img': imagePreview }" @click="imgInput.click()">
              <img v-if="imagePreview" :src="imagePreview" class="img-preview" />
              <div v-else class="img-upload-placeholder">
                <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <p class="upload-label">Click to upload image</p>
                <p class="upload-hint">PNG, JPG, WEBP · max 2 MB</p>
              </div>
              <input ref="imgInput" type="file" accept="image/png,image/jpeg,image/webp" @change="onImageChange" style="display:none" />
            </div>
            <button v-if="imagePreview" type="button" @click="clearImage" class="img-remove-btn">✕ Remove image</button>
          </div>

          <div class="form-grid">
            <div class="fg fg--full">
              <label class="fl">Item Name *</label>
              <input v-model="form.name" class="fi" required placeholder="e.g. Ray-Ban Classic Frame" />
            </div>
            <div class="fg">
              <label class="fl">Category *</label>
              <select v-model="form.category_id" class="fi" required>
                <option value="">Select category</option>
                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>
            <div class="fg">
              <label class="fl">Supplier</label>
              <select v-model="form.supplier_id" class="fi">
                <option value="">Select supplier</option>
                <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
              </select>
            </div>
            <div class="fg">
              <label class="fl">Brand</label>
              <input v-model="form.brand" class="fi" placeholder="e.g. Ray-Ban" />
            </div>
            <div class="fg">
              <label class="fl">Model</label>
              <input v-model="form.model" class="fi" placeholder="e.g. RB3025" />
            </div>
            <div class="fg">
              <label class="fl">Color</label>
              <input v-model="form.color" class="fi" placeholder="e.g. Black" />
            </div>
            <div class="fg">
              <label class="fl">Size</label>
              <input v-model="form.size" class="fi" placeholder="e.g. 54mm" />
            </div>
          </div>

          <div class="section-divider">Pricing &amp; Stock</div>
          <div class="form-grid">
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
          </div>

          <div v-if="formError" class="error-msg">{{ formError }}</div>
          <div class="modal-footer">
            <button type="button" @click="closeModal" class="btn-cancel">Cancel</button>
            <button type="submit" class="btn-save" :disabled="saving">{{ saving ? 'Saving…' : (editingId ? 'Save Changes' : 'Add Item') }}</button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>

  <!-- ── Stock In Modal ─────────────────────────────────────── -->
  <Teleport to="body">
    <div v-if="showStockInModal" class="modal-backdrop" @click.self="showStockInModal = false">
      <div class="modal-box modal-md">
        <div class="modal-header">
          <div class="flex items-center gap-3">
            <div class="modal-icon green">
              <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            </div>
            <div>
              <h3 class="modal-title">Stock In</h3>
              <p class="modal-sub">{{ stockInProduct?.name ?? 'Select a product' }}</p>
            </div>
          </div>
          <button @click="showStockInModal = false" class="modal-close">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
        <form @submit.prevent="doStockIn" class="modal-body">
          <div class="form-grid-1">
            <div v-if="!stockInProduct" class="fg">
              <label class="fl">Product *</label>
              <select v-model="stockInForm.product_id" class="fi" required>
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
              <input v-model="stockInForm.unit_cost" type="number" step="0.01" min="0" class="fi" placeholder="Optional" />
            </div>
            <div class="fg">
              <label class="fl">Reference / DR No.</label>
              <input v-model="stockInForm.reference_number" class="fi" placeholder="e.g. DR-001" />
            </div>
            <div class="fg">
              <label class="fl">Notes</label>
              <textarea v-model="stockInForm.notes" class="fi fi--ta" rows="2" placeholder="Optional remarks…"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" @click="showStockInModal = false" class="btn-cancel">Cancel</button>
            <button type="submit" class="btn-save green-btn" :disabled="saving">{{ saving ? 'Processing…' : 'Confirm Stock In' }}</button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>

  <!-- ── Adjust Stock Modal ──────────────────────────────── -->
  <Teleport to="body">
    <div v-if="showAdjustModal" class="modal-backdrop" @click.self="showAdjustModal = false">
      <div class="modal-box modal-md">
        <div class="modal-header">
          <div class="flex items-center gap-3">
            <div class="mh-icon orange">
              <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div>
              <h3 class="modal-title">Adjust Stock</h3>
              <p class="modal-sub">{{ adjustProduct?.name }}</p>
            </div>
          </div>
          <button @click="showAdjustModal = false" class="modal-close">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
        <form @submit.prevent="doAdjust" class="modal-body">
          <div class="form-grid-1">
            <div class="fg">
              <label class="fl">Adjustment Type *</label>
              <select v-model="adjustForm.type" class="fi" required>
                <option value="adjustment">Adjustment — manual stock correction</option>
                <option value="damage">Damage — items damaged, unusable</option>
                <option value="loss">Loss — theft, expiry, or unexplained loss</option>
              </select>
            </div>
            <div class="adjust-info-row" :class="adjustForm.type">
              <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01"/></svg>
              <span v-if="adjustForm.type === 'adjustment'">Stock will be reduced by the entered quantity.</span>
              <span v-else-if="adjustForm.type === 'damage'">Damaged items will be deducted and recorded as a damage event.</span>
              <span v-else>Lost items will be deducted and recorded as a loss event.</span>
            </div>
            <div class="fg">
              <label class="fl">Quantity *</label>
              <input v-model.number="adjustForm.quantity" type="number" min="1" :max="adjustProduct?.stock_quantity" class="fi" required />
              <span class="fi-hint">Current stock: {{ adjustProduct?.stock_quantity ?? 0 }} units</span>
            </div>
            <div class="fg">
              <label class="fl">Reference No.</label>
              <input v-model="adjustForm.reference_number" class="fi" placeholder="e.g. ADJ-001" />
            </div>
            <div class="fg">
              <label class="fl">Reason / Notes *</label>
              <textarea v-model="adjustForm.notes" class="fi fi--ta" rows="2" placeholder="Describe the reason for this adjustment…" required></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" @click="showAdjustModal = false" class="btn-cancel">Cancel</button>
            <button type="submit" class="btn-save orange-btn" :disabled="saving">{{ saving ? 'Processing…' : 'Confirm Adjustment' }}</button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch, useTemplateRef } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import { useAlerts } from '@/composables/useAlerts'

const route  = useRoute()
const router = useRouter()

const { alerts } = useAlerts(false)

const outOfStockAlerts = computed(() => alerts.value.filter(a => a.type === 'out_of_stock'))
const lowStockWarnings = computed(() => alerts.value.filter(a => a.type === 'low_stock'))

const products       = ref([])
const categories     = ref([])
const suppliers      = ref([])
const pagination     = ref({})
const loading        = ref(true)
const search         = ref('')
const filterCategory = ref('')
const filterLowStock = ref(false)
const highlightId    = ref(null)
const showModal      = ref(false)
const showStockInModal = ref(false)
const editingId      = ref(null)
const saving         = ref(false)
const formError      = ref('')
const stockInProduct = ref(null)

const imgInput    = useTemplateRef('imgInput')
const imageFile   = ref(null)
const imagePreview = ref('')

const emptyForm = () => ({ name: '', category_id: '', supplier_id: '', brand: '', model: '', color: '', size: '', cost_price: 0, selling_price: 0, stock_quantity: 0, reorder_point: 5, reorder_quantity: 20 })
const form = ref(emptyForm())

function onImageChange(e) {
  const file = e.target.files[0]
  if (!file) return
  imageFile.value   = file
  imagePreview.value = URL.createObjectURL(file)
}
function clearImage() {
  imageFile.value    = null
  imagePreview.value = ''
  if (imgInput.value) imgInput.value.value = ''
}
const stockInForm  = ref({ product_id: '', quantity: 1, unit_cost: null, reference_number: '', notes: '' })
const showAdjustModal = ref(false)
const adjustProduct   = ref(null)
const adjustForm      = ref({ type: 'adjustment', quantity: 1, reference_number: '', notes: '' })

const invStats = ref({})
const lowStockCount   = computed(() => invStats.value.low_stock   ?? 0)
const outOfStockCount = computed(() => invStats.value.out_of_stock ?? 0)
const stockValue      = computed(() => invStats.value.stock_value  ?? 0)

async function fetchStats() {
  try { const { data } = await api.get('/products/stats'); invStats.value = data } catch { /* */ }
}

function stockStripe(p) {
  if (p.stock_quantity === 0) return 'stripe-red'
  if (p.stock_quantity <= p.reorder_point) return 'stripe-amber'
  return 'stripe-teal'
}
function iconBg(p) {
  if (p.stock_quantity === 0) return 'icon-red'
  if (p.stock_quantity <= p.reorder_point) return 'icon-amber'
  return 'icon-teal'
}
function stockBarWidth(p) {
  if (!p.reorder_point) return '100%'
  const pct = Math.min((p.stock_quantity / (p.reorder_point * 3)) * 100, 100)
  return pct + '%'
}
function stockBarClass(p) {
  if (p.stock_quantity === 0) return 'bar-red'
  if (p.stock_quantity <= p.reorder_point) return 'bar-amber'
  return 'bar-teal'
}
function stockNumClass(p) {
  if (p.stock_quantity === 0) return 'num-red'
  if (p.stock_quantity <= p.reorder_point) return 'num-amber'
  return 'num-teal'
}
function stockStatusLabel(p) {
  if (p.stock_quantity === 0) return 'Out of stock'
  if (p.stock_quantity <= p.reorder_point) return 'Low stock'
  return 'In stock'
}
function stockStatusClass(p) {
  if (p.stock_quantity === 0) return 'status-red'
  if (p.stock_quantity <= p.reorder_point) return 'status-amber'
  return 'status-teal'
}

function clearFilters() { search.value = ''; filterCategory.value = ''; filterLowStock.value = false; fetchPage(1) }

let debounceTimer
function debouncedFetch() { clearTimeout(debounceTimer); debounceTimer = setTimeout(() => fetchPage(1), 400) }

async function fetchPage(page = 1) {
  loading.value = true
  try {
    const { data } = await api.get('/products', {
      params: { page, per_page: 16, search: search.value || undefined, category_id: filterCategory.value || undefined, low_stock: filterLowStock.value ? 1 : undefined }
    })
    products.value   = data.data
    pagination.value = { current_page: data.current_page, last_page: data.last_page, total: data.total }
  } catch { /* backend unavailable */ }
  finally { loading.value = false }
}

function openModal(product = null) {
  formError.value = ''
  clearImage()
  if (product) {
    editingId.value    = product.id
    form.value         = { ...product }
    imagePreview.value = product.image_url ?? ''
  } else {
    editingId.value = null
    form.value      = emptyForm()
  }
  showModal.value = true
}
function closeModal() { showModal.value = false; clearImage() }

async function saveProduct() {
  formError.value = ''; saving.value = true
  try {
    const payload = imageFile.value ? buildFormData() : form.value
    const headers = imageFile.value ? { 'Content-Type': 'multipart/form-data' } : {}

    if (editingId.value) {
      // Laravel doesn't parse files from PUT; spoof with POST + _method
      if (imageFile.value) {
        payload.append('_method', 'PUT')
        await api.post(`/products/${editingId.value}`, payload, { headers })
      } else {
        await api.put(`/products/${editingId.value}`, payload)
      }
    } else {
      await api.post('/products', payload, { headers })
    }
    closeModal(); fetchPage(pagination.value.current_page ?? 1); fetchStats()
  } catch (e) {
    formError.value = Object.values(e.response?.data?.errors ?? {}).flat().join(' ') || 'Failed to save.'
  } finally { saving.value = false }
}

function buildFormData() {
  const fd = new FormData()
  Object.entries(form.value).forEach(([k, v]) => {
    if (v !== null && v !== undefined) fd.append(k, v)
  })
  fd.append('image', imageFile.value)
  return fd
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
    showStockInModal.value = false; fetchPage(pagination.value.current_page ?? 1); fetchStats()
  } finally { saving.value = false }
}

function openAdjust(product) {
  adjustProduct.value = product
  adjustForm.value = { type: 'adjustment', quantity: 1, reference_number: '', notes: '' }
  showAdjustModal.value = true
}

async function doAdjust() {
  saving.value = true
  try {
    await api.post(`/products/${adjustProduct.value.id}/adjust`, adjustForm.value)
    showAdjustModal.value = false
    fetchPage(pagination.value.current_page ?? 1); fetchStats()
  } finally { saving.value = false }
}

function fmtAmt(v) { return Number(v || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }

async function activateHighlight(id) {
  highlightId.value    = id
  search.value         = ''
  filterCategory.value = ''
  filterLowStock.value = false
  loading.value = true
  try {
    const { data } = await api.get('/products', { params: { per_page: 16, highlight_id: id } })
    products.value   = data.data
    pagination.value = { current_page: data.current_page, last_page: data.last_page, total: data.total }
  } catch {}
  finally { loading.value = false }

  await nextTick()
  await new Promise(r => setTimeout(r, 80))
  const el = document.querySelector(`[data-product-id="${id}"]`)
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'center' })
    setTimeout(() => {
      highlightId.value = null
      router.replace({ path: route.path, query: {} })
    }, 950)
  }
}

watch(() => route.query.highlight, (newId) => {
  if (newId) activateHighlight(Number(newId))
})

onMounted(async () => {
  try {
    const [cats, sups] = await Promise.all([
      api.get('/product-categories'),
      api.get('/suppliers', { params: { per_page: 100 } }),
    ])
    categories.value = cats.data
    suppliers.value  = sups.data.data ?? sups.data
  } catch {}

  fetchStats()
  if (route.query.highlight) {
    activateHighlight(Number(route.query.highlight))
  } else {
    fetchPage()
  }
})
</script>

<style scoped>
.inv-page { padding: 28px 32px 48px; }

@keyframes card-blink {
  0%, 100% { box-shadow: 0 0 0 0 rgba(245,158,11,0); }
  50%       { box-shadow: 0 0 0 7px rgba(245,158,11,.9); background-color: rgba(255,251,235,.6); }
}
.card-highlighted {
  outline: 2px solid #f59e0b;
  animation: card-blink 0.3s ease-in-out 3;
  position: relative; z-index: 1;
}

/* ── Header ─────────────────────────────────────────── */
.page-header {
  display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;
}
.header-icon {
  width: 44px; height: 44px; border-radius: 12px;
  background: linear-gradient(135deg, #0d9488, #0f766e);
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.header-icon svg { color: white; }
.page-title { font-size: 20px; font-weight: 800; color: #111827; margin: 0; }
.page-sub   { font-size: 13px; color: #9ca3af; margin: 2px 0 0; }

.btn-add {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 10px 20px; background: linear-gradient(135deg, #0d9488, #0f766e);
  color: white; font-size: 13px; font-weight: 700; border-radius: 10px;
  border: none; cursor: pointer; transition: all 0.2s;
  box-shadow: 0 4px 12px rgba(13,148,136,0.35);
}
.btn-add:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(13,148,136,0.45); }

/* ── Stats ──────────────────────────────────────────── */
.stats-row {
  display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 20px;
}
.stat-icon svg { color: white; }
.stat-icon.teal  { background: #0d9488; }
.stat-icon.amber { background: #f59e0b; }
.stat-icon.red   { background: #ef4444; }
.stat-icon.green { background: #10b981; }
.stat-num { font-size: 22px; font-weight: 800; color: #111827; line-height: 1; }
.stat-lbl { font-size: 11px; font-weight: 600; color: #9ca3af; margin-top: 3px; }

/* ── Alerts ─────────────────────────────────────────── */
.alerts-wrap { display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px; }
.alert-banner {
  display: flex; align-items: center; gap: 14px;
  padding: 14px 18px; border-radius: 12px; border: 1.5px solid;
}
.alert-banner.alert-red   { background: #fef2f2; border-color: #fecaca; }
.alert-banner.alert-amber { background: #fffbeb; border-color: #fde68a; }
.alert-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.alert-dot.red   { background: #ef4444; box-shadow: 0 0 0 3px #fee2e2; }
.alert-dot.amber { background: #f59e0b; box-shadow: 0 0 0 3px #fef3c7; }
.alert-title { font-size: 13px; font-weight: 700; color: #111827; }
.alert-body  { font-size: 12px; color: #6b7280; margin-top: 2px; }
.alert-action {
  flex-shrink: 0; font-size: 12px; font-weight: 700; padding: 6px 14px;
  border-radius: 8px; border: none; cursor: pointer; transition: all 0.2s;
}
.alert-action.red   { background: #fee2e2; color: #991b1b; }
.alert-action.red:hover   { background: #fecaca; }
.alert-action.amber { background: #fef3c7; color: #92400e; }
.alert-action.amber:hover { background: #fde68a; }

/* ── Filters ────────────────────────────────────────── */
.filter-bar {
  display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
  background: white; border: 1.5px solid #f3f4f6; border-radius: 14px;
  padding: 16px 20px; margin-bottom: 24px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}
.filter-field {
  display: flex; align-items: center; gap: 8px;
  background: #f9fafb; border: 1.5px solid #e5e7eb; border-radius: 10px; padding: 0 12px;
}
.filter-icon  { color: #9ca3af; flex-shrink: 0; }
.filter-input {
  padding: 9px 4px; background: transparent; border: none; outline: none;
  font-size: 13px; font-weight: 500; color: #374151; font-family: inherit; min-width: 120px;
}

/* Toggle */
.toggle-wrap { display: flex; align-items: center; gap: 8px; cursor: pointer; }
.toggle-cb   { display: none; }
.toggle-track {
  width: 36px; height: 20px; border-radius: 99px; background: #e5e7eb;
  position: relative; transition: background 0.2s; flex-shrink: 0;
}
.toggle-track.on  { background: #0d9488; }
.toggle-thumb {
  position: absolute; top: 3px; left: 3px;
  width: 14px; height: 14px; border-radius: 50%; background: white;
  transition: transform 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}
.toggle-track.on .toggle-thumb { transform: translateX(16px); }
.toggle-label { font-size: 13px; font-weight: 600; color: #374151; }

.clear-btn {
  padding: 9px 16px; background: #f0fdfa; color: #0d9488;
  font-size: 12px; font-weight: 700; border: none; border-radius: 8px; cursor: pointer;
}
.clear-btn:hover { background: #ccfbf1; }

/* ── Product Grid ───────────────────────────────────── */
.products-grid {
  display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; margin-bottom: 28px;
}

.product-card {
  background: white; border-radius: 14px; border: 1.5px solid #f3f4f6;
  box-shadow: 0 1px 6px rgba(0,0,0,0.05); overflow: hidden;
  display: flex; flex-direction: columns; flex-direction: column; padding: 0;
  transition: box-shadow 0.2s, transform 0.2s;
}
.product-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,0.1); transform: translateY(-2px); }

.card-stripe { height: 5px; width: 100%; flex-shrink: 0; }
.stripe-teal  { background: linear-gradient(90deg, #0d9488, #2dd4bf); }
.stripe-amber { background: linear-gradient(90deg, #f59e0b, #fcd34d); }
.stripe-red   { background: linear-gradient(90deg, #ef4444, #fca5a5); }

/* Product image area */
.product-img-area {
  position: relative; width: 100%; height: 140px;
  overflow: hidden; flex-shrink: 0;
}
.product-img {
  width: 100%; height: 100%; object-fit: cover;
}
.product-img-placeholder {
  width: 100%; height: 100%;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 6px;
}
.product-img-placeholder svg { color: rgba(255,255,255,0.75); }
.placeholder-label { font-size: 11px; font-weight: 600; color: rgba(255,255,255,0.75); }
.icon-teal  { background: linear-gradient(135deg, #0d9488, #0f766e); }
.icon-amber { background: linear-gradient(135deg, #f59e0b, #d97706); }
.icon-red   { background: linear-gradient(135deg, #ef4444, #dc2626); }

.sku-badge-overlay {
  position: absolute; bottom: 8px; right: 8px;
  font-size: 10px; font-family: monospace; font-weight: 700;
  background: rgba(0,0,0,0.55); color: #fff;
  padding: 3px 8px; border-radius: 6px; backdrop-filter: blur(4px);
}

.product-name  { font-size: 14px; font-weight: 700; color: #111827; padding: 12px 16px 2px; }
.product-brand { font-size: 11px; color: #9ca3af; padding: 0 16px 8px; }

.category-badge {
  display: inline-block; font-size: 11px; font-weight: 600;
  background: #f0fdfa; color: #0d9488; padding: 3px 10px;
  border-radius: 99px; margin: 0 16px 12px;
}

.price-row {
  display: flex; justify-content: space-between; align-items: flex-end;
  padding: 0 16px 14px; border-bottom: 1.5px solid #f3f4f6;
}
.price-label  { font-size: 10px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; }
.price-val    { font-size: 13px; font-weight: 700; color: #374151; margin-top: 2px; }
.price-val.selling { color: #0d9488; }

.stock-section { padding: 12px 16px 14px; }
.stock-row   { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
.stock-label { font-size: 11px; font-weight: 600; color: #9ca3af; }
.stock-num   { font-size: 18px; font-weight: 800; }
.num-teal    { color: #0d9488; }
.num-amber   { color: #f59e0b; }
.num-red     { color: #ef4444; }

.stock-bar-bg   { height: 6px; background: #f3f4f6; border-radius: 99px; overflow: hidden; }
.stock-bar-fill { height: 100%; border-radius: 99px; transition: width 0.4s ease; }
.bar-teal  { background: #0d9488; }
.bar-amber { background: #f59e0b; }
.bar-red   { background: #ef4444; }

.stock-rop { font-size: 11px; color: #9ca3af; }
.stock-status { font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 99px; }
.status-teal  { background: #f0fdfa; color: #0d9488; }
.status-amber { background: #fffbeb; color: #92400e; }
.status-red   { background: #fef2f2; color: #991b1b; }

.card-actions {
  display: flex; gap: 8px; padding: 12px 16px 16px; margin-top: auto;
}
.action-btn {
  flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 5px;
  padding: 9px 12px; border-radius: 9px; border: none; cursor: pointer;
  font-size: 12px; font-weight: 700; font-family: inherit; transition: all 0.15s;
}
.btn-stockin { background: #f0fdfa; color: #0d9488; }
.btn-stockin:hover { background: #ccfbf1; }
.btn-adjust  { background: #fff7ed; color: #c2410c; }
.btn-adjust:hover  { background: #ffedd5; }
.btn-edit    { background: #f3f4f6; color: #374151; }
.btn-edit:hover    { background: #e5e7eb; }

/* ── Empty State ─────────────────────────────────────── */
.empty-state {
  text-align: center; padding: 60px 20px;
  background: white; border: 1.5px solid #f3f4f6; border-radius: 14px;
  margin-bottom: 28px;
}
.empty-icon { color: #d1d5db; margin: 0 auto 12px; }
.empty-text { font-size: 15px; font-weight: 700; color: #374151; margin: 0; }
.empty-sub  { font-size: 13px; color: #9ca3af; margin-top: 6px; }

/* ── Pagination ──────────────────────────────────────── */
.pagination { display: flex; align-items: center; justify-content: center; gap: 12px; }
.page-btn {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 10px 18px; background: white; border: 1.5px solid #e5e7eb;
  border-radius: 10px; font-size: 13px; font-weight: 600; color: #374151;
  cursor: pointer; transition: all 0.2s; font-family: inherit;
}
.page-btn:hover:not(:disabled) { border-color: #0d9488; color: #0d9488; background: #f0fdfa; }
.page-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.page-info { font-size: 13px; color: #6b7280; font-weight: 500; }

/* ── Modals ──────────────────────────────────────────── */
.modal-backdrop {
  position: fixed; inset: 0; z-index: 9999;
  background: rgba(0,0,0,0.5);
  display: flex; align-items: center; justify-content: center;
  padding: 16px; overflow-y: auto;
}
.modal-box {
  background: white; border-radius: 16px;
  box-shadow: 0 24px 64px rgba(0,0,0,0.18);
  width: 100%; margin: auto;
}
.modal-md { max-width: 480px; }
.modal-lg { max-width: 680px; }

.modal-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 20px 24px; border-bottom: 1.5px solid #f3f4f6;
}
.modal-icon {
  width: 36px; height: 36px; border-radius: 10px;
  background: linear-gradient(135deg, #0d9488, #0f766e);
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.modal-icon svg { color: white; }
.modal-icon.green { background: linear-gradient(135deg, #10b981, #059669); }
.modal-title { font-size: 16px; font-weight: 700; color: #111827; margin: 0; }
.modal-sub   { font-size: 12px; color: #9ca3af; margin-top: 2px; }
.modal-close {
  color: #9ca3af; background: none; border: none; cursor: pointer;
  padding: 4px; border-radius: 6px; transition: color 0.2s;
}
.modal-close:hover { color: #374151; }
.modal-body   { padding: 24px; }
.modal-footer {
  display: flex; justify-content: flex-end; gap: 10px;
  margin-top: 24px; padding-top: 16px; border-top: 1.5px solid #f3f4f6;
}

.section-divider {
  font-size: 11px; font-weight: 700; color: #0d9488; text-transform: uppercase;
  letter-spacing: 1px; border-bottom: 1.5px solid #ccfbf1; padding-bottom: 6px;
  margin: 20px 0 16px;
}

/* Form */
.form-grid   { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.form-grid-1 { display: flex; flex-direction: column; gap: 14px; }
.fg          { display: flex; flex-direction: column; gap: 6px; }
.fg--full    { grid-column: 1 / -1; }
.fl          { font-size: 12px; font-weight: 700; color: #374151; }
.fi {
  padding: 10px 13px; border: 1.5px solid #e5e7eb; border-radius: 10px;
  font-family: inherit; font-size: 13px; color: #111827;
  background: #fff; outline: none; width: 100%; transition: border-color 0.2s;
}
.fi:focus { border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13,148,136,0.1); }
.fi--ta   { resize: vertical; min-height: 70px; }

/* Image upload zone */
.img-upload-zone {
  border: 2px dashed #e5e7eb; border-radius: 12px;
  cursor: pointer; overflow: hidden; transition: border-color 0.2s, background 0.2s;
  background: #f9fafb; min-height: 130px;
  display: flex; align-items: center; justify-content: center;
}
.img-upload-zone:hover { border-color: #0d9488; background: #f0fdfa; }
.img-upload-zone.has-img { border-style: solid; border-color: #0d9488; background: #000; }
.img-preview  { width: 100%; max-height: 220px; object-fit: contain; display: block; }
.img-upload-placeholder {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 6px; padding: 24px; color: #9ca3af; text-align: center;
}
.upload-label { font-size: 13px; font-weight: 600; color: #374151; margin: 0; }
.upload-hint  { font-size: 11px; color: #9ca3af; margin: 0; }
.img-remove-btn {
  margin-top: 8px; padding: 5px 14px; background: #fef2f2; color: #dc2626;
  border: 1.5px solid #fecaca; border-radius: 8px; font-size: 12px; font-weight: 700;
  cursor: pointer; font-family: inherit; transition: background 0.2s;
}
.img-remove-btn:hover { background: #fee2e2; }

.error-msg {
  margin-top: 12px; padding: 10px 14px;
  background: #fef2f2; border: 1px solid #fecaca;
  border-radius: 8px; font-size: 13px; color: #dc2626;
}
.btn-cancel {
  padding: 10px 20px; background: #f9fafb; border: 1.5px solid #e5e7eb;
  border-radius: 10px; font-size: 13px; font-weight: 600; color: #374151;
  cursor: pointer; transition: all 0.2s; font-family: inherit;
}
.btn-cancel:hover { background: #f3f4f6; }
.btn-save {
  padding: 10px 24px; background: linear-gradient(135deg, #0d9488, #0f766e);
  color: white; border: none; border-radius: 10px;
  font-size: 13px; font-weight: 700; cursor: pointer;
  transition: all 0.2s; font-family: inherit;
}
.btn-save:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(13,148,136,0.4); }
.btn-save:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-save.green-btn  { background: linear-gradient(135deg, #10b981, #059669); }
.btn-save.green-btn:hover:not(:disabled)  { box-shadow: 0 4px 12px rgba(16,185,129,0.4); }
.btn-save.orange-btn { background: linear-gradient(135deg, #f97316, #ea580c); }
.btn-save.orange-btn:hover:not(:disabled) { box-shadow: 0 4px 12px rgba(249,115,22,0.4); }

/* Adjust modal extras */
.mh-icon {
  width: 36px; height: 36px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.mh-icon.orange { background: linear-gradient(135deg, #f97316, #ea580c); color: #fff; }
.fi-hint { font-size: 11px; color: #9ca3af; margin-top: 2px; }
.adjust-info-row {
  display: flex; align-items: flex-start; gap: 8px;
  padding: 10px 12px; border-radius: 9px; font-size: 12px; font-weight: 500;
}
.adjust-info-row.adjustment { background: #eff6ff; color: #1d4ed8; }
.adjust-info-row.damage     { background: #fff7ed; color: #c2410c; }
.adjust-info-row.loss       { background: #fef2f2; color: #b91c1c; }
.adjust-info-row svg        { flex-shrink: 0; margin-top: 1px; }

/* Responsive */
@media (max-width: 1300px) { .products-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 1000px) { .products-grid { grid-template-columns: repeat(2, 1fr); } .stats-row { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 640px)  { .products-grid { grid-template-columns: 1fr; } .form-grid { grid-template-columns: 1fr; } }
</style>
