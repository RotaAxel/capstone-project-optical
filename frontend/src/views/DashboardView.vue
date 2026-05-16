<template>
  <div class="space-y-6">

    <!-- ══════════════════════════════════════════════════════ ADMIN -->
    <template v-if="role === 'admin'">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div v-for="stat in adminStats" :key="stat.label" class="card flex items-start gap-4">
          <div :class="stat.color" class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0">
            <component :is="stat.icon" class="w-5 h-5 text-white" />
          </div>
          <div>
            <p class="text-xs text-gray-500 font-medium">{{ stat.label }}</p>
            <p class="text-2xl font-bold text-gray-900 mt-0.5">{{ stat.value }}</p>
            <p v-if="stat.sub" class="text-xs text-gray-400 mt-0.5">{{ stat.sub }}</p>
          </div>
        </div>
      </div>

      <div v-if="data.stats?.low_stock_count > 0" class="flex items-center gap-3 bg-red-50 border border-red-200 rounded-xl px-5 py-4">
        <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        <div>
          <p class="text-sm font-semibold text-red-800">Low Stock Alert</p>
          <p class="text-xs text-red-600">{{ data.stats?.low_stock_count }} product(s) are at or below reorder point</p>
        </div>
        <RouterLink to="/inventory?low_stock=1" class="ml-auto btn-danger btn-sm">View Items</RouterLink>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="card lg:col-span-2">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-gray-800">Monthly Sales ({{ currentYear }})</h2>
            <span class="badge-blue">Revenue</span>
          </div>
          <div class="flex items-end gap-2 h-40">
            <div v-for="(m, i) in monthlyChart" :key="i" class="flex-1 flex flex-col items-center gap-1 group">
              <span class="text-xs text-gray-500 opacity-0 group-hover:opacity-100 transition">₱{{ fmt(m.total) }}</span>
              <div class="w-full rounded-t-sm transition-all duration-300" :style="{ height: m.height + '%' }" :class="m.height > 3 ? 'bg-blue-500' : 'bg-gray-100'"></div>
              <span class="text-xs text-gray-400">{{ m.label }}</span>
            </div>
          </div>
        </div>
        <div class="card">
          <h2 class="text-sm font-semibold text-gray-800 mb-4">Low Stock Items</h2>
          <div v-if="data.low_stock_products?.length" class="space-y-3">
            <div v-for="p in data.low_stock_products" :key="p.id" class="flex items-center gap-3">
              <div class="w-2 h-2 rounded-full bg-red-400 shrink-0"></div>
              <div class="flex-1 min-w-0">
                <p class="text-xs font-medium text-gray-800 truncate">{{ p.name }}</p>
                <p class="text-xs text-gray-400">{{ p.category?.name }}</p>
              </div>
              <span class="badge-red text-xs">{{ p.stock_quantity }} left</span>
            </div>
          </div>
          <p v-else class="text-sm text-gray-400 text-center py-4">All stocks are sufficient</p>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-gray-800">Recent Sales</h2>
            <RouterLink to="/sales" class="text-xs text-blue-600 hover:text-blue-800">View all</RouterLink>
          </div>
          <div class="divide-y divide-gray-50">
            <div v-for="sale in data.recent_sales" :key="sale.id" class="flex items-center justify-between py-2.5">
              <div>
                <p class="text-sm font-medium text-gray-800">{{ sale.receipt_number }}</p>
                <p class="text-xs text-gray-400">{{ sale.patient?.first_name ?? 'Walk-in' }} · {{ sale.cashier?.name }}</p>
              </div>
              <p class="text-sm font-semibold text-green-700">₱{{ fmt(sale.total_amount) }}</p>
            </div>
            <p v-if="!data.recent_sales?.length" class="text-sm text-gray-400 py-4 text-center">No sales today</p>
          </div>
        </div>
        <div class="card">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-gray-800">Upcoming Appointments</h2>
            <RouterLink to="/appointments" class="text-xs text-blue-600 hover:text-blue-800">View all</RouterLink>
          </div>
          <div class="divide-y divide-gray-50">
            <div v-for="appt in data.upcoming_appointments" :key="appt.id" class="flex items-center gap-3 py-2.5">
              <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-800 truncate">{{ appt.patient?.first_name }} {{ appt.patient?.last_name }}</p>
                <p class="text-xs text-gray-400">{{ fmtDateTime(appt.appointment_date) }}</p>
              </div>
              <span class="badge-blue text-xs capitalize">{{ appt.type?.replace('_', ' ') }}</span>
            </div>
            <p v-if="!data.upcoming_appointments?.length" class="text-sm text-gray-400 py-4 text-center">No upcoming appointments</p>
          </div>
        </div>
      </div>
    </template>

    <!-- ══════════════════════════════════════════════════ RECEPTIONIST -->
    <template v-else-if="role === 'receptionist'">
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card flex items-start gap-4">
          <div class="w-11 h-11 rounded-xl bg-blue-500 flex items-center justify-center shrink-0"><svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></div>
          <div><p class="text-xs text-gray-500 font-medium">Appointments Today</p><p class="text-2xl font-bold text-gray-900 mt-0.5">{{ data.stats?.appointments_today ?? 0 }}</p><p class="text-xs text-gray-400 mt-0.5">{{ data.stats?.scheduled_today ?? 0 }} scheduled</p></div>
        </div>
        <div class="card flex items-start gap-4">
          <div class="w-11 h-11 rounded-xl bg-green-500 flex items-center justify-center shrink-0"><svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
          <div><p class="text-xs text-gray-500 font-medium">Completed Today</p><p class="text-2xl font-bold text-gray-900 mt-0.5">{{ data.stats?.completed_today ?? 0 }}</p></div>
        </div>
        <div class="card flex items-start gap-4">
          <div class="w-11 h-11 rounded-xl bg-purple-500 flex items-center justify-center shrink-0"><svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg></div>
          <div><p class="text-xs text-gray-500 font-medium">Total Patients</p><p class="text-2xl font-bold text-gray-900 mt-0.5">{{ data.stats?.total_patients ?? 0 }}</p><p class="text-xs text-gray-400 mt-0.5">+{{ data.stats?.new_patients_today ?? 0 }} today</p></div>
        </div>
        <div class="card flex items-start gap-4">
          <div class="w-11 h-11 rounded-xl bg-red-400 flex items-center justify-center shrink-0"><svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></div>
          <div><p class="text-xs text-gray-500 font-medium">Cancelled Today</p><p class="text-2xl font-bold text-gray-900 mt-0.5">{{ data.stats?.cancelled_today ?? 0 }}</p></div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Today's Schedule -->
        <div class="card">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-gray-800">Today's Schedule</h2>
            <RouterLink to="/appointments" class="text-xs text-blue-600 hover:text-blue-800">View all</RouterLink>
          </div>
          <div v-if="data.today_appointments?.length" class="space-y-2">
            <div v-for="appt in data.today_appointments" :key="appt.id"
              class="flex items-center gap-3 p-3 rounded-lg border border-gray-100 hover:border-blue-200 transition-colors">
              <div class="text-center shrink-0 w-12">
                <p class="text-xs font-bold text-blue-700">{{ fmtTime(appt.appointment_date) }}</p>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">{{ appt.patient?.first_name }} {{ appt.patient?.last_name }}</p>
                <p class="text-xs text-gray-400">{{ appt.type?.replace('_', ' ') }} · Dr. {{ appt.optometrist?.name ?? '—' }}</p>
              </div>
              <span :class="{
                'badge-blue':  appt.status === 'scheduled',
                'badge-green': appt.status === 'completed',
                'badge-red':   appt.status === 'cancelled',
                'badge-gray':  appt.status === 'no_show',
              }" class="capitalize text-xs shrink-0">{{ appt.status?.replace('_',' ') }}</span>
            </div>
          </div>
          <p v-else class="text-sm text-gray-400 py-6 text-center">No appointments scheduled today</p>
        </div>

        <!-- Recent Patients + Upcoming -->
        <div class="space-y-6">
          <div class="card">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-sm font-semibold text-gray-800">Recently Registered Patients</h2>
              <RouterLink to="/patients" class="text-xs text-blue-600 hover:text-blue-800">View all</RouterLink>
            </div>
            <div class="divide-y divide-gray-50">
              <div v-for="p in data.recent_patients" :key="p.id" class="flex items-center gap-3 py-2.5">
                <div class="w-7 h-7 rounded-full bg-purple-100 flex items-center justify-center text-purple-700 text-xs font-bold shrink-0">{{ p.first_name?.charAt(0) }}</div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-800 truncate">{{ p.first_name }} {{ p.last_name }}</p>
                  <p class="text-xs text-gray-400 font-mono">{{ p.patient_code }}</p>
                </div>
                <p class="text-xs text-gray-400 shrink-0">{{ fmtDateShort(p.created_at) }}</p>
              </div>
              <p v-if="!data.recent_patients?.length" class="text-sm text-gray-400 py-4 text-center">No patients yet</p>
            </div>
          </div>

          <div class="card">
            <h2 class="text-sm font-semibold text-gray-800 mb-3">Next Appointments</h2>
            <div class="space-y-2">
              <div v-for="appt in data.upcoming_appointments" :key="appt.id" class="flex items-center gap-3">
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-800 truncate">{{ appt.patient?.first_name }} {{ appt.patient?.last_name }}</p>
                  <p class="text-xs text-gray-400">{{ fmtDateTime(appt.appointment_date) }}</p>
                </div>
                <span class="badge-blue text-xs capitalize">{{ appt.type?.replace('_',' ') }}</span>
              </div>
              <p v-if="!data.upcoming_appointments?.length" class="text-sm text-gray-400 py-2 text-center">No upcoming appointments</p>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- ══════════════════════════════════════════════════ OPTOMETRIST -->
    <template v-else-if="role === 'optometrist'">
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card flex items-start gap-4">
          <div class="w-11 h-11 rounded-xl bg-blue-500 flex items-center justify-center shrink-0"><svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></div>
          <div><p class="text-xs text-gray-500 font-medium">My Appointments Today</p><p class="text-2xl font-bold text-gray-900 mt-0.5">{{ data.stats?.my_appointments_today ?? 0 }}</p></div>
        </div>
        <div class="card flex items-start gap-4">
          <div class="w-11 h-11 rounded-xl bg-green-500 flex items-center justify-center shrink-0"><svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
          <div><p class="text-xs text-gray-500 font-medium">Completed Today</p><p class="text-2xl font-bold text-gray-900 mt-0.5">{{ data.stats?.completed_today ?? 0 }}</p></div>
        </div>
        <div class="card flex items-start gap-4">
          <div class="w-11 h-11 rounded-xl bg-purple-500 flex items-center justify-center shrink-0"><svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg></div>
          <div><p class="text-xs text-gray-500 font-medium">Prescriptions This Week</p><p class="text-2xl font-bold text-gray-900 mt-0.5">{{ data.stats?.prescriptions_this_week ?? 0 }}</p></div>
        </div>
        <div class="card flex items-start gap-4">
          <div class="w-11 h-11 rounded-xl bg-orange-400 flex items-center justify-center shrink-0"><svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
          <div><p class="text-xs text-gray-500 font-medium">Pending Today</p><p class="text-2xl font-bold text-gray-900 mt-0.5">{{ data.stats?.scheduled_today ?? 0 }}</p></div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- My Today's Schedule -->
        <div class="card">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-gray-800">My Schedule Today</h2>
            <RouterLink to="/appointments" class="text-xs text-blue-600 hover:text-blue-800">View all</RouterLink>
          </div>
          <div v-if="data.today_appointments?.length" class="space-y-2">
            <div v-for="appt in data.today_appointments" :key="appt.id"
              class="flex items-center gap-3 p-3 rounded-lg border border-gray-100 hover:border-purple-200 transition-colors">
              <div class="text-center shrink-0 w-12">
                <p class="text-xs font-bold text-purple-700">{{ fmtTime(appt.appointment_date) }}</p>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">{{ appt.patient?.first_name }} {{ appt.patient?.last_name }}</p>
                <p class="text-xs text-gray-400 capitalize">{{ appt.type?.replace('_', ' ') }}</p>
                <p v-if="appt.reason" class="text-xs text-gray-400 truncate">{{ appt.reason }}</p>
              </div>
              <span :class="{
                'badge-blue':  appt.status === 'scheduled',
                'badge-green': appt.status === 'completed',
                'badge-red':   appt.status === 'cancelled',
              }" class="capitalize text-xs shrink-0">{{ appt.status }}</span>
            </div>
          </div>
          <p v-else class="text-sm text-gray-400 py-6 text-center">No appointments assigned to you today</p>
        </div>

        <div class="space-y-6">
          <!-- Upcoming -->
          <div class="card">
            <h2 class="text-sm font-semibold text-gray-800 mb-3">My Upcoming Appointments</h2>
            <div class="space-y-2">
              <div v-for="appt in data.upcoming_appointments" :key="appt.id" class="flex items-center gap-3">
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-800 truncate">{{ appt.patient?.first_name }} {{ appt.patient?.last_name }}</p>
                  <p class="text-xs text-gray-400">{{ fmtDateTime(appt.appointment_date) }}</p>
                </div>
                <span class="badge-blue text-xs capitalize">{{ appt.type?.replace('_',' ') }}</span>
              </div>
              <p v-if="!data.upcoming_appointments?.length" class="text-sm text-gray-400 py-2 text-center">No upcoming appointments</p>
            </div>
          </div>

          <!-- Recent Prescriptions -->
          <div class="card">
            <div class="flex items-center justify-between mb-3">
              <h2 class="text-sm font-semibold text-gray-800">Recent Prescriptions</h2>
            </div>
            <div class="divide-y divide-gray-50">
              <div v-for="rx in data.recent_prescriptions" :key="rx.id" class="flex items-center justify-between py-2.5">
                <div>
                  <p class="text-sm font-medium text-gray-800">{{ rx.patient?.first_name }} {{ rx.patient?.last_name }}</p>
                  <p class="text-xs text-gray-400">{{ fmtDateShort(rx.exam_date) }}</p>
                </div>
                <div class="text-xs text-right text-gray-500 font-mono">
                  <p>OD {{ rx.od_sphere ?? '—' }} / {{ rx.od_cylinder ?? '—' }}</p>
                  <p>OS {{ rx.os_sphere ?? '—' }} / {{ rx.os_cylinder ?? '—' }}</p>
                </div>
              </div>
              <p v-if="!data.recent_prescriptions?.length" class="text-sm text-gray-400 py-4 text-center">No recent prescriptions</p>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- ══════════════════════════════════════════════ INVENTORY STAFF -->
    <template v-else-if="role === 'inventory_staff'">
      <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="card flex items-start gap-4">
          <div class="w-11 h-11 rounded-xl bg-blue-500 flex items-center justify-center shrink-0"><svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></div>
          <div><p class="text-xs text-gray-500 font-medium">Total Products</p><p class="text-2xl font-bold text-gray-900 mt-0.5">{{ data.stats?.total_products ?? 0 }}</p></div>
        </div>
        <div class="card flex items-start gap-4">
          <div class="w-11 h-11 rounded-xl bg-red-500 flex items-center justify-center shrink-0"><svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></div>
          <div><p class="text-xs text-gray-500 font-medium">Out of Stock</p><p class="text-2xl font-bold text-red-600 mt-0.5">{{ data.stats?.out_of_stock ?? 0 }}</p></div>
        </div>
        <div class="card flex items-start gap-4">
          <div class="w-11 h-11 rounded-xl bg-yellow-500 flex items-center justify-center shrink-0"><svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg></div>
          <div><p class="text-xs text-gray-500 font-medium">Low Stock</p><p class="text-2xl font-bold text-yellow-600 mt-0.5">{{ data.stats?.low_stock_count ?? 0 }}</p></div>
        </div>
        <div class="card flex items-start gap-4">
          <div class="w-11 h-11 rounded-xl bg-green-500 flex items-center justify-center shrink-0"><svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
          <div><p class="text-xs text-gray-500 font-medium">Total Stock Value</p><p class="text-xl font-bold text-green-700 mt-0.5">₱{{ fmt(data.stats?.total_stock_value) }}</p></div>
        </div>
        <div class="card flex items-start gap-4">
          <div class="w-11 h-11 rounded-xl bg-indigo-500 flex items-center justify-center shrink-0"><svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg></div>
          <div><p class="text-xs text-gray-500 font-medium">Stock-ins Today</p><p class="text-2xl font-bold text-gray-900 mt-0.5">{{ data.stats?.stock_ins_today ?? 0 }}</p></div>
        </div>
      </div>

      <!-- FSN Summary -->
      <div v-if="data.fsn_summary" class="grid grid-cols-3 gap-4">
        <div class="card text-center border-l-4 border-l-green-500">
          <p class="text-xs text-gray-500">Fast Moving</p>
          <p class="text-3xl font-bold text-green-600 mt-1">{{ data.fsn_summary.fast }}</p>
          <p class="text-xs text-gray-400 mt-1">≥ 1 unit/day</p>
        </div>
        <div class="card text-center border-l-4 border-l-yellow-400">
          <p class="text-xs text-gray-500">Slow Moving</p>
          <p class="text-3xl font-bold text-yellow-600 mt-1">{{ data.fsn_summary.slow }}</p>
          <p class="text-xs text-gray-400 mt-1">0.1–1 unit/day</p>
        </div>
        <div class="card text-center border-l-4 border-l-red-400">
          <p class="text-xs text-gray-500">Non-Moving</p>
          <p class="text-3xl font-bold text-red-600 mt-1">{{ data.fsn_summary.non_moving }}</p>
          <p class="text-xs text-gray-400 mt-1">< 0.1 unit/day</p>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Low / Out of Stock -->
        <div class="card">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-gray-800">Stock Alerts</h2>
            <RouterLink to="/inventory?low_stock=1" class="text-xs text-blue-600 hover:text-blue-800">View inventory</RouterLink>
          </div>
          <div v-if="data.low_stock_products?.length" class="space-y-2">
            <div v-for="p in data.low_stock_products" :key="p.id" class="flex items-center gap-3 p-2.5 rounded-lg bg-gray-50">
              <div class="w-2 h-2 rounded-full shrink-0" :class="p.stock_quantity === 0 ? 'bg-red-500' : 'bg-yellow-400'"></div>
              <div class="flex-1 min-w-0">
                <p class="text-xs font-medium text-gray-800 truncate">{{ p.name }}</p>
                <p class="text-xs text-gray-400">{{ p.category?.name }} · ROP: {{ p.reorder_point }}</p>
              </div>
              <span :class="p.stock_quantity === 0 ? 'badge-red' : 'badge-yellow'" class="text-xs shrink-0">
                {{ p.stock_quantity === 0 ? 'Out of stock' : p.stock_quantity + ' left' }}
              </span>
            </div>
          </div>
          <p v-else class="text-sm text-gray-400 text-center py-6">All stocks are sufficient</p>
        </div>

        <!-- Recent Stock Movements -->
        <div class="card">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-gray-800">Recent Stock Movements</h2>
            <RouterLink to="/stock-movements" class="text-xs text-blue-600 hover:text-blue-800">View all</RouterLink>
          </div>
          <div v-if="data.recent_stock_movements?.length" class="divide-y divide-gray-50">
            <div v-for="m in data.recent_stock_movements" :key="m.id" class="flex items-center gap-3 py-2.5">
              <span :class="{
                'badge-green':  m.type === 'stock_in' || m.type === 'return',
                'badge-blue':   m.type === 'sale',
                'badge-yellow': m.type === 'adjustment',
              }" class="capitalize text-xs shrink-0">{{ m.type?.replace('_',' ') }}</span>
              <div class="flex-1 min-w-0">
                <p class="text-xs font-medium text-gray-800 truncate">{{ m.product?.name }}</p>
                <p class="text-xs text-gray-400">{{ fmtDateShort(m.created_at) }} · by {{ m.user?.name }}</p>
              </div>
              <span class="text-sm font-semibold shrink-0" :class="['stock_in','return'].includes(m.type) ? 'text-green-600' : 'text-red-500'">
                {{ ['stock_in','return'].includes(m.type) ? '+' : '-' }}{{ m.quantity }}
              </span>
            </div>
          </div>
          <p v-else class="text-sm text-gray-400 text-center py-6">No recent movements</p>
        </div>
      </div>
    </template>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="animate-spin w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full"></div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const auth    = useAuthStore()
const role    = computed(() => auth.user?.role)
const data    = ref({})
const loading = ref(true)
let   refreshTimer = null
const currentYear  = new Date().getFullYear()
const MONTHS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']

const monthlyChart = computed(() => {
  const sales  = data.value.monthly_sales ?? []
  const maxVal = Math.max(...sales.map(s => +s.total), 1)
  return MONTHS.map((label, i) => {
    const entry = sales.find(s => +s.month === i + 1)
    return { label, total: entry?.total ?? 0, height: entry ? Math.max(5, (+entry.total / maxVal) * 100) : 3 }
  })
})

const adminStats = computed(() => [
  { label: 'Total Patients',     value: data.value.stats?.total_patients ?? 0,                              color: 'bg-blue-500',   icon: IconPatients,  sub: `+${data.value.stats?.new_patients_today ?? 0} today` },
  { label: 'Appointments Today', value: data.value.stats?.appointments_today ?? 0,                          color: 'bg-purple-500', icon: IconCalendar },
  { label: 'Sales Today',        value: '₱' + fmt(data.value.stats?.sales_today ?? 0),                      color: 'bg-green-500',  icon: IconSales,     sub: `${data.value.stats?.transactions_today ?? 0} transactions` },
  { label: 'Low Stock Items',    value: data.value.stats?.low_stock_count ?? 0,                              color: 'bg-red-500',    icon: IconAlert },
])

function fmt(v) { return Number(v || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }
function fmtDateTime(v) { return v ? new Date(v).toLocaleString('en-PH', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—' }
function fmtTime(v) { return v ? new Date(v).toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' }) : '—' }
function fmtDateShort(v) { return v ? new Date(v).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '—' }

async function fetchDashboard() {
  try { data.value = (await api.get('/dashboard')).data }
  catch { /* backend not available */ }
}

onMounted(async () => {
  await fetchDashboard()
  loading.value = false
  refreshTimer = setInterval(fetchDashboard, 60000)
})

onUnmounted(() => { if (refreshTimer) clearInterval(refreshTimer) })

const IconPatients = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>` }
const IconCalendar = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>` }
const IconSales    = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>` }
const IconAlert    = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>` }
</script>
