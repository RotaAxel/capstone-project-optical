<template>
  <div class="analytics-page fade-up">

    <!-- ── Page Header ─────────────────────────────────────────── -->
    <div class="page-header">
      <div class="flex items-center gap-3">
      </div>
      <div class="header-actions">
        <button @click="showThresholds = !showThresholds" class="btn-thresholds" :class="{ active: showThresholds }">
          <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="3"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06A1.65 1.65 0 004.6 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06A1.65 1.65 0 009 4.6a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>
          </svg>
          Thresholds
        </button>
        <button @click="runAnalytics" class="btn-run" :disabled="running">
          <svg v-if="running" class="spin w-4 h-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          <svg v-else width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          {{ running ? 'Computing…' : 'Run Analytics' }}
        </button>
      </div>
    </div>

    <!-- ── Threshold Settings Panel ───────────────────────────── -->
    <div v-if="showThresholds" class="thresholds-panel">
      <div v-if="thresholdsBanner" class="th-banner" :class="thresholdsBanner.type">{{ thresholdsBanner.text }}</div>

      <div v-if="thresholdsLoading" class="flex items-center justify-center py-10">
        <div class="animate-spin w-7 h-7 border-4 border-indigo-400 border-t-transparent rounded-full"></div>
      </div>

      <form v-else @submit.prevent="saveThresholds" class="th-form">
        <div class="th-group">
          <h3 class="th-group-title">Low-Stock Alert Sensitivity</h3>
          <p class="th-group-sub">How far ahead of a product's own Reorder Point the "Low Stock" alert fires. 100% = fires exactly at the reorder point.</p>
          <div class="th-field">
            <label class="th-label" for="th-lowstock">Sensitivity</label>
            <div class="th-input-row">
              <input id="th-lowstock" v-model.number="thresholds.low_stock_sensitivity_percent" type="number" min="50" max="500" step="5" class="th-input" />
              <span class="th-unit">%</span>
            </div>
            <p class="th-hint">Example — Reorder Point of 20 units → alerts once stock ≤ {{ Math.round(20 * (thresholds.low_stock_sensitivity_percent || 100) / 100) }} units. Out-of-stock alerts always fire regardless of this setting.</p>
          </div>
        </div>

        <div class="th-group">
          <h3 class="th-group-title">FSN Classification (Fast / Slow / Non-Moving)</h3>
          <p class="th-group-sub">Applies the next time "Run Analytics" is clicked — past results keep the thresholds they were computed with.</p>
          <div class="th-grid">
            <div class="th-field">
              <label class="th-label" for="th-fast">Fast-moving threshold</label>
              <div class="th-input-row">
                <input id="th-fast" v-model.number="thresholds.fsn_fast_threshold_percent" type="number" min="1" max="100" class="th-input" />
                <span class="th-unit">% of weeks active</span>
              </div>
            </div>
            <div class="th-field">
              <label class="th-label" for="th-nonmoving">Non-moving threshold</label>
              <div class="th-input-row">
                <input id="th-nonmoving" v-model.number="thresholds.fsn_nonmoving_threshold_percent" type="number" min="0" max="99" class="th-input" />
                <span class="th-unit">% of weeks active</span>
              </div>
            </div>
            <div class="th-field">
              <label class="th-label" for="th-deadweeks">Dead-stock cutoff</label>
              <div class="th-input-row">
                <input id="th-deadweeks" v-model.number="thresholds.fsn_dead_stock_weeks" type="number" min="1" max="208" class="th-input" />
                <span class="th-unit">weeks with no sale</span>
              </div>
              <p class="th-hint">≈ {{ (thresholds.fsn_dead_stock_weeks / 4.345).toFixed(1) }} months. A product unsold this long is always "Non-Moving."</p>
            </div>
            <div class="th-field">
              <label class="th-label" for="th-fastdaily">"Always fast" override</label>
              <div class="th-input-row">
                <input id="th-fastdaily" v-model.number="thresholds.fsn_fast_min_daily_avg" type="number" min="0" max="1000" step="0.1" class="th-input" />
                <span class="th-unit">units/day, avg</span>
              </div>
            </div>
          </div>
          <div v-if="thresholds.fsn_nonmoving_threshold_percent >= thresholds.fsn_fast_threshold_percent" class="th-warn">
            ⚠ Non-moving threshold must be lower than the Fast threshold, or every product collapses into one bucket.
          </div>
        </div>

        <div class="th-actions">
          <button type="button" class="th-btn-ghost" :disabled="thresholdsSaving" @click="resetThresholdDefaults">Reset to Defaults</button>
          <button type="submit" class="th-btn-save" :disabled="thresholdsSaving || thresholds.fsn_nonmoving_threshold_percent >= thresholds.fsn_fast_threshold_percent">
            {{ thresholdsSaving ? 'Saving…' : 'Save Thresholds' }}
          </button>
        </div>
      </form>
    </div>

    <!-- ── Running Overlay ───────────────────────────────────── -->
    <div v-if="running" class="running-overlay">
      <div class="running-card">
        <div class="running-anim">
          <div class="running-ring"></div>
          <svg class="running-icon" width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
              d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
          </svg>
        </div>
        <div class="running-text">
          <p class="running-title">Computing Analytics…</p>
          <p class="running-stage">{{ loadingStage }}</p>
        </div>
        <div class="running-right">
          <p class="running-elapsed">{{ loadingElapsed }}s</p>
          <div class="running-dots">
            <span :class="{ active: loadingDot >= 0 }"></span>
            <span :class="{ active: loadingDot >= 1 }"></span>
            <span :class="{ active: loadingDot >= 2 }"></span>
          </div>
        </div>
      </div>
      <div class="running-steps">
        <div v-for="(step, i) in runningSteps" :key="i" class="running-step" :class="step.state">
          <div class="rs-icon">
            <svg v-if="step.state === 'done'" width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <polyline points="20 6 9 17 4 12" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <div v-else-if="step.state === 'active'" class="rs-spin"></div>
            <div v-else class="rs-dot"></div>
          </div>
          <span class="rs-label">{{ step.label }}</span>
          <span v-if="step.state === 'done'" class="rs-done">done</span>
          <span v-else-if="step.state === 'active'" class="rs-working">working…</span>
        </div>
      </div>
    </div>

    <!-- ── Algorithm Info Cards ────────────────────────────────── -->
    <div class="algo-grid">

      <div class="algo-card algo-blue">
        <div class="algo-icon">
          <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4"/>
          </svg>
        </div>
        <div>
          <p class="algo-label">ARIMA</p>
          <p class="algo-name">Demand Forecast</p>
          <p class="algo-desc">Forecasts weekly sales from each product's own sales pattern, with a 95% confidence range</p>
        </div>
      </div>

      <div class="algo-card algo-purple">
        <div class="algo-icon">
          <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
          </svg>
        </div>
        <div>
          <p class="algo-label">EOQ</p>
          <p class="algo-name">Order Quantity</p>
          <p class="algo-desc">Calculates the most cost-effective amount to order at once</p>
        </div>
      </div>

      <div class="algo-card algo-orange">
        <div class="algo-icon">
          <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
          </svg>
        </div>
        <div>
          <p class="algo-label">ROP</p>
          <p class="algo-name">Reorder Point</p>
          <p class="algo-desc">Tells you when to place a new order before stock runs out</p>
        </div>
      </div>

      <div class="algo-card algo-emerald">
        <div class="algo-icon">
          <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M3 4a1 1 0 011-1h16a1 1 0 010 2H4a1 1 0 01-1-1zm0 8a1 1 0 011-1h10a1 1 0 010 2H4a1 1 0 01-1-1zm0 8a1 1 0 011-1h6a1 1 0 010 2H4a1 1 0 01-1-1z"/>
          </svg>
        </div>
        <div>
          <p class="algo-label">FSN</p>
          <p class="algo-name">Sales Speed</p>
          <p class="algo-desc">Rates products by how fast they sell (since each product's first sale)</p>
        </div>
      </div>

    </div>

    <!-- ── FSN Summary ─────────────────────────────────────────── -->
    <div v-if="summary" class="fsn-summary">
      <div class="fsn-card">
        <div class="fsn-icon fast">
          <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
          </svg>
        </div>
        <div>
          <p class="fsn-count fast">{{ summary.fsn_summary.fast }}</p>
          <p class="fsn-lbl">Fast Moving</p>
          <p class="fsn-sub">products</p>
        </div>
      </div>
      <div class="fsn-card">
        <div class="fsn-icon slow">
          <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <div>
          <p class="fsn-count slow">{{ summary.fsn_summary.slow }}</p>
          <p class="fsn-lbl">Slow Moving</p>
          <p class="fsn-sub">products</p>
        </div>
      </div>
      <div class="fsn-card">
        <div class="fsn-icon non">
          <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
          </svg>
        </div>
        <div>
          <p class="fsn-count non">{{ summary.fsn_summary.nonMoving }}</p>
          <p class="fsn-lbl">Non-Moving</p>
          <p class="fsn-sub">products</p>
        </div>
      </div>
    </div>

    <!-- ── Aging of non-moving products ─────────────────────────── -->
    <div v-if="results.length && nonMovingTotal" class="aging-card">
      <div class="aging-head">
        <p class="aging-title">Aging of Non-Moving Products</p>
        <p class="aging-sub">Time since each non-moving product last sold — the older it is, the more likely it is dead stock</p>
      </div>
      <div class="aging-grid">
        <div v-for="b in agingBuckets" :key="b.label" class="aging-cell" :class="b.tone">
          <p class="aging-count">{{ b.count }}</p>
          <p class="aging-lbl">{{ b.label }}</p>
          <p class="aging-pct">{{ nonMovingTotal ? (b.count / nonMovingTotal * 100).toFixed(1) : '0.0' }}% of non-moving</p>
        </div>
      </div>
    </div>

    <!-- ── Charts ──────────────────────────────────────────────── -->
    <div v-if="results.length" class="charts-section">

      <!-- Product selector -->
      <div class="product-selector">
        <div class="sel-icon">
          <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
          </svg>
        </div>
        <span class="sel-label">Viewing product:</span>
        <div class="product-search-wrap">
          <input
            v-model="productSearchText"
            @input="onProductSearchInput"
            @keydown="e => productSuggest.onKeydown(e, pickProductSuggestion)"
            @focus="onProductSearchFocus"
            @blur="onProductSearchBlur"
            placeholder="Search product by name or SKU…"
            class="sel-select"
            autocomplete="off"
          />
          <SuggestDropdown :items="productSuggest.suggestions.value" :active-index="productSuggest.activeIndex.value"
            :visible="productSuggest.open.value" @pick="pickProductSuggestion" @hover="i => productSuggest.activeIndex.value = i" />
        </div>
        <span class="sel-hint">↑ controls ARIMA · EOQ · ROP charts</span>
      </div>

      <!-- Row 1: FSN Activity + ARIMA Forecast -->
      <div class="chart-row chart-row-1">

        <!-- FSN Activity -->
        <div class="chart-card">
          <div class="chart-header ch-emerald">
            <div class="ch-dot emerald"></div>
            <div>
              <p class="ch-title">How Often Products Sell</p>
              <p class="ch-sub">How frequently each product sold since its first sale (up to 4 years)</p>
            </div>
          </div>
          <div class="chart-body" style="min-height:240px">
            <Bar :data="fsnActivityData" :options="fsnActivityOptions" />
          </div>
        </div>

        <!-- ARIMA Forecast -->
        <div class="chart-card chart-card-wide">
          <div class="chart-header ch-blue">
            <div class="ch-dot blue"></div>
            <div>
              <p class="ch-title">Predicted Future Demand</p>
              <p class="ch-sub">Current demand + 6-month forecast (95% confidence)</p>
            </div>
            <div class="ch-legend">
              <span class="leg-item"><span class="leg-line blue-line"></span>Current</span>
              <span class="leg-item"><span class="leg-line orange-dash"></span>Monthly Forecast</span>
              <span class="leg-item"><span class="leg-band"></span>95% CI</span>
            </div>
          </div>
          <div class="chart-body" style="min-height:240px">
            <Line :data="forecastLineData" :options="forecastLineOptions" />
          </div>
        </div>

      </div>

      <!-- Row 2: EOQ + ROP -->
      <div class="chart-row chart-row-2">

        <!-- EOQ Cost Curve -->
        <div class="chart-card">
          <div class="chart-header ch-purple">
            <div class="ch-dot purple"></div>
            <div>
              <p class="ch-title">Best Order Quantity</p>
              <p class="ch-sub">Find the order size that minimizes total ordering and storage costs</p>
            </div>
          </div>
          <div class="chart-body" style="min-height:260px">
            <Line :data="eoqCurveData" :options="eoqCurveOptions" />
          </div>
        </div>

        <!-- ROP Simulation -->
        <div class="chart-card">
          <div class="chart-header ch-orange">
            <div class="ch-dot orange"></div>
            <div>
              <p class="ch-title">When to Reorder Stock</p>
              <p class="ch-sub">Stock simulation over 4 months — shows when to place an order</p>
            </div>
            <div class="ch-legend">
              <span class="leg-item"><span class="leg-bar blue-bar"></span>Stock</span>
              <span class="leg-item"><span class="leg-line orange-dash"></span>ROP</span>
              <span class="leg-item"><span class="leg-line red-dash"></span>Safety Stock</span>
            </div>
          </div>
          <div class="chart-body" style="min-height:260px">
            <Line :data="ropSimData" :options="ropSimOptions" />
          </div>
        </div>

      </div>

      <!-- Row 3: Stock Runway -->
      <div class="chart-card">
        <div class="chart-header ch-gray">
          <div class="ch-dot gray"></div>
          <div>
            <p class="ch-title">Days Until Stock Runs Out</p>
            <p class="ch-sub">Top 15 most urgent products — sorted by fewest days remaining</p>
          </div>
          <div class="ch-legend">
            <span class="leg-item"><span class="leg-dot red-dot"></span>&lt; 14 days</span>
            <span class="leg-item"><span class="leg-dot yellow-dot"></span>14–30 days</span>
            <span class="leg-item"><span class="leg-dot green-dot"></span>&gt; 30 days</span>
          </div>
        </div>
        <div class="chart-body" :style="{ height: stockRunwayHeight }">
          <Bar :data="stockRunwayData" :options="stockRunwayOptions" />
        </div>
      </div>

    </div>

    <!-- ── Results Table ───────────────────────────────────────── -->
    <div v-if="results.length" class="results-wrapper">

      <!-- Monthly Forecast Accuracy -->
      <div class="chart-card monthly-forecast-card">
        <div class="chart-header ch-gray">
          <div class="ch-dot gray"></div>
          <div>
            <p class="ch-title">Monthly Forecast Accuracy</p>
            <p class="ch-sub">Actual vs forecast over the last 6 months</p>
          </div>
          <div class="ch-legend">
            <span class="leg-item"><span class="leg-bar blue-bar"></span>Actual Sales</span>
            <span class="leg-item"><span class="leg-line orange-dash"></span>Forecast</span>
          </div>
        </div>

        <div class="chart-body mf-body">
          <!-- WMAPE badge -->
          <div class="mf-metric" :class="monthlyWmapeClass">
            <div>
              <p class="mf-metric-label">Monthly WMAPE</p>
              <p class="mf-metric-sub">Lower is better</p>
            </div>
            <p class="mf-metric-value">
              {{ monthlyWmape !== null ? Number(monthlyWmape).toFixed(2) + '%' : 'N/A' }}
            </p>
          </div>

          <!-- Chart -->
          <div v-if="monthlyForecastChartData" class="mf-chart">
            <Bar :data="monthlyForecastChartData" :options="monthlyForecastChartOptions" />
          </div>

          <!-- Table -->
          <div v-if="monthlyForecastRows.length" class="mf-table-wrap">
            <table class="mf-table">
              <thead>
                <tr>
                  <th>Month</th>
                  <th>Actual</th>
                  <th>Forecast</th>
                  <th>WMAPE</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in monthlyForecastRows" :key="row.label">
                  <td class="mf-month">{{ row.label }}</td>
                  <td><span class="val-blue">{{ row.actual }}</span></td>
                  <td><span class="val-orange">{{ row.forecast }}</span></td>
                  <td>{{ row.wmape }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="results-card">
        <div class="results-header">
          <div class="flex items-center gap-2">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <span class="results-title">Analytics Results</span>
            <span class="results-count">{{ results.length }} products</span>
          </div>
          <div class="results-search">
            <svg class="results-search-icon" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
            </svg>
            <input v-model="tableSearch" @input="tablePage = 1" placeholder="Search by product name or SKU…" class="results-search-input" />
            <button v-if="tableSearch" @click="tableSearch = ''; tablePage = 1" class="results-search-clear" type="button">
              <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>
          <div class="results-header-right">
            <span class="results-computed">
              Computed: {{ computedAt }}
              <span v-if="running" class="stale-badge refreshing">● Refreshing…</span>
              <span v-else-if="summary?.is_stale" class="stale-badge">● Outdated</span>
              <span v-else class="stale-badge fresh">● Up to date</span>
            </span>
          </div>
        </div>
        <div class="table-wrap">
          <table class="res-table">
            <thead>
              <tr>
                <th class="th-sortable" @click="setSort('product')" title="Sort by Product">
                  Product
                  <span class="col-sort-arrow" :class="{ inactive: sortKey !== 'product' }">{{ sortIcon('product') }}</span>
                </th>
                <th class="th-sortable" @click="setSort('stock')" title="Sort by Current Stock">
                  Current Stock
                  <span class="col-sort-arrow" :class="{ inactive: sortKey !== 'stock' }">{{ sortIcon('stock') }}</span>
                </th>
                <th class="th-sortable" @click="setSort('demand')" title="Expected Sales (30 days) = sum of the next 4 weekly forecasts × 30 ÷ 28. Click to sort.">
                  Expected Sales <span class="th-note">(30 days)</span>
                  <span class="col-sort-arrow" :class="{ inactive: sortKey !== 'demand' }">{{ sortIcon('demand') }}</span>
                </th>
                <th class="th-sortable" @click="setSort('eoq')" title="Sort by Best Order Qty">
                  Best Order Qty
                  <span class="col-sort-arrow" :class="{ inactive: sortKey !== 'eoq' }">{{ sortIcon('eoq') }}</span>
                </th>
                <th class="th-sortable" @click="setSort('rop')" title="Sort by Reorder point">
                  Reorder When
                  <span class="col-sort-arrow" :class="{ inactive: sortKey !== 'rop' }">{{ sortIcon('rop') }}</span>
                </th>
                <th class="th-sortable" @click="setSort('fsn')" title="Sort by Sales Speed">
                  Sales Speed
                  <span class="col-sort-arrow" :class="{ inactive: sortKey !== 'fsn' }">{{ sortIcon('fsn') }}</span>
                </th>
                <th class="th-sortable" @click="setSort('alert')" title="Sort by Alert">
                  Alert
                  <span class="col-sort-arrow" :class="{ inactive: sortKey !== 'alert' }">{{ sortIcon('alert') }}</span>
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in pagedResults" :key="r.product.id" @click="openDetail(r)" class="res-row">
                <td>
                  <p class="res-product">{{ r.product.name }}</p>
                  <p class="res-sku">{{ r.product.sku }}</p>
                </td>
                <td>
                  <span :class="needsReorder(r) ? 'stock-low' : 'stock-ok'">
                    {{ r.product.stock_quantity }}
                  </span>
                </td>
                <td><span class="val-blue">{{ r.analytics?.predicted_demand ?? '—' }}</span></td>
                <td><span class="val-purple">{{ r.analytics?.eoq_value ?? '—' }}</span></td>
                <td><span class="val-orange">{{ r.analytics?.rop_value ?? '—' }}</span></td>
                <td>
                  <span :class="{
                    'fsn-pill fast':       r.analytics?.fsn_classification === 'fast',
                    'fsn-pill slow':       r.analytics?.fsn_classification === 'slow',
                    'fsn-pill non-moving': r.analytics?.fsn_classification === 'non_moving',
                    'fsn-pill unknown':    !r.analytics?.fsn_classification,
                  }">
                    {{ r.analytics?.fsn_classification?.replace('_', ' ') ?? '—' }}
                  </span>
                  <span class="mape-sub">{{ turnoverLabel(r.analytics?.turnover_ratio) }} · {{ turnoverPercent(r.analytics?.turnover_ratio) }} /yr</span>
                </td>
                <td>
                  <div class="alert-cell">
                    <span v-if="needsReorder(r)" class="alert-pill danger" title="Current stock is at or below the Reorder Point (ROP)">⚠ Reorder Now</span>
                    <span v-else class="alert-pill ok">✓ OK</span>
                    <span class="view-hint">
                      View details
                      <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
                    </span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination footer -->
        <div v-if="tableLastPage > 1" class="res-pagination">
          <span class="res-page-info">Showing {{ tablePageFrom }}–{{ tablePageTo }} of {{ results.length }}</span>
          <div class="res-page-btns">
            <button class="res-page-btn" :disabled="tablePage === 1" @click="tablePage--">
              <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
              Prev
            </button>
            <span class="res-page-cur">{{ tablePage }} / {{ tableLastPage }}</span>
            <button class="res-page-btn" :disabled="tablePage === tableLastPage" @click="tablePage++">
              Next
              <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Empty State ─────────────────────────────────────────── -->
    <div v-if="!results.length && !running" class="empty-state">
      <div class="empty-icon">
        <svg width="30" height="30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
      </div>
      <p class="empty-title">No analytics computed yet</p>
      <p class="empty-sub">Click <strong>Run Analytics</strong> to compute ARIMA, EOQ, ROP, and FSN for all products.</p>
      <button @click="runAnalytics" class="btn-run mt-4">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Run Analytics
      </button>
    </div>

    <!-- ── Product Detail Slide-over ──────────────────────────── -->
    <Teleport to="body">
      <div v-if="selected" class="so-backdrop" @click="selected = null"></div>

      <transition
        enter-active-class="so-enter-active"
        enter-from-class="so-enter-from"
        enter-to-class="so-enter-to"
        leave-active-class="so-leave-active"
        leave-from-class="so-leave-from"
        leave-to-class="so-leave-to">
        <div v-if="selected" class="so-panel">

          <!-- ── Panel Header ───────────────────────────────────── -->
          <div class="sop-header">
            <div class="sop-avatar">{{ selected.product.name?.charAt(0)?.toUpperCase() }}</div>
            <div class="sop-info">
              <p class="sop-name">{{ selected.product.name }}</p>
              <p class="sop-sku">{{ selected.product.sku }}</p>
              <div class="sop-badges">
                <span :class="{
                  'sop-fsn-badge fast':       selected.analytics?.fsn_classification === 'fast',
                  'sop-fsn-badge slow':       selected.analytics?.fsn_classification === 'slow',
                  'sop-fsn-badge non-moving': selected.analytics?.fsn_classification === 'non_moving',
                  'sop-fsn-badge unknown':    !selected.analytics?.fsn_classification,
                }">{{ fsnLabel(selected.analytics?.fsn_classification) }}</span>
                <span v-if="needsReorder(selected)"
                  class="sop-stock-badge danger">⚠ Below Reorder Point</span>
                <span v-else class="sop-stock-badge ok">✓ Stock OK</span>
              </div>
            </div>
            <button @click="selected = null" class="sop-close">
              <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <!-- ── Scrollable Body ────────────────────────────────── -->
          <div class="sop-body">

            <!-- Stock warning banner -->
            <div v-if="needsReorder(selected)" class="sop-alert-banner">
              <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
              </svg>
              Stock is at or below the Reorder Point (ROP). Consider placing an order now.
            </div>

            <!-- ── Key Metrics ──────────────────────────────────── -->
            <div class="sop-metrics">
              <div class="sop-metric gray">
                <div class="sop-metric-icon">
                  <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                  </svg>
                </div>
                <p class="sop-metric-val" :class="needsReorder(selected) ? 'red' : ''">
                  {{ selected.product.stock_quantity }}
                </p>
                <p class="sop-metric-lbl">Current Stock</p>
                <p class="sop-metric-unit">units</p>
              </div>

              <div class="sop-metric blue">
                <div class="sop-metric-icon">
                  <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4"/>
                  </svg>
                </div>
                <p class="sop-metric-val">{{ selected.analytics?.predicted_demand ?? '—' }}</p>
                <p class="sop-metric-lbl">Demand (30d)</p>
                <p class="sop-metric-unit">ARIMA forecast</p>
              </div>

              <div class="sop-metric purple">
                <div class="sop-metric-icon">
                  <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                  </svg>
                </div>
                <p class="sop-metric-val">{{ selected.analytics?.eoq_value ?? '—' }}</p>
                <p class="sop-metric-lbl">EOQ</p>
                <p class="sop-metric-unit">units/order</p>
              </div>

              <div class="sop-metric orange">
                <div class="sop-metric-icon">
                  <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                  </svg>
                </div>
                <p class="sop-metric-val">{{ selected.analytics?.rop_value ?? '—' }}</p>
                <p class="sop-metric-lbl">Reorder Point</p>
                <p class="sop-metric-unit">units threshold</p>
              </div>
            </div>

            <!-- ── Mini Chart ───────────────────────────────────── -->
            <div class="sop-chart-card">
              <div class="sop-chart-header">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Stock, Expected Sales &amp; Reorder Point
              </div>
              <div style="height: 18rem; padding: 0 4px 4px;">
                <Bar :data="detailChartData" :options="detailChartOptions" />
              </div>
            </div>

            <!-- ── ARIMA Section ─────────────────────────────────── -->
            <div class="sop-section">
              <div class="sop-sec-header blue">
                <div class="sop-sec-tag blue">
                  <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4"/>
                  </svg>
                  ARIMA
                </div>
                <span class="sop-sec-formula">{{ methodLabel(selected.analytics?.result_data) }}</span>
              </div>
              <div class="sop-sec-body">
                <div class="sop-kv"><span class="sop-k">Sales last 90 days</span><span class="sop-v">{{ selected.analytics?.result_data?.sales_90d ?? '—' }} units</span></div>
                <div class="sop-kv"><span class="sop-k">Average daily sales</span><span class="sop-v">{{ Number(selected.analytics?.result_data?.avg_daily ?? 0).toFixed(4) }} units/day</span></div>
                <div class="sop-kv" v-for="(v, i) in (selected.analytics?.result_data?.ar_params ?? [])" :key="'ar' + i">
                  <span class="sop-k">AR coefficient (φ{{ i + 1 }})</span><span class="sop-v mono">{{ v }}</span>
                </div>
                <div class="sop-kv" v-for="(v, i) in (selected.analytics?.result_data?.ma_params ?? [])" :key="'ma' + i">
                  <span class="sop-k">MA coefficient (θ{{ i + 1 }})</span><span class="sop-v mono">{{ v }}</span>
                </div>
                <div class="sop-kv" v-if="selected.analytics?.method === 'auto_arima' && !selected.analytics?.result_data?.used_fallback">
                  <span class="sop-k">Mean weekly change (μ) · residual SD</span>
                  <span class="sop-v mono">{{ selected.analytics.result_data.diff_mean }} · {{ selected.analytics.result_data.resid_sd ?? '—' }}</span>
                </div>
                <div class="sop-kv" v-if="selected.analytics?.result_data?.aic_table?.length">
                  <span class="sop-k">History used for the model</span>
                  <span class="sop-v">{{ selected.analytics.result_data.total_weeks }} weeks (since first sale)</span>
                </div>
                <div v-if="selected.analytics?.result_data?.aic_table?.length" class="sop-mini">
                  <p class="sop-mini-title">Candidate orders and AIC — lowest AIC is selected</p>
                  <table class="sop-mini-table">
                    <thead><tr><th>Order (p,d,q)</th><th>AIC</th><th></th></tr></thead>
                    <tbody>
                      <tr v-for="a in selected.analytics.result_data.aic_table" :key="a.order.join()"
                        :class="{ 'sop-mini-best': a.order.join() === (selected.analytics.result_data.arima_order ?? []).join() }">
                        <td>({{ a.order.join(',') }})</td><td>{{ a.aic }}</td>
                        <td>{{ a.order.join() === (selected.analytics.result_data.arima_order ?? []).join() ? '← selected' : '' }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <div class="sop-kv"><span class="sop-k">Forecast range (95% accuracy)</span><span class="sop-v dim">{{ selected.analytics?.result_data?.conf_lower_30d ?? '—' }} – {{ selected.analytics?.result_data?.conf_upper_30d ?? '—' }} units</span></div>
                <div class="sop-kv" v-if="wmapePct(selected.analytics?.result_data) != null">
                  <span class="sop-k">Forecast error rate (WMAPE — lower is better)</span>
                  <span class="sop-v" :class="mapeClass(wmapePct(selected.analytics.result_data))">
                    {{ wmapePct(selected.analytics.result_data) }}%
                    <span class="mape-detail">{{ wmapePct(selected.analytics.result_data) <= 30 ? '● Good' : wmapePct(selected.analytics.result_data) <= 60 ? '● Fair' : '● Poor' }}</span>
                  </span>
                </div>
                <div class="sop-kv" v-else-if="selected.analytics?.method === 'auto_arima'">
                  <span class="sop-k">Forecast error rate</span>
                  <span class="sop-v dim">N/A — needs at least 36 four-week periods of sales history and more than 30 units in the last 12 periods</span>
                </div>
                <div class="sop-kv" v-else-if="selected.analytics?.fsn_classification === 'slow' || selected.analytics?.fsn_classification === 'non_moving'">
                  <span class="sop-k">Forecast error rate</span>
                  <span class="sop-v dim">Not measured — intermittent demand makes percentage error unreliable for {{ selected.analytics.fsn_classification === 'slow' ? 'slow' : 'non-moving' }} items</span>
                </div>
                <div v-if="selected.analytics?.result_data?.wmape_holdout?.length" class="sop-mini">
                  <p class="sop-mini-title">Hold-out test — 12 withheld four-week periods (predicted vs actual)</p>
                  <p v-if="selected.analytics.result_data.wmape_model" class="sop-mini-note">
                    Hold-out model (fitted on {{ selected.analytics.result_data.wmape_model.train_periods }} four-week totals):
                    ARIMA({{ (selected.analytics.result_data.wmape_model.order ?? []).join(',') }}),
                    AR [{{ (selected.analytics.result_data.wmape_model.ar ?? []).join(', ') }}],
                    MA [{{ (selected.analytics.result_data.wmape_model.ma ?? []).join(', ') }}],
                    μ = {{ selected.analytics.result_data.wmape_model.mean }}
                  </p>
                  <table class="sop-mini-table">
                    <thead><tr><th>#</th><th>Weeks</th><th>Actual</th><th>Predicted</th></tr></thead>
                    <tbody>
                      <tr v-for="h in selected.analytics.result_data.wmape_holdout" :key="h.period">
                        <td>{{ h.period }}</td><td>{{ h.from }} to {{ h.to }}</td><td>{{ h.actual }}</td><td>{{ h.forecast }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <div class="sop-kv"><span class="sop-k">Days of stock remaining</span>
                  <span class="sop-v" :class="daysRemaining(selected) < 14 ? 'danger-val' : 'ok-val'">{{ daysRemaining(selected) }} days</span>
                </div>
                <div class="sop-result blue">
                  <span>Predicted demand — next 30 days</span>
                  <span class="sop-result-val">{{ selected.analytics?.predicted_demand ?? '—' }} units</span>
                </div>
              </div>
            </div>

            <!-- ── EOQ Section ───────────────────────────────────── -->
            <div class="sop-section">
              <div class="sop-sec-header purple">
                <div class="sop-sec-tag purple">
                  <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                  </svg>
                  EOQ
                </div>
                <span class="sop-sec-formula">√(2 · D · S / H)</span>
              </div>
              <div class="sop-sec-body">
                <div class="sop-kv"><span class="sop-k">Expected annual sales</span><span class="sop-v">{{ Math.round(selected.analytics?.result_data?.annual_demand ?? (selected.analytics?.result_data?.avg_daily ?? 0) * 365) }} units/yr</span></div>
                <div class="sop-kv"><span class="sop-k">Cost per order</span><span class="sop-v">₱{{ selected.analytics?.result_data?.order_cost ?? 500 }}.00 / order</span></div>
                <div class="sop-kv"><span class="sop-k">Storage cost per unit/year</span><span class="sop-v">₱{{ selected.analytics?.result_data?.holding_cost ?? holdingCost(selected) }} / unit / yr</span></div>
                <div class="sop-result purple">
                  <span>Best quantity to order at once</span>
                  <span class="sop-result-val">{{ selected.analytics?.eoq_value ?? '—' }} units</span>
                </div>
              </div>
            </div>

            <!-- ── ROP Section ───────────────────────────────────── -->
            <div class="sop-section">
              <div class="sop-sec-header orange">
                <div class="sop-sec-tag orange">
                  <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                  </svg>
                  ROP
                </div>
                <span class="sop-sec-formula">d̄ · L + Z · σ · √L</span>
              </div>
              <div class="sop-sec-body">
                <div class="sop-kv"><span class="sop-k">Average daily sales</span><span class="sop-v">{{ Number(selected.analytics?.result_data?.avg_daily ?? 0).toFixed(4) }} units/day</span></div>
                <div class="sop-kv"><span class="sop-k">Sales variability</span><span class="sop-v">{{ Number(selected.analytics?.result_data?.std_daily ?? 0).toFixed(4) }} units/day</span></div>
                <div class="sop-kv"><span class="sop-k">Delivery wait time</span><span class="sop-v">{{ selected.analytics?.result_data?.lead_time ?? 7 }} days</span></div>
                <div class="sop-kv"><span class="sop-k">Stock reliability target</span><span class="sop-v">1.645 → {{ selected.analytics?.result_data?.service_level ?? '95%' }}</span></div>
                <div class="sop-kv"><span class="sop-k">Extra buffer stock</span><span class="sop-v orange-val">{{ selected.analytics?.result_data?.safety_stock ?? '—' }} units</span></div>
                <div class="sop-kv"><span class="sop-k">Current stock vs reorder point</span>
                  <span class="sop-v" :class="selected.product.stock_quantity <= (selected.analytics?.rop_value ?? 0) ? 'danger-val' : 'ok-val'">
                    {{ selected.product.stock_quantity <= (selected.analytics?.rop_value ?? 0) ? '⚠ Order now!' : '✓ Sufficient' }}
                  </span>
                </div>
                <div class="sop-result orange">
                  <span>Reorder when stock drops to</span>
                  <span class="sop-result-val">{{ selected.analytics?.rop_value ?? '—' }} units</span>
                </div>
              </div>
            </div>

            <!-- ── FSN Section ───────────────────────────────────── -->
            <div class="sop-section">
              <div class="sop-sec-header" :class="{
                'blue':   selected.analytics?.fsn_classification === 'fast',
                'yellow':  selected.analytics?.fsn_classification === 'slow',
                'red':     selected.analytics?.fsn_classification === 'non_moving',
                'neutral': !selected.analytics?.fsn_classification,
              }">
                <div class="sop-sec-tag" :class="{
                  'blue':   selected.analytics?.fsn_classification === 'fast',
                  'yellow':  selected.analytics?.fsn_classification === 'slow',
                  'red':     selected.analytics?.fsn_classification === 'non_moving',
                  'neutral': !selected.analytics?.fsn_classification,
                }">
                  <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 4a1 1 0 011-1h16a1 1 0 010 2H4a1 1 0 01-1-1zm0 8a1 1 0 011-1h10a1 1 0 010 2H4a1 1 0 01-1-1zm0 8a1 1 0 011-1h6a1 1 0 010 2H4a1 1 0 01-1-1z"/>
                  </svg>
                  FSN
                </div>
                <span class="sop-sec-formula">Activity-based classification</span>
              </div>
              <div class="sop-sec-body">
                <!-- Activity ratio progress bar -->
                <div class="sop-activity-bar">
                  <div class="sop-activity-labels">
                    <span>Activity ratio</span>
                    <span class="sop-activity-pct">{{ ((selected.analytics?.result_data?.activity_ratio ?? 0) * 100).toFixed(1) }}%</span>
                  </div>
                  <div class="sop-activity-track">
                    <div class="sop-activity-fill" :class="{
                      'fast':  selected.analytics?.fsn_classification === 'fast',
                      'slow':  selected.analytics?.fsn_classification === 'slow',
                      'non':   selected.analytics?.fsn_classification === 'non_moving',
                    }" :style="{ width: ((selected.analytics?.result_data?.activity_ratio ?? 0) * 100).toFixed(1) + '%' }"></div>
                    <div class="sop-activity-marker" :style="{ left: fsnThresholds(selected.analytics?.result_data).fast + '%' }" :title="`Fast threshold (${fsnThresholds(selected.analytics?.result_data).fast}%)`"></div>
                    <div class="sop-activity-marker" :style="{ left: fsnThresholds(selected.analytics?.result_data).nonMoving + '%' }" :title="`Non-moving threshold (${fsnThresholds(selected.analytics?.result_data).nonMoving}%)`"></div>
                  </div>
                  <div class="sop-activity-legend">
                    <span>Non-moving &lt;{{ fsnThresholds(selected.analytics?.result_data).nonMoving }}%</span>
                    <span>Slow {{ fsnThresholds(selected.analytics?.result_data).nonMoving }}–{{ fsnThresholds(selected.analytics?.result_data).fast }}%</span>
                    <span>Fast ≥{{ fsnThresholds(selected.analytics?.result_data).fast }}%</span>
                  </div>
                </div>
                <div class="sop-kv">
                  <span class="sop-k">Weeks with sales</span>
                  <span class="sop-v">
                    {{ selected.analytics?.result_data?.active_weeks ?? '—' }} / {{ selected.analytics?.result_data?.total_weeks ?? 52 }} weeks
                    <span v-if="selected.analytics?.result_data?.total_weeks != null && selected.analytics.result_data.total_weeks < 52" class="stale-badge">Stale — re-run analytics</span>
                  </span>
                </div>
                <div class="sop-kv"><span class="sop-k">Last sale date</span><span class="sop-v">{{ selected.analytics?.result_data?.last_sale_date ?? 'No sales recorded' }}</span></div>
                <div class="sop-kv"><span class="sop-k">Average daily sales</span><span class="sop-v">{{ Number(selected.analytics?.result_data?.avg_daily ?? 0).toFixed(4) }} units/day</span></div>
                <div class="sop-result" :class="{
                  'blue':   selected.analytics?.fsn_classification === 'fast',
                  'yellow':  selected.analytics?.fsn_classification === 'slow',
                  'red':     selected.analytics?.fsn_classification === 'non_moving',
                  'neutral': !selected.analytics?.fsn_classification,
                }">
                  <span>How fast this product sells</span>
                  <span class="sop-result-val capitalize">{{ fsnLabel(selected.analytics?.fsn_classification) }}</span>
                </div>
                <p class="sop-fsn-note" :class="{
                  'fast':  selected.analytics?.fsn_classification === 'fast',
                  'slow':  selected.analytics?.fsn_classification === 'slow',
                  'non':   selected.analytics?.fsn_classification === 'non_moving',
                }">{{ fsnDescription(selected.analytics?.fsn_classification, selected.analytics?.result_data) }}</p>
              </div>
            </div>

            <!-- ── Turnover Ratio Section ───────────────────────────── -->
            <div class="sop-section">
              <div class="sop-sec-header green">
                <div class="sop-sec-tag green">
                  <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h5M20 20v-5h-5M4.5 9a8 8 0 0113.9-3.5L20 9M19.5 15a8 8 0 01-13.9 3.5L4 15"/>
                  </svg>
                  Turnover
                </div>
                <span class="sop-sec-formula">Annual Demand ÷ Current Stock</span>
              </div>
              <div class="sop-sec-body">
                <div class="sop-kv"><span class="sop-k">Estimated annual sales</span><span class="sop-v">{{ Math.round(selected.analytics?.result_data?.annual_demand ?? 0) }} units/yr</span></div>
                <div class="sop-kv"><span class="sop-k">Current stock on hand</span><span class="sop-v">{{ selected.product.stock_quantity }} units</span></div>
                <div class="sop-result green">
                  <span>Inventory turns per year</span>
                  <span class="sop-result-val">{{ turnoverLabel(selected.analytics?.turnover_ratio) }}</span>
                </div>
                <p class="sop-fsn-note green">{{ turnoverDescription(selected.analytics?.turnover_ratio) }} {{ turnoverPercent(selected.analytics?.turnover_ratio) }} annual turnover.</p>
              </div>
            </div>

          </div>
        </div>
      </transition>
    </Teleport>

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
import { useFlashStore } from '@/stores/flash'
import { useSearchSuggest } from '@/composables/useSearchSuggest'
import SuggestDropdown from '@/components/SuggestDropdown.vue'

const flashStore = useFlashStore()

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
const monthlyForecast         = ref(null)

// ── Product search (forecast chart selector) ──────────────────────────────
const productSearchText = ref('')

const productSuggest = useSearchSuggest(async (q) => {
  const query = q.toLowerCase()
  return results.value
    .filter(r => r.product.name.toLowerCase().includes(query) || r.product.sku?.toLowerCase().includes(query))
    .slice(0, 8)
    .map(r => ({ id: r.product.id, label: r.product.name, sub: r.product.sku }))
}, { delay: 120 })

// Full alphabetical catalog shown before the user types anything — the
// dropdown itself scrolls (see .sug-dropdown), so nothing is cut off.
function defaultProductSuggestions() {
  return [...results.value]
    .sort((a, b) => a.product.name.localeCompare(b.product.name))
    .map(r => ({ id: r.product.id, label: r.product.name, sub: r.product.sku }))
}

function onProductSearchFocus() {
  if (!productSearchText.value) {
    productSuggest.suggestions.value = defaultProductSuggestions()
    productSuggest.open.value = productSuggest.suggestions.value.length > 0
    productSuggest.activeIndex.value = -1
  } else {
    productSuggest.reopen()
  }
}

function onProductSearchInput() {
  if (!productSearchText.value) {
    onProductSearchFocus()
    return
  }
  productSuggest.query(productSearchText.value)
}

function onProductSearchBlur() {
  setTimeout(() => {
    productSuggest.close()
    const r = results.value.find(row => row.product.id === selectedForecastProduct.value)
    productSearchText.value = r ? r.product.name : ''
  }, 150)
}

function pickProductSuggestion(item) {
  productSuggest.close()
  selectedForecastProduct.value = item.id
}

// ── Results table: search, sort, pagination ────────────────────────────────
const tablePage     = ref(1)
const tablePageSize = 20
const tableSearch   = ref('')

// Column sorting — click a header to sort asc, click again to reverse.
const sortKey = ref('fsn')   // default: Fast → Slow → Non-moving (same as before)
const sortDir = ref('asc')

const fsnRank = { fast: 0, slow: 1, non_moving: 2 }

// Restocking alert rule (one rule everywhere): current stock <= Reorder Point (ROP).
// ROP is the one computed by Run Analytics; the product's own reorder_point is only
// used when analytics have not been run for that product yet.
function needsReorder(r) {
  const rop = Number(r?.analytics?.rop_value)
  const stock = Number(r?.product?.stock_quantity ?? 0)
  if (r?.analytics && Number.isFinite(rop)) return stock <= rop
  return stock <= Number(r?.product?.reorder_point ?? 0)
}

// Aging of non-moving products: days since the last sale.
const nonMovingRows = computed(() => results.value.filter(r => r.analytics?.fsn_classification === 'non_moving'))
const nonMovingTotal = computed(() => nonMovingRows.value.length)
const agingBuckets = computed(() => {
  const days = r => {
    const w = Number(r.analytics?.result_data?.weeks_no_sale)
    return Number.isFinite(w) ? w * 7 : Infinity   // never sold = oldest bucket
  }
  const defs = [
    { label: 'Sold within 90 days', tone: 'a-ok',   test: d => d <= 90 },
    { label: '91 – 180 days',       tone: 'a-warn', test: d => d > 90 && d <= 180 },
    { label: '181 – 365 days',      tone: 'a-old',  test: d => d > 180 && d <= 365 },
    { label: 'Over 365 days',       tone: 'a-dead', test: d => d > 365 },
  ]
  return defs.map(b => ({ ...b, count: nonMovingRows.value.filter(r => b.test(days(r))).length }))
})

const toNum = v => {
  const n = parseFloat(v)
  return Number.isFinite(n) ? n : null
}

// Each column's sortable value. Return null for "no data" so it always sinks to the bottom.
const sortAccessors = {
  product: r => r.product.name?.toLowerCase() ?? '',
  stock:   r => toNum(r.product.stock_quantity),
  demand:  r => toNum(r.analytics?.predicted_demand),
  eoq:     r => toNum(r.analytics?.eoq_value),
  rop:     r => toNum(r.analytics?.rop_value),
  fsn:     r => fsnRank[r.analytics?.fsn_classification] ?? 3,
  alert:   r => {
    return needsReorder(r) ? 0 : 1      // 0 = Reorder Now (stock <= ROP), 1 = OK
  },
}

const sortedResults = computed(() => {
  const query = tableSearch.value.trim().toLowerCase()
  let arr = results.value
  if (query) {
    arr = arr.filter(r =>
      r.product.name.toLowerCase().includes(query) || r.product.sku?.toLowerCase().includes(query)
    )
  }
  const get = sortAccessors[sortKey.value]
  const dir = sortDir.value === 'asc' ? 1 : -1
  return [...arr].sort((a, b) => {
    const va = get(a), vb = get(b)
    if (va === null && vb === null) return 0
    if (va === null) return 1      // missing values always last
    if (vb === null) return -1
    if (typeof va === 'string') return va.localeCompare(vb) * dir
    return (va - vb) * dir
  })
})

const tableLastPage  = computed(() => Math.max(1, Math.ceil(sortedResults.value.length / tablePageSize)))
const pagedResults   = computed(() => {
  const start = (tablePage.value - 1) * tablePageSize
  return sortedResults.value.slice(start, start + tablePageSize)
})
const tablePageFrom  = computed(() => sortedResults.value.length === 0 ? 0 : (tablePage.value - 1) * tablePageSize + 1)
const tablePageTo    = computed(() => Math.min(tablePage.value * tablePageSize, sortedResults.value.length))

function setSort(key) {
  if (sortKey.value === key) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortDir.value = 'asc'
  }
  tablePage.value = 1
}

function sortIcon(key) {
  if (sortKey.value !== key) return '↕'
  return sortDir.value === 'asc' ? '↑' : '↓'
}

// ── Monthly forecast accuracy ────────────────────────────────────────────────
function formatMonth(label) {
  const d = new Date(label)
  return isNaN(d) ? label : d.toLocaleDateString('en-PH', { month: 'short', year: 'numeric' })
}

const monthlyWmape = computed(() => {
  return Number(monthlyForecast.value?.wmape ?? summary.value?.monthly_wmape ?? 0)
})

const monthlyWmapeClass = computed(() => {
  const v = Number(monthlyWmape.value || 0)
  if (v <= 20) return 'good'
  if (v <= 40) return 'warn'
  return 'poor'
})

const monthlyForecastRows = computed(() => {
  if (!monthlyForecast.value) return []

  const labels = monthlyForecast.value.labels ?? []
  const actual = monthlyForecast.value.actual ?? []
  const forecast = monthlyForecast.value.forecast ?? []
  const wmape = monthlyForecast.value.wmape_series ?? []

  const data = labels.map((label, i) => ({
    label: formatMonth(label),
    actual: Number(actual[i] ?? 0).toFixed(1),
    forecast: Number(forecast[i] ?? 0).toFixed(1),
    wmape: Number.isFinite(Number(wmape[i])) ? `${Number(wmape[i]).toFixed(2)}%` : `${Number(monthlyWmape.value || 0).toFixed(2)}%`
  }))

  return data.slice(-6)
})

const monthlyForecastChartData = computed(() => {
  if (!monthlyForecast.value) return null

  const labels   = monthlyForecast.value.labels   ?? []
  const actual   = monthlyForecast.value.actual   ?? []
  const forecast = monthlyForecast.value.forecast ?? []
  const start    = Math.max(0, labels.length - 6)

  return {
    labels: labels.slice(start).map(formatMonth),
    datasets: [
      {
        type: 'bar',
        label: 'Actual Sales',
        data: actual.slice(start).map(Number),
        backgroundColor: 'rgba(59,130,246,0.4)',
        borderColor: '#3b82f6',
        borderWidth: 1,
        borderRadius: 6,
        order: 2,
      },
      {
        type: 'line',
        label: 'Forecast',
        data: forecast.slice(start).map(Number),
        borderColor: '#f97316',
        borderWidth: 2.5,
        borderDash: [7, 4],
        pointRadius: 5,
        pointBackgroundColor: '#f97316',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        tension: 0.3,
        fill: false,
        order: 1,
      },
    ],
  }
})

const monthlyForecastChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false }, // legend is already shown in the card header
    tooltip: { mode: 'index', intersect: false },
  },
  scales: {
    x: { ticks: { font: { size: 10 } }, grid: { display: false } },
    y: { beginAtZero: true, ticks: { font: { size: 10 } }, grid: { color: '#f3f4f6' },
         title: { display: true, text: 'Units / month', font: { size: 10 }, color: '#9ca3af' } },
  },
}

// ── Threshold settings (Low-Stock sensitivity + FSN cutoffs) ────────────────
const THRESHOLD_DEFAULTS = {
  low_stock_sensitivity_percent:   100,
  fsn_fast_threshold_percent:      50,
  fsn_nonmoving_threshold_percent: 10,
  fsn_dead_stock_weeks:            26,
  fsn_fast_min_daily_avg:          1.0,
}

const showThresholds     = ref(false)
const thresholds         = ref({ ...THRESHOLD_DEFAULTS })
const thresholdsLoading  = ref(false)
const thresholdsSaving   = ref(false)
const thresholdsBanner   = ref(null)
let thresholdsLoaded     = false

function applyThresholdResponse(data) {
  thresholds.value = {
    low_stock_sensitivity_percent:   data.low_stock_sensitivity_percent,
    fsn_fast_threshold_percent:      data.fsn_fast_threshold_percent,
    fsn_nonmoving_threshold_percent: data.fsn_nonmoving_threshold_percent,
    fsn_dead_stock_weeks:            data.fsn_dead_stock_weeks,
    fsn_fast_min_daily_avg:          Number(data.fsn_fast_min_daily_avg),
  }
}

async function loadThresholds() {
  thresholdsLoading.value = true
  try {
    const { data } = await api.get('/settings')
    applyThresholdResponse(data)
    thresholdsLoaded = true
  } catch (e) {
    thresholdsBanner.value = { type: 'error', text: 'Could not load thresholds. Make sure the backend is running.' }
  } finally {
    thresholdsLoading.value = false
  }
}

function resetThresholdDefaults() {
  thresholds.value = { ...THRESHOLD_DEFAULTS }
  thresholdsBanner.value = { type: 'info', text: 'Defaults loaded — click Save Thresholds to apply them.' }
}

async function saveThresholds() {
  thresholdsSaving.value = true
  thresholdsBanner.value = null
  try {
    const { data } = await api.put('/settings', thresholds.value)
    applyThresholdResponse(data)
    const successText = 'Saved. Alerts use the new sensitivity immediately; re-run Analytics to apply the FSN changes.'
    thresholdsBanner.value = { type: 'success', text: successText }
    flashStore.set(successText, 'success')
  } catch (e) {
    const msg = Object.values(e.response?.data?.errors ?? {}).flat().join(' ') || 'Could not save thresholds.'
    thresholdsBanner.value = { type: 'error', text: msg }
    flashStore.set(msg, 'error')
  } finally {
    thresholdsSaving.value = false
  }
}

watch(showThresholds, (open) => {
  if (open && !thresholdsLoaded) loadThresholds()
})

// ── Loading state ─────────────────────────────────────────
const loadingStage   = ref('')
const loadingElapsed = ref(0)
const loadingDot     = ref(0)

const STAGES = [
  { label: 'Loading sales history',        threshold: 0  },
  { label: 'Running ARIMA forecasting',    threshold: 3  },
  { label: 'Computing EOQ & ROP values',   threshold: 10 },
  { label: 'Classifying products (FSN)',   threshold: 18 },
  { label: 'Finalizing analytics report',  threshold: 26 },
]

const runningSteps = computed(() => {
  const elapsed = loadingElapsed.value
  return STAGES.map((s, i) => {
    const next = STAGES[i + 1]
    if (next && elapsed >= next.threshold) return { ...s, state: 'done' }
    if (elapsed >= s.threshold)            return { ...s, state: 'active' }
    return { ...s, state: 'pending' }
  })
})

let _elapsedTimer = null
let _dotTimer     = null

function startLoadingTimers() {
  loadingElapsed.value = 0
  loadingDot.value     = 0
  loadingStage.value   = STAGES[0].label

  _elapsedTimer = setInterval(() => {
    loadingElapsed.value++
    const active = [...STAGES].reverse().find(s => loadingElapsed.value >= s.threshold)
    if (active) loadingStage.value = active.label
  }, 1000)

  _dotTimer = setInterval(() => {
    loadingDot.value = (loadingDot.value + 1) % 3
  }, 500)
}

function stopLoadingTimers() {
  clearInterval(_elapsedTimer)
  clearInterval(_dotTimer)
  _elapsedTimer = null
  _dotTimer     = null
}

watch(results, (val) => {
  tablePage.value = 1
  const ids = new Set(val.map(r => r.product.id))
  if (!ids.has(selectedForecastProduct.value)) {
    selectedForecastProduct.value = val[0]?.product.id ?? null
  }
})

function openDetail(r) { selected.value = r }

function fsnLabel(cls) {
  return { fast: 'Fast Moving', slow: 'Slow Moving', non_moving: 'Non-Moving' }[cls] ?? '—'
}

function methodLabel(rd) {
  if (!rd) return 'ARIMA(1,1,1)'
  if (rd.method_used === 'croston')      return "Croston's Method (intermittent demand)"
  if (rd.method_used === 'ses')          return 'Optimised SES (non-moving)'
  if (rd.method_used === 'auto_arima') {
    const o = rd.arima_order ?? [1, 1, 1]
    return `Auto-ARIMA(${o.join(',')})`
  }
  if (rd.used_fallback) return 'Exponential Smoothing (fallback)'
  return 'ARIMA(1,1,1)'
}

// The four FSN thresholds are admin-configurable (Settings page) and are
// stamped into each analytics run's result_data at compute time, so the copy
// below always matches whatever was actually applied — never a hardcoded
// guess that can drift out of sync with the real setting.
function fsnThresholds(rd) {
  return {
    fast:      rd?.fsn_fast_threshold_percent      ?? 50,
    nonMoving: rd?.fsn_nonmoving_threshold_percent ?? 10,
    deadWeeks: rd?.fsn_dead_stock_weeks            ?? 26,
    minDaily:  rd?.fsn_fast_min_daily_avg          ?? 1,
  }
}

function fsnDescription(cls, rd) {
  const t = fsnThresholds(rd)
  return {
    fast:       `Active in ≥${t.fast}% of the weeks since its first sale or ≥${t.minDaily} unit/day average. Prioritise replenishment and maintain higher safety stock.`,
    slow:       `Active in ${t.nonMoving}–${t.fast}% of the weeks since its first sale. Monitor levels regularly and avoid over-ordering.`,
    non_moving: `Active in <${t.nonMoving}% of the weeks since its first sale or no sale in ≥${t.deadWeeks} weeks. Review for dead stock — consider promotions or clearance.`,
  }[cls] ?? ''
}

// Turnover ratio = annual units sold ÷ current stock on hand — how many times
// stock fully rotates per year. Independent of returns/refunds (units-sold based).
function turnoverLabel(ratio) {
  if (ratio === null || ratio === undefined) return '—'
  return `${Number(ratio).toFixed(1)}x`
}

function turnoverPercent(ratio) {
  if (ratio === null || ratio === undefined) return '—'
  return `${(Number(ratio) * 100).toFixed(0)}%`
}

function turnoverDescription(ratio) {
  if (ratio === null || ratio === undefined) return 'No stock on hand to compute a ratio against — restock to enable this metric.'
  const r = Number(ratio)
  if (r >= 6)  return 'Stock rotates frequently — capital isn\'t tied up for long in this item.'
  if (r >= 2)  return 'Moderate rotation — stock is sold and replenished a few times a year.'
  if (r > 0)   return 'Stock rotates slowly — capital sits in this item for a long stretch before it sells through.'
  return 'No sales recorded against current stock — a strong dead-stock signal alongside its FSN classification.'
}

function daysRemaining(r) {
  const daily = predictedDaily(r)
  if (!daily) return '∞'
  return Math.max(0, Math.round(r.product.stock_quantity / daily))
}

function wmapePct(rd) {
  return rd?.wmape_pct ?? rd?.mape_pct ?? null
}

function mapeAccuracy(mapePct) {
  return Math.max(0, 100 - mapePct).toFixed(1)
}

function mapeClass(mapePct) {
  if (mapePct <= 30) return 'mape-good'
  if (mapePct <= 60) return 'mape-warn'
  return 'mape-poor'
}

function holdingCost(r) {
  return Number((r.product?.cost_price ?? 0) * 0.20).toLocaleString('en-PH', { minimumFractionDigits: 2 })
}

const detailChartData = computed(() => {
  if (!selected.value) return { labels: [], datasets: [] }
  const r = selected.value
  return {
    labels: ['Current Stock', 'Expected Sales (30d)', 'Best Order Qty', 'Reorder Point'],
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

const productLabels = computed(() => results.value.map(r => r.product.sku ?? r.product.name))

const eoqChartHeight = computed(() => Math.max(260, results.value.length * 36) + 'px')

const stockRunwayItems = computed(() => {
  return [...results.value]
    .map(r => ({ r, days: daysLeft(r) }))
    .sort((a, b) => a.days - b.days)
    .slice(0, 15)
})

// Compact height that grows with the number of bars instead of a fixed tall block.
const stockRunwayHeight = computed(() => Math.max(140, stockRunwayItems.value.length * 26) + 'px')

const selectedForecastRow = computed(() =>
  results.value.find(r => r.product.id === selectedForecastProduct.value) ?? null
)

const forecastLineData = computed(() => {
  const r = selectedForecastRow.value
  if (!r?.analytics?.result_data) return { labels: [], datasets: [] }

  const rd             = r.analytics.result_data
  const weeklySeries   = rd.weekly_series     ?? []
  const forecastMonths = rd.forecast_monthly  ?? []
  const lowerMonths    = rd.conf_lower_monthly ?? forecastMonths.map(() => 0)
  const upperMonths    = rd.conf_upper_monthly ?? forecastMonths

  const histSlice = weeklySeries.slice(-1)
  const histLen   = histSlice.length
  const fLen      = forecastMonths.length
  const lastHist  = histLen > 0 ? histSlice[histLen - 1] : 0

  const labels = ['Now', ...Array.from({ length: fLen }, (_, i) => `+${i + 1}m`)]

  return {
    labels,
    datasets: [
      {
        label: 'Current',
        data: [lastHist, ...Array(fLen).fill(null)],
        borderColor: '#3b82f6',
        backgroundColor: 'rgba(59,130,246,0.07)',
        borderWidth: 2.5,
        pointRadius: [6, ...Array(fLen).fill(0)],
        pointBackgroundColor: '#3b82f6',
        pointHoverRadius: 5,
        tension: 0.35,
        fill: false,
        order: 3,
      },
      {
        label: 'Monthly Forecast',
        data: [lastHist, ...forecastMonths],
        borderColor: '#f97316',
        borderWidth: 2.5,
        borderDash: [7, 4],
        pointRadius: [0, ...Array(fLen).fill(5)],
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
        data: [lastHist, ...upperMonths],
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
        data: [lastHist, ...lowerMonths],
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
      labels: { font: { size: 11 }, boxWidth: 14, padding: 12, filter: item => !item.text.startsWith('95% CI') },
    },
    tooltip: {
      mode: 'index', intersect: false,
      callbacks: { label: ctx => ctx.parsed.y === null ? null : ` ${ctx.dataset.label}: ${Number(ctx.parsed.y).toFixed(1)} units` },
    },
  },
  scales: {
    x: { ticks: { font: { size: 10 } }, grid: { color: '#f3f4f6' } },
    y: { beginAtZero: true, ticks: { font: { size: 10 } }, grid: { color: '#f3f4f6' },
         title: { display: true, text: 'Units / month', font: { size: 10 }, color: '#9ca3af' } },
  },
}

function predictedDaily(r) {
  const predicted = parseFloat(r.analytics?.predicted_demand ?? 0)
  if (predicted > 0) return predicted / 30
  return r.analytics?.result_data?.avg_daily ?? 0
}

function daysLeft(r) {
  const daily = predictedDaily(r)
  const stock = Math.max(0, r.product?.stock_quantity ?? 0)
  return daily > 0 ? Math.min(365, Math.round(stock / daily)) : 365
}

const stockRunwayData = computed(() => {
  const items = stockRunwayItems.value
  return {
    labels: items.map(({ r }) => r.product.sku ?? r.product.name),
    datasets: [{
      label: 'Days of Stock Remaining',
      // Bars are capped at 30d — the axis only needs to distinguish "how soon", not the full 365d range.
      data: items.map(({ days }) => Math.min(days, 30)),
      backgroundColor: items.map(({ days }) =>
        days < 14 ? 'rgba(239,68,68,0.82)' : days < 30 ? 'rgba(234,179,8,0.82)' : 'rgba(34,197,94,0.82)'
      ),
      borderColor: items.map(({ days }) =>
        days < 14 ? '#ef4444' : days < 30 ? '#ca8a04' : '#16a34a'
      ),
      borderWidth: 1,
      borderRadius: 5,
      barThickness: 16,
    }],
  }
})

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

  const eoqIdx   = qty.reduce((b, q, i) => Math.abs(q - eoq) < Math.abs(qty[b] - eoq) ? i : b, 0)
  const eoqMarker = Array(steps).fill(null)
  eoqMarker[eoqIdx] = totalCosts[eoqIdx]

  return {
    labels: qty.map(String),
    datasets: [
      { label: 'Total Cost', data: totalCosts, borderColor: '#3b82f6', borderWidth: 2.5, pointRadius: 0, tension: 0.3, fill: false, order: 3 },
      { label: 'Ordering Cost', data: orderCosts, borderColor: '#f97316', borderWidth: 1.5, borderDash: [6,4], pointRadius: 0, tension: 0.15, fill: false, order: 2 },
      { label: 'Storage Cost', data: holdCosts, borderColor: '#22c55e', borderWidth: 1.5, borderDash: [6,4], pointRadius: 0, tension: 0.15, fill: false, order: 1 },
      { label: `EOQ = ${Math.round(eoq)} units`, data: eoqMarker, borderColor: '#7c3aed', backgroundColor: '#7c3aed', borderWidth: 0, pointRadius: eoqMarker.map(v => v !== null ? 9 : 0), pointHoverRadius: 11, showLine: false, order: 4 },
    ],
  }
})

const eoqCurveOptions = {
  responsive: true, maintainAspectRatio: false,
  plugins: {
    legend: { position: 'top', labels: { font: { size: 10 }, boxWidth: 14, padding: 10 } },
    tooltip: { mode: 'index', intersect: false,
      callbacks: { title: ctx => `Order Qty: ${ctx[0]?.label} units`, label: ctx => ctx.parsed.y !== null ? ` ${ctx.dataset.label}: ₱${Number(ctx.parsed.y).toFixed(2)}` : null } },
  },
  scales: {
    x: { ticks: { font: { size: 9 }, maxTicksLimit: 10 }, grid: { color: '#f3f4f6' }, title: { display: true, text: 'Order Quantity (units)', font: { size: 10 }, color: '#9ca3af' } },
    y: { beginAtZero: true, ticks: { font: { size: 9 } }, grid: { color: '#f3f4f6' }, title: { display: true, text: 'Annual Cost (₱)', font: { size: 10 }, color: '#9ca3af' } },
  },
}

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

  const simDays   = 120
  const stockData = []
  let   pendingOrder = null

  for (let d = 0; d <= simDays; d++) {
    if (pendingOrder !== null && d === pendingOrder) { stock += eoq; pendingOrder = null }
    stockData.push(Math.max(0, stock))
    if (stock <= rop && pendingOrder === null) pendingOrder = d + lt
    stock -= avgDaily
  }

  const labels = Array.from({ length: simDays + 1 }, (_, i) => `D${i}`)

  return {
    labels,
    datasets: [
      { label: 'Stock Level', data: stockData, borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.08)', borderWidth: 2, pointRadius: 0, pointHoverRadius: 4, tension: 0, fill: true, order: 3 },
      { label: `ROP (${Math.round(rop)} units)`, data: Array(simDays + 1).fill(rop), borderColor: '#f97316', borderWidth: 2, borderDash: [6,4], pointRadius: 0, tension: 0, fill: false, order: 2 },
      { label: `Safety Stock (${Math.round(ss)} units)`, data: Array(simDays + 1).fill(ss), borderColor: '#ef4444', borderWidth: 1.5, borderDash: [3,3], pointRadius: 0, tension: 0, fill: false, order: 1 },
    ],
  }
})

const ropSimOptions = {
  responsive: true, maintainAspectRatio: false,
  plugins: {
    legend: { position: 'top', labels: { font: { size: 10 }, boxWidth: 14, padding: 10 } },
    tooltip: { mode: 'index', intersect: false, callbacks: { title: ctx => `Day ${ctx[0]?.dataIndex}`, label: ctx => ctx.parsed.y !== null ? ` ${ctx.dataset.label}: ${Number(ctx.parsed.y).toFixed(1)} units` : null } },
  },
  scales: {
    x: { ticks: { font: { size: 9 }, maxTicksLimit: 13 }, grid: { color: '#f3f4f6' }, title: { display: true, text: 'Days', font: { size: 10 }, color: '#9ca3af' } },
    y: { beginAtZero: true, ticks: { font: { size: 9 } }, grid: { color: '#f3f4f6' }, title: { display: true, text: 'Stock Level (units)', font: { size: 10 }, color: '#9ca3af' } },
  },
}

const fsnActivityData = computed(() => {
  const labels = productLabels.value
  const actPct = results.value.map(r => Math.round((r.analytics?.result_data?.activity_ratio ?? 0) * 100))
  const barColors   = results.value.map(r => ({ fast: 'rgba(34,197,94,0.75)', slow: 'rgba(250,204,21,0.75)' }[r.analytics?.fsn_classification] ?? 'rgba(248,113,113,0.75)'))
  const borderColors = results.value.map(r => ({ fast: '#16a34a', slow: '#ca8a04' }[r.analytics?.fsn_classification] ?? '#ef4444'))
  const n = labels.length
  // All rows from the same run share the same (admin-configured) thresholds —
  // read them off the first row rather than hardcoding 50/10.
  const t = fsnThresholds(results.value[0]?.analytics?.result_data)
  return {
    labels,
    datasets: [
      { type: 'bar', label: 'Sales Activity (%)', data: actPct, backgroundColor: barColors, borderColor: borderColors, borderWidth: 1, borderRadius: 4, order: 2 },
      { type: 'line', label: `Fast-Moving Threshold (${t.fast}%)`, data: Array(n).fill(t.fast), borderColor: '#16a34a', borderWidth: 1.5, borderDash: [5,4], pointRadius: 0, fill: false, order: 1 },
      { type: 'line', label: `Slow/Dead Stock Threshold (${t.nonMoving}%)`, data: Array(n).fill(t.nonMoving), borderColor: '#ef4444', borderWidth: 1.5, borderDash: [5,4], pointRadius: 0, fill: false, order: 0 },
    ],
  }
})

const fsnActivityOptions = {
  responsive: true, maintainAspectRatio: false,
  plugins: {
    legend: { position: 'top', labels: { font: { size: 10 }, boxWidth: 14, padding: 10 } },
    tooltip: { mode: 'index', intersect: false, callbacks: { label: ctx => ctx.parsed.y !== null ? ` ${ctx.dataset.label}: ${ctx.parsed.y}%` : null } },
  },
  scales: {
    x: { ticks: { font: { size: 10 }, maxRotation: 40, minRotation: 0 }, grid: { display: false } },
    y: { beginAtZero: true, max: 105, ticks: { font: { size: 10 }, callback: v => v + '%' }, grid: { color: '#f3f4f6' },
         title: { display: true, text: '% of weeks with sales activity (since first sale)', font: { size: 10 }, color: '#9ca3af' } },
  },
}

const barOptions = {
  responsive: true, maintainAspectRatio: false,
  plugins: { legend: { position: 'top', labels: { font: { size: 11 }, boxWidth: 12, padding: 16 } }, tooltip: { mode: 'index', intersect: false } },
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

const stockRunwayOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  indexAxis: 'y',
  plugins: {
    legend: { display: false },
    tooltip: {
      callbacks: {
        title: ctx => {
          const item = stockRunwayItems.value[ctx[0]?.dataIndex]
          return item ? item.r.product.name : ''
        },
        label: ctx => {
          // Use the true (uncapped) day count for the tooltip — the bar itself is capped at 30d.
          const days = stockRunwayItems.value[ctx.dataIndex]?.days ?? ctx.parsed.x
          const urgency = days < 14 ? '⚠ Critical' : days < 30 ? '● Warning' : '✓ Safe'
          const daysLabel = days >= 30 ? '30+' : `${days} days`
          return `  ${urgency} — ${daysLabel} remaining`
        },
      },
    },
  },
  scales: {
    y: {
      ticks: { font: { size: 12 }, color: '#374151' },
      grid: { display: false },
    },
    x: {
      beginAtZero: true,
      max: 30,
      ticks: {
        font: { size: 11 },
        color: '#9ca3af',
        stepSize: 5,
        callback: v => v === 30 ? '30+' : v + 'd',
      },
      grid: { color: '#f3f4f6' },
      title: { display: true, text: 'Days of stock remaining', font: { size: 11 }, color: '#9ca3af' },
    },
  },
}))

async function runAnalytics() {
  running.value = true
  startLoadingTimers()
  try {
    const { data } = await api.post('/analytics/run')
    if (!Array.isArray(data.results)) {
      throw new Error('Invalid analytics payload')
    }

    results.value = data.results
    computedAt.value = new Date(data.computed_at).toLocaleString('en-PH')
    await loadSummary()
    await loadMonthlyAccuracy()
  } catch (error) {
    console.error('runAnalytics failed:', error)
    flashStore.set(
      error.response?.data?.message || 'Analytics failed. Check backend logs.',
      'error'
    )
  } finally {
    stopLoadingTimers()
    running.value = false
  }
}

async function loadSummary() {
  try {
    const { data } = await api.get('/analytics/summary')
    summary.value = data

    if (Array.isArray(data.items)) {
      results.value = data.items.map(item => ({
        product: item.product,
        analytics: item
      }))
    }

    if (data.last_run) {
      // FIX: don't append 'T00:00:00' — last_run may already include a time,
      // which produced "Invalid Date".
      const d = new Date(data.last_run)
      computedAt.value = isNaN(d)
        ? String(data.last_run)
        : d.toLocaleString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
    }
  } catch (error) {
    console.error('loadSummary failed:', error)
    flashStore.set(
      error.response?.data?.message || 'Summary failed to load.',
      'error'
    )
  }
}

async function loadMonthlyAccuracy() {
  try {
    const { data } = await api.get('/analytics/monthly-accuracy')
    monthlyForecast.value = data
  } catch (error) {
    console.error('monthly accuracy failed:', error)
    monthlyForecast.value = null
  }
}

watch(selectedForecastProduct, (id) => {
  const r = results.value.find(row => row.product.id === id)
  productSearchText.value = r ? r.product.name : ''
})

onMounted(() => {
  loadSummary()
  loadMonthlyAccuracy()
})
</script>

<style scoped>
.analytics-page { padding: 28px 32px 40px; display: flex; flex-direction: column; gap: 24px; }

/* Header */
.page-header { display: flex; align-items: center; justify-content: space-between; }
.header-icon { width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, #3b82f6, #2563eb); display: flex; align-items: center; justify-content: center; color: #fff; box-shadow: 0 4px 12px rgba(59,130,246,.35); flex-shrink: 0; }
.page-title  { font-size: 1.25rem; font-weight: 800; color: #111827; margin: 0; }
.page-sub    { font-size: 12px; color: #6b7280; margin: 2px 0 0; }
.btn-run     { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border: none; border-radius: 11px; background: linear-gradient(135deg, #3b82f6, #2563eb); color: #fff; font-size: 13px; font-weight: 700; font-family: inherit; cursor: pointer; box-shadow: 0 4px 12px rgba(59,130,246,.35); transition: all .2s; }
.btn-run:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(59,130,246,.45); }
.btn-run:disabled { opacity: .65; cursor: not-allowed; }
.spin { animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.header-actions { display: flex; align-items: center; gap: 10px; }
.btn-thresholds {
  display: inline-flex; align-items: center; gap: 8px; padding: 10px 16px;
  border: 1.5px solid #e5e7eb; border-radius: 11px; background: #fff;
  color: #4b5563; font-size: 13px; font-weight: 700; font-family: inherit;
  cursor: pointer; transition: all .2s;
}
.btn-thresholds:hover { border-color: #a5b4fc; color: #4338ca; background: #f5f5ff; }
.btn-thresholds.active { border-color: #4338ca; color: #4338ca; background: #eef2ff; }

/* Threshold settings panel */
.thresholds-panel {
  background: #fff; border: 1.5px solid #e5e7eb; border-radius: 14px;
  padding: 20px 22px; box-shadow: 0 2px 8px rgba(0,0,0,.04);
}
.th-banner { padding: 10px 14px; border-radius: 9px; font-size: 12.5px; font-weight: 600; margin-bottom: 14px; }
.th-banner.success { background: #dcfce7; color: #15803d; border: 1.5px solid #86efac; }
.th-banner.error   { background: #fee2e2; color: #b91c1c; border: 1.5px solid #fca5a5; }
.th-banner.info    { background: #e0f2fe; color: #075985; border: 1.5px solid #bae6fd; }

.th-form  { display: flex; flex-direction: column; gap: 22px; }
.th-group + .th-group { padding-top: 20px; border-top: 1px dashed #e5e7eb; }
.th-group-title { font-size: 13.5px; font-weight: 700; color: #111827; margin: 0 0 3px; }
.th-group-sub   { font-size: 12px; color: #6b7280; margin: 0 0 14px; max-width: 68ch; line-height: 1.5; }

.th-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px 24px; }
.th-field { display: flex; flex-direction: column; gap: 5px; }
.th-group > .th-field { margin-top: 4px; }
.th-label { font-size: 11.5px; font-weight: 700; color: #374151; }
.th-input-row { display: flex; align-items: center; gap: 8px; }
.th-input {
  width: 100px; flex-shrink: 0; padding: 8px 11px; border: 1.5px solid #e5e7eb;
  border-radius: 9px; font-size: 13px; font-family: inherit; color: #111827;
  background: #fff; outline: none; transition: border-color .2s;
}
.th-input:focus { border-color: #4338ca; box-shadow: 0 0 0 3px rgba(67,56,202,.12); }
.th-unit { font-size: 12px; color: #6b7280; font-weight: 600; }
.th-hint { font-size: 11.5px; color: #9ca3af; margin: 0; line-height: 1.5; }

.th-warn {
  margin-top: 4px; padding: 9px 13px; border-radius: 9px;
  background: #fef3c7; color: #92400e; font-size: 12px; font-weight: 600;
}

.th-actions { display: flex; justify-content: flex-end; gap: 10px; }
.th-btn-ghost {
  padding: 9px 16px; border: 1.5px solid #e5e7eb; border-radius: 10px;
  background: #fff; font-size: 12.5px; font-weight: 600; color: #4b5563;
  font-family: inherit; cursor: pointer; transition: background .2s;
}
.th-btn-ghost:hover:not(:disabled) { background: #f9fafb; }
.th-btn-ghost:disabled { opacity: .6; cursor: not-allowed; }
.th-btn-save {
  padding: 9px 20px; border: none; border-radius: 10px;
  background: linear-gradient(135deg, #4338ca, #3730a3); color: #fff;
  font-size: 12.5px; font-weight: 700; font-family: inherit; cursor: pointer;
  box-shadow: 0 4px 12px rgba(67,56,202,.3); transition: opacity .2s;
}
.th-btn-save:disabled { opacity: .6; cursor: not-allowed; }

@media (max-width: 640px) { .th-grid { grid-template-columns: 1fr; } }

/* Algorithm cards */
.algo-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
.algo-card { background: #fff; border-radius: 14px; border: 1.5px solid #f3f4f6; box-shadow: 0 2px 8px rgba(0,0,0,.04); padding: 16px 18px; display: flex; align-items: flex-start; gap: 12px; border-top-width: 4px; }
.algo-card.algo-blue   { border-top-color: #3b82f6; }
.algo-card.algo-purple { border-top-color: #a855f7; }
.algo-card.algo-orange { border-top-color: #f97316; }
.algo-card.algo-emerald { border-top-color: #10b981; }
.algo-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.algo-blue   .algo-icon { background: #eff6ff; color: #2563eb; }
.algo-purple .algo-icon { background: #f5f3ff; color: #9333ea; }
.algo-orange .algo-icon { background: #fff7ed; color: #ea580c; }
.algo-emerald .algo-icon { background: #ecfdf5; color: #059669; }
.algo-label { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }
.algo-blue   .algo-label { color: #2563eb; }
.algo-purple .algo-label { color: #9333ea; }
.algo-orange .algo-label { color: #ea580c; }
.algo-emerald .algo-label { color: #059669; }
.algo-name  { font-size: 13px; font-weight: 700; color: #111827; margin: 3px 0; }
.algo-desc  { font-size: 11px; color: #9ca3af; line-height: 1.5; }

/* FSN Summary */
.fsn-summary { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
.fsn-card    { background: #fff; border-radius: 14px; border: 1.5px solid #f3f4f6; box-shadow: 0 2px 8px rgba(0,0,0,.04); padding: 18px 20px; display: flex; align-items: center; gap: 16px; }
.fsn-icon    { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.fsn-icon.fast { background: #ecfdf5; color: #059669; }
.fsn-icon.slow { background: #fefce8; color: #ca8a04; }
.fsn-icon.non  { background: #fef2f2; color: #dc2626; }
.fsn-count   { font-size: 2rem; font-weight: 800; line-height: 1; }
.fsn-count.fast { color: #059669; }
.fsn-count.slow { color: #ca8a04; }
.fsn-count.non  { color: #dc2626; }
.fsn-lbl     { font-size: 12px; font-weight: 700; color: #374151; margin-top: 2px; }
.fsn-sub     { font-size: 11px; color: #9ca3af; }

/* Charts */
.charts-section { display: flex; flex-direction: column; gap: 20px; }

.product-selector { display: flex; align-items: center; gap: 12px; background: #fff; border: 1.5px solid #dbeafe; border-radius: 12px; padding: 12px 16px; box-shadow: 0 2px 8px rgba(0,0,0,.04); flex-wrap: wrap; }
.sel-icon  { width: 28px; height: 28px; border-radius: 7px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.sel-label { font-size: 12px; font-weight: 700; color: #374151; }
.sel-select { border: 1.5px solid #e5e7eb; border-radius: 8px; padding: 6px 12px; font-size: 13px; font-family: inherit; color: #374151; background: #f9fafb; outline: none; width: 100%; min-width: 220px; transition: border-color .2s; }
.sel-select:focus { border-color: #3b82f6; }
.sel-hint  { font-size: 11px; color: #9ca3af; margin-left: auto; }
.product-search-wrap { position: relative; min-width: 220px; }

.chart-row   { display: grid; gap: 20px; }
.chart-row-1 { grid-template-columns: 1fr 2fr; }
.chart-row-2 { grid-template-columns: 1fr 1fr; }

.chart-card  { background: #fff; border-radius: 14px; border: 1.5px solid #f3f4f6; box-shadow: 0 2px 8px rgba(0,0,0,.04); overflow: visible; display: flex; flex-direction: column; }

.chart-header { display: flex; align-items: flex-start; gap: 10px; padding: 14px 18px; border-bottom: 1.5px solid transparent; flex-wrap: wrap; }
.ch-blue   { background: #eff6ff; border-bottom-color: #dbeafe; }
.ch-purple { background: #f5f3ff; border-bottom-color: #ede9fe; }
.ch-orange { background: #fff7ed; border-bottom-color: #fed7aa; }
.ch-emerald { background: #ecfdf5; border-bottom-color: #d1fae5; }
.ch-gray   { background: #f9fafb; border-bottom-color: #f3f4f6; }

.ch-dot    { width: 24px; height: 24px; border-radius: 6px; flex-shrink: 0; margin-top: 1px; }
.ch-dot.blue    { background: #3b82f6; }
.ch-dot.purple  { background: #a855f7; }
.ch-dot.orange  { background: #f97316; }
.ch-dot.emerald { background: #10b981; }
.ch-dot.gray    { background: #6b7280; }

.ch-title  { font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: .5px; color: #374151; }
.ch-sub    { font-size: 11px; color: #9ca3af; margin-top: 2px; }
.ch-legend { display: flex; align-items: center; gap: 12px; margin-left: auto; flex-wrap: wrap; }
.leg-item  { display: flex; align-items: center; gap: 5px; font-size: 11px; color: #6b7280; }
.leg-line  { display: inline-block; width: 20px; height: 2px; border-radius: 1px; }
.blue-line   { background: #3b82f6; }
.orange-dash { background: none; border-top: 2px dashed #f97316; }
.red-dash    { background: none; border-top: 2px dashed #ef4444; }
.leg-band    { display: inline-block; width: 16px; height: 10px; border-radius: 2px; background: rgba(249,115,22,0.2); border: 1px dashed rgba(249,115,22,.5); }
.leg-bar     { display: inline-block; width: 14px; height: 8px; border-radius: 2px; }
.blue-bar    { background: rgba(59,130,246,0.4); }
.leg-dot     { display: inline-block; width: 10px; height: 10px; border-radius: 3px; }
.red-dot     { background: rgba(239,68,68,0.75); }
.yellow-dot  { background: rgba(234,179,8,0.75); }
.green-dot   { background: rgba(34,197,94,0.75); }

.chart-body { padding: 16px; flex: 1; }

/* Results wrapper (spacing between Monthly Accuracy card and Results table) */
.results-wrapper { display: flex; flex-direction: column; gap: 20px; }

/* Monthly Forecast Accuracy */
.mf-body { display: flex; flex-direction: column; gap: 16px; }

.mf-metric {
  display: flex; align-items: center; justify-content: space-between;
  padding: 12px 16px; border-radius: 12px; border: 1.5px solid transparent;
}
.mf-metric.good { background: #ecfdf5; border-color: #d1fae5; color: #15803d; }
.mf-metric.warn { background: #fffbeb; border-color: #fde68a; color: #b45309; }
.mf-metric.poor { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }
.mf-metric-label { font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: .5px; margin: 0; }
.mf-metric-sub   { font-size: 11px; opacity: .7; margin: 2px 0 0; }
.mf-metric-value { font-size: 1.6rem; font-weight: 800; line-height: 1; margin: 0; }

.mf-chart { height: 240px; }

.mf-table-wrap { overflow-x: auto; border: 1.5px solid #f3f4f6; border-radius: 10px; }
.mf-table { width: 100%; border-collapse: collapse; }
.mf-table thead tr { background: #f9fafb; border-bottom: 2px solid #f3f4f6; }
.mf-table thead th {
  padding: 7px 12px; text-align: left; font-size: 10px; font-weight: 700;
  color: #6b7280; text-transform: uppercase; letter-spacing: .5px;
}
.mf-table tbody tr { border-bottom: 1px solid #f9fafb; transition: background .15s; }
.mf-table tbody tr:last-child { border-bottom: none; }
.mf-table tbody tr:hover { background: #eff6ff; }
.mf-table td { padding: 7px 12px; font-size: 12px; color: #374151; }
.mf-month { font-weight: 700; color: #111827; }

/* Results table */
.results-card   { background: #fff; border-radius: 16px; border: 1.5px solid #f3f4f6; box-shadow: 0 2px 12px rgba(0,0,0,.05); overflow: hidden; }
.results-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; padding: 14px 20px; background: #f9fafb; border-bottom: 1.5px solid #f3f4f6; font-size: 13px; color: #4b5563; }
.results-search { position: relative; display: flex; align-items: center; flex: 1 1 220px; max-width: 320px; }
.results-search-icon { position: absolute; left: 10px; color: #9ca3af; pointer-events: none; }
.results-search-input { width: 100%; padding: 7px 30px; border: 1.5px solid #e5e7eb; border-radius: 8px; font-size: 12.5px; font-family: inherit; color: #374151; background: #fff; outline: none; transition: border-color .2s; }
.results-search-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.1); }
.results-search-clear { position: absolute; right: 6px; width: 18px; height: 18px; border: none; border-radius: 50%; background: #f3f4f6; color: #6b7280; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all .15s; }
.results-search-clear:hover { background: #fee2e2; color: #dc2626; }
.stale-badge { font-size: 11px; font-weight: 700; margin-left: 8px; color: #d97706; }
.stale-badge.fresh { color: #16a34a; }
.stale-badge.refreshing { color: #3b82f6; animation: pulse 1.2s ease-in-out infinite; }
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }
.results-title  { font-weight: 700; color: #111827; }
.results-count  { font-size: 11px; font-weight: 600; color: #6366f1; background: #eef2ff; border-radius: 20px; padding: 2px 9px; }
.results-computed { font-size: 11px; color: #9ca3af; }
.results-header-right { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; justify-content: flex-end; }

/* Sortable column headers */
.th-sortable { cursor: pointer; user-select: none; white-space: nowrap; }
.th-sortable:hover { color: #2563eb; }
.col-sort-arrow { font-size: 13px; font-weight: 900; color: #2563eb; margin-left: 5px; vertical-align: middle; }
.col-sort-arrow.inactive { color: #d1d5db; font-weight: 600; }
.th-sortable:hover .col-sort-arrow.inactive { color: #93c5fd; }
.table-wrap     { overflow-x: visible; }

.res-pagination {
  display: flex; align-items: center; justify-content: space-between;
  padding: 12px 20px; border-top: 1.5px solid #f3f4f6; background: #f9fafb;
}
.res-page-info  { font-size: 12px; color: #6b7280; font-weight: 500; }
.res-page-btns  { display: flex; align-items: center; gap: 8px; }
.res-page-btn {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 7px 14px; border: 1.5px solid #e5e7eb; border-radius: 8px;
  background: #fff; font-family: inherit; font-size: 12px; font-weight: 700;
  color: #374151; cursor: pointer; transition: all .2s;
}
.res-page-btn:hover:not(:disabled) { background: #eff6ff; border-color: #3b82f6; color: #2563eb; }
.res-page-btn:disabled { opacity: .4; cursor: not-allowed; }
.res-page-cur   { font-size: 12px; font-weight: 700; color: #111827; min-width: 52px; text-align: center; }

.res-table { width: 100%; border-collapse: collapse; }
.res-table thead tr { background: #f9fafb; border-bottom: 2px solid #f3f4f6; }
.res-table thead th { padding: 11px 16px; text-align: left; font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: .5px; }
.th-note { font-size: 10px; font-weight: 500; color: #9ca3af; }
.res-table tbody tr { border-bottom: 1px solid #f9fafb; transition: background .15s; cursor: pointer; }
.res-table tbody tr:hover { background: #eff6ff; }
.res-table td { padding: 13px 16px; font-size: 13px; color: #374151; vertical-align: middle; }
.res-product { font-weight: 700; color: #111827; }
.res-sku     { font-size: 11px; color: #9ca3af; font-family: 'Courier New', monospace; margin-top: 2px; }
.stock-ok    { font-weight: 700; color: #059669; }
.stock-low   { font-weight: 700; color: #dc2626; }
.val-blue    { font-weight: 700; color: #2563eb; }
.val-purple  { font-weight: 700; color: #9333ea; }
.val-orange  { font-weight: 700; color: #ea580c; }

.fsn-pill    { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: capitalize; }
.fsn-pill.fast        { background: #dcfce7; color: #15803d; }
.fsn-pill.slow        { background: #fef9c3; color: #92400e; }
.fsn-pill.non-moving  { background: #fee2e2; color: #b91c1c; }
.fsn-pill.unknown     { background: #f3f4f6; color: #6b7280; }

.alert-pill  { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.alert-pill.danger { background: #fee2e2; color: #b91c1c; }
.alert-pill.warn   { background: #fef9c3; color: #92400e; }
.alert-pill.ok     { background: #dcfce7; color: #15803d; }

/* WMAPE accuracy */
.mape-good { font-weight: 700; color: #15803d; }
.mape-warn { font-weight: 700; color: #d97706; }
.mape-poor { font-weight: 700; color: #b91c1c; }
.mape-sub  { display: block; font-size: 10px; color: #9ca3af; margin-top: 2px; }
.mape-detail { font-size: 11px; font-weight: 400; color: #9ca3af; margin-left: 4px; }

/* Running overlay */
.running-overlay {
  display: flex; flex-direction: column; gap: 12px;
}
.running-card {
  display: flex; align-items: center; gap: 20px;
  background: #fff; border: 1.5px solid #dbeafe; border-radius: 16px;
  padding: 20px 24px; box-shadow: 0 4px 20px rgba(59,130,246,.1);
}
.running-anim {
  position: relative; width: 56px; height: 56px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
}
.running-ring {
  position: absolute; inset: 0; border-radius: 50%;
  border: 3px solid #dbeafe;
  border-top-color: #3b82f6;
  animation: spin 1s linear infinite;
}
.running-icon { color: #3b82f6; z-index: 1; }
.running-text { flex: 1; min-width: 0; }
.running-title { font-size: 15px; font-weight: 800; color: #111827; margin: 0 0 4px; }
.running-stage { font-size: 13px; color: #3b82f6; font-weight: 600; margin: 0; }
.running-right { text-align: right; flex-shrink: 0; }
.running-elapsed {
  font-size: 22px; font-weight: 800; color: #111827;
  font-variant-numeric: tabular-nums; line-height: 1; margin-bottom: 6px;
}
.running-dots { display: flex; gap: 5px; justify-content: flex-end; }
.running-dots span {
  width: 8px; height: 8px; border-radius: 50%;
  background: #e5e7eb; transition: background .2s;
}
.running-dots span.active { background: #3b82f6; }

.running-steps {
  display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px;
}
.running-step {
  display: flex; flex-direction: column; align-items: center; gap: 6px;
  background: #fff; border: 1.5px solid #f3f4f6;
  border-radius: 12px; padding: 12px 10px; text-align: center;
  transition: border-color .3s, background .3s;
}
.running-step.active  { border-color: #bfdbfe; background: #eff6ff; }
.running-step.done    { border-color: #bbf7d0; background: #f0fdf4; }
.rs-icon {
  width: 24px; height: 24px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.running-step.pending .rs-icon { background: #f3f4f6; }
.running-step.active  .rs-icon { background: #dbeafe; }
.running-step.done    .rs-icon { background: #dcfce7; color: #16a34a; }
.rs-spin {
  width: 12px; height: 12px; border-radius: 50%;
  border: 2px solid #bfdbfe; border-top-color: #2563eb;
  animation: spin 0.7s linear infinite;
}
.rs-dot { width: 8px; height: 8px; border-radius: 50%; background: #d1d5db; }
.rs-label { font-size: 11px; font-weight: 600; color: #4b5563; line-height: 1.4; }
.running-step.active  .rs-label { color: #1d4ed8; }
.running-step.done    .rs-label { color: #15803d; }
.rs-done    { font-size: 10px; font-weight: 700; color: #15803d; background: #dcfce7; border-radius: 4px; padding: 1px 6px; }
.rs-working { font-size: 10px; font-weight: 700; color: #1d4ed8; background: #dbeafe; border-radius: 4px; padding: 1px 6px; }

/* Empty state */
.empty-state { padding: 64px 20px; text-align: center; background: #fff; border-radius: 16px; border: 2px dashed #e5e7eb; display: flex; flex-direction: column; align-items: center; }
.empty-icon  { width: 64px; height: 64px; border-radius: 16px; background: #eff6ff; color: #93c5fd; display: flex; align-items: center; justify-content: center; margin-bottom: 16px; }
.empty-title { font-size: 15px; font-weight: 700; color: #374151; margin-bottom: 6px; }
.empty-sub   { font-size: 13px; color: #9ca3af; margin-bottom: 4px; }

/* ════════════════════════════════════════════════════════════════ */
/* SLIDE-OVER PANEL                                                 */
/* ════════════════════════════════════════════════════════════════ */

/* Slide-over backdrop + panel */
.so-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  z-index: 9998;
}

.so-panel {
  position: fixed;
  top: 0;
  right: 0;
  height: 100vh;
  width: 100%;
  max-width: 480px;
  background: #fff;
  box-shadow: -8px 0 40px rgba(0, 0, 0, 0.18);
  z-index: 9999;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

/* Slide transition */
.so-enter-active {
  transition: transform 0.3s cubic-bezier(0.22, 0.68, 0, 1.2);
}

.so-leave-active {
  transition: transform 0.2s ease-in;
}

.so-enter-from,
.so-leave-to {
  transform: translateX(100%);
}

.so-enter-to,
.so-leave-from {
  transform: translateX(0);
}

/* ── HEADER (stays fixed at top) ──────────────────────────────── */
.sop-header {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 18px 20px;
  border-bottom: 1.5px solid #f3f4f6;
  background: #fff;
  flex-shrink: 0;
  z-index: 10;
  position: relative;
}

.sop-avatar {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  flex-shrink: 0;
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  color: #fff;
  font-size: 22px;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.sop-info {
  flex: 1;
  min-width: 0;
}

.sop-name {
  font-size: 15px;
  font-weight: 800;
  color: #111827;
  margin: 0 0 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.sop-sku {
  font-size: 11px;
  color: #9ca3af;
  font-family: 'Courier New', monospace;
  margin: 0 0 6px;
}

.sop-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.sop-fsn-badge {
  display: inline-block;
  padding: 3px 9px;
  border-radius: 20px;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.sop-fsn-badge.fast {
  background: #dcfce7;
  color: #15803d;
}

.sop-fsn-badge.slow {
  background: #fef9c3;
  color: #92400e;
}

.sop-fsn-badge.non-moving {
  background: #fee2e2;
  color: #b91c1c;
}

.sop-fsn-badge.unknown {
  background: #f3f4f6;
  color: #6b7280;
}

.sop-stock-badge {
  display: inline-block;
  padding: 3px 9px;
  border-radius: 20px;
  font-size: 10px;
  font-weight: 700;
}

.sop-stock-badge.danger {
  background: #fee2e2;
  color: #b91c1c;
}

.sop-stock-badge.ok {
  background: #dcfce7;
  color: #15803d;
}

.sop-close {
  width: 32px;
  height: 32px;
  border: none;
  background: #f3f4f6;
  border-radius: 8px;
  cursor: pointer;
  color: #6b7280;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: background 0.2s, color 0.2s;
}

.sop-close:hover {
  background: #e5e7eb;
  color: #374151;
}

/* ── BODY (SCROLLABLE) ────────────────────────────────────────── */
.sop-body {
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 16px;
  min-height: 0;
  align-items: stretch;
}

.sop-body > * {
  flex-shrink: 1;
  width: 100%;
}

.sop-body::-webkit-scrollbar {
  width: 10px;
}

.sop-body::-webkit-scrollbar-track {
  background: transparent;
}

.sop-body::-webkit-scrollbar-thumb {
  background: #d1d5db;
  border-radius: 5px;
  transition: background 0.2s;
}

.sop-body::-webkit-scrollbar-thumb:hover {
  background: #9ca3af;
}

.sop-body {
  scrollbar-width: thin;
  scrollbar-color: #d1d5db transparent;
}

/* ── Alert banner ─────────────────────────────────────────────────── */
.sop-alert-banner {
  display: flex;
  align-items: center;
  gap: 10px;
  background: #fff7ed;
  border: 1.5px solid #fed7aa;
  border-radius: 10px;
  padding: 10px 14px;
  font-size: 12px;
  font-weight: 600;
  color: #92400e;
  line-height: 1.5;
  flex-shrink: 0;
}

/* ── Metric cards ─────────────────────────────────────────────────── */
.sop-metrics {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.5rem;
  flex-shrink: 0;
  width: 100%;
}

.sop-metric {
  border-radius: 12px;
  padding: 12px 12px 10px;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  border: 1.5px solid transparent;
  overflow: visible;
}

.sop-metric.gray {
  background: #f9fafb;
  border-color: #f3f4f6;
}

.sop-metric.blue {
  background: #eff6ff;
  border-color: #dbeafe;
}

.sop-metric.purple {
  background: #f5f3ff;
  border-color: #ede9fe;
}

.sop-metric.orange {
  background: #fff7ed;
  border-color: #fed7aa;
}

.sop-metric-icon {
  width: 26px;
  height: 26px;
  border-radius: 7px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 4px;
  flex-shrink: 0;
}

.sop-metric.gray .sop-metric-icon {
  background: #e5e7eb;
  color: #6b7280;
}

.sop-metric.blue .sop-metric-icon {
  background: #dbeafe;
  color: #2563eb;
}

.sop-metric.purple .sop-metric-icon {
  background: #ede9fe;
  color: #9333ea;
}

.sop-metric.orange .sop-metric-icon {
  background: #fed7aa;
  color: #ea580c;
}

.sop-metric-val {
  font-size: 1.6rem;
  font-weight: 800;
  line-height: 1;
  color: #111827;
  word-break: break-word;
}

.sop-metric-val.red {
  color: #dc2626;
}

.sop-metric.blue .sop-metric-val {
  color: #2563eb;
}

.sop-metric.purple .sop-metric-val {
  color: #9333ea;
}

.sop-metric.orange .sop-metric-val {
  color: #ea580c;
}

.sop-metric-lbl {
  font-size: 11px;
  font-weight: 700;
  color: #374151;
}

.sop-metric-unit {
  font-size: 10px;
  color: #9ca3af;
}

/* ── Chart card ───────────────────────────────────────────────────── */
.sop-chart-card {
  background: #fff;
  border: 1.5px solid #f3f4f6;
  border-radius: 12px;
  overflow: visible;
  display: flex;
  flex-direction: column;
}

.sop-chart-header {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  background: #f9fafb;
  border-bottom: 1.5px solid #f3f4f6;
  font-size: 11px;
  font-weight: 700;
  color: #374151;
  flex-shrink: 0;
}

.sop-chart-card > div:last-child {
  flex-shrink: 1;
  overflow: visible;
  min-height: 240px;
}

/* ── Sections (ARIMA, EOQ, ROP, FSN) ──────────────────────────────── */
.sop-section {
  border: 0.1rem solid #f3f4f6;
  border-radius: 12px;
  overflow: visible;
  display: flex;
  flex-direction: column;
}

.sop-sec-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  padding: 10px 14px;
  flex-wrap: wrap;
  flex-shrink: 0;
}

.sop-sec-header.blue {
  background: #eff6ff;
  border-bottom: 1.5px solid #dbeafe;
}

.sop-sec-header.purple {
  background: #f5f3ff;
  border-bottom: 1.5px solid #ede9fe;
}

.sop-sec-header.orange {
  background: #fff7ed;
  border-bottom: 1.5px solid #fed7aa;
}

.sop-sec-header.green {
  background: #ecfdf5;
  border-bottom: 1.5px solid #d1fae5;
}

.sop-sec-header.yellow {
  background: #fefce8;
  border-bottom: 1.5px solid #fef08a;
}

.sop-sec-header.red {
  background: #fef2f2;
  border-bottom: 1.5px solid #fecaca;
}

.sop-sec-header.neutral {
  background: #f9fafb;
  border-bottom: 1.5px solid #f3f4f6;
}

.sop-sec-tag {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 11px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.8px;
}

.sop-sec-tag.blue {
  color: #2563eb;
}

.sop-sec-tag.purple {
  color: #9333ea;
}

.sop-sec-tag.orange {
  color: #ea580c;
}

.sop-sec-tag.green {
  color: #059669;
}

.sop-sec-tag.yellow {
  color: #92400e;
}

.sop-sec-tag.red {
  color: #b91c1c;
}

.sop-sec-tag.neutral {
  color: #6b7280;
}

.sop-sec-formula {
  font-size: 11px;
  color: #9ca3af;
  font-family: 'Courier New', monospace;
  margin-left: auto;
  flex-shrink: 0;
}

.sop-sec-body {
  padding: 12px 14px;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  overflow: visible;
}

/* ── Key-value rows ───────────────────────────────────────────────── */
.sop-kv {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: 8px;
  padding: 5px 0;
  border-bottom: 1px solid #f9fafb;
  font-size: 12px;
  word-break: break-word;
}

.sop-kv:last-of-type {
  border-bottom: none;
}

.sop-k {
  color: #6b7280;
  font-weight: 500;
  flex-shrink: 0;
}

.sop-v {
  font-weight: 700;
  color: #111827;
  text-align: right;
  word-break: break-word;
}

.sop-v.mono {
  font-family: 'Courier New', monospace;
  font-size: 11px;
  color: #374151;
  font-weight: 400;
}

.sop-v.dim {
  color: #9ca3af;
  font-weight: 500;
}

.sop-v.danger-val {
  color: #dc2626;
}

.sop-v.ok-val {
  color: #059669;
}

.sop-v.orange-val {
  color: #ea580c;
}

/* ── Highlighted result row ───────────────────────────────────────── */
.sop-result {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
  margin-top: 10px;
  padding: 10px 12px;
  border-radius: 9px;
  font-size: 12px;
  font-weight: 700;
  flex-wrap: wrap;
}

.sop-result.blue {
  background: #eff6ff;
  color: #1d4ed8;
}

.sop-result.purple {
  background: #f5f3ff;
  color: #7c3aed;
}

.sop-result.orange {
  background: #fff7ed;
  color: #c2410c;
}

.sop-result.green {
  background: #ecfdf5;
  color: #15803d;
}

.sop-result.yellow {
  background: #fefce8;
  color: #92400e;
}

.sop-result.red {
  background: #fef2f2;
  color: #b91c1c;
}

.sop-result.neutral {
  background: #f9fafb;
  color: #374151;
}

.sop-result-val {
  font-size: 14px;
  font-weight: 800;
  white-space: nowrap;
}

.capitalize {
  text-transform: capitalize;
}

/* ── FSN Activity progress bar ────────────────────────────────────── */
.sop-activity-bar {
  margin: 6px 0 8px;
  width: 100%;
}

.sop-activity-labels {
  display: flex;
  justify-content: space-between;
  font-size: 11px;
  color: #6b7280;
  margin-bottom: 5px;
}

.sop-activity-pct {
  font-weight: 700;
  color: #374151;
}

.sop-activity-track {
  position: relative;
  height: 10px;
  background: #f3f4f6;
  border-radius: 100px;
  overflow: visible;
}

.sop-activity-fill {
  height: 100%;
  border-radius: 100px;
  transition: width 0.5s ease;
  max-width: 100%;
}

.sop-activity-fill.fast {
  background: linear-gradient(90deg, #4ade80, #16a34a);
}

.sop-activity-fill.slow {
  background: linear-gradient(90deg, #facc15, #ca8a04);
}

.sop-activity-fill.non {
  background: linear-gradient(90deg, #f87171, #dc2626);
}

.sop-activity-marker {
  position: absolute;
  top: -3px;
  width: 2px;
  height: 16px;
  background: #9ca3af;
  border-radius: 1px;
}

.sop-activity-legend {
  display: flex;
  justify-content: space-between;
  font-size: 10px;
  color: #9ca3af;
  margin-top: 5px;
}

/* ── FSN description note ─────────────────────────────────────────── */
.sop-fsn-note {
  font-size: 12px;
  line-height: 1.65;
  border-radius: 9px;
  padding: 10px 12px;
  margin-top: 4px;
}

.sop-fsn-note.fast {
  background: #f0fdf4;
  color: #15803d;
}

.sop-fsn-note.slow {
  background: #fefce8;
  color: #92400e;
}

.sop-fsn-note.non {
  background: #fef2f2;
  color: #b91c1c;
}

.sop-fsn-note.green {
  background: #f0fdf4;
  color: #15803d;
}

.stale-badge {
  display: inline-block;
  margin-left: 8px;
  padding: 2px 7px;
  border-radius: 10px;
  background: #fef3c7;
  color: #92400e;
  font-size: 10px;
  font-weight: 700;
}

/* Alert cell with view hint */
.alert-cell { display: flex; align-items: center; gap: 8px; }
.view-hint  { display: inline-flex; align-items: center; gap: 3px; font-size: 11px; color: #6366f1; font-weight: 600; opacity: 0; transition: opacity .2s; white-space: nowrap; }
.res-table tbody tr:hover .view-hint { opacity: 1; }

/* Responsive adjustments */
@media (max-width: 1024px) { 
  .algo-grid { grid-template-columns: repeat(2, 1fr); } 
  .chart-row-1 { grid-template-columns: 1fr; } 
  .chart-row-2 { grid-template-columns: 1fr; } 
  .fsn-summary { grid-template-columns: 1fr; } 
  .running-steps { grid-template-columns: repeat(3, 1fr); } 
}

@media (max-width: 640px)  {
  .algo-grid { grid-template-columns: 1fr; }
  .analytics-page { padding: 16px; }
  .page-header { flex-wrap: wrap; gap: 10px; }
  .header-actions { width: 100%; }
  .header-actions .btn-thresholds, .header-actions .btn-run { flex: 1; justify-content: center; }
  .running-steps { grid-template-columns: repeat(2, 1fr); } 
  .running-card { flex-wrap: wrap; }
  .so-panel { max-width: 100%; }
}

/* ── Non-moving aging ─────────────────────────────────────────────── */
.aging-card { background: #fff; border: 1.5px solid #f3f4f6; border-radius: 14px; padding: 18px 20px; margin: 16px 0; }
.aging-title { font-size: 14px; font-weight: 800; color: #111827; margin: 0; }
.aging-sub { font-size: 12px; color: #6b7280; margin: 2px 0 14px; }
.aging-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
.aging-cell { border-radius: 12px; padding: 12px 14px; border: 1.5px solid transparent; }
.aging-count { font-size: 24px; font-weight: 800; margin: 0; }
.aging-lbl { font-size: 12px; font-weight: 700; margin: 2px 0 0; }
.aging-pct { font-size: 11px; opacity: .75; margin: 2px 0 0; }
.aging-cell.a-ok   { background: #f0fdf4; color: #15803d; border-color: #bbf7d0; }
.aging-cell.a-warn { background: #fffbeb; color: #b45309; border-color: #fde68a; }
.aging-cell.a-old  { background: #fff7ed; color: #c2410c; border-color: #fed7aa; }
.aging-cell.a-dead { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }
@media (max-width: 768px) { .aging-grid { grid-template-columns: repeat(2, 1fr); } }

/* ── Detail modal: AIC and hold-out tables ────────────────────────── */
.sop-mini { margin: 10px 0 6px; }
.sop-mini-title { font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: .5px; margin: 0 0 6px; }
.sop-mini-note { font-size: 11px; color: #6b7280; margin: 0 0 6px; }
.sop-mini-table { width: 100%; border-collapse: collapse; font-size: 12px; }
.sop-mini-table th { text-align: left; font-weight: 700; color: #6b7280; padding: 4px 6px; border-bottom: 1.5px solid #e5e7eb; }
.sop-mini-table td { padding: 4px 6px; border-bottom: 1px solid #f3f4f6; font-variant-numeric: tabular-nums; }
.sop-mini-best td { background: #eff6ff; font-weight: 700; color: #1d4ed8; }
</style>