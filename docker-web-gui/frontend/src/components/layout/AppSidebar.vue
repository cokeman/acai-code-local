<script setup>
import { computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAppStore } from '../../stores/app.js'
import { useAcaiStore } from '../../stores/acai.js'

const router = useRouter()
const route = useRoute()
const appStore = useAppStore()
const acaiStore = useAcaiStore()

const projectsCount = computed(() => appStore.projects.length)
const websCount = computed(() => appStore.localWebs.length)
const watcherCount = computed(() => appStore.watcherLogs.length)

const acaiLabel = computed(() => {
  if (acaiStore.isConnected) return acaiStore.domain?.domain || 'Conectado'
  if (acaiStore.isLoggedIn) return acaiStore.username
  return 'Sin conectar'
})

const acaiDotClass = computed(() =>
  acaiStore.isConnected || acaiStore.isLoggedIn ? 'acai-dot connected' : 'acai-dot disconnected'
)

function isActive(name) {
  return route.name === name
}

function emit(name) {
  router.push({ name })
}

function openAcaiModal() {
  router.push({ name: 'webs-locales' })
  // The webs-locales page handles the Acai modal
  window.dispatchEvent(new CustomEvent('open-acai-modal'))
}
</script>

<template>
  <aside class="sidebar">
    <div class="sidebar-brand">
      <img src="https://code.acaisuite.com/assets/logo.75aeeddb.png" alt="Acai" class="sidebar-logo">
      <span class="status-dot ok" title="Servidor conectado"></span>
    </div>

    <nav class="sidebar-nav">
      <div class="nav-label">Menu</div>
      <router-link to="/projects" class="nav-item" :class="{ active: isActive('projects') }">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        En ejecucion
        <span class="nav-badge">{{ projectsCount }}</span>
      </router-link>

      <router-link to="/nueva-web" class="nav-item" :class="{ active: isActive('nueva-web') }">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
        Nueva Web
      </router-link>

      <router-link to="/webs-locales" class="nav-item" :class="{ active: isActive('webs-locales') }">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
        Acai Pulls
        <span class="nav-badge">{{ websCount }}</span>
      </router-link>

      <router-link to="/watcher" class="nav-item" :class="{ active: isActive('watcher') }">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        Watcher
        <span class="nav-badge">{{ watcherCount }}</span>
      </router-link>
    </nav>

    <div class="sidebar-acai">
      <div class="nav-label">Acai Code</div>
      <div class="acai-sidebar-status" @click="openAcaiModal">
        <span :class="acaiDotClass"></span>
        <span class="acai-label">{{ acaiLabel }}</span>
      </div>
    </div>

    <div style="padding: 12px 10px; border-top: 1px solid var(--border); margin-top: auto;">
      <router-link to="/settings" class="nav-item" :class="{ active: isActive('settings') }">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
        Ajustes
      </router-link>
    </div>
  </aside>
</template>

<style scoped>
.sidebar {
  width: 260px;
  min-width: 260px;
  background: var(--sidebar-bg);
  border-right: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  height: 100vh;
  position: fixed;
  top: 0;
  left: 0;
  z-index: 50;
}
.sidebar-brand {
  padding: 20px 20px 16px;
  border-bottom: 1px solid var(--border);
  display: flex;
  align-items: center;
  gap: 10px;
}
.sidebar-brand svg {
  width: 24px;
  height: 24px;
  color: var(--accent);
  flex-shrink: 0;
}
.sidebar-logo {
  height: 32px;
  object-fit: contain;
  flex-shrink: 0;
  filter: brightness(0) invert(1);
}
.sidebar-brand h1 {
  font-size: 15px;
  font-weight: 600;
  letter-spacing: -0.3px;
}
.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  margin-left: auto;
  flex-shrink: 0;
}
.status-dot.ok { background: var(--green); box-shadow: 0 0 6px var(--green); }
.status-dot.err { background: var(--red); }
.sidebar-nav {
  padding: 12px 10px;
  flex: 1;
}
.nav-label {
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  color: var(--text3);
  padding: 8px 12px 6px;
  font-weight: 600;
}
.nav-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 12px;
  border-radius: 6px;
  font-size: 14px;
  color: var(--text2);
  cursor: pointer;
  transition: all var(--transition);
  margin-bottom: 2px;
  border: 1px solid transparent;
  text-decoration: none;
}
.nav-item:hover {
  background: var(--surface);
  color: var(--text);
  text-decoration: none;
}
.nav-item.active {
  background: var(--accent-dim);
  color: var(--accent);
  border-color: rgba(59,130,246,0.2);
}
.nav-item svg {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
}
.nav-badge {
  margin-left: auto;
  font-size: 11px;
  padding: 1px 7px;
  border-radius: 10px;
  background: var(--surface2);
  color: var(--text2);
  font-weight: 600;
}
.sidebar-acai {
  padding: 12px 10px;
  border-top: 1px solid var(--border);
}
.acai-sidebar-status {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  border-radius: 6px;
  font-size: 13px;
  color: var(--text2);
  cursor: pointer;
  transition: all var(--transition);
}
.acai-sidebar-status:hover {
  background: var(--surface);
}
.acai-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
}
.acai-dot.connected { background: var(--green); box-shadow: 0 0 6px var(--green); }
.acai-dot.disconnected { background: var(--text3); }
.acai-label { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

@media (max-width: 900px) {
  .sidebar { width: 220px; min-width: 220px; }
}
@media (max-width: 700px) {
  .sidebar {
    position: fixed;
    transform: translateX(-100%);
    transition: transform 0.25s ease;
  }
  .sidebar.open { transform: translateX(0); }
}
</style>
