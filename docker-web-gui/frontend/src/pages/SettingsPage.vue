<script setup>
import { ref, onMounted } from 'vue'
import MainHeader from '../components/layout/MainHeader.vue'
import PathSelector from '../components/common/PathSelector.vue'
import ToggleSwitch from '../components/common/ToggleSwitch.vue'
import FileBrowser from '../components/browser/FileBrowser.vue'
import { useAppStore } from '../stores/app.js'
import { api, apiPost } from '../composables/useApi.js'
import { applyTheme } from '../composables/useTheme.js'

const appStore = useAppStore()

// General
const websDir = ref('')
const refreshInterval = ref(15)
const darkTheme = ref(true)
const dockerShPath = ref('')
const webBasePath = ref('')

// Acai
const acaiUser = ref('')
const acaiPass = ref('')
const mysqlPass = ref('')
const hasAcaiPassword = ref(false)
const hasMysqlPassword = ref(false)

// Gitea
const giteaUrl = ref('')
const giteaOrg = ref('acai')
const giteaUser = ref('')
const giteaToken = ref('')
const giteaTestResult = ref('')
const hasGiteaPassword = ref(false)

// File browsers
const showBrowserWebsDir = ref(false)
const showBrowserWebBase = ref(false)
const showBrowserDockerSh = ref(false)

// Flash messages
const savedGeneral = ref(false)
const savedAcai = ref(false)
const savedGitea = ref(false)

onMounted(async () => {
  try {
    const data = await api('/api/settings')
    websDir.value = data.webs_dir || ''
    refreshInterval.value = data.refresh_interval || 15
    darkTheme.value = (data.theme || 'dark') === 'dark'
    dockerShPath.value = data.docker_web_sh || ''
    webBasePath.value = data.web_base_dir || ''
    acaiUser.value = data.acai_username || ''
    hasAcaiPassword.value = !!data.has_acai_password
    hasMysqlPassword.value = !!data.has_mysql_password
    giteaUrl.value = data.gitea_url || ''
    giteaOrg.value = data.gitea_org || 'acai'
    giteaUser.value = data.gitea_username || ''
    hasGiteaPassword.value = !!data.has_gitea_password
  } catch { /* ignore */ }
})

function onThemeChange(val) {
  darkTheme.value = val
  const theme = val ? 'dark' : 'light'
  applyTheme(theme)
  appStore.settings.theme = theme
}

async function saveGeneral() {
  await apiPost('/api/settings', {
    webs_dir: websDir.value,
    refresh_interval: refreshInterval.value,
    theme: darkTheme.value ? 'dark' : 'light',
    docker_web_sh: dockerShPath.value,
    web_base_dir: webBasePath.value,
  })
  savedGeneral.value = true
  setTimeout(() => { savedGeneral.value = false }, 2000)
}

async function saveAcai() {
  const body = { acai_username: acaiUser.value }
  if (acaiPass.value) body.acai_password = acaiPass.value
  if (mysqlPass.value) body.mysql_password = mysqlPass.value
  await apiPost('/api/settings', body)
  acaiPass.value = ''
  mysqlPass.value = ''
  savedAcai.value = true
  setTimeout(() => { savedAcai.value = false }, 2000)
}

async function testGitea() {
  giteaTestResult.value = ''
  try {
    const data = await apiPost('/api/gitea/test', {
      gitea_url: giteaUrl.value,
      gitea_username: giteaUser.value,
      gitea_password: giteaToken.value,
    })
    giteaTestResult.value = data.ok ? 'Conexion OK' : (data.error || 'Error')
  } catch {
    giteaTestResult.value = 'Error de conexion'
  }
}

async function saveGitea() {
  const body = {
    gitea_url: giteaUrl.value,
    gitea_org: giteaOrg.value || 'acai',
    gitea_username: giteaUser.value,
  }
  if (giteaToken.value) body.gitea_password = giteaToken.value
  await apiPost('/api/settings', body)
  giteaToken.value = ''
  savedGitea.value = true
  setTimeout(() => { savedGitea.value = false }, 2000)
}
</script>

<template>
  <div>
    <MainHeader title="Ajustes" />
    <div class="main-content">

      <!-- General -->
      <div class="card">
        <div class="card-header">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          General
        </div>
        <div class="card-body">
          <div class="form-row full">
            <div class="form-group">
              <label>Directorio base de webs</label>
              <PathSelector v-model="websDir" placeholder="/ruta/a/webs" @browse="showBrowserWebsDir = true" />
              <FileBrowser
                v-model="showBrowserWebsDir"
                mode="dir"
                title="Seleccionar directorio de webs"
                :start-path="websDir || '/'"
                @select="websDir = $event"
              />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Intervalo de refresco (seg)</label>
              <input type="number" v-model.number="refreshInterval" min="1" style="max-width: 120px;">
            </div>
            <div class="form-group">
              <label>Tema</label>
              <ToggleSwitch :model-value="darkTheme" @update:model-value="onThemeChange" :label="darkTheme ? 'Oscuro' : 'Claro'" />
            </div>
          </div>
          <div class="form-row full">
            <div class="form-group">
              <label>Ruta docker-web.sh</label>
              <PathSelector v-model="dockerShPath" placeholder="/ruta/docker-web.sh" @browse="showBrowserDockerSh = true" />
              <FileBrowser
                v-model="showBrowserDockerSh"
                mode="file"
                ext="sh"
                title="Seleccionar docker-web.sh"
                :start-path="dockerShPath || '/'"
                @select="dockerShPath = $event"
              />
            </div>
          </div>
          <div class="form-row full">
            <div class="form-group">
              <label>Directorio web-base</label>
              <PathSelector v-model="webBasePath" placeholder="/ruta/web-base" @browse="showBrowserWebBase = true" />
              <FileBrowser
                v-model="showBrowserWebBase"
                mode="dir"
                title="Seleccionar directorio web-base"
                :start-path="webBasePath || '/'"
                @select="webBasePath = $event"
              />
            </div>
          </div>
          <div class="actions-row">
            <button class="btn btn-primary" @click="saveGeneral">Guardar</button>
            <span v-if="savedGeneral" style="color: var(--green); font-size: 13px;">Guardado</span>
          </div>
        </div>
      </div>

      <!-- Acai Code -->
      <div class="card">
        <div class="card-header">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          Acai Code
        </div>
        <div class="card-body">
          <div class="form-row full">
            <div class="form-group">
              <label>Usuario Acai</label>
              <input type="text" v-model="acaiUser" placeholder="usuario">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Contrasena Acai</label>
              <input type="password" v-model="acaiPass" :placeholder="hasAcaiPassword ? '(guardada)' : 'contrasena'">
            </div>
            <div class="form-group">
              <label>Clave MySQL</label>
              <input type="password" v-model="mysqlPass" :placeholder="hasMysqlPassword ? '(guardada)' : 'clave mysql'">
            </div>
          </div>
          <div class="actions-row">
            <button class="btn btn-primary" @click="saveAcai">Guardar</button>
            <span v-if="savedAcai" style="color: var(--green); font-size: 13px;">Guardado</span>
          </div>
          <p style="color: var(--text3); font-size: 12px; margin-top: 12px;">
            Las contrasenas se guardan en el Keychain de macOS
          </p>
        </div>
      </div>

      <!-- Git Sync (Gitea) -->
      <div class="card">
        <div class="card-header">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="18" r="3"/><circle cx="6" cy="6" r="3"/><path d="M13 6h3a2 2 0 0 1 2 2v7"/><path d="M6 9v12"/></svg>
          Git Sync (Gitea)
        </div>
        <div class="card-body">
          <div class="form-row full">
            <div class="form-group">
              <label>URL Gitea</label>
              <input type="text" v-model="giteaUrl" placeholder="https://gitea.ejemplo.com">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Organizacion</label>
              <input type="text" v-model="giteaOrg" placeholder="organizacion">
            </div>
            <div class="form-group">
              <label>Usuario</label>
              <input type="text" v-model="giteaUser" placeholder="usuario">
            </div>
          </div>
          <div class="form-row full">
            <div class="form-group">
              <label>Token / Contrasena Gitea</label>
              <input type="password" v-model="giteaToken" :placeholder="hasGiteaPassword ? 'Token guardado (dejar vacio para mantener)' : 'Sin token guardado'">
            </div>
          </div>
          <div class="actions-row">
            <button class="btn btn-secondary" @click="testGitea">Probar conexion</button>
            <button class="btn btn-primary" @click="saveGitea">Guardar</button>
            <span v-if="savedGitea" style="color: var(--green); font-size: 13px;">Guardado</span>
            <span v-if="giteaTestResult" :style="{ color: giteaTestResult.includes('OK') ? 'var(--green)' : 'var(--red)', fontSize: '13px' }">
              {{ giteaTestResult }}
            </span>
          </div>
          <p style="color: var(--text3); font-size: 12px; margin-top: 12px;">
            Configura Gitea para sincronizar webs via Git.
          </p>
        </div>
      </div>

    </div>
  </div>
</template>
