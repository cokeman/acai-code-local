<script setup>
import { onMounted } from 'vue'
import { useAppStore } from './stores/app.js'
import AppSidebar from './components/layout/AppSidebar.vue'

const appStore = useAppStore()

onMounted(async () => {
  await appStore.loadSettings()
  appStore.refreshProjects()
  appStore.refreshLocalWebs()
  appStore.refreshWatcherLogs()
})
</script>

<template>
  <AppSidebar />
  <div class="main-wrapper">
    <router-view />
  </div>
</template>
