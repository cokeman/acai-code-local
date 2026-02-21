import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api, apiPost } from '../composables/useApi.js'

export const useAcaiStore = defineStore('acai', () => {
  // State
  const username = ref('')
  const hash = ref('')
  const domains = ref([])
  const token = ref('')
  const tokenHash = ref('')
  const domain = ref(null)
  const user = ref(null)

  // Computed
  const isLoggedIn = computed(() => !!hash.value)
  const hasDomain = computed(() => !!domain.value)
  const isConnected = computed(() => !!token.value && !!domain.value)

  // Actions
  async function login(usr, pass) {
    const data = await apiPost('/api/acai/login', {
      username: usr,
      password: pass,
    })

    if (!data.success) {
      throw new Error(data.error || 'Error de autenticacion')
    }

    username.value = usr
    hash.value = data.hash
    domains.value = data.domains || []
    return data
  }

  async function selectDomain(domainNum) {
    const data = await apiPost('/api/acai/select-domain', {
      username: username.value,
      hash: hash.value,
      domain_num: String(domainNum),
    })

    if (!data.success) {
      throw new Error(data.error || 'Error al seleccionar dominio')
    }

    token.value = data.token
    tokenHash.value = data.tokenHash
    domain.value = data.domain
    user.value = data.user
    return data
  }

  async function pullWeb(opts) {
    return apiPost('/api/acai/pull-web', {
      domain: domain.value.domain,
      ssl: domain.value.ssl,
      token: token.value,
      tokenHash: tokenHash.value,
      ...opts,
    })
  }

  function backToDomains() {
    token.value = ''
    tokenHash.value = ''
    domain.value = null
    user.value = null
  }

  function logout() {
    username.value = ''
    hash.value = ''
    domains.value = []
    token.value = ''
    tokenHash.value = ''
    domain.value = null
    user.value = null
  }

  return {
    username,
    hash,
    domains,
    token,
    tokenHash,
    domain,
    user,
    isLoggedIn,
    hasDomain,
    isConnected,
    login,
    selectDomain,
    pullWeb,
    backToDomains,
    logout,
  }
})
