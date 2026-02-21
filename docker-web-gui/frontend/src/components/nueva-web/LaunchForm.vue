<script setup>
import { ref } from 'vue'
import { api, apiPost } from '../../composables/useApi.js'
import PathSelector from '../common/PathSelector.vue'
import ToggleSwitch from '../common/ToggleSwitch.vue'
import FileBrowser from '../browser/FileBrowser.vue'
import ConfirmDialog from '../common/ConfirmDialog.vue'

const emit = defineEmits(['refresh'])

// Form state
const projectDir = ref('')
const sqlTab = ref('local')
const sqlFile = ref('')
const remoteHost = ref('')
const remotePort = ref('3306')
const remoteUser = ref('')
const remotePass = ref('')
const remoteDbname = ref('')
const redis = ref(false)
const rebuild = ref(false)

// UI state
const launching = ref(false)
const output = ref('')
const showOutput = ref(false)
const showDirBrowser = ref(false)
const showFileBrowser = ref(false)

// Confirm stop dialog
const showConfirmStop = ref(false)
const runningProject = ref(null)

function onDirSelected(path) {
  projectDir.value = path
}

function onSqlFileSelected(path) {
  sqlFile.value = path
}

async function launch() {
  if (!projectDir.value) return

  // Check for running projects
  try {
    const data = await api('/api/projects')
    const running = (data.projects || []).find(p => p.status === 'running')
    if (running) {
      runningProject.value = running
      showConfirmStop.value = true
      return
    }
  } catch {
    // continue with launch
  }

  await doLaunch()
}

async function onConfirmStop() {
  if (runningProject.value) {
    try {
      await apiPost('/api/stop', { project_dir: runningProject.value.project_dir })
    } catch {
      // ignore stop errors
    }
  }
  await doLaunch()
}

async function doLaunch() {
  launching.value = true
  output.value = ''
  showOutput.value = true

  const body = {
    project_dir: projectDir.value,
    redis: redis.value,
    rebuild: rebuild.value,
  }

  if (sqlTab.value === 'local' && sqlFile.value) {
    body.sql_file = sqlFile.value
  } else if (sqlTab.value === 'remote') {
    body.remote_host = remoteHost.value
    body.remote_port = remotePort.value
    body.remote_user = remoteUser.value
    body.remote_pass = remotePass.value
    body.remote_dbname = remoteDbname.value
  }

  try {
    const data = await apiPost('/api/launch', body)
    output.value = data.output || data.message || JSON.stringify(data, null, 2)
    emit('refresh')
  } catch (e) {
    output.value = 'Error: ' + (e.message || 'Error desconocido')
  } finally {
    launching.value = false
  }
}
</script>

<template>
  <div class="main-content">
    <div class="card">
      <div class="card-header">Directorio del proyecto</div>
      <div class="card-body">
        <PathSelector
          v-model="projectDir"
          placeholder="/ruta/al/proyecto"
          @browse="showDirBrowser = true"
        />
      </div>
    </div>

    <div class="card">
      <div class="card-header">Base de datos SQL</div>
      <div class="card-body">
        <div class="tabs">
          <div class="tab" :class="{ active: sqlTab === 'local' }" @click="sqlTab = 'local'">SQL local</div>
          <div class="tab" :class="{ active: sqlTab === 'remote' }" @click="sqlTab = 'remote'">Servidor remoto</div>
          <div class="tab" :class="{ active: sqlTab === 'none' }" @click="sqlTab = 'none'">Sin SQL</div>
        </div>

        <div v-if="sqlTab === 'local'">
          <PathSelector
            v-model="sqlFile"
            placeholder="Seleccionar archivo .sql"
            @browse="showFileBrowser = true"
          />
        </div>

        <div v-else-if="sqlTab === 'remote'">
          <div class="form-row">
            <div class="form-group">
              <label>Host</label>
              <input type="text" v-model="remoteHost" placeholder="ejemplo.com" />
            </div>
            <div class="form-group">
              <label>Puerto</label>
              <input type="text" v-model="remotePort" placeholder="3306" />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Usuario</label>
              <input type="text" v-model="remoteUser" placeholder="root" />
            </div>
            <div class="form-group">
              <label>Password</label>
              <input type="password" v-model="remotePass" placeholder="********" />
            </div>
          </div>
          <div class="form-row full">
            <div class="form-group">
              <label>Base de datos</label>
              <input type="text" v-model="remoteDbname" placeholder="nombre_bd" />
            </div>
          </div>
        </div>

        <div v-else>
          <p style="color: var(--text3); font-size: 13px;">No se importara ninguna base de datos.</p>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">Opciones</div>
      <div class="card-body">
        <ToggleSwitch v-model="redis" label="Habilitar Redis" />
        <ToggleSwitch v-model="rebuild" label="Forzar rebuild (docker-compose build)" />
      </div>
    </div>

    <div class="actions-row">
      <button class="btn btn-primary" :disabled="!projectDir || launching" @click="launch">
        <span v-if="launching" class="spinner"></span>
        {{ launching ? 'Lanzando...' : 'Lanzar proyecto' }}
      </button>
    </div>

    <div class="output-panel" :class="{ visible: showOutput }">
      <div class="card">
        <div class="card-header">Salida</div>
        <div class="card-body">
          <div class="log-output">{{ output || 'Esperando resultado...' }}</div>
        </div>
      </div>
    </div>

    <!-- File browsers -->
    <FileBrowser
      v-model="showDirBrowser"
      mode="dir"
      title="Seleccionar directorio del proyecto"
      start-path="/"
      @select="onDirSelected"
    />
    <FileBrowser
      v-model="showFileBrowser"
      mode="file"
      title="Seleccionar archivo SQL"
      start-path="/"
      ext="sql"
      @select="onSqlFileSelected"
    />

    <!-- Confirm stop running project -->
    <ConfirmDialog
      v-model="showConfirmStop"
      title="Proyecto en ejecucion"
      :message="`El proyecto '${runningProject?.name || ''}' esta corriendo. ¿Deseas detenerlo y lanzar el nuevo?`"
      confirm-label="Detener y lanzar"
      confirm-class="btn btn-warning"
      @confirm="onConfirmStop"
    />
  </div>
</template>
