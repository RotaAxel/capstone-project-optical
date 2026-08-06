import { ref } from 'vue'

/**
 * Debounced type-ahead suggestions backed by a small search request.
 *
 * @param {(query: string) => Promise<Array<{id: any, label: string, sub?: string}>>} fetchFn
 *        Resolves to a short list of already-formatted suggestion items for the given query.
 * @param {object} [opts]
 * @param {number} [opts.minChars=1]  minimum characters typed before suggestions are requested
 * @param {number} [opts.delay=200]   debounce delay in ms
 */
export function useSearchSuggest(fetchFn, { minChars = 1, delay = 200 } = {}) {
  const suggestions  = ref([])
  const open         = ref(false)
  const activeIndex  = ref(-1)
  const loading      = ref(false)

  let timer = null
  let requestSeq = 0

  function query(text) {
    clearTimeout(timer)
    const q = (text ?? '').trim()

    if (q.length < minChars) {
      suggestions.value = []
      open.value = false
      activeIndex.value = -1
      return
    }

    timer = setTimeout(async () => {
      const seq = ++requestSeq
      loading.value = true
      try {
        const items = await fetchFn(q)
        if (seq !== requestSeq) return // superseded by a newer keystroke
        suggestions.value = items ?? []
        open.value = suggestions.value.length > 0
        activeIndex.value = -1
      } catch {
        if (seq === requestSeq) { suggestions.value = []; open.value = false }
      } finally {
        if (seq === requestSeq) loading.value = false
      }
    }, delay)
  }

  function close() {
    open.value = false
    activeIndex.value = -1
  }

  function reopen() {
    if (suggestions.value.length) open.value = true
  }

  /** Wire to @keydown on the input; call onPick(item) when Enter selects the highlighted row. */
  function onKeydown(e, onPick) {
    if (!open.value || !suggestions.value.length) return

    if (e.key === 'ArrowDown') {
      e.preventDefault()
      activeIndex.value = (activeIndex.value + 1) % suggestions.value.length
    } else if (e.key === 'ArrowUp') {
      e.preventDefault()
      activeIndex.value = (activeIndex.value - 1 + suggestions.value.length) % suggestions.value.length
    } else if (e.key === 'Enter' && activeIndex.value >= 0) {
      e.preventDefault()
      onPick(suggestions.value[activeIndex.value])
      close()
    } else if (e.key === 'Escape') {
      close()
    }
  }

  return { suggestions, open, activeIndex, loading, query, close, reopen, onKeydown }
}
