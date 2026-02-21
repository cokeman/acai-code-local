<script setup>
import { computed } from 'vue'
import { apiPost } from '../../composables/useApi.js'
import ProjectInfoRows from './ProjectInfoRows.vue'

const props = defineProps({
  project: { type: Object, required: true },
  loadingAction: { type: String, default: null },
})

const emit = defineEmits(['show-logs', 'stop', 'destroy', 'refresh'])

const isLoading = computed(() => !!props.loadingAction)
function isActionLoading(action) { return props.loadingAction === action }

const webContainerName = computed(() => {
  const c = props.project.containers?.find(c => c.name.endsWith('-web'))
  return c ? c.name : ''
})

function openTunnel() {
  window.open(`https://${props.project.tunnel_url}?pruebas=1`, '_blank')
}

async function openVSCode() {
  await apiPost('/api/local-webs/vscode', { path: props.project.project_dir })
}

function openCmsAdmin() {
  const p = props.project
  const params = new URLSearchParams({
    ACAI_TOKEN: p.acai_token || '',
    localPrimaryDomain: p.acai_domain,
    localOverrideDomain: p.tunnel_url,
    localDbUser: p.db_user || '',
    localDbPass: p.db_pass || '',
    localDbName: p.db_name || '',
  })
  if (p.bore_db_url) {
    const parts = p.bore_db_url.split(':')
    params.set('localDbHost', parts[0])
    if (parts[1]) params.set('localDbPort', parts[1])
  }
  window.open(`https://cms.cocosolution.com/admin.php?${params.toString()}`, '_blank')
}
</script>

<template>
  <div class="project-card">
    <!-- Header -->
    <div class="project-header">
      <div class="project-header-left">
        <span class="project-name">{{ project.name }}</span>
        <span
          class="badge"
          :class="project.status === 'running' ? 'badge-running' : 'badge-stopped'"
        >{{ project.status === 'running' ? 'Running' : 'Stopped' }}</span>
        <span v-if="project.acai" class="badge-acai">Acai</span>
      </div>
      <div class="project-header-actions">
        <button
          v-if="webContainerName"
          class="btn btn-secondary btn-sm"
          @click="emit('show-logs', webContainerName)"
        >Logs</button>
        <button
          class="btn btn-warning btn-sm"
          :disabled="isLoading"
          @click="emit('stop', project)"
        >
          <span class="spinner" v-if="isActionLoading('stop')"></span>
          {{ isActionLoading('stop') ? 'Parando...' : 'Parar' }}
        </button>
        <button
          class="btn btn-danger btn-sm"
          :disabled="isLoading"
          @click="emit('destroy', project)"
        >
          <span class="spinner" v-if="isActionLoading('destroy')"></span>
          {{ isActionLoading('destroy') ? 'Destruyendo...' : 'Destruir' }}
        </button>
      </div>
    </div>

    <!-- Info rows -->
    <ProjectInfoRows :project="project" />

    <!-- Footer -->
    <div class="project-footer">
      <button
        v-if="project.tunnel_url"
        class="btn btn-primary btn-sm btn-icon"
        @click="openTunnel"
      >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/><path d="M12 12m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"/><path d="M12 9h8.4"/><path d="M14.598 13.5l-4.2 7.275"/><path d="M9.402 13.5l-4.2 -7.275"/></svg>
        Abrir
      </button>
      <button
        v-if="project.project_dir"
        class="btn btn-secondary btn-sm btn-icon"
        @click="openVSCode"
      >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 3v18l4 -2.5v-13z"/><path d="M9.165 13.903l-4.165 3.597l-2 -1l4.333 -4.5m1.735 -1.802l6.932 -7.198v5l-4.795 4.141"/><path d="M16 16.5l-11 -10l-2 1l13 13.5"/></svg>
        VSCode
      </button>
      <button
        v-if="project.tunnel_url && project.acai_domain"
        class="btn btn-secondary btn-sm btn-icon"
        @click="openCmsAdmin"
      >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 5m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z"/><path d="M7 15v-4a2 2 0 0 1 4 0v4"/><path d="M7 13l4 0"/><path d="M17 9v6h-1.5a1.5 1.5 0 1 1 1.5 -1.5"/></svg>
        CMS
      </button>
    </div>
  </div>
</template>

<style scoped>
.projects-grid { display: grid; grid-template-columns: 1fr; gap: 12px; }
.project-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 20px 24px; display: flex; flex-direction: column; gap: 14px; transition: border-color var(--transition); }
.project-card:hover { border-color: var(--border-hover); }
.project-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
.project-header-left { display: flex; align-items: center; gap: 8px; min-width: 0; }
.project-name { font-weight: 600; font-size: 15px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.project-header-actions { display: flex; gap: 6px; flex-shrink: 0; }
.project-footer { display: flex; gap: 6px; justify-content: flex-end; }
</style>
