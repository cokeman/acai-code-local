import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api, apiPost } from '../composables/useApi.js'
import { applyTheme } from '../composables/useTheme.js'

export const useAppStore = defineStore('app', () => {
  // State
  const projects = ref([])
  const localWebs = ref([])
  const settings = ref({})
  const refreshTimer = ref(null)

  // Actions
  async function loadSettings() {
    try {
      const data = await api('/api/settings')
      settings.value = data
      applyTheme(data.theme || 'dark')
      applyRefreshInterval(data.refresh_interval || 15)
    } catch (e) {
      applyRefreshInterval(15)
    }
  }

  async function refreshProjects() {
    try {
      const data = await api('/api/projects')
      projects.value = data.projects || []
    } catch (e) {
      projects.value = []
    }
  }

  async function refreshLocalWebs() {
    try {
      const data = await api('/api/local-webs')
      localWebs.value = data.webs || []
    } catch (e) {
      localWebs.value = []
    }
  }

  function applyRefreshInterval(seconds) {
    if (refreshTimer.value) clearInterval(refreshTimer.value)
    refreshTimer.value = setInterval(refreshProjects, (seconds || 15) * 1000)
  }

  async function saveSettings(body) {
    return apiPost('/api/settings', body)
  }

  return {
    projects,
    localWebs,
    settings,
    loadSettings,
    refreshProjects,
    refreshLocalWebs,
    applyRefreshInterval,
    saveSettings,
  }
})
