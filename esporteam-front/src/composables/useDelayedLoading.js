import { onScopeDispose, ref, watch } from 'vue'

export const SKELETON_DELAY_MS = 400

export function useDelayedLoading(isLoading, delay = SKELETON_DELAY_MS) {
  const visible = ref(false)
  let timer = null

  watch(isLoading, (loading) => {
    clearTimeout(timer)

    if (!loading) {
      visible.value = false
      return
    }

    timer = setTimeout(() => {
      visible.value = true
    }, delay)
  }, { immediate: true })

  onScopeDispose(() => clearTimeout(timer))

  return visible
}
