import { ref, computed, onMounted, onUnmounted } from 'vue'
import api from '@/services/api'

const alerts       = ref([])
const loading      = ref(false)
const lastChecked  = ref(null)
let   pollInterval = null

export function useAlerts(autoPoll = true, intervalMs = 60000) {
  const criticalCount = computed(() => alerts.value.filter(a => a.severity === 'critical').length)
  const warningCount  = computed(() => alerts.value.filter(a => a.severity === 'warning').length)
  const totalCount    = computed(() => alerts.value.length)
  const badgeCount    = computed(() => criticalCount.value + warningCount.value)

  async function fetchAlerts() {
    loading.value = true
    try {
      const { data } = await api.get('/alerts')
      alerts.value      = data.alerts
      lastChecked.value = new Date()
    } catch { /* silent — badge just stays at last known count */ }
    finally { loading.value = false }
  }

  function startPolling() {
    fetchAlerts()
    pollInterval = setInterval(fetchAlerts, intervalMs)
  }

  function stopPolling() {
    if (pollInterval) { clearInterval(pollInterval); pollInterval = null }
  }

  if (autoPoll) {
    onMounted(startPolling)
    onUnmounted(stopPolling)
  }

  return { alerts, loading, lastChecked, criticalCount, warningCount, totalCount, badgeCount, fetchAlerts }
}
