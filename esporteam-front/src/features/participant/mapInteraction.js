export function createLongPressGesture({
  delay = 500,
  movementTolerance = 10,
  schedule = (callback, timeout) => globalThis.setTimeout(callback, timeout),
  cancelSchedule = timer => globalThis.clearTimeout(timer),
  onConfirm,
} = {}) {
  let timer = null
  let origin = null

  function cancel() {
    if (timer !== null) cancelSchedule(timer)
    timer = null
    origin = null
  }

  function start(point, payload) {
    cancel()
    origin = point
    timer = schedule(() => {
      timer = null
      origin = null
      onConfirm?.(payload)
    }, delay)
  }

  function move(point) {
    if (!origin) return
    if (Math.hypot(point.x - origin.x, point.y - origin.y) > movementTolerance) cancel()
  }

  return { start, move, cancel }
}

export function changeMapZoom(map, delta) {
  if (!map) return false
  if (delta > 0) map.zoomIn()
  else map.zoomOut()
  return true
}

export function focusMapSelection(target) {
  const element = target?.getElement?.() ?? target
  if (typeof element?.focus !== 'function') return false
  element.focus({ preventScroll: true })
  return true
}
