<template>
  <div class="rep-page fade-up">

    <!-- ── Header ─────────────────────────────────────────────── -->
    <!-- <div class="page-header">
      <div class="flex items-center gap-3">
        <div class="header-icon">
          <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
        </div>
        <div>
          <h2 class="page-title">Reports</h2>
          <p class="page-sub">Sales, inventory, and performance summaries</p>
        </div>
      </div>
    </div> -->

    <!-- ── Error Banner ───────────────────────────────────────── -->
    <div v-if="error" class="error-banner">
      <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
      </svg>
      {{ error }}
    </div>

    <!-- ── Tab Navigation ─────────────────────────────────────── -->
    <div class="tab-nav">
      <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id"
        class="tab-btn" :class="{ active: activeTab === tab.id }">
        <span v-html="tab.icon" class="tab-icon"></span>
        {{ tab.label }}
      </button>
    </div>

    

    <!-- ── Loading ────────────────────────────────────────────── -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="animate-spin w-9 h-9 border-4 border-indigo-400 border-t-transparent rounded-full"></div>
    </div>

    <!-- ════════════════════════════════════════════════════════ -->
    <!-- Daily Sales                                             -->
    <!-- ════════════════════════════════════════════════════════ -->
    <div v-if="!loading && activeTab === 'daily'" class="tab-content">
      <div class="control-bar">
        <div class="ctrl-icon">
          <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
          </svg>
        </div>
        <div class="ctrl-group">
          <label class="ctrl-lbl">Report Date</label>
          <input v-model="dailyDate" type="date" class="ctrl-input" @change="loadDaily" />
        </div>
        <button @click="loadDaily" class="gen-btn">
          <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/>
          </svg>
          Generate
        </button>
        <button v-if="dailyData" @click="exportDailyPdf" class="pdf-btn">
          <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          Export PDF
        </button>
      </div>

      <template v-if="dailyData">
        <div class="stats-row stats-3">
          <div class="stat-card">
            <div class="stat-icon indigo">
              <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 2.5 2 2.5-2 2.5 2 2.5-2 3.5 2z"/>
              </svg>
            </div>
            <div>
              <p class="stat-num">{{ dailyData.total_transactions }}</p>
              <p class="stat-lbl">Transactions</p>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon green">
              <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div>
              <p class="stat-num green">₱{{ fmt(dailyData.total_revenue) }}</p>
              <p class="stat-lbl">Total Revenue</p>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon red">
              <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
              </svg>
            </div>
            <div>
              <p class="stat-num red">₱{{ fmt(dailyData.total_discount) }}</p>
              <p class="stat-lbl">Discounts Given</p>
            </div>
          </div>
        </div>

        <div class="table-card">
          <div class="table-heading">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            Sales Transactions
          </div>
          <div class="table-wrap">
            <table class="rep-table">
              <thead><tr>
                <th>Receipt #</th><th>Patient</th><th>Items</th><th>Total</th><th>Payment</th>
              </tr></thead>
              <tbody>
                <tr v-for="s in dailyData.sales" :key="s.id">
                  <td><span class="mono-badge">{{ s.receipt_number }}</span></td>
                  <td>{{ s.patient ? s.patient.first_name + ' ' + s.patient.last_name : 'Walk-in' }}</td>
                  <td>{{ s.items?.length }} item{{ s.items?.length !== 1 ? 's' : '' }}</td>
                  <td><span class="amount-val">₱{{ fmt(s.total_amount) }}</span></td>
                  <td><span :class="payPill(s.payment_method)" class="pay-pill">{{ s.payment_method }}</span></td>
                </tr>
                <tr v-if="!dailyData.sales?.length">
                  <td colspan="5" class="empty-row">No transactions for this date.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </template>

      <div v-else class="empty-state">
        <div class="empty-icon indigo">
          <svg width="26" height="26" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
          </svg>
        </div>
        <p class="empty-title">Select a date and click Generate</p>
        <p class="empty-sub">Daily sales report will appear here</p>
      </div>
    </div>

    <!-- ════════════════════════════════════════════════════════ -->
    <!-- Monthly Sales                                           -->
    <!-- ════════════════════════════════════════════════════════ -->
    <div v-if="!loading && activeTab === 'monthly'" class="tab-content">
      <div class="control-bar">
        <div class="ctrl-icon">
          <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
          </svg>
        </div>
        <div class="ctrl-group">
          <label class="ctrl-lbl">Year</label>
          <select v-model="monthlyYear" class="ctrl-select">
            <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
          </select>
        </div>
        <div class="ctrl-group">
          <label class="ctrl-lbl">From Month</label>
          <select v-model="monthlyMonthFrom" class="ctrl-select">
            <option v-for="(m, i) in months" :key="i" :value="i + 1">{{ m }}</option>
          </select>
        </div>
        <div class="ctrl-group">
          <label class="ctrl-lbl">To Month</label>
          <select v-model="monthlyMonthTo" class="ctrl-select">
            <option v-for="(m, i) in months" :key="i" :value="i + 1">{{ m }}</option>
          </select>
        </div>
        <button @click="loadMonthly" class="gen-btn">
          <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/>
          </svg>
          Generate
        </button>
        <button v-if="monthlyData" @click="exportMonthlyPdf" class="pdf-btn">
          <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          Export PDF
        </button>
      </div>

      <template v-if="monthlyData">
        <div class="stats-row stats-2">
          <div class="stat-card">
            <div class="stat-icon indigo">
              <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
              </svg>
            </div>
            <div>
              <p class="stat-num">{{ monthlyData.total_transactions }}</p>
              <p class="stat-lbl">Total Transactions</p>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon green">
              <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div>
              <p class="stat-num green">₱{{ fmt(monthlyData.total_revenue) }}</p>
              <p class="stat-lbl">Total Revenue</p>
            </div>
          </div>
        </div>

        <div class="table-card">
          <div class="table-heading">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Daily Breakdown
          </div>
          <div class="table-wrap">
            <table class="rep-table">
              <thead><tr><th>Date</th><th>Transactions</th><th>Revenue</th><th>Share</th></tr></thead>
              <tbody>
                <tr v-for="d in monthlyData.daily_breakdown" :key="d.date">
                  <td class="day-cell">{{ fmtDate(d.date) }}</td>
                  <td>
                    <span class="tx-count">{{ d.transactions }}</span>
                  </td>
                  <td><span class="amount-val">₱{{ fmt(d.revenue) }}</span></td>
                  <td>
                    <div class="share-bar-wrap">
                      <div class="share-bar" :style="{ width: revenueShare(d.revenue, monthlyData.total_revenue) + '%' }"></div>
                      <span class="share-pct">{{ revenueShare(d.revenue, monthlyData.total_revenue) }}%</span>
                    </div>
                  </td>
                </tr>
                <tr v-if="!monthlyData.daily_breakdown?.length">
                  <td colspan="4" class="empty-row">No data for this month.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </template>

      <div v-else class="empty-state">
        <div class="empty-icon indigo">
          <svg width="26" height="26" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
          </svg>
        </div>
        <p class="empty-title">Select a year and month range, then click Generate</p>
        <p class="empty-sub">Daily breakdown for that span will appear here</p>
      </div>
    </div>

    <!-- ════════════════════════════════════════════════════════ -->
    <!-- Yearly Sales                                             -->
    <!-- ════════════════════════════════════════════════════════ -->
    <div v-if="!loading && activeTab === 'yearly'" class="tab-content">
      <div class="control-bar">
        <div class="ctrl-icon">
          <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
          </svg>
        </div>
        <div class="ctrl-group">
          <label class="ctrl-lbl">Year</label>
          <select v-model="yearlyYear" class="ctrl-select">
            <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
          </select>
        </div>
        <button @click="loadYearly" class="gen-btn">
          <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/>
          </svg>
          Generate
        </button>
        <button v-if="yearlyData" @click="exportYearlyPdf" class="pdf-btn">
          <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          Export PDF
        </button>
      </div>

      <template v-if="yearlyData">
        <div class="stats-row stats-3">
          <div class="stat-card">
            <div class="stat-icon indigo">
              <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
              </svg>
            </div>
            <div>
              <p class="stat-num">{{ yearlyData.total_transactions }}</p>
              <p class="stat-lbl">Total Transactions</p>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon green">
              <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div>
              <p class="stat-num green">₱{{ fmt(yearlyData.total_revenue) }}</p>
              <p class="stat-lbl">Total Revenue</p>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon amber">
              <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
              </svg>
            </div>
            <div>
              <p class="stat-num amber">₱{{ fmt(yearlyData.total_discount) }}</p>
              <p class="stat-lbl">Discounts Given</p>
            </div>
          </div>
        </div>

        <div class="table-card">
          <div class="table-heading">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Yearly Breakdown
          </div>
          <div class="table-wrap">
            <table class="rep-table">
              <thead><tr><th>Year</th><th>Transactions</th><th>Revenue</th><th>Discounts</th><th>Share</th></tr></thead>
              <tbody>
                <tr v-for="y in yearlyData.yearly_breakdown" :key="y.year">
                  <td class="day-cell">{{ y.year }}</td>
                  <td>
                    <span class="tx-count">{{ y.transactions }}</span>
                  </td>
                  <td><span class="amount-val">₱{{ fmt(y.revenue) }}</span></td>
                  <td><span class="amount-val">₱{{ fmt(y.discount) }}</span></td>
                  <td>
                    <div class="share-bar-wrap">
                      <div class="share-bar" :style="{ width: revenueShare(y.revenue, yearlyData.total_revenue) + '%' }"></div>
                      <span class="share-pct">{{ revenueShare(y.revenue, yearlyData.total_revenue) }}%</span>
                    </div>
                  </td>
                </tr>
                <tr v-if="!yearlyData.yearly_breakdown?.length">
                  <td colspan="5" class="empty-row">No data for this period.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </template>

      <div v-else class="empty-state">
        <div class="empty-icon indigo">
          <svg width="26" height="26" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
          </svg>
        </div>
        <p class="empty-title">Select a year and click Generate</p>
        <p class="empty-sub">Sales totals for that year will appear here</p>
      </div>
    </div>

    <!-- ════════════════════════════════════════════════════════ -->
    <!-- Inventory Report                                        -->
    <!-- ════════════════════════════════════════════════════════ -->
    <div v-if="!loading && activeTab === 'inventory'" class="tab-content">
      <div class="control-bar">
        <div class="ctrl-icon">
          <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
          </svg>
        </div>
        <span class="ctrl-desc">Current inventory status across all products</span>
        <button @click="loadInventory" class="gen-btn">
          <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/>
          </svg>
          Refresh
        </button>
        <button v-if="inventoryData" @click="exportInventoryPdf" class="pdf-btn">
          <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          Export PDF
        </button>
      </div>

      <template v-if="inventoryData">
        <div class="stats-row">
          <div class="stat-card">
            <div class="stat-icon teal">
              <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
              </svg>
            </div>
            <div>
              <p class="stat-num">{{ inventoryData.total_products }}</p>
              <p class="stat-lbl">Total Products</p>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon amber">
              <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
              </svg>
            </div>
            <div>
              <p class="stat-num amber">{{ inventoryData.low_stock_count }}</p>
              <p class="stat-lbl">Low Stock</p>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon red">
              <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div>
              <p class="stat-num red">{{ inventoryData.out_of_stock_count }}</p>
              <p class="stat-lbl">Out of Stock</p>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon green">
              <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div>
              <p class="stat-num green">₱{{ fmt(inventoryData.total_stock_value) }}</p>
              <p class="stat-lbl">Total Stock Value</p>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon indigo">
              <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 010 2H4a1 1 0 01-1-1zm0 8a1 1 0 011-1h10a1 1 0 010 2H4a1 1 0 01-1-1zm0 8a1 1 0 011-1h6a1 1 0 010 2H4a1 1 0 01-1-1z"/>
              </svg>
            </div>
            <div>
              <p class="stat-num indigo">{{ inventoryData.dead_stock_count }}</p>
              <p class="stat-lbl">Dead Stock (Non-moving)</p>
            </div>
          </div>
        </div>

        <div class="table-card">
          <div class="table-heading">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            Product Inventory
          </div>
          <div class="table-wrap">
            <table class="rep-table">
              <thead><tr><th>SKU</th><th>Product Name</th><th>Category</th><th>Stock</th><th>Reorder Point</th><th>Status</th><th>Sales Speed</th><th>Turnover</th><th>Stock Value</th></tr></thead>
              <tbody>
                <tr v-for="p in inventoryData.products" :key="p.id" :class="{ 'row-warn': p.is_low_stock || p.is_out_of_stock }">
                  <td><span class="mono-badge">{{ p.sku }}</span></td>
                  <td class="product-name-cell">{{ p.name }}</td>
                  <td><span class="cat-badge">{{ p.category }}</span></td>
                  <td>
                    <span :class="p.is_out_of_stock ? 'stock-low' : (p.is_low_stock ? 'stock-low' : 'stock-ok')">{{ p.stock_quantity }}</span>
                  </td>
                  <td class="rop-val">{{ p.reorder_point }}</td>
                  <td>
                    <span :class="p.is_out_of_stock ? 'status-pill red' : (p.is_low_stock ? 'status-pill amber' : 'status-pill green')">
                      <span class="status-dot"></span>
                      {{ p.is_out_of_stock ? 'Out of Stock' : (p.is_low_stock ? 'Low Stock' : 'In Stock') }}
                    </span>
                  </td>
                  <td>
                    <span :class="fsnPillClass(p.fsn_classification)">
                      <span class="status-dot"></span>
                      {{ fsnLabel(p.fsn_classification) }}
                    </span>
                  </td>
                  <td><span class="rop-val">{{ turnoverLabel(p.turnover_ratio) }}</span></td>
                  <td><span class="amount-val">₱{{ fmt(p.stock_value) }}</span></td>
                </tr>
                <tr v-if="!inventoryData.products?.length">
                  <td colspan="9" class="empty-row">No products found.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </template>

      <div v-else class="empty-state">
        <div class="empty-icon teal">
          <svg width="26" height="26" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
          </svg>
        </div>
        <p class="empty-title">Click Refresh to load inventory report</p>
        <p class="empty-sub">Full product stock status will appear here</p>
      </div>
    </div>

    <!-- ════════════════════════════════════════════════════════ -->
    <!-- Top Products                                            -->
    <!-- ════════════════════════════════════════════════════════ -->
    <div v-if="!loading && activeTab === 'top_products'" class="tab-content">
      <div class="control-bar">
        <div class="ctrl-icon">
          <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3l14 9-14 9V3z"/>
          </svg>
        </div>
        <div class="ctrl-group">
          <label class="ctrl-lbl">From</label>
          <input v-model="topFrom" type="date" class="ctrl-input" />
        </div>
        <div class="ctrl-group">
          <label class="ctrl-lbl">To</label>
          <input v-model="topTo" type="date" class="ctrl-input" />
        </div>
        <button @click="loadTop" class="gen-btn">
          <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/>
          </svg>
          Generate
        </button>
        <button v-if="topData" @click="exportTopPdf" class="pdf-btn">
          <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          Export PDF
        </button>
      </div>

      <template v-if="topData">
        <div class="table-card">
          <div class="table-heading">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
            </svg>
            Best-Selling Products
          </div>
          <div class="table-wrap">
            <table class="rep-table">
              <thead><tr><th>Rank</th><th>Product</th><th>Category</th><th>Qty Sold</th><th>Revenue</th><th>Performance</th></tr></thead>
              <tbody>
                <tr v-for="(p, i) in topData.products" :key="p.product_id">
                  <td>
                    <span :class="rankBadge(i)" class="rank-badge">{{ i + 1 }}</span>
                  </td>
                  <td class="product-name-cell">{{ p.product?.name }}</td>
                  <td><span class="cat-badge">{{ p.product?.category?.name ?? '—' }}</span></td>
                  <td><span class="qty-sold">{{ p.total_qty }}</span></td>
                  <td><span class="amount-val">₱{{ fmt(p.total_revenue) }}</span></td>
                  <td>
                    <div class="perf-bar-wrap">
                      <div class="perf-bar" :style="{ width: perfShare(p.total_qty, topData.products) + '%' }"></div>
                      <span class="perf-pct">{{ perfShare(p.total_qty, topData.products) }}%</span>
                    </div>
                  </td>
                </tr>
                <tr v-if="!topData.products?.length">
                  <td colspan="6" class="empty-row">No products data for this period.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </template>

      <div v-else class="empty-state">
        <div class="empty-icon indigo">
          <svg width="26" height="26" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
          </svg>
        </div>
        <p class="empty-title">Select a date range and click Generate</p>
        <p class="empty-sub">Top-selling products will appear here</p>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const companyLogo = '/onlylogo.png'

const auth = useAuthStore()

const tabIcons = {
  daily:        '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
  monthly:      '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>',
  yearly:       '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14h.01M12 14h.01M16 14h.01M8 17h.01M12 17h.01M16 17h.01"/></svg>',
  inventory:    '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>',
  top_products: '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>',
}

const allTabs = [
  { id: 'daily',        label: 'Daily Sales',    roles: ['admin'],                          icon: tabIcons.daily },
  { id: 'monthly',      label: 'Monthly Sales',  roles: ['admin'],                          icon: tabIcons.monthly },
  { id: 'yearly',       label: 'Yearly Sales',   roles: ['admin'],                          icon: tabIcons.yearly },
  { id: 'inventory',    label: 'Inventory',      roles: ['admin', 'inventory_staff'],       icon: tabIcons.inventory },
  { id: 'top_products', label: 'Top Products',   roles: ['admin', 'inventory_staff'],       icon: tabIcons.top_products },
]
const tabs      = computed(() => allTabs.filter(t => t.roles.includes(auth.user?.role)))
const activeTab = ref('')

watch(activeTab, (tab) => {
  if (tab === 'daily'        && !dailyData.value)      loadDaily()
  if (tab === 'monthly'      && !monthlyData.value)    loadMonthly()
  if (tab === 'yearly'       && !yearlyData.value)     loadYearly()
  if (tab === 'inventory'    && !inventoryData.value)  loadInventory()
  if (tab === 'top_products' && !topData.value)        loadTop()
})

onMounted(() => { activeTab.value = tabs.value[0]?.id ?? 'inventory'; loadControlNumber() })

const months = ['January','February','March','April','May','June','July','August','September','October','November','December']
// Current year down to 2020, for the report year pickers.
const years = computed(() => {
  const now = new Date().getFullYear()
  return Array.from({ length: now - 2019 }, (_, i) => now - i)
})

const dailyDate     = ref(new Date().toISOString().split('T')[0])
const dailyData     = ref(null)
const monthlyMonthFrom = ref(new Date().getMonth() + 1)
const monthlyMonthTo   = ref(new Date().getMonth() + 1)
const monthlyYear   = ref(new Date().getFullYear())
const monthlyData   = ref(null)
const yearlyYear    = ref(new Date().getFullYear())
const yearlyData    = ref(null)
const inventoryData = ref(null)
const topFrom       = ref(new Date(new Date().setDate(1)).toISOString().split('T')[0])
const topTo         = ref(new Date().toISOString().split('T')[0])
const topData       = ref(null)
const error         = ref('')
const loading       = ref(false)
const controlNumber = ref(0)

function loadControlNumber() {
  const n = parseInt(localStorage.getItem('report_control_number') || '0', 10)
  controlNumber.value = Number.isFinite(n) ? n : 0
}

function incrementControlNumber() {
  controlNumber.value = Number(controlNumber.value || 0) + 1
  localStorage.setItem('report_control_number', String(controlNumber.value))
}

function resetControlNumber() {
  if (!confirm('Reset control number to 0?')) return
  controlNumber.value = 0
  localStorage.setItem('report_control_number', '0')
}

async function loadDaily() {
  error.value = ''; loading.value = true
  try { dailyData.value = (await api.get('/reports/sales/daily', { params: { date: dailyDate.value } })).data }
  catch (e) { error.value = e.response?.data?.message || 'Could not load report. Make sure the backend is running.' }
  finally { loading.value = false }
}
async function loadMonthly() {
  error.value = ''; loading.value = true
  try { monthlyData.value = (await api.get('/reports/sales/monthly', { params: { month_from: monthlyMonthFrom.value, month_to: monthlyMonthTo.value, year: monthlyYear.value } })).data }
  catch (e) { error.value = e.response?.data?.message || 'Could not load report. Make sure the backend is running.' }
  finally { loading.value = false }
}
async function loadYearly() {
  error.value = ''; loading.value = true
  try { yearlyData.value = (await api.get('/reports/sales/yearly', { params: { year: yearlyYear.value } })).data }
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

function fmt(v)     { return Number(v || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }
function fmtDate(d) { return d ? new Date(d).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '—' }

// FSN classification + turnover ratio — from the last Analytics run (see AnalyticsController).
function fsnLabel(cls) {
  return { fast: 'Fast Moving', slow: 'Slow Moving', non_moving: 'Non-Moving' }[cls] ?? 'Not Run'
}
function fsnPillClass(cls) {
  return 'status-pill ' + ({ fast: 'green', slow: 'amber', non_moving: 'red' }[cls] ?? 'gray')
}
function turnoverLabel(ratio) {
  if (ratio === null || ratio === undefined) return '—'
  return `${Number(ratio).toFixed(1)}x/yr`
}

function revenueShare(revenue, total) {
  if (!total) return 0
  return Math.round((Number(revenue) / Number(total)) * 100)
}

function perfShare(qty, products) {
  const max = Math.max(...(products ?? []).map(p => Number(p.total_qty) || 0), 1)
  return Math.round((Number(qty) / max) * 100)
}

function rankBadge(i) {
  return ['rank-gold', 'rank-silver', 'rank-bronze'][i] ?? 'rank-default'
}


function payPill(m) {
  return { cash: 'pill-green', card: 'pill-blue', gcash: 'pill-sky', maya: 'pill-purple', other: 'pill-gray' }[m] ?? 'pill-gray'
}

// ── PDF Export ────────────────────────────────────────────────────────────
function pdfShell(title, periodLabel, body, controlNumber = '') {
  const now = new Date().toLocaleString('en-PH', { dateStyle: 'long', timeStyle: 'short' })
  const controlText = (controlNumber !== '' && controlNumber !== null && typeof controlNumber !== 'undefined') ? `Controller ID: ${controlNumber}` : ''
  return `<!DOCTYPE html><html><head><meta charset="utf-8">
<title></title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #111827; padding: 32px 36px 72px; }
  .report-header { text-align: center; margin-bottom: 28px; }
  .company { display: inline-flex; align-items: center; justify-content: center; gap: 10px; font-size: 24px; font-weight: 800; color: #111827; letter-spacing: -.4px; }
  .company-logo { width: 46px; height: 46px; object-fit: contain; border-radius: 12px; }
  .address { margin-top: 6px; font-size: 12px; color: #6b7280; }
  .report-title { margin-top: 22px; font-size: 18px; font-weight: 700; color: #111827; }
  .report-period { margin-top: 6px; font-size: 12px; color: #4b5563; }
  .section { margin-top: 20px; font-size: 12px; font-weight: 700; color: #374151; letter-spacing: .5px; text-transform: uppercase; }
  table { width: 100%; border-collapse: collapse; margin-top: 14px; }
  thead tr { background: #f9fafb; }
  th { padding: 10px 12px; text-align: left; font-size: 10px; font-weight: 700; color: #6b7280; border-bottom: 2px solid #e5e7eb; }
  td { padding: 10px 12px; font-size: 12px; color: #374151; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
  tr:last-child td { border-bottom: none; }
  .mono { font-family: 'Courier New', monospace; font-size: 11px; font-weight: 700; color: #4f46e5; background: #eef2ff; padding: 2px 6px; border-radius: 4px; }
  .amt { font-weight: 700; color: #059669; }
  .badge { display: inline-block; padding: 3px 8px; border-radius: 16px; background: #f3f4f6; color: #4b5563; font-size: 10px; font-weight: 700; }
  .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin-top: 22px; }
  .summary-box { padding: 14px 16px; border: 1px solid #e5e7eb; border-radius: 12px; background: #fff; }
  .summary-label { font-size: 10px; font-weight: 700; color: #6b7280; letter-spacing: .5px; text-transform: uppercase; margin-bottom: 6px; }
  .summary-value { font-size: 18px; font-weight: 800; color: #111827; }
  .footer { margin-top: 30px; padding-top: 12px; border-top: 1px solid #e5e7eb; font-size: 10px; line-height: 1.5; color: #9ca3af; text-align: left; }
  .footer div { display: block; }
  @media print {
    @page { margin: 15mm 12mm; size: auto; }
    body { padding: 0; }
    table { page-break-inside: auto; break-inside: auto; }
    thead { display: table-header-group; }
    tr { page-break-inside: avoid; break-inside: avoid; }
    .footer {
      position: static;
      width: 100%;
      margin-top: 12mm;
      padding-top: 3mm;
      page-break-inside: avoid;
      break-inside: avoid;
    }
  }
</style>
</head><body>
<div class="report-header">
  <div class="company">
    <img class="company-logo" src="${companyLogo}" alt="Acebedo Optical logo" />
    <span>Acebedo Optical</span>
  </div>
  <div class="address">Alcasid Camus Building, Brgy. Uno, <br />Crossing Highway, Calamba City, Laguna</div>
  <div class="report-title">${title}</div>
  <div class="report-period">${periodLabel}</div>
</div>
${body}
<div class="footer"><div>Printed: ${now}${controlText ? ` | ${controlText}` : ''}</div></div>
</body></html>`
}

function openPdf(html) {
  const w = window.open('', '_blank', 'width=900,height=700')
  w.document.write(html)
  w.document.close()
  w.onload = () => { w.focus(); w.print() }
}

function exportDailyPdf() {
  const d = dailyData.value
  const rows = (d.sales ?? []).map(s => {
    const patient = s.patient ? `${s.patient.first_name} ${s.patient.last_name}` : 'Walk-in'
    const items = `${s.items?.length ?? 0} item${s.items?.length !== 1 ? 's' : ''}`
    const pmLabels = { cash: 'Cash', card: 'Card', gcash: 'GCash', maya: 'Maya', other: 'Other' }
    return `<tr><td><span class="mono">${s.receipt_number}</span></td><td>${patient}</td><td>${items}</td><td><span class="amt">₱${fmt(s.total_amount)}</span></td><td>${pmLabels[s.payment_method] ?? s.payment_method}</td></tr>`
  }).join('')
  const table = `<div class="section">Sales Transactions</div>
  <table><thead><tr><th>Receipt #</th><th>Patient</th><th>Items</th><th>Total</th><th>Payment</th></tr></thead>
  <tbody>${rows || '<tr><td colspan="5" style="text-align:center;padding:24px 0;color:#9ca3af;">No transactions for this date.</td></tr>'}</tbody></table>`
  const summary = `<div class="summary-grid">
    <div class="summary-box"><div class="summary-label">Transactions</div><div class="summary-value">${d.total_transactions}</div></div>
    <div class="summary-box"><div class="summary-label">Total Revenue</div><div class="summary-value">₱${fmt(d.total_revenue)}</div></div>
    <div class="summary-box"><div class="summary-label">Discounts Given</div><div class="summary-value">₱${fmt(d.total_discount)}</div></div>
  </div>`
  incrementControlNumber()
  openPdf(pdfShell('Daily Sales Report', fmtDate(dailyDate.value), table + summary, controlNumber.value))
}

function exportMonthlyPdf() {
  const d = monthlyData.value
  const mFrom = months[monthlyMonthFrom.value - 1]
  const mTo   = months[monthlyMonthTo.value - 1]
  const periodLabel = mFrom === mTo
    ? `${mFrom} ${monthlyYear.value}`
    : `${mFrom} – ${mTo} ${monthlyYear.value}`
  const rows = (d.daily_breakdown ?? []).map(day => {
    const share = revenueShare(day.revenue, d.total_revenue)
    return `<tr><td>${fmtDate(day.date)}</td><td>${day.transactions}</td><td><span class="amt">₱${fmt(day.revenue)}</span></td><td>${share}%</td></tr>`
  }).join('')
  const table = `<div class="section">Daily Breakdown</div>
  <table><thead><tr><th>Date</th><th>Transactions</th><th>Revenue</th><th>Share</th></tr></thead>
  <tbody>${rows || '<tr><td colspan="4" style="text-align:center;padding:24px 0;color:#9ca3af;">No data for this month.</td></tr>'}</tbody></table>`
  const summary = `<div class="summary-grid">
    <div class="summary-box"><div class="summary-label">Total Transactions</div><div class="summary-value">${d.total_transactions}</div></div>
    <div class="summary-box"><div class="summary-label">Total Revenue</div><div class="summary-value">₱${fmt(d.total_revenue)}</div></div>
  </div>`
  incrementControlNumber()
  openPdf(pdfShell('Monthly Sales Report', periodLabel, table + summary, controlNumber.value))
}

function exportYearlyPdf() {
  const d = yearlyData.value
  const rows = (d.yearly_breakdown ?? []).map(y => {
    const share = revenueShare(y.revenue, d.total_revenue)
    return `<tr><td style="font-weight:600;">${y.year}</td><td>${y.transactions}</td><td><span class="amt">₱${fmt(y.revenue)}</span></td><td><span class="amt">₱${fmt(y.discount)}</span></td><td>${share}%</td></tr>`
  }).join('')
  const table = `<div class="section">Yearly Breakdown</div>
  <table><thead><tr><th>Year</th><th>Transactions</th><th>Revenue</th><th>Discounts</th><th>Share</th></tr></thead>
  <tbody>${rows || '<tr><td colspan="5" style="text-align:center;padding:24px 0;color:#9ca3af;">No data for this period.</td></tr>'}</tbody></table>`
  const summary = `<div class="summary-grid">
    <div class="summary-box"><div class="summary-label">Total Transactions</div><div class="summary-value">${d.total_transactions}</div></div>
    <div class="summary-box"><div class="summary-label">Total Revenue</div><div class="summary-value">₱${fmt(d.total_revenue)}</div></div>
    <div class="summary-box"><div class="summary-label">Discounts Given</div><div class="summary-value">₱${fmt(d.total_discount)}</div></div>
  </div>`
  incrementControlNumber()
  openPdf(pdfShell('Yearly Sales Report', String(d.year ?? yearlyYear.value), table + summary, controlNumber.value))
}

function exportInventoryPdf() {
  const d = inventoryData.value
  const fsnBadge = cls => {
    const styles = {
      fast:       'background:#dcfce7;color:#15803d',
      slow:       'background:#fef3c7;color:#92400e',
      non_moving: 'background:#fee2e2;color:#b91c1c',
    }
    return `<span class="badge" style="${styles[cls] ?? 'background:#f3f4f6;color:#6b7280'}">${fsnLabel(cls)}</span>`
  }
  const rows = (d.products ?? []).map(p => {
    const status = p.is_out_of_stock ? '<span class="badge" style="background:#fee2e2;color:#991b1b">Out of Stock</span>'
      : p.is_low_stock ? '<span class="badge" style="background:#fef3c7;color:#92400e">Low Stock</span>'
      : '<span class="badge" style="background:#dcfce7;color:#15803d">In Stock</span>'
    return `<tr><td><span class="mono">${p.sku}</span></td><td style="font-weight:600;">${p.name}</td><td><span class="badge">${p.category}</span></td><td>${p.stock_quantity}</td><td>${p.reorder_point}</td><td>${status}</td><td>${fsnBadge(p.fsn_classification)}</td><td>${turnoverLabel(p.turnover_ratio)}</td><td><span class="amt">₱${fmt(p.stock_value)}</span></td></tr>`
  }).join('')
  const table = `<div class="section">Product Inventory</div>
  <table><thead><tr><th>SKU</th><th>Product Name</th><th>Category</th><th>Stock</th><th>Reorder Point</th><th>Status</th><th>Sales Speed</th><th>Turnover</th><th>Value</th></tr></thead>
  <tbody>${rows || '<tr><td colspan="9" style="text-align:center;padding:24px 0;color:#9ca3af;">No products.</td></tr>'}</tbody></table>`
  const summary = `<div class="summary-grid">
    <div class="summary-box"><div class="summary-label">Total Products</div><div class="summary-value">${d.total_products}</div></div>
    <div class="summary-box"><div class="summary-label">Low Stock</div><div class="summary-value">${d.low_stock_count}</div></div>
    <div class="summary-box"><div class="summary-label">Out of Stock</div><div class="summary-value">${d.out_of_stock_count}</div></div>
    <div class="summary-box"><div class="summary-label">Dead Stock (Non-moving)</div><div class="summary-value">${d.dead_stock_count}</div></div>
    <div class="summary-box"><div class="summary-label">Total Stock Value</div><div class="summary-value">₱${fmt(d.total_stock_value)}</div></div>
  </div>`
  incrementControlNumber()
  openPdf(pdfShell('Inventory Report', `As of ${new Date().toLocaleDateString('en-PH', { dateStyle: 'long' })}`, table + summary, controlNumber.value))
}

function exportTopPdf() {
  const d = topData.value
  const totalQty = (d.products ?? []).reduce((sum, p) => sum + (Number(p.total_qty) || 0), 0)
  const totalRevenue = (d.products ?? []).reduce((sum, p) => sum + (Number(p.total_revenue) || 0), 0)
  const rows = (d.products ?? []).map((p, i) => {
    const share = perfShare(p.total_qty, d.products)
    return `<tr><td><span class="badge" style="padding:6px 10px;margin:0;border-radius:14px;background:#f3f4f6;color:#374151;">${i + 1}</span></td><td style="font-weight:600">${p.product?.name ?? '—'}</td><td><span class="badge">${p.product?.category?.name ?? '—'}</span></td><td>${p.total_qty}</td><td><span class="amt">₱${fmt(p.total_revenue)}</span></td><td>${share}%</td></tr>`
  }).join('')
  const table = `<div class="section">Best-Selling Products</div>
  <table><thead><tr><th>Rank</th><th>Product</th><th>Category</th><th>Qty Sold</th><th>Revenue</th><th>Performance</th></tr></thead>
  <tbody>${rows || '<tr><td colspan="6" style="text-align:center;padding:24px 0;color:#9ca3af;">No data for this period.</td></tr>'}</tbody></table>`
  const summary = `<div class="summary-grid">
    <div class="summary-box"><div class="summary-label">Products Listed</div><div class="summary-value">${d.products?.length ?? 0}</div></div>
    <div class="summary-box"><div class="summary-label">Total Qty Sold</div><div class="summary-value">${totalQty}</div></div>
    <div class="summary-box"><div class="summary-label">Total Revenue</div><div class="summary-value">₱${fmt(totalRevenue)}</div></div>
  </div>`
  incrementControlNumber()
  openPdf(pdfShell('Top Products Report', `${fmtDate(topFrom.value)} — ${fmtDate(topTo.value)}`, table + summary, controlNumber.value))
}
</script>

<style scoped>
.rep-page { padding: 28px 32px 40px; }

/* Header */
.page-header  { display: flex; align-items: center; justify-content: space-between; margin-bottom: 28px; }
.header-icon  { width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, #6366f1, #4f46e5); display: flex; align-items: center; justify-content: center; color: #fff; box-shadow: 0 4px 12px rgba(99,102,241,.35); flex-shrink: 0; }
.page-title   { font-size: 1.25rem; font-weight: 800; color: #111827; margin: 0; }
.page-sub     { font-size: 12px; color: #6b7280; margin: 2px 0 0; }

/* Error banner */
.error-banner { display: flex; align-items: center; gap: 10px; background: #fef2f2; border: 1.5px solid #fecaca; border-radius: 12px; padding: 12px 16px; font-size: 13px; color: #dc2626; margin-bottom: 20px; }

/* Tab nav */
.tab-nav { display: flex; gap: 4px; background: #f3f4f6; border-radius: 14px; padding: 5px; margin-bottom: 24px; width: fit-content; }
.tab-btn {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 9px 18px; border-radius: 10px; border: none;
  font-size: 13px; font-weight: 600; font-family: inherit;
  color: #6b7280; background: transparent; cursor: pointer;
  transition: all .2s;
}
.tab-btn:hover { color: #374151; background: rgba(255,255,255,.6); }
.tab-btn.active { background: #fff; color: #4f46e5; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
.tab-icon { display: flex; align-items: center; }

/* Tab content */
.tab-content { display: flex; flex-direction: column; gap: 20px; }

/* Control bar */
.control-bar { display: flex; align-items: center; gap: 14px; background: #fff; border: 1.5px solid #f3f4f6; border-radius: 14px; box-shadow: 0 2px 8px rgba(0,0,0,.04); padding: 14px 20px; flex-wrap: wrap; }
.ctrl-icon   { color: #6366f1; flex-shrink: 0; }
.ctrl-desc   { font-size: 13px; color: #6b7280; flex: 1; }
.ctrl-group  { display: flex; flex-direction: column; gap: 4px; }
.ctrl-lbl    { font-size: 11px; font-weight: 700; color: #6b7280; }
.ctrl-input  { padding: 8px 12px; border: 1.5px solid #e5e7eb; border-radius: 9px; font-size: 13px; color: #374151; font-family: inherit; background: #f9fafb; outline: none; transition: border-color .2s; min-width: 150px; }
.ctrl-input:focus  { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.1); }
.ctrl-select { padding: 8px 12px; border: 1.5px solid #e5e7eb; border-radius: 9px; font-size: 13px; color: #374151; font-family: inherit; background: #f9fafb; outline: none; cursor: pointer; transition: border-color .2s; }
.ctrl-select:focus { border-color: #6366f1; }
.ctrl-year   { min-width: 90px; }
.gen-btn     { display: inline-flex; align-items: center; gap: 7px; padding: 9px 18px; border: none; border-radius: 10px; background: linear-gradient(135deg, #6366f1, #4f46e5); color: #fff; font-size: 13px; font-weight: 700; font-family: inherit; cursor: pointer; box-shadow: 0 4px 12px rgba(99,102,241,.3); transition: all .2s; white-space: nowrap; align-self: flex-end; }
.gen-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(99,102,241,.4); }
.pdf-btn     { display: inline-flex; align-items: center; gap: 7px; padding: 9px 18px; border: 1.5px solid #dc2626; border-radius: 10px; background: #fff; color: #dc2626; font-size: 13px; font-weight: 700; font-family: inherit; cursor: pointer; transition: all .2s; white-space: nowrap; align-self: flex-end; }
.pdf-btn:hover { background: #fef2f2; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(220,38,38,.15); }

/* Stats */
.stats-row   { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
.stats-3     { grid-template-columns: repeat(3, 1fr); }
.stats-2     { grid-template-columns: repeat(2, 1fr); }
.stat-icon.indigo { background: #eef2ff; color: #4f46e5; }
.stat-icon.green  { background: #f0fdf4; color: #16a34a; }
.stat-icon.red    { background: #fef2f2; color: #dc2626; }
.stat-icon.teal   { background: #f0fdfa; color: #0d9488; }
.stat-icon.amber  { background: #fffbeb; color: #d97706; }
.stat-num    { font-size: 1.5rem; font-weight: 800; color: #111827; line-height: 1; }
.stat-num.green  { color: #16a34a; font-size: 1.2rem; }
.stat-num.red    { color: #dc2626; font-size: 1.2rem; }
.stat-num.amber  { color: #d97706; }
.stat-num.indigo { color: #4f46e5; font-size: 1.2rem; }
.stat-lbl    { font-size: 11px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: .5px; margin-top: 4px; }

/* Table */
.table-heading { display: flex; align-items: center; gap: 8px; padding: 14px 20px; font-size: 13px; font-weight: 700; color: #374151; border-bottom: 1.5px solid #f3f4f6; }
.table-wrap    { overflow-x: auto; }

.rep-table { width: 100%; border-collapse: collapse; }
.rep-table thead tr { background: #f9fafb; border-bottom: 2px solid #f3f4f6; }
.rep-table thead th { padding: 12px 16px; text-align: left; font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: .6px; white-space: nowrap; }
.rep-table tbody tr { border-bottom: 1px solid #f9fafb; transition: background .15s; }
.rep-table tbody tr:last-child { border-bottom: none; }
.rep-table tbody tr:hover { background: #fafafa; }
.rep-table tbody tr.row-warn { background: #fffbeb; }
.rep-table tbody tr.row-warn:hover { background: #fef9c3; }
.rep-table td { padding: 13px 16px; font-size: 13px; color: #374151; vertical-align: middle; }

/* Cells */
.mono-badge  { font-family: 'Courier New', monospace; font-size: 11px; font-weight: 700; color: #4f46e5; background: #eef2ff; padding: 3px 8px; border-radius: 5px; }
.amount-val  { font-size: 13px; font-weight: 700; color: #059669; }
.product-name-cell { font-weight: 600; color: #111827; }
.cat-badge   { display: inline-block; padding: 3px 9px; border-radius: 20px; background: #f3f4f6; color: #4b5563; font-size: 11px; font-weight: 600; }
.stock-ok    { font-weight: 700; color: #059669; }
.stock-low   { font-weight: 700; color: #dc2626; }
.rop-val     { color: #9ca3af; }
.empty-row   { text-align: center; padding: 48px 20px; color: #9ca3af; font-size: 13px; }

/* Status pill */
.status-pill { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.status-dot  { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
.status-pill.green { background: #dcfce7; color: #15803d; }
.status-pill.amber { background: #fef3c7; color: #92400e; }
.status-pill.red   { background: #fee2e2; color: #b91c1c; }
.status-pill.gray  { background: #f3f4f6; color: #6b7280; }

/* Payment pill */
.pay-pill    { display: inline-block; padding: 3px 9px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: capitalize; }
.pill-green  { background: #dcfce7; color: #15803d; }
.pill-blue   { background: #dbeafe; color: #1d4ed8; }
.pill-sky    { background: #e0f2fe; color: #0369a1; }
.pill-purple { background: #ede9fe; color: #6d28d9; }
.pill-gray   { background: #f3f4f6; color: #4b5563; }

/* Tx count */
.tx-count    { display: inline-flex; align-items: center; justify-content: center; min-width: 28px; height: 24px; background: #eef2ff; color: #4f46e5; font-size: 12px; font-weight: 700; border-radius: 6px; padding: 0 8px; }

/* Revenue share bar */
.share-bar-wrap { display: flex; align-items: center; gap: 8px; min-width: 120px; }
.share-bar      { height: 6px; border-radius: 3px; background: linear-gradient(90deg, #6366f1, #818cf8); min-width: 2px; transition: width .3s; }
.share-pct      { font-size: 11px; font-weight: 600; color: #6b7280; white-space: nowrap; }

/* Performance bar (top products) */
.perf-bar-wrap { display: flex; align-items: center; gap: 8px; min-width: 120px; }
.perf-bar      { height: 8px; border-radius: 4px; background: linear-gradient(90deg, #10b981, #34d399); min-width: 2px; transition: width .3s; }
.perf-pct      { font-size: 11px; font-weight: 600; color: #6b7280; white-space: nowrap; }

/* Rank badge */
.rank-badge    { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 50%; font-size: 12px; font-weight: 800; }
.rank-gold     { background: #fef9c3; color: #92400e; border: 2px solid #fbbf24; }
.rank-silver   { background: #f1f5f9; color: #475569; border: 2px solid #94a3b8; }
.rank-bronze   { background: #fff7ed; color: #9a3412; border: 2px solid #fb923c; }
.rank-default  { background: #f3f4f6; color: #6b7280; border: 2px solid #e5e7eb; }

/* Qty sold */
 qty-sold { font-size: 14px; font-weight: 700; color: #374151; }

/* Day cell */
.day-cell { font-weight: 600; color: #111827; }

/* Empty state */
.empty-state { padding: 64px 20px; text-align: center; background: #fff; border-radius: 16px; border: 1.5px solid #f3f4f6; }
.empty-icon  { width: 56px; height: 56px; border-radius: 14px; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; }
.empty-icon.indigo { background: #eef2ff; color: #6366f1; }
.empty-icon.teal   { background: #f0fdfa; color: #0d9488; }
.empty-title { font-size: 15px; font-weight: 700; color: #374151; margin-bottom: 6px; }
.empty-sub   { font-size: 13px; color: #9ca3af; }

@media (max-width: 1200px) { .stats-row { grid-template-columns: repeat(2, 1fr); } .stats-3 { grid-template-columns: repeat(3, 1fr); } .stats-2 { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 768px)  { .stats-row { grid-template-columns: repeat(2, 1fr); } .stats-3 { grid-template-columns: repeat(2, 1fr); } .stats-2 { grid-template-columns: 1fr; } }
@media (max-width: 640px)  { .stats-row { grid-template-columns: 1fr; } .stats-3 { grid-template-columns: 1fr; } .rep-page { padding: 16px; } .tab-nav { flex-wrap: wrap; } }
</style>