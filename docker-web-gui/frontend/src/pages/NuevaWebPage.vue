<script setup>
import MainHeader from '../components/layout/MainHeader.vue'
import LaunchForm from '../components/nueva-web/LaunchForm.vue'
import { useAppStore } from '../stores/app.js'

const appStore = useAppStore()

function onRefresh() {
  appStore.refreshProjects()
}
</script>

<template>
  <div>
    <MainHeader title="Nueva Web" />
    <LaunchForm @refresh="onRefresh" />
  </div>
</template>
