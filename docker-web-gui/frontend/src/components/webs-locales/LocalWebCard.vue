<script setup>
import { computed } from 'vue'

const props = defineProps({
  web: { type: Object, required: true },
  giteaConfigured: { type: Boolean, default: false },
  serverSync: { type: Object, default: null },
  loadingAction: { type: String, default: null },
})

const emit = defineEmits([
  'launch', 'git-init', 'git-push', 'git-pull',
  'server-git-status', 'server-git-setup', 'server-git-push', 'server-git-pull',
  'sync-from', 'sync-to',
  'pull-db', 'migrate', 'open-folder', 'open-vscode',
  'delete', 'show-git-status',
])

const gitConnected = computed(() => props.web.has_git && props.web.git_remote_exists)

const formattedDate = computed(() => {
  if (!props.web.modified) return ''
  return new Date(props.web.modified * 1000).toLocaleDateString('es-ES', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  })
})

const launchDisabled = computed(() => {
  return props.web.running || (props.web.acai && !gitConnected.value) || isLoading.value
})

const launchTitle = computed(() => {
  if (props.web.running) return 'Ya esta en ejecucion'
  if (props.web.acai && !gitConnected.value) return 'Conecta Git primero'
  return ''
})

const gitStatusLabel = computed(() => {
  if (!props.web.has_git) return null
  if (props.web.git_clean) return 'clean'
  return 'dirty'
})

// Is any action loading for this web?
const isLoading = computed(() => !!props.loadingAction)

// Check if a specific action is loading
function isActionLoading(action) {
  return props.loadingAction === action
}

// Sync button logic
const canPull = computed(() => {
  if (!props.serverSync || props.serverSync.error || !props.serverSync.has_remote) return false
  const serverHasChanges = !props.serverSync.clean || props.serverSync.ahead > 0
  const localBehind = props.web.git_behind || 0
  return serverHasChanges || localBehind > 0
})

const canPush = computed(() => {
  if (!props.serverSync || props.serverSync.error || !props.serverSync.has_remote) return false
  const localHasChanges = !props.web.git_clean || props.web.git_ahead > 0
  const serverBehind = props.serverSync.behind || 0
  return localHasChanges || serverBehind > 0
})

const pullLabel = computed(() => {
  if (!props.serverSync || !props.serverSync.has_remote) return 'Traer de Prod'
  const serverHasChanges = !props.serverSync.clean || props.serverSync.ahead > 0
  const localBehind = props.web.git_behind || 0
  if (serverHasChanges && localBehind > 0) return 'Traer de Prod (' + (props.serverSync.ahead + localBehind) + ')'
  if (serverHasChanges) return props.serverSync.ahead > 0 ? 'Traer de Prod (' + props.serverSync.ahead + ')' : 'Traer de Prod (cambios)'
  if (localBehind > 0) return 'Traer de Prod (' + localBehind + ')'
  return 'Traer de Prod'
})

// Git connection badge for acai webs
const gitConnBadge = computed(() => {
  if (!props.giteaConfigured || !props.web.acai) return null
  if (gitConnected.value) return { label: 'Repo conectado', cls: 'badge-git-clean' }
  if (props.web.has_git && !props.web.git_remote_exists) return { label: 'Repo no existe', cls: 'badge-git-dirty' }
  if (!props.web.has_git && props.web.git_remote_exists) return { label: 'Repo disponible', cls: 'badge-accent' }
  return { label: 'Sin repo', cls: 'badge-git-dirty' }
})
</script>

<template>
  <div class="local-web-card">
    <!-- Header -->
    <div class="local-web-header">
      <div class="local-web-name">
        {{ web.name }}
        <span v-if="web.running" class="badge badge-running">Running</span>
        <span v-if="web.acai" class="badge badge-acai">Acai</span>
        <span v-if="gitConnBadge" class="badge" :class="gitConnBadge.cls">{{ gitConnBadge.label }}</span>
      </div>
      <span class="local-web-date">{{ formattedDate }}</span>
    </div>

    <!-- Path -->
    <div class="local-web-path">{{ web.path }}</div>

    <!-- Middle: stats + git -->
    <div class="local-web-middle">
      <div class="local-web-stats">
        <div class="stat-box stat-modules" v-if="web.modules != null">
          <span class="stat-icon">M</span>
          <span class="stat-num">{{ web.modules }}</span>
          <span>modulos</span>
        </div>
        <div class="stat-box stat-hooks" v-if="web.hooks != null">
          <span class="stat-icon">H</span>
          <span class="stat-num">{{ web.hooks }}</span>
          <span>hooks</span>
        </div>
        <div class="stat-box stat-assets" v-if="web.assets != null">
          <span class="stat-icon">A</span>
          <span class="stat-num">{{ web.assets }}</span>
          <span>assets</span>
        </div>
        <div class="stat-box stat-db" v-if="web.has_db">
          <span class="stat-icon">BD</span>
          <span class="stat-num">Si</span>
        </div>
      </div>

      <div class="local-web-git" v-if="web.has_git" @click="emit('show-git-status', web)" style="cursor: pointer;">
        <span v-if="gitStatusLabel === 'clean'" class="badge badge-git-clean">Limpio</span>
        <span v-if="gitStatusLabel === 'dirty'" class="badge badge-git-dirty">
          {{ web.git_changed || 0 }} cambios
        </span>
        <span v-if="web.git_ahead > 0" class="badge badge-queue">{{ web.git_ahead }} por subir</span>
        <span v-if="web.git_behind > 0" class="badge badge-queue">{{ web.git_behind }} por bajar</span>
      </div>
    </div>

    <!-- Sync area -->
    <div v-if="web.acai && gitConnected" class="local-web-sync-area">
      <template v-if="serverSync && !serverSync.error && serverSync.has_remote">
        <div class="sync-status">
          <span v-if="serverSync.ahead > 0" class="badge badge-queue">Servidor {{ serverSync.ahead }} ahead</span>
          <span v-if="serverSync.behind > 0" class="badge badge-queue">Servidor {{ serverSync.behind }} behind</span>
          <span v-if="serverSync.ahead === 0 && serverSync.behind === 0 && (serverSync.clean !== false)" class="badge badge-git-clean">Sincronizado</span>
          <span v-if="serverSync.clean === false && serverSync.ahead === 0" class="badge badge-git-dirty">Servidor con cambios</span>
        </div>
        <div class="sync-buttons">
          <button
            class="btn btn-sm btn-sync-pull"
            :class="{ dimmed: !canPull }"
            :disabled="isLoading || !canPull"
            @click="emit('sync-from', web)"
          >
            <span class="spinner" v-if="isActionLoading('sync-from')"></span>
            {{ isActionLoading('sync-from') ? 'Sincronizando...' : pullLabel }}
          </button>
          <button
            class="btn btn-sm btn-sync-push"
            :class="{ dimmed: !canPush }"
            :disabled="isLoading || !canPush"
            @click="emit('sync-to', web)"
          >
            <span class="spinner" v-if="isActionLoading('sync-to')"></span>
            {{ isActionLoading('sync-to') ? 'Sincronizando...' : 'Subir a Prod' }}
          </button>
          <button v-if="web.has_db && web.running" class="btn btn-sm btn-pull-db" :disabled="isLoading" @click="emit('pull-db', web)">
            <span class="spinner" v-if="isActionLoading('pull-db')"></span>
            {{ isActionLoading('pull-db') ? 'Descargando BD...' : 'Traer BD' }}
          </button>
        </div>
      </template>
      <template v-else-if="serverSync && !serverSync.error && !serverSync.has_remote">
        <button class="btn btn-primary btn-sm" :disabled="isLoading" @click="emit('server-git-setup', web)">
          <span class="spinner" v-if="isActionLoading('server-setup')"></span>
          {{ isActionLoading('server-setup') ? 'Configurando...' : 'Conectar servidor a Gitea' }}
        </button>
      </template>
      <template v-else-if="serverSync && serverSync.error">
        <span style="font-size: 12px; color: var(--red);">Error al consultar servidor</span>
      </template>
      <template v-else>
        <span style="font-size: 12px; color: var(--text3);">Comprobando servidor...</span>
      </template>
    </div>

    <!-- Actions -->
    <div class="local-web-actions">
      <button
        v-if="web.acai"
        class="btn btn-primary btn-sm"
        :disabled="launchDisabled"
        @click="emit('launch', web)"
        :title="launchTitle"
      >
        <span class="spinner" v-if="isActionLoading('launch')"></span>
        {{ isActionLoading('launch') ? 'Levantando...' : 'Levantar' }}
      </button>

      <template v-if="!(web.has_git && web.git_remote_exists) && web.acai && giteaConfigured && !gitConnected">
        <button class="btn btn-secondary btn-sm" :disabled="isLoading" @click="emit('git-init', web)">
          <span class="spinner" v-if="isActionLoading('git-init')"></span>
          {{ isActionLoading('git-init') ? 'Iniciando...' : 'Iniciar Git' }}
        </button>
      </template>

      <template v-if="web.has_git && gitConnected">
        <button class="btn btn-secondary btn-sm" :disabled="isLoading" @click="emit('git-push', web)">
          <span class="spinner" v-if="isActionLoading('git-push')"></span>
          {{ isActionLoading('git-push') ? 'Pushing...' : 'Push' }}
        </button>
        <button class="btn btn-secondary btn-sm" :disabled="isLoading" @click="emit('git-pull', web)">
          <span class="spinner" v-if="isActionLoading('git-pull')"></span>
          {{ isActionLoading('git-pull') ? 'Pulling...' : 'Pull' }}
        </button>
      </template>

      <template v-if="web.acai && giteaConfigured">
        <button class="btn btn-secondary btn-sm" :disabled="isLoading" @click="emit('server-git-status', web)">
          <span class="spinner" v-if="isActionLoading('server-status')"></span>
          {{ isActionLoading('server-status') ? 'Consultando...' : 'Servidor' }}
        </button>
      </template>

      <template v-if="web.needs_migration">
        <button class="btn btn-warning btn-sm" :disabled="isLoading" @click="emit('migrate', web)">
          <span class="spinner" v-if="isActionLoading('migrate')"></span>
          {{ isActionLoading('migrate') ? 'Migrando...' : 'Migrar' }}
        </button>
      </template>

      <span class="local-web-actions-spacer"></span>

      <button v-if="web.running" class="btn btn-ghost btn-sm" @click="emit('open-vscode', web)" title="Abrir en VSCode">VSCode</button>
      <button class="btn btn-ghost btn-sm" @click="emit('open-folder', web)" title="Abrir carpeta">Abrir carpeta</button>
      <button class="btn btn-danger btn-xs" @click="emit('delete', web)" :disabled="web.running || isLoading" :title="web.running ? 'Para el proyecto primero' : ''">Eliminar</button>
    </div>
  </div>
</template>

<style scoped>
.local-web-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 18px 22px;
  display: flex;
  flex-direction: column;
  gap: 14px;
  transition: border-color var(--transition);
}
.local-web-card:hover {
  border-color: var(--border-hover);
}
.local-web-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}
.local-web-name {
  font-weight: 600;
  font-size: 15px;
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
  min-width: 0;
}
.local-web-date {
  font-size: 12px;
  color: var(--text3);
  white-space: nowrap;
  flex-shrink: 0;
}
.local-web-path {
  font-size: 12px;
  color: var(--text3);
  word-break: break-all;
  margin-top: -6px;
}
.local-web-middle {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}
.local-web-stats {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}
.stat-box {
  display: flex;
  align-items: center;
  gap: 6px;
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 5px 10px;
  font-size: 12px;
  color: var(--text2);
}
.stat-icon {
  font-size: 13px;
  opacity: 0.7;
}
.stat-num {
  font-weight: 700;
  font-size: 14px;
  color: var(--text);
}
.stat-modules {
  border-color: rgba(59,130,246,0.3);
  background: rgba(59,130,246,0.06);
}
.stat-hooks {
  border-color: rgba(168,85,247,0.3);
  background: rgba(168,85,247,0.06);
}
.stat-assets {
  border-color: rgba(34,197,94,0.3);
  background: rgba(34,197,94,0.06);
}
.stat-db {
  border-color: rgba(249,115,22,0.3);
  background: rgba(249,115,22,0.06);
}
.local-web-git {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-left: auto;
}
.local-web-actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  align-items: center;
  padding-top: 10px;
  border-top: 1px solid var(--border);
}
.local-web-actions-spacer {
  flex: 1;
}
.local-web-sync-area {
  padding: 8px 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.sync-status {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}
.sync-buttons {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}
.btn-sync-pull {
  background: var(--accent);
  color: #fff;
  border: none;
}
.btn-sync-pull:hover:not(:disabled) {
  filter: brightness(1.1);
}
.btn-sync-push {
  background: #e67e22;
  color: #fff;
  border: none;
}
.btn-sync-push:hover:not(:disabled) {
  filter: brightness(1.1);
}
.btn-pull-db {
  background: #8b5cf6;
  color: #fff;
  border: none;
}
.btn-pull-db:hover {
  background: #7c3aed;
}
.dimmed {
  opacity: 0.5;
}
.badge-accent {
  background: var(--accent-dim);
  color: var(--accent);
}
</style>
