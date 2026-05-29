import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useFlashStore = defineStore('flash', () => {
  const message = ref(null)
  const type    = ref('warning') // 'error' | 'warning' | 'info'
  let timer     = null

  function set(msg, msgType = 'warning', duration = 4000) {
    if (timer) clearTimeout(timer)
    message.value = msg
    type.value    = msgType
    timer = setTimeout(clear, duration)
  }

  function clear() {
    message.value = null
    if (timer) { clearTimeout(timer); timer = null }
  }

  return { message, type, set, clear }
})
