<script setup>
import { ref, watch, nextTick } from 'vue'

const props = defineProps({
  logs: { type: Array, default: () => [] },
  autoScroll: { type: Boolean, default: true },
})

const logContainer = ref(null)

function formatTs(ts) {
  return new Date(ts * 1000).toLocaleTimeString('es-ES', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  })
}

watch(
  () => props.logs,
  () => {
    if (props.autoScroll && logContainer.value) {
      nextTick(() => {
        logContainer.value.scrollTop = logContainer.value.scrollHeight
      })
    }
  },
  { deep: true }
)
</script>

<template>
  <div ref="logContainer" class="watcher-log">
    <div v-if="!logs.length" class="watcher-log-empty">No hay entradas de log.</div>
    <div v-for="(entry, i) in logs" :key="i" class="watcher-entry">
      <span class="watcher-ts">{{ formatTs(entry.ts) }}</span>
      <span class="watcher-level" :class="entry.level">{{ entry.level }}</span>
      <span class="watcher-msg">{{ entry.msg }}</span>
    </div>
  </div>
</template>

<style scoped>
.watcher-log { background: #0a0c10; border: 1px solid var(--border); border-radius: 6px; padding: 0; font-family: 'SF Mono', 'JetBrains Mono', 'Fira Code', monospace; font-size: 12px; line-height: 1.7; max-height: 70vh; overflow-y: auto; }
.watcher-log-empty { padding: 32px; text-align: center; color: var(--text3); }
.watcher-entry { padding: 4px 14px; border-bottom: 1px solid rgba(255,255,255,0.04); display: flex; gap: 10px; align-items: baseline; }
.watcher-entry:last-child { border-bottom: none; }
.watcher-ts { color: var(--text3); flex-shrink: 0; font-size: 11px; min-width: 70px; }
.watcher-level { flex-shrink: 0; font-size: 10px; font-weight: 700; text-transform: uppercase; min-width: 42px; text-align: center; padding: 1px 5px; border-radius: 3px; }
.watcher-level.info { color: var(--green); background: var(--green-dim); }
.watcher-level.error { color: var(--red); background: var(--red-dim); }
.watcher-level.warn { color: var(--yellow); background: rgba(234,179,8,0.12); }
.watcher-msg { color: #c9d1d9; word-break: break-all; }
</style>
