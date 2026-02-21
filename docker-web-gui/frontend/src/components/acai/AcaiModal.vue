<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import BaseModal from '../common/BaseModal.vue'
import ToggleSwitch from '../common/ToggleSwitch.vue'
import { useAcaiStore } from '../../stores/acai.js'
import { useAppStore } from '../../stores/app.js'
import { api, apiPost } from '../../composables/useApi.js'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue'])

const acaiStore = useAcaiStore()
const appStore = useAppStore()

// Step control
const step = ref(1)

// Step 1 state
const username = ref('')
const password = ref('')
const loginLoading = ref(false)
const loginError = ref('')

// Step 2 state
const searchQuery = ref('')
const domainLoading = ref(false)
const domainError = ref('')

// Step 3 state
const destDir = ref('')
const includeUploads = ref(false)
const mysqlPassword = ref('')
const mysqlPasswordError = ref(false)
const pullLoading = ref(false)
const pullError = ref('')
const pullSuccess = ref(false)
const pullOutput = ref('')

const modalTitle = computed(() => {
  if (step.value === 1) return 'Conectar a Acai'
  if (step.value === 2) return 'Seleccionar Web'
  return 'Traer Web'
})

const filteredDomains = computed(() => {
  if (!searchQuery.value) return acaiStore.domains
  const q = searchQuery.value.toLowerCase()
  return acaiStore.domains.filter(d =>
    (d.domain || '').toLowerCase().includes(q) ||
    (d.name || '').toLowerCase().includes(q)
  )
})

// Pre-fill credentials from saved settings
async function prefillPassword() {
  try {
    const settings = await api('/api/settings')
    if (settings.acai_username) username.value = settings.acai_username
    const passwords = await api('/api/settings/passwords')
    if (passwords.acai_password) password.value = passwords.acai_password
    if (passwords.mysql_password) mysqlPassword.value = passwords.mysql_password
  } catch {
    // ignore
  }
}

watch(() => props.modelValue, async (open) => {
  if (open) {
    loginError.value = ''
    domainError.value = ''
    pullError.value = ''
    pullSuccess.value = false
    pullOutput.value = ''

    if (acaiStore.isConnected) {
      step.value = 3
    } else if (acaiStore.isLoggedIn) {
      step.value = 2
    } else {
      step.value = 1
      await prefillPassword()
    }
  }
})

// Step 1: Login
async function doLogin() {
  if (!username.value || !password.value) return
  loginLoading.value = true
  loginError.value = ''
  try {
    await acaiStore.login(username.value, password.value)
    step.value = 2
  } catch (e) {
    loginError.value = e.message || 'Error de conexion'
  } finally {
    loginLoading.value = false
  }
}

// Step 2: Select domain
async function doSelectDomain(domainNum) {
  domainLoading.value = true
  domainError.value = ''
  try {
    await acaiStore.selectDomain(domainNum)
    step.value = 3
  } catch (e) {
    domainError.value = e.message || 'Error al seleccionar dominio'
  } finally {
    domainLoading.value = false
  }
}

// Logout
function doLogout() {
  acaiStore.logout()
  step.value = 1
  username.value = ''
  password.value = ''
  searchQuery.value = ''
  prefillPassword()
}

// Back to domains
function backToDomains() {
  acaiStore.backToDomains()
  step.value = 2
  pullError.value = ''
  pullSuccess.value = false
  pullOutput.value = ''
}

// Step 3: Pull web
async function doPullWeb() {
  if (!mysqlPassword.value) {
    mysqlPasswordError.value = true
    setTimeout(() => { mysqlPasswordError.value = false }, 2000)
    return
  }
  pullLoading.value = true
  pullError.value = ''
  pullSuccess.value = false
  pullOutput.value = ''
  try {
    const data = await acaiStore.pullWeb({
      dest_dir: destDir.value || undefined,
      include_uploads: includeUploads.value,
      db_password: mysqlPassword.value || undefined,
      db_host: acaiStore.domain?.mysql_host || '',
      db_user: acaiStore.domain?.mysql_user || '',
      db_name: acaiStore.domain?.mysql_db || '',
    })
    if (data.success) {
      pullSuccess.value = true
      pullOutput.value = data.output || 'Web traida correctamente'
      await appStore.refreshLocalWebs()
      setTimeout(() => {
        acaiStore.logout()
        close()
      }, 2500)
    } else {
      pullError.value = data.error || 'Error al traer la web'
      pullOutput.value = data.output || ''
    }
  } catch (e) {
    pullError.value = e.message || 'Error de conexion'
  } finally {
    pullLoading.value = false
  }
}

function close() {
  emit('update:modelValue', false)
}
</script>

<template>
  <BaseModal
    :model-value="modelValue"
    @update:model-value="emit('update:modelValue', $event)"
    :title="modalTitle"
    max-width="700px"
  >
    <!-- Step 1: Login -->
    <div v-if="step === 1">
      <div class="form-row full">
        <div class="form-group">
          <label>Usuario</label>
          <input
            type="text"
            v-model="username"
            placeholder="Usuario de Acai Code"
            @keydown.enter="doLogin"
          >
        </div>
      </div>
      <div class="form-row full">
        <div class="form-group">
          <label>Contrasena</label>
          <input
            type="password"
            v-model="password"
            placeholder="Contrasena"
            @keydown.enter="doLogin"
          >
        </div>
      </div>
      <div style="display: flex; justify-content: flex-end; margin-top: 8px;">
        <button class="btn btn-primary" :disabled="loginLoading || !username || !password" @click="doLogin">
          <span class="spinner" v-if="loginLoading"></span>
          Conectar
        </button>
      </div>
      <div class="acai-status error" v-if="loginError">{{ loginError }}</div>
      <div class="acai-status loading" v-if="loginLoading"><span class="spinner"></span> Conectando...</div>
    </div>

    <!-- Step 2: Domains -->
    <div v-if="step === 2">
      <div class="acai-logged-bar">
        <span>Conectado como <span class="acai-user">{{ acaiStore.username }}</span></span>
        <button class="btn btn-secondary btn-sm" @click="doLogout">Desconectar</button>
      </div>

      <div class="form-row full" style="margin-bottom: 14px;">
        <div class="form-group">
          <input
            type="text"
            v-model="searchQuery"
            placeholder="Buscar dominio..."
          >
        </div>
      </div>

      <div v-if="domainLoading" style="text-align: center; padding: 24px;">
        <span class="spinner"></span>
      </div>

      <div v-else class="acai-domains-grid">
        <div
          v-for="d in filteredDomains"
          :key="d.num"
          class="domain-card"
          @click="doSelectDomain(d.num)"
        >
          <div class="domain-name">{{ d.domain }}</div>
          <div class="domain-meta" v-if="d.name">{{ d.name }}</div>
        </div>
      </div>

      <div v-if="!domainLoading && filteredDomains.length === 0" class="empty-state">
        No se encontraron dominios
      </div>

      <div class="acai-status error" v-if="domainError">{{ domainError }}</div>
    </div>

    <!-- Step 3: Pull -->
    <div v-if="step === 3">
      <div class="acai-logged-bar">
        <span>{{ acaiStore.domain?.domain }}</span>
        <button class="btn btn-secondary btn-sm" @click="backToDomains">Cambiar web</button>
      </div>

      <div class="form-row full">
        <div class="form-group">
          <label>Directorio destino</label>
          <input
            type="text"
            v-model="destDir"
            placeholder="Dejar vacio para usar el por defecto"
          >
        </div>
      </div>

      <ToggleSwitch v-model="includeUploads" label="Incluir uploads" />

      <div class="form-row full">
        <div class="form-group">
          <label>Contrasena MySQL</label>
          <input
            type="password"
            v-model="mysqlPassword"
            placeholder="Contrasena root de MySQL"
            :style="mysqlPasswordError ? 'border-color: var(--red)' : ''"
          >
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; margin-top: 8px;">
        <button
          class="btn btn-primary"
          :disabled="pullLoading"
          @click="doPullWeb"
        >
          <span class="spinner" v-if="pullLoading"></span>
          Traer Web
        </button>
      </div>

      <div class="acai-status loading" v-if="pullLoading"><span class="spinner"></span> Trayendo web...</div>
      <div class="acai-status success" v-if="pullSuccess">Web traida correctamente. Cerrando...</div>
      <div class="acai-status error" v-if="pullError">{{ pullError }}</div>

      <div v-if="pullOutput" class="log-output" style="margin-top: 12px;">{{ pullOutput }}</div>
    </div>
  </BaseModal>
</template>

<style scoped>
.acai-domains-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 10px;
}
.domain-card {
  background: var(--bg2);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 14px 16px;
  cursor: pointer;
  transition: all var(--transition);
}
.domain-card:hover {
  border-color: var(--border-hover);
  background: var(--surface2);
}
.domain-card.selected {
  border-color: var(--accent);
  box-shadow: 0 0 0 1px var(--accent);
  background: var(--accent-dim);
}
.domain-name {
  font-weight: 600;
  font-size: 14px;
  margin-bottom: 3px;
  word-break: break-all;
}
.domain-meta {
  font-size: 11px;
  color: var(--text3);
}
.acai-logged-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 10px 16px;
  background: var(--bg2);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  margin-bottom: 16px;
}
.acai-logged-bar .acai-user {
  font-weight: 600;
  font-size: 13px;
  color: var(--accent);
}
.acai-status {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  border-radius: var(--radius);
  font-size: 13px;
  margin-top: 12px;
}
.acai-status.success {
  background: var(--green-dim);
  border: 1px solid rgba(34,197,94,0.25);
  color: var(--green);
}
.acai-status.error {
  background: var(--red-dim);
  border: 1px solid rgba(239,68,68,0.25);
  color: var(--red);
}
.acai-status.loading {
  background: var(--accent-dim);
  border: 1px solid rgba(59,130,246,0.25);
  color: var(--accent);
}
</style>
