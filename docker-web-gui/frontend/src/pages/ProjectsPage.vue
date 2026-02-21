<script setup>
import { ref, reactive, onMounted } from 'vue'
import MainHeader from '../components/layout/MainHeader.vue'
import ProjectCard from '../components/projects/ProjectCard.vue'
import OutputModal from '../components/common/OutputModal.vue'
import ConfirmDialog from '../components/common/ConfirmDialog.vue'
import { useAppStore } from '../stores/app.js'
import { api, apiPost } from '../composables/useApi.js'

const appStore = useAppStore()
const loading = ref(false)

// Logs modal
const showLogsModal = ref(false)
const logsTitle = ref('')
const logsContent = ref('')

// Confirm dialog
const showConfirm = ref(false)
const confirmTitle = ref('')
const confirmMessage = ref('')
const confirmAction = ref(null)

// Result modal
const showResult = ref(false)
const resultTitle = ref('Resultado')
const resultContent = ref('')

// Per-project loading tracking
const actionLoading = reactive(new Map())
function setLoading(project, action) { actionLoading.set(project.project_dir, action) }
function clearLoading(project) { actionLoading.delete(project.project_dir) }

onMounted(async () => {
  loading.value = true
  await appStore.refreshProjects()
  loading.value = false
})

function onRefresh() {
  appStore.refreshProjects()
}

async function onShowLogs(containerName) {
  logsTitle.value = `Logs: ${containerName}`
  logsContent.value = 'Cargando...'
  showLogsModal.value = true
  try {
    const data = await api(`/api/logs/${encodeURIComponent(containerName)}`)
    logsContent.value = data.logs || '(sin logs)'
  } catch {
    logsContent.value = 'Error al obtener logs'
  }
}

function onStop(project) {
  confirmTitle.value = 'Parar proyecto'
  confirmMessage.value = `Parar "${project.name}"? Se detendran los contenedores.`
  confirmAction.value = async () => {
    setLoading(project, 'stop')
    try {
      const data = await apiPost('/api/stop', { project_dir: project.project_dir })
      resultContent.value = data.output || data.message || 'Proyecto parado'
      resultTitle.value = 'Parar proyecto'
      showResult.value = true
    } catch {
      resultContent.value = 'Error al parar el proyecto'
      resultTitle.value = 'Error'
      showResult.value = true
    } finally {
      clearLoading(project)
    }
    appStore.refreshProjects()
  }
  showConfirm.value = true
}

function onDestroy(project) {
  confirmTitle.value = 'Destruir proyecto'
  confirmMessage.value = `Destruir "${project.name}"? Se eliminaran los contenedores y volumenes.`
  confirmAction.value = async () => {
    setLoading(project, 'destroy')
    try {
      const data = await apiPost('/api/destroy', { project_dir: project.project_dir })
      resultContent.value = data.output || data.message || 'Proyecto destruido'
      resultTitle.value = 'Destruir proyecto'
      showResult.value = true
    } catch {
      resultContent.value = 'Error al destruir el proyecto'
      resultTitle.value = 'Error'
      showResult.value = true
    } finally {
      clearLoading(project)
    }
    appStore.refreshProjects()
  }
  showConfirm.value = true
}

function onConfirm() {
  if (confirmAction.value) confirmAction.value()
}
</script>

<template>
  <div>
    <MainHeader title="En ejecucion">
      <button class="btn btn-secondary btn-sm" @click="onRefresh">Refrescar</button>
    </MainHeader>
    <div class="main-content">
      <div v-if="loading" style="text-align: center; padding: 48px;">
        <span class="spinner"></span>
      </div>
      <div v-else-if="!appStore.projects.length" class="empty-state">
        No hay proyectos activos
      </div>
      <div v-else class="projects-grid">
        <ProjectCard
          v-for="project in appStore.projects"
          :key="project.name"
          :project="project"
          :loading-action="actionLoading.get(project.project_dir) || null"
          @show-logs="onShowLogs"
          @stop="onStop"
          @destroy="onDestroy"
        />
      </div>
    </div>

    <OutputModal
      v-model="showLogsModal"
      :title="logsTitle"
      :content="logsContent"
    />

    <OutputModal
      v-model="showResult"
      :title="resultTitle"
      :content="resultContent"
    />

    <ConfirmDialog
      v-model="showConfirm"
      :title="confirmTitle"
      :message="confirmMessage"
      confirm-label="Confirmar"
      confirm-class="btn btn-danger"
      @confirm="onConfirm"
    />
  </div>
</template>

<style scoped>
.projects-grid { display: grid; grid-template-columns: 1fr; gap: 12px; }
</style>
