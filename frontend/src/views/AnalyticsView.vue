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
        <p class="text-xs text-gray-400 mt-1">ARIMA(1,1,1) on 24-week history — AR via Yule-Walker, MA via CSS, with 95% CI</p>
      </div>
      <div class="card">
        <p class="text-xs font-semibold text-gray-500 uppercase">EOQ</p>
        <p class="text-sm text-gray-700 mt-1">Economic Order Quantity</p>
        <p class="text-xs text-gray-400 mt-1">√(2DS/H) — minimises total ordering + holding cost</p>
      </div>
      <div class="card">
        <p class="text-xs font-semibold text-gray-500 uppercase">ROP</p>
        <p class="text-sm text-gray-700 mt-1">Reorder Point</p>
        <p class="text-xs text-gray-400 mt-1">ROP = d̄·L + Z·σ·√L (95% service level, 7-day lead time)</p>
      </div>
      <div class="card">
        <p class="text-xs font-semibold text-gray-500 uppercase">FSN</p>
        <p class="text-sm text-gray-700 mt-1">Fast-Slow-Non Moving</p>
        <p class="text-xs text-gray-400 mt-1">Activity-based: active weeks ratio over 24-week review window</p>
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

      <!-- Shared product selector for per-product charts -->
      <div class="flex items-center gap-3 bg-blue-50 rounded-xl px-4 py-2.5">
        <svg class="w-4 h-4 text-blue-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        <span class="text-xs text-blue-600 font-medium">Product for ARIMA / EOQ / ROP charts:</span>
        <select
          v-model="selectedForecastProduct"
          class="text-xs border border-blue-200 rounded-lg px-2 py-1 text-blue-700 bg-white shadow-sm focus:outline-none focus:ring-1 focus:ring-blue-400 ml-auto">
          <option v-for="r in results" :key="r.product.id" :value="r.product.id">{{ r.product.name }}</option>
        </select>
      </div>

      <!-- Row 1: ARIMA Forecast + FSN Activity -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- FSN Activity Analysis -->
        <div class="card flex flex-col">
          <h3 class="text-sm font-semibold text-gray-800 mb-1">FSN Activity Analysis</h3>
          <p class="text-xs text-gray-400 mb-3">% of the last 24 weeks with sales</p>
          <div class="flex-1" style="min-height:220px">
            <Bar :data="fsnActivityData" :options="fsnActivityOptions" />
          </div>
        </div>

        <!-- ARIMA Forecasting Line Chart -->
        <div class="card lg:col-span-2">
          <div class="mb-3">
            <h3 class="text-sm font-semibold text-gray-800">ARIMA(1,1,1) Demand Forecast</h3>
            <p class="text-xs text-gray-400 mt-0.5">Historical weekly sales (solid) → 4-week forecast (dashed) with 95% CI band</p>
          </div>
          <div class="flex items-center gap-5 mb-3 text-xs text-gray-500">
            <span class="flex items-center gap-1.5">
              <span class="inline-block w-6 h-0.5 bg-blue-500 rounded"></span>Actual
            </span>
            <span class="flex items-center gap-1.5">
              <span class="inline-block w-6 h-0 border-t-2 border-dashed border-orange-400"></span>Forecast
            </span>
            <span class="flex items-center gap-1.5">
              <span class="inline-block w-5 h-3 rounded" style="background:rgba(249,115,22,0.15);border:1px dashed rgba(249,115,22,0.4)"></span>95% CI
            </span>
          </div>
          <div style="height:230px">
            <Line :data="forecastLineData" :options="forecastLineOptions" />
          </div>
        </div>
      </div>

      <!-- Row 2: EOQ Cost Curve + ROP Inventory Simulation -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- EOQ Cost Curve -->
        <div class="card">
          <div class="mb-3">
            <h3 class="text-sm font-semibold text-gray-800">EOQ Cost Curve</h3>
            <p class="text-xs text-gray-400 mt-0.5">EOQ = √(2·D·S/H) — minimises total annual cost</p>
          </div>
          <div style="height:260px">
            <Line :data="eoqCurveData" :options="eoqCurveOptions" />
          </div>
        </div>

        <!-- ROP Inventory Simulation -->
        <div class="card">
          <div class="mb-3">
            <h3 class="text-sm font-semibold text-gray-800">ROP Inventory Simulation</h3>
            <p class="text-xs text-gray-400 mt-0.5">120-day stock-level forecast showing reorder cycles</p>
          </div>
          <div class="flex items-center gap-5 mb-2 text-xs text-gray-500">
            <span class="flex items-center gap-1.5"><span class="inline-block w-5 h-2 rounded-sm bg-blue-200"></span>Stock</span>
            <span class="flex items-center gap-1.5"><span class="inline-block w-5 h-0 border-t-2 border-dashed border-orange-400"></span>ROP</span>
            <span class="flex items-center gap-1.5"><span class="inline-block w-5 h-0 border-t-2 border-dashed border-red-400"></span>Safety Stock</span>
          </div>
          <div style="height:240px">
            <Line :data="ropSimData" :options="ropSimOptions" />
          </div>
        </div>
      </div>

      <!-- Row 3: Stock Runway (days until stockout) -->
      <div class="card">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 class="text-sm font-semibold text-gray-800">Stock Runway — Days Until Stockout</h3>
            <p class="text-xs text-gray-400 mt-0.5">Based on ARIMA avg daily demand forecast</p>
          </div>
          <div class="flex items-center gap-4 text-xs text-gray-500">
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-red-400 inline-block"></span>&lt; 14 days</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-yellow-400 inline-block"></span>14–30 days</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-green-500 inline-block"></span>&gt; 30 days</span>
          </div>
        </div>
        <div :style="{ height: eoqChartHeight }">
          <Bar :data="stockRunwayData" :options="{ ...eoqRopOptions, plugins: { ...eoqRopOptions.plugins, legend: { display: false } } }" />
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
                <span class="text-xs text-blue-500">
                  {{ selected.analytics?.result_data?.used_fallback ? 'Exponential Smoothing (fallback — insufficient history)' : 'ARIMA(1,1,1) Demand Forecast' }}
                </span>
              </div>
              <div class="p-4 space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500">Sales last 90 days</span>
                  <span class="font-semibold">{{ selected.analytics?.result_data?.sales_90d ?? '—' }} units</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500">Avg daily demand (d̄)</span>
                  <span class="font-semibold">{{ Number(selected.analytics?.result_data?.avg_daily ?? 0).toFixed(4) }} units/day</span>
                </div>
                <template v-if="!selected.analytics?.result_data?.used_fallback">
                  <div class="flex justify-between text-sm">
                    <span class="text-gray-500">AR parameter (φ₁)</span>
                    <span class="font-semibold font-mono text-blue-700">{{ selected.analytics?.result_data?.ar_param ?? '—' }}</span>
                  </div>
                  <div class="flex justify-between text-sm">
                    <span class="text-gray-500">MA parameter (θ₁)</span>
                    <span class="font-semibold font-mono text-blue-700">{{ selected.analytics?.result_data?.ma_param ?? '—' }}</span>
                  </div>
                </template>
                <div class="flex justify-between text-sm border-t pt-2 mt-1">
                  <span class="text-gray-700 font-medium">Predicted next 30 days</span>
                  <span class="font-bold text-blue-700">{{ selected.analytics?.predicted_demand ?? '—' }} units</span>
                </div>
                <div class="flex justify-between text-xs text-gray-400">
                  <span>95% Confidence Interval</span>
                  <span>{{ selected.analytics?.result_data?.conf_lower_30d ?? '—' }} – {{ selected.analytics?.result_data?.conf_upper_30d ?? '—' }} units</span>
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
                <span class="text-xs text-purple-500">Economic Order Quantity — √(2·D·S / H)</span>
              </div>
              <div class="p-4 space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500">Annual demand (D)</span>
                  <span class="font-semibold">{{ Math.round(selected.analytics?.result_data?.annual_demand ?? (selected.analytics?.result_data?.avg_daily ?? 0) * 365) }} units/yr</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500">Order cost (S)</span>
                  <span class="font-semibold">₱{{ selected.analytics?.result_data?.order_cost ?? 500 }}.00 / order</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500">Holding cost (H = 20% of cost)</span>
                  <span class="font-semibold">₱{{ selected.analytics?.result_data?.holding_cost ?? holdingCost(selected) }} / unit / yr</span>
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
                <span class="text-xs text-orange-500">Reorder Point — d̄·L + Z·σ·√L</span>
              </div>
              <div class="p-4 space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500">Avg daily demand (d̄)</span>
                  <span class="font-semibold">{{ Number(selected.analytics?.result_data?.avg_daily ?? 0).toFixed(4) }} units/day</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500">Demand std dev (σ)</span>
                  <span class="font-semibold">{{ Number(selected.analytics?.result_data?.std_daily ?? 0).toFixed(4) }} units/day</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500">Lead time (L)</span>
                  <span class="font-semibold">{{ selected.analytics?.result_data?.lead_time ?? 7 }} days</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500">Service level (Z = 1.645)</span>
                  <span class="font-semibold">{{ selected.analytics?.result_data?.service_level ?? '95%' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500">Safety stock (Z·σ·√L)</span>
                  <span class="font-semibold text-orange-500">{{ selected.analytics?.result_data?.safety_stock ?? '—' }} units</span>
                </div>
                <div class="flex justify-between text-sm border-t pt-2 mt-1">
                  <span class="text-gray-700 font-medium">ROP = d̄·L + SS</span>
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
                  <span class="text-gray-500">Active weeks (last 24 weeks)</span>
                  <span class="font-semibold">{{ selected.analytics?.result_data?.active_weeks ?? '—' }} / {{ selected.analytics?.result_data?.total_weeks ?? 24 }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500">Activity ratio</span>
                  <span class="font-semibold">{{ ((selected.analytics?.result_data?.activity_ratio ?? 0) * 100).toFixed(1) }}%</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500">Last sale date</span>
                  <span class="font-semibold">{{ selected.analytics?.result_data?.last_sale_date ?? 'No sales recorded' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500">Avg daily demand</span>
                  <span class="font-semibold">{{ Number(selected.analytics?.result_data?.avg_daily ?? 0).toFixed(4) }} units/day</span>
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
import { ref, computed, onMounted, watch } from 'vue'
import { Bar, Line } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale, LinearScale, BarElement,
  LineElement, PointElement, Filler,
  Title, Tooltip, Legend
} from 'chart.js'
import api from '@/services/api'

ChartJS.register(
  CategoryScale, LinearScale, BarElement,
  LineElement, PointElement, Filler,
  Title, Tooltip, Legend
)

const results                 = ref([])
const summary                 = ref(null)
const running                 = ref(false)
const computedAt              = ref('')
const selected                = ref(null)
const selectedForecastProduct = ref(null)

watch(results, (val) => {
  if (val.length && selectedForecastProduct.value === null) {
    selectedForecastProduct.value = val[0].product.id
  }
})

function openDetail(r) { selected.value = r }

function fsnLabel(cls) {
  return { fast: 'Fast Moving', slow: 'Slow Moving', non_moving: 'Non-Moving' }[cls] ?? '—'
}

function fsnDescription(cls) {
  return {
    fast:       'Active in ≥50% of the last 24 weeks or ≥1 unit/day average. Prioritise replenishment and maintain higher safety stock.',
    slow:       'Active in 10–50% of the last 24 weeks. Monitor levels regularly and avoid over-ordering.',
    non_moving: 'Active in <10% of the last 24 weeks or no sale in ≥6 months. Review for dead stock — consider promotions or clearance.',
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


const eoqChartHeight = computed(() => Math.max(260, results.value.length * 36) + 'px')

// ── ARIMA Forecast Line Chart ───────────────────────────────────────

const selectedForecastRow = computed(() =>
  results.value.find(r => r.product.id === selectedForecastProduct.value) ?? null
)

const forecastLineData = computed(() => {
  const r = selectedForecastRow.value
  if (!r?.analytics?.result_data) return { labels: [], datasets: [] }

  const rd            = r.analytics.result_data
  const weeklySeries  = rd.weekly_series  ?? []
  const forecastWeeks = rd.forecast_weekly ?? []
  const histSlice     = weeklySeries.slice(-12)
  const histLen       = histSlice.length
  const fLen          = forecastWeeks.length
  const lastHist      = histLen > 0 ? histSlice[histLen - 1] : 0

  const labels = []
  for (let i = histLen - 1; i >= 0; i--) labels.push(i === 0 ? 'Now' : `−${i}w`)
  for (let i = 1; i <= fLen; i++)        labels.push(`+${i}w`)

  // CI margin grows with forecast horizon: base × √h
  const halfRange  = ((rd.conf_upper_30d ?? 0) - (rd.conf_lower_30d ?? 0)) / 2
  const weeklyBase = halfRange / Math.max(1, Math.sqrt(fLen))
  const upperCI    = forecastWeeks.map((v, i) => v + weeklyBase * Math.sqrt(i + 1))
  const lowerCI    = forecastWeeks.map((v, i) => Math.max(0, v - weeklyBase * Math.sqrt(i + 1)))

  const nullPad = Array(histLen - 1).fill(null)

  return {
    labels,
    datasets: [
      {
        label: 'Actual (weekly)',
        data: [...histSlice, ...Array(fLen).fill(null)],
        borderColor: '#3b82f6',
        backgroundColor: 'rgba(59,130,246,0.07)',
        borderWidth: 2.5,
        pointRadius: histSlice.map((_, i) => i === histLen - 1 ? 6 : 3),
        pointBackgroundColor: '#3b82f6',
        pointHoverRadius: 5,
        tension: 0.35,
        fill: false,
        order: 3,
      },
      {
        label: 'ARIMA Forecast',
        data: [...nullPad, lastHist, ...forecastWeeks],
        borderColor: '#f97316',
        borderWidth: 2.5,
        borderDash: [7, 4],
        pointRadius: [...Array(histLen - 1).fill(0), 0, ...Array(fLen).fill(5)],
        pointBackgroundColor: '#f97316',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        pointHoverRadius: 6,
        tension: 0.35,
        fill: false,
        order: 2,
      },
      {
        label: '95% CI Upper',
        data: [...nullPad, lastHist, ...upperCI],
        borderColor: 'rgba(249,115,22,0.25)',
        borderWidth: 1,
        borderDash: [3, 3],
        backgroundColor: 'rgba(249,115,22,0.12)',
        pointRadius: 0,
        tension: 0.35,
        fill: '+1',
        order: 1,
      },
      {
        label: '95% CI Lower',
        data: [...nullPad, lastHist, ...lowerCI],
        borderColor: 'rgba(249,115,22,0.25)',
        borderWidth: 1,
        borderDash: [3, 3],
        backgroundColor: 'transparent',
        pointRadius: 0,
        tension: 0.35,
        fill: false,
        order: 0,
      },
    ],
  }
})

const forecastLineOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top',
      labels: {
        font: { size: 11 },
        boxWidth: 14,
        padding: 12,
        filter: item => !item.text.startsWith('95% CI'),
      },
    },
    tooltip: {
      mode: 'index',
      intersect: false,
      callbacks: {
        label: ctx => {
          if (ctx.parsed.y === null) return null
          return ` ${ctx.dataset.label}: ${Number(ctx.parsed.y).toFixed(1)} units`
        },
      },
    },
  },
  scales: {
    x: {
      ticks: { font: { size: 10 } },
      grid: { color: '#f3f4f6' },
    },
    y: {
      beginAtZero: true,
      ticks: { font: { size: 10 } },
      grid: { color: '#f3f4f6' },
      title: { display: true, text: 'Units / week', font: { size: 10 }, color: '#9ca3af' },
    },
  },
}

// ── Stock Runway Chart ─────────────────────────────────────────────

const stockRunwayData = computed(() => ({
  labels: productLabels.value,
  datasets: [{
    label: 'Days of Stock Remaining',
    data: results.value.map(r => {
      const avg = r.analytics?.result_data?.avg_daily ?? 0
      return avg > 0 ? Math.min(365, Math.round(r.product.stock_quantity / avg)) : 365
    }),
    backgroundColor: results.value.map(r => {
      const avg  = r.analytics?.result_data?.avg_daily ?? 0
      const days = avg > 0 ? r.product.stock_quantity / avg : 999
      if (days < 14) return 'rgba(239,68,68,0.75)'
      if (days < 30) return 'rgba(234,179,8,0.75)'
      return 'rgba(34,197,94,0.75)'
    }),
    borderColor: results.value.map(r => {
      const avg  = r.analytics?.result_data?.avg_daily ?? 0
      const days = avg > 0 ? r.product.stock_quantity / avg : 999
      if (days < 14) return '#ef4444'
      if (days < 30) return '#ca8a04'
      return '#16a34a'
    }),
    borderWidth: 1,
    borderRadius: 4,
  }],
}))

// ── EOQ Cost Curve ─────────────────────────────────────────────────

const eoqCurveData = computed(() => {
  const r = selectedForecastRow.value
  if (!r?.analytics?.result_data) return { labels: [], datasets: [] }

  const rd  = r.analytics.result_data
  const D   = rd.annual_demand ?? 0
  const S   = rd.order_cost   ?? 500
  const H   = rd.holding_cost ?? 1
  const eoq = parseFloat(r.analytics.eoq_value ?? 0)

  if (!D || !H || !eoq) return { labels: [], datasets: [] }

  const maxQ  = Math.ceil(eoq * 3)
  const steps = 60
  const qty   = Array.from({ length: steps }, (_, i) => Math.max(1, Math.round(maxQ * (i + 1) / steps)))

  const orderCosts = qty.map(q => (D * S) / q)
  const holdCosts  = qty.map(q => (H * q) / 2)
  const totalCosts = qty.map((_, i) => orderCosts[i] + holdCosts[i])

  // Find the label index closest to EOQ to place the marker
  const eoqIdx   = qty.reduce((b, q, i) => Math.abs(q - eoq) < Math.abs(qty[b] - eoq) ? i : b, 0)
  const eoqMarker = Array(steps).fill(null)
  eoqMarker[eoqIdx] = totalCosts[eoqIdx]

  return {
    labels: qty.map(String),
    datasets: [
      {
        label: 'Total Cost  TC = D·S/Q + H·Q/2',
        data: totalCosts,
        borderColor: '#3b82f6',
        borderWidth: 2.5,
        pointRadius: 0,
        tension: 0.3,
        fill: false,
        order: 3,
      },
      {
        label: 'Ordering Cost  D·S/Q',
        data: orderCosts,
        borderColor: '#f97316',
        borderWidth: 1.5,
        borderDash: [6, 4],
        pointRadius: 0,
        tension: 0.15,
        fill: false,
        order: 2,
      },
      {
        label: 'Holding Cost  H·Q/2',
        data: holdCosts,
        borderColor: '#22c55e',
        borderWidth: 1.5,
        borderDash: [6, 4],
        pointRadius: 0,
        tension: 0.15,
        fill: false,
        order: 1,
      },
      {
        label: `EOQ = ${Math.round(eoq)} units`,
        data: eoqMarker,
        borderColor: '#7c3aed',
        backgroundColor: '#7c3aed',
        borderWidth: 0,
        pointRadius: eoqMarker.map(v => v !== null ? 9 : 0),
        pointHoverRadius: 11,
        showLine: false,
        order: 4,
      },
    ],
  }
})

const eoqCurveOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top',
      labels: { font: { size: 10 }, boxWidth: 14, padding: 10 },
    },
    tooltip: {
      mode: 'index',
      intersect: false,
      callbacks: {
        title: ctx => `Order Qty: ${ctx[0]?.label} units`,
        label: ctx => ctx.parsed.y !== null
          ? ` ${ctx.dataset.label}: ₱${Number(ctx.parsed.y).toFixed(2)}`
          : null,
      },
    },
  },
  scales: {
    x: {
      ticks: { font: { size: 9 }, maxTicksLimit: 10 },
      grid: { color: '#f3f4f6' },
      title: { display: true, text: 'Order Quantity (units)', font: { size: 10 }, color: '#9ca3af' },
    },
    y: {
      beginAtZero: true,
      ticks: { font: { size: 9 }, callback: v => '₱' + Number(v).toLocaleString('en-PH') },
      grid: { color: '#f3f4f6' },
      title: { display: true, text: 'Annual Cost (₱)', font: { size: 10 }, color: '#9ca3af' },
    },
  },
}

// ── ROP Inventory Simulation ────────────────────────────────────────

const ropSimData = computed(() => {
  const r = selectedForecastRow.value
  if (!r?.analytics?.result_data) return { labels: [], datasets: [] }

  const rd       = r.analytics.result_data
  const avgDaily = rd.avg_daily    ?? 0
  const rop      = parseFloat(r.analytics.rop_value ?? 0)
  const ss       = rd.safety_stock ?? 0
  const eoq      = parseFloat(r.analytics.eoq_value ?? 1) || 1
  const lt       = rd.lead_time    ?? 7
  let   stock    = r.product.stock_quantity ?? 0

  if (!avgDaily) return { labels: [], datasets: [] }

  // Simulate 120 days (sawtooth inventory pattern)
  const simDays   = 120
  const stockData = []
  let   pendingOrder = null   // day when restocking arrives

  for (let d = 0; d <= simDays; d++) {
    if (pendingOrder !== null && d === pendingOrder) {
      stock += eoq
      pendingOrder = null
    }
    stockData.push(Math.max(0, stock))
    if (stock <= rop && pendingOrder === null) pendingOrder = d + lt
    stock -= avgDaily
  }

  const labels = Array.from({ length: simDays + 1 }, (_, i) => `D${i}`)

  return {
    labels,
    datasets: [
      {
        label: 'Stock Level',
        data: stockData,
        borderColor: '#3b82f6',
        backgroundColor: 'rgba(59,130,246,0.08)',
        borderWidth: 2,
        pointRadius: 0,
        pointHoverRadius: 4,
        tension: 0,
        fill: true,
        order: 3,
      },
      {
        label: `ROP (${Math.round(rop)} units)`,
        data: Array(simDays + 1).fill(rop),
        borderColor: '#f97316',
        borderWidth: 2,
        borderDash: [6, 4],
        pointRadius: 0,
        tension: 0,
        fill: false,
        order: 2,
      },
      {
        label: `Safety Stock (${Math.round(ss)} units)`,
        data: Array(simDays + 1).fill(ss),
        borderColor: '#ef4444',
        borderWidth: 1.5,
        borderDash: [3, 3],
        pointRadius: 0,
        tension: 0,
        fill: false,
        order: 1,
      },
    ],
  }
})

const ropSimOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top',
      labels: { font: { size: 10 }, boxWidth: 14, padding: 10 },
    },
    tooltip: {
      mode: 'index',
      intersect: false,
      callbacks: {
        title: ctx => `Day ${ctx[0]?.dataIndex}`,
        label: ctx => ctx.parsed.y !== null
          ? ` ${ctx.dataset.label}: ${Number(ctx.parsed.y).toFixed(1)} units`
          : null,
      },
    },
  },
  scales: {
    x: {
      ticks: { font: { size: 9 }, maxTicksLimit: 13 },
      grid: { color: '#f3f4f6' },
      title: { display: true, text: 'Days', font: { size: 10 }, color: '#9ca3af' },
    },
    y: {
      beginAtZero: true,
      ticks: { font: { size: 9 } },
      grid: { color: '#f3f4f6' },
      title: { display: true, text: 'Stock Level (units)', font: { size: 10 }, color: '#9ca3af' },
    },
  },
}

// ── FSN Activity Analysis ───────────────────────────────────────────

const fsnActivityData = computed(() => {
  const labels = productLabels.value
  const actPct = results.value.map(r =>
    Math.round((r.analytics?.result_data?.activity_ratio ?? 0) * 100)
  )
  const barColors = results.value.map(r => {
    const cls = r.analytics?.fsn_classification
    return cls === 'fast' ? 'rgba(34,197,94,0.75)'
         : cls === 'slow' ? 'rgba(250,204,21,0.75)'
         :                  'rgba(248,113,113,0.75)'
  })
  const borderColors = results.value.map(r => {
    const cls = r.analytics?.fsn_classification
    return cls === 'fast' ? '#16a34a' : cls === 'slow' ? '#ca8a04' : '#ef4444'
  })
  const n = labels.length
  return {
    labels,
    datasets: [
      {
        type: 'bar',
        label: 'Activity Ratio (%)',
        data: actPct,
        backgroundColor: barColors,
        borderColor: borderColors,
        borderWidth: 1,
        borderRadius: 4,
        order: 2,
      },
      {
        type: 'line',
        label: 'Fast threshold (50%)',
        data: Array(n).fill(50),
        borderColor: '#16a34a',
        borderWidth: 1.5,
        borderDash: [5, 4],
        pointRadius: 0,
        fill: false,
        order: 1,
      },
      {
        type: 'line',
        label: 'Non-moving threshold (10%)',
        data: Array(n).fill(10),
        borderColor: '#ef4444',
        borderWidth: 1.5,
        borderDash: [5, 4],
        pointRadius: 0,
        fill: false,
        order: 0,
      },
    ],
  }
})

const fsnActivityOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top',
      labels: { font: { size: 10 }, boxWidth: 14, padding: 10 },
    },
    tooltip: {
      mode: 'index',
      intersect: false,
      callbacks: {
        label: ctx => ctx.parsed.y !== null
          ? ` ${ctx.dataset.label}: ${ctx.parsed.y}%`
          : null,
      },
    },
  },
  scales: {
    x: {
      ticks: { font: { size: 10 }, maxRotation: 40, minRotation: 0 },
      grid: { display: false },
    },
    y: {
      beginAtZero: true,
      max: 105,
      ticks: { font: { size: 10 }, callback: v => v + '%' },
      grid: { color: '#f3f4f6' },
      title: {
        display: true,
        text: '% of weeks with sales activity (last 24 weeks)',
        font: { size: 10 },
        color: '#9ca3af',
      },
    },
  },
}

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
