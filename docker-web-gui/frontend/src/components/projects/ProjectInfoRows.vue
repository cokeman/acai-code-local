<script setup>
import DbCredentials from './DbCredentials.vue'

const props = defineProps({
  project: { type: Object, required: true },
})

function copyTunnelUrl() {
  navigator.clipboard.writeText(`https://${props.project.tunnel_url}`)
}
</script>

<template>
  <div v-if="project.https_url || project.tunnel_url || project.db_port" class="info-rows">
    <!-- Web local -->
    <div v-if="project.https_url" class="info-row">
      <div class="info-row-icon web">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/><path d="M12 12m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"/><path d="M12 9h8.4"/><path d="M14.598 13.5l-4.2 7.275"/><path d="M9.402 13.5l-4.2 -7.275"/></svg>
      </div>
      <div class="info-row-content">
        <div class="info-row-label">Web local</div>
        <div class="info-row-value">
          <a :href="`${project.https_url}?pruebas=1`" target="_blank">{{ project.https_url.replace('https://', '') }}</a>
        </div>
        <div v-if="project.project_dir" class="info-row-sub">{{ project.project_dir }}</div>
      </div>
    </div>

    <!-- Tunnel -->
    <div v-if="project.tunnel_url" class="info-row">
      <div class="info-row-icon tunnel">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/><path d="M12 12m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"/><path d="M12 9h8.4"/><path d="M14.598 13.5l-4.2 7.275"/><path d="M9.402 13.5l-4.2 -7.275"/></svg>
      </div>
      <div class="info-row-content">
        <div class="info-row-label">Tunnel publico</div>
        <div class="info-row-value">
          <a :href="`https://${project.tunnel_url}?pruebas=1`" target="_blank">{{ project.tunnel_url }}</a>
        </div>
      </div>
      <div class="info-row-actions">
        <button class="btn btn-secondary btn-sm" @click="copyTunnelUrl">Copiar</button>
      </div>
    </div>

    <!-- DB -->
    <div v-if="project.db_port" class="info-row db-row">
      <div class="db-row-header">
        <div class="info-row-icon db">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 6m-8 0a8 3 0 1 0 16 0a8 3 0 1 0 -16 0"/><path d="M4 6v6a8 3 0 0 0 16 0v-6"/><path d="M4 12v6a8 3 0 0 0 16 0v-6"/></svg>
        </div>
        <div class="info-row-content">
          <div class="info-row-label">Base de datos</div>
          <div class="info-row-value">MySQL — localhost:{{ project.db_port }}</div>
        </div>
      </div>
      <DbCredentials :project="project" />
    </div>
  </div>
</template>
