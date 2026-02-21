<script setup>
import { computed } from 'vue'

const props = defineProps({
  project: { type: Object, required: true },
})

const credentials = computed(() => {
  const creds = []
  if (props.project.db_port) creds.push({ label: 'Host', value: `localhost:${props.project.db_port}` })
  if (props.project.bore_db_url) creds.push({ label: 'Tunnel', value: props.project.bore_db_url })
  if (props.project.db_name) creds.push({ label: 'Base', value: props.project.db_name })
  if (props.project.db_user) creds.push({ label: 'Usuario', value: props.project.db_user })
  if (props.project.db_pass) creds.push({ label: 'Pass', value: props.project.db_pass })
  return creds
})

function copyAll() {
  const text = credentials.value.map(c => `${c.label}: ${c.value}`).join('\n')
  navigator.clipboard.writeText(text)
}

function copyTunnel() {
  navigator.clipboard.writeText(props.project.bore_db_url)
}
</script>

<template>
  <div class="db-creds">
    <div v-for="cred in credentials" :key="cred.label" class="db-cred">
      <span class="db-cred-label">{{ cred.label }}</span>
      <span class="db-cred-value">{{ cred.value }}</span>
    </div>
  </div>
  <div class="db-row-actions">
    <button
      v-if="project.bore_db_url"
      class="btn btn-secondary btn-sm"
      @click="copyTunnel"
    >Copiar tunnel</button>
    <button class="btn btn-secondary btn-sm" @click="copyAll">Copiar todo</button>
  </div>
</template>
