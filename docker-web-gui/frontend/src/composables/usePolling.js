import { onMounted, onUnmounted, ref } from 'vue'

/**
 * Sets up a polling timer that auto-cleans on unmount.
 * @param {Function} callback - function to call on each tick
 * @param {number} intervalMs - interval in milliseconds
 * @param {boolean} immediate - whether to call immediately on mount
 */
export function usePolling(callback, intervalMs = 15000, immediate = true) {
  const timer = ref(null)

  function start() {
    stop()
    timer.value = setInterval(callback, intervalMs)
  }

  function stop() {
    if (timer.value) {
      clearInterval(timer.value)
      timer.value = null
    }
  }

  function restart(newInterval) {
    stop()
    timer.value = setInterval(callback, newInterval || intervalMs)
  }

  onMounted(() => {
    if (immediate) callback()
    start()
  })

  onUnmounted(() => {
    stop()
  })

  return { start, stop, restart }
}
