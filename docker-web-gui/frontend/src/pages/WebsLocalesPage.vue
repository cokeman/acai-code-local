<script setup>
import { ref, reactive, onMounted, onUnmounted, computed, nextTick } from 'vue'
import MainHeader from '../components/layout/MainHeader.vue'
import LocalWebCard from '../components/webs-locales/LocalWebCard.vue'
import AcaiModal from '../components/acai/AcaiModal.vue'
import OutputModal from '../components/common/OutputModal.vue'
import ConfirmDialog from '../components/common/ConfirmDialog.vue'
import { useAppStore } from '../stores/app.js'
import { api, apiPost } from '../composables/useApi.js'
import { notifyExtension } from '../composables/useVscodeBridge.js'

const appStore = useAppStore()

// Modal states
const showAcaiModal = ref(false)
const showOutputModal = ref(false)
const outputTitle = ref('Resultado')
const outputContent = ref('')
const showConfirm = ref(false)
const confirmMessage = ref('')
const confirmAction = ref(null)
// Gitea configuration
const giteaConfigured = ref(false)

// Server sync status map: domain -> status data
const serverSyncStatus = reactive(new Map())

// Loading
const loading = ref(false)

// Per-web action loading: Map<webPath, actionName>
const actionLoading = reactive(new Map())

const baseDir = computed(() => appStore.settings.webs_dir || '')

// Load local webs
async function refresh(silent = false) {
  if (!silent) loading.value = true
  try {
    await appStore.refreshLocalWebs()
    await nextTick()
    checkServerSyncStatus()
  } catch {
    // ignore
  } finally {
    if (!silent) loading.value = false
  }
}

// Check Gitea config
async function checkGiteaConfig() {
  try {
    const data = await api('/api/settings')
    giteaConfigured.value = !!(data.gitea_url && data.gitea_username && data.has_gitea_password)
  } catch {
    giteaConfigured.value = false
  }
}

// Check server sync status for all acai webs with git connected
async function checkServerSyncStatus() {
  const webs = appStore.localWebs.filter(w => w.acai && w.has_git && w.git_remote_exists)
  for (const w of webs) {
    try {
      const data = await apiPost('/api/server-git/status', { domain: w.name })
      serverSyncStatus.set(w.name, data)
    } catch {
      serverSyncStatus.set(w.name, { error: true })
    }
  }
}

// Open acai modal listener
function onOpenAcaiModal() {
  showAcaiModal.value = true
}

// Auto-refresh polling (every 10s)
let refreshTimer = null

onMounted(async () => {
  window.addEventListener('open-acai-modal', onOpenAcaiModal)
  await checkGiteaConfig()
  await refresh()
  refreshTimer = setInterval(refresh, 10000)
})

onUnmounted(() => {
  window.removeEventListener('open-acai-modal', onOpenAcaiModal)
  if (refreshTimer) clearInterval(refreshTimer)
})

// Show result in output modal
function showResult(title, data) {
  outputTitle.value = title
  if (typeof data === 'string') {
    outputContent.value = data
  } else if (data.steps && Array.isArray(data.steps)) {
    outputContent.value = data.steps.join('\n')
  } else {
    outputContent.value = data.output || data.error || data.message || JSON.stringify(data, null, 2)
  }
  showOutputModal.value = true
}

// Confirm dialog helper
function requestConfirm(message, action) {
  confirmMessage.value = message
  confirmAction.value = action
  showConfirm.value = true
}

function onConfirm() {
  if (confirmAction.value) confirmAction.value()
  confirmAction.value = null
}

// ---- Action helpers ----

function setLoading(web, action) {
  actionLoading.set(web.path, action)
}
function clearLoading(web) {
  actionLoading.delete(web.path)
}

// ---- Action handlers ----

async function handleLaunch(web) {
  setLoading(web, 'launch')
  try {
    // Check if a DIFFERENT project is running
    const projectsData = await api('/api/projects')
    const projects = projectsData.projects || []
    const otherRunning = projects.find(p => p.status === 'running' && p.project_dir !== web.path)
    if (otherRunning) {
      clearLoading(web)
      requestConfirm(
        `Ya hay un proyecto corriendo: "${otherRunning.name}". ¿Quieres pararlo para levantar el nuevo?`,
        async () => {
          setLoading(web, 'launch')
          try {
            await apiPost('/api/stop', { project_dir: otherRunning.project_dir })
          } catch { /* ignore stop errors */ }
          await doLaunch(web)
        }
      )
      return
    }
    await doLaunch(web)
  } catch (e) {
    showResult('Error', e.message || 'Error al verificar proyectos')
    clearLoading(web)
  }
}

async function doLaunch(web) {
  try {
    const body = {
      project_dir: web.path,
      acai: true,
      redis: true,
    }
    if (web.has_db) {
      body.sql_file = web.path + '/database.sql'
    }
    const data = await apiPost('/api/launch', body)
    showResult('Levantar Web', data)
    await appStore.refreshProjects()
    notifyExtension('refreshTree')
  } catch (e) {
    showResult('Error', e.message || 'Error al levantar')
  } finally {
    clearLoading(web)
  }
}

async function handleGitInit(web) {
  setLoading(web, 'git-init')
  try {
    const data = await apiPost('/api/git/init', { path: web.path, domain: web.name })
    showResult('Git Init', data)
    await refresh()
  } catch (e) {
    showResult('Error', e.message || 'Error en git init')
  } finally {
    clearLoading(web)
  }
}

async function handleGitPush(web) {
  setLoading(web, 'git-push')
  try {
    const data = await apiPost('/api/git/push', { path: web.path })
    showResult('Git Push', data)
    await refresh()
  } catch (e) {
    showResult('Error', e.message || 'Error en git push')
  } finally {
    clearLoading(web)
  }
}

async function handleGitPull(web) {
  setLoading(web, 'git-pull')
  try {
    const data = await apiPost('/api/git/pull', { path: web.path })
    showResult('Git Pull', data)
    await refresh()
  } catch (e) {
    showResult('Error', e.message || 'Error en git pull')
  } finally {
    clearLoading(web)
  }
}

async function handleServerGitStatus(web) {
  setLoading(web, 'server-status')
  try {
    const data = await apiPost('/api/server-git/status', { domain: web.name })
    if (data.error) {
      showResult('Git Servidor: ' + web.name, 'Error: ' + (data.error.message || data.error))
      return
    }
    if (!data.has_git) {
      showResult('Git Servidor: ' + web.name, 'El servidor no tiene git configurado.\nUsa el boton "Conectar servidor a Gitea" en la tarjeta de la web.')
      return
    }
    let text = 'Rama: ' + data.branch + '\n'
    text += 'Estado: ' + (data.clean ? 'Limpio' : (data.changed || 0) + ' cambio(s)') + '\n'
    if (data.ahead > 0) text += 'Commits por subir: ' + data.ahead + '\n'
    if (data.behind > 0) text += 'Commits por bajar: ' + data.behind + '\n'
    if (data.last_commits && data.last_commits.length > 0) {
      text += '\n--- Ultimos commits ---\n'
      data.last_commits.forEach(c => { text += (c.date || '') + ' ' + (c.message || '') + '\n' })
    }
    if (!data.has_remote) {
      text += '\nEl servidor no tiene remote de Gitea configurado.'
    }
    showResult('Git Servidor: ' + web.name, text)
  } catch (e) {
    showResult('Error', e.message || 'Error al consultar servidor')
  } finally {
    clearLoading(web)
  }
}

async function handleSyncFrom(web) {
  setLoading(web, 'sync-from')
  try {
    // Step 1: push server to gitea
    const pushData = await apiPost('/api/server-git/push', { domain: web.name })
    if (pushData.error) {
      showResult('Traer de produccion', 'Error en push del servidor:\n' + (pushData.error.message || pushData.error))
      return
    }
    // Step 2: pull from gitea to local
    const pullData = await apiPost('/api/git/pull', { path: web.path })
    let result = 'Servidor → Gitea: OK\n'
    if (pushData.commit) result += pushData.commit + '\n'
    result += '\nGitea → Local: ' + (pullData.success ? 'OK' : 'Error') + '\n'
    if (pullData.output) result += pullData.output
    showResult('Traer de produccion', result)
    await refresh()
  } catch (e) {
    showResult('Error', e.message || 'Error en sync')
  } finally {
    clearLoading(web)
  }
}

async function handleSyncTo(web) {
  setLoading(web, 'sync-to')
  try {
    // Step 1: push local to gitea
    const pushData = await apiPost('/api/git/push', { path: web.path })
    if (pushData.error) {
      showResult('Subir a produccion', 'Error en push local:\n' + (pushData.error.message || pushData.error))
      return
    }
    // Step 2: pull from gitea to server
    const pullData = await apiPost('/api/server-git/pull', { domain: web.name })
    let result = 'Local → Gitea: OK\n'
    if (pushData.output) result += pushData.output + '\n'
    result += '\nGitea → Servidor: ' + (pullData.error ? 'Error' : 'OK') + '\n'
    if (pullData.output) result += pullData.output
    showResult('Subir a produccion', result)
    await refresh()
  } catch (e) {
    showResult('Error', e.message || 'Error en sync')
  } finally {
    clearLoading(web)
  }
}

async function handlePullDb(web) {
  requestConfirm(
    'Esto reemplazara la base de datos local con la de produccion. ¿Continuar?',
    async () => {
      setLoading(web, 'pull-db')
      try {
        const data = await apiPost('/api/pull-database', { path: web.path })
        if (data.success) {
          let text = 'Base de datos importada correctamente!\n\n' + (data.steps || []).join('\n')
          if (data.errors && data.errors.length) text += '\n\nAvisos:\n' + data.errors.join('\n')
          showResult('Traer BD', text)
        } else {
          showResult('Traer BD', 'Error: ' + (data.error || 'desconocido') + '\n\n' + (data.steps || []).join('\n'))
        }
      } catch (e) {
        showResult('Error', e.message || 'Error al traer BD')
      } finally {
        clearLoading(web)
      }
    }
  )
}

async function handleServerGitSetup(web) {
  setLoading(web, 'server-setup')
  try {
    const data = await apiPost('/api/server-git/setup', { domain: web.name })
    showResult('Setup Git Servidor', data)
    await refresh()
  } catch (e) {
    showResult('Error', e.message || 'Error en setup git servidor')
  } finally {
    clearLoading(web)
  }
}

async function handleServerGitPush(web) {
  setLoading(web, 'server-push')
  try {
    const data = await apiPost('/api/server-git/push', { domain: web.name })
    showResult('Push Servidor', data)
    await refresh()
  } catch (e) {
    showResult('Error', e.message || 'Error en push servidor')
  } finally {
    clearLoading(web)
  }
}

async function handleServerGitPull(web) {
  setLoading(web, 'server-pull')
  try {
    const data = await apiPost('/api/server-git/pull', { domain: web.name })
    showResult('Pull Servidor', data)
    await refresh()
  } catch (e) {
    showResult('Error', e.message || 'Error en pull servidor')
  } finally {
    clearLoading(web)
  }
}

async function handleMigrate(web) {
  setLoading(web, 'migrate')
  try {
    const data = await apiPost('/api/local-webs/migrate', { path: web.path })
    showResult('Migrar', data)
    await refresh()
  } catch (e) {
    showResult('Error', e.message || 'Error en migracion')
  } finally {
    clearLoading(web)
  }
}

async function handleOpenFolder(web) {
  try {
    await apiPost('/api/local-webs/open', { path: web.path })
  } catch {
    // ignore
  }
}

async function handleOpenVscode(web) {
  try {
    await apiPost('/api/local-webs/vscode', { path: web.path })
  } catch {
    // ignore
  }
}

function handleDelete(web) {
  requestConfirm(
    `Seguro que quieres eliminar "${web.name}"? Esta accion no se puede deshacer.`,
    async () => {
      try {
        const data = await apiPost('/api/local-webs/delete', { path: web.path })
        showResult('Eliminar', data)
        await refresh()
      } catch (e) {
        showResult('Error', e.message || 'Error al eliminar')
      }
    }
  )
}

async function handleShowGitStatus(web) {
  try {
    const data = await api(`/api/git/status?path=${encodeURIComponent(web.path)}`)
    if (!data.has_git) {
      showResult('Estado Git', 'No es un repositorio git.')
      return
    }
    let text = 'Rama: ' + data.branch + '\n'
    text += 'Estado: ' + (data.clean ? 'Limpio (sin cambios)' : (data.changed_files?.length || 0) + ' archivo(s) modificado(s)') + '\n'
    if (data.ahead > 0) text += 'Commits por subir: ' + data.ahead + '\n'
    if (data.behind > 0) text += 'Commits por bajar: ' + data.behind + '\n'
    if (data.changed_files && data.changed_files.length > 0) {
      text += '\n--- Archivos modificados ---\n'
      data.changed_files.forEach(f => { text += f + '\n' })
    }
    if (data.last_commits && data.last_commits.length > 0) {
      text += '\n--- Ultimos commits ---\n'
      data.last_commits.forEach(c => { text += c + '\n' })
    }
    showResult('Estado Git', text)
  } catch (e) {
    showResult('Error', e.message || 'Error al obtener estado git')
  }
}
</script>

<template>
  <div>
    <MainHeader title="Acai Pulls">
      <button class="btn btn-secondary btn-sm" @click="refresh" :disabled="loading">
        <span class="spinner" v-if="loading"></span>
        Refrescar
      </button>
    </MainHeader>

    <div class="main-content">
      <!-- Acai connection bar -->
      <div class="acai-pull-bar">
        <span>Traer web desde Acai Code</span>
        <button class="btn btn-primary btn-sm" @click="showAcaiModal = true">Traer Web</button>
      </div>

      <!-- Base dir label -->
      <div v-if="baseDir" class="base-dir-label">
        Directorio base: <span>{{ baseDir }}</span>
      </div>

      <!-- Loading -->
      <div v-if="loading && appStore.localWebs.length === 0" class="empty-state">
        <span class="spinner"></span>
      </div>

      <!-- Empty state -->
      <div v-else-if="appStore.localWebs.length === 0" class="empty-state">
        No hay webs locales. Usa "Traer Web" para descargar una desde Acai Code.
      </div>

      <!-- Web cards list -->
      <div v-else class="local-webs-list">
        <LocalWebCard
          v-for="web in appStore.localWebs"
          :key="web.path"
          :web="web"
          :gitea-configured="giteaConfigured"
          :server-sync="serverSyncStatus.get(web.name) || null"
          :loading-action="actionLoading.get(web.path) || null"
          @launch="handleLaunch"
          @git-init="handleGitInit"
          @git-push="handleGitPush"
          @git-pull="handleGitPull"
          @server-git-status="handleServerGitStatus"
          @server-git-setup="handleServerGitSetup"
          @server-git-push="handleServerGitPush"
          @server-git-pull="handleServerGitPull"
          @sync-from="handleSyncFrom"
          @sync-to="handleSyncTo"
          @pull-db="handlePullDb"
          @migrate="handleMigrate"
          @open-folder="handleOpenFolder"
          @open-vscode="handleOpenVscode"
          @delete="handleDelete"
          @show-git-status="handleShowGitStatus"
        />
      </div>
    </div>

    <!-- Modals -->
    <AcaiModal v-model="showAcaiModal" />

    <OutputModal
      v-model="showOutputModal"
      :title="outputTitle"
      :content="outputContent"
      @close="refresh"
    />

    <ConfirmDialog
      v-model="showConfirm"
      title="Confirmar"
      :message="confirmMessage"
      confirm-label="Confirmar"
      confirm-class="btn btn-danger"
      @confirm="onConfirm"
    />
  </div>
</template>

<style scoped>
.acai-pull-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 14px 18px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  margin-bottom: 16px;
  font-size: 14px;
  font-weight: 500;
}
.base-dir-label {
  font-size: 12px;
  color: var(--text3);
  margin-bottom: 18px;
}
.base-dir-label span {
  color: var(--text2);
  font-family: 'SF Mono', 'Fira Code', monospace;
}
.local-webs-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
</style>
