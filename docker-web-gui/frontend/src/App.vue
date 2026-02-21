<script setup>
import { onMounted } from 'vue'
import { useAppStore } from './stores/app.js'
import AppSidebar from './components/layout/AppSidebar.vue'
import ServerOffline from './components/common/ServerOffline.vue'

const appStore = useAppStore()

onMounted(async () => {
  await appStore.loadSettings()
  if (appStore.serverOnline) {
    appStore.refreshProjects()
    appStore.refreshLocalWebs()
    appStore.refreshWatcherLogs()
  }
})
</script>

<template>
  <template v-if="appStore.serverOnline === false">
    <ServerOffline @retry="appStore.checkServer()" />
  </template>
  <template v-else-if="appStore.serverOnline">
    <AppSidebar />
    <div class="main-wrapper">
      <router-view />
    </div>
  </template>
</template>
