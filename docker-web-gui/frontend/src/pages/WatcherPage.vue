<script setup>
import { ref } from 'vue'
import MainHeader from '../components/layout/MainHeader.vue'
import WatcherLog from '../components/watcher/WatcherLog.vue'
import { useAppStore } from '../stores/app.js'
import { usePolling } from '../composables/usePolling.js'

const appStore = useAppStore()
const autoScroll = ref(true)

usePolling(() => appStore.refreshWatcherLogs(), 3000)

function onRefresh() {
  appStore.refreshWatcherLogs()
}
</script>

<template>
  <div>
    <MainHeader title="Watcher">
      <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; color: var(--text2); cursor: pointer;">
        <input type="checkbox" v-model="autoScroll"> Auto-scroll
      </label>
      <button class="btn btn-secondary btn-sm" @click="onRefresh">Refrescar</button>
    </MainHeader>
    <div class="main-content" style="max-width: 1200px;">
      <p style="color: var(--text3); font-size: 13px; margin-bottom: 16px;">
        Log en tiempo real de los cambios detectados en modulos/ de proyectos activos.
      </p>
      <WatcherLog :logs="appStore.watcherLogs" :auto-scroll="autoScroll" />
    </div>
  </div>
</template>
