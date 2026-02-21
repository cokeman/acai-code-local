<script setup>
import { ref } from 'vue'

const emit = defineEmits(['retry'])
const retrying = ref(false)

async function retry() {
  retrying.value = true
  emit('retry')
  setTimeout(() => { retrying.value = false }, 2000)
}
</script>

<template>
  <div class="offline">
    <div class="offline-icon">&#9888;</div>
    <h1>No se pudo conectar al servidor</h1>
    <p class="offline-desc">
      El dashboard necesita el servidor <strong>docker-web-gui</strong> corriendo en
      <code>localhost:9090</code>.
    </p>

    <div class="offline-card">
      <h2>Instrucciones de instalacion</h2>

      <p class="step"><strong>1.</strong> Clona el repositorio:</p>
      <pre>git clone https://github.com/acai-cms/docker-web-gui.git</pre>

      <p class="step"><strong>2.</strong> Inicia el servidor (Python 3, sin dependencias externas):</p>
      <pre>cd docker-web-gui
python3 server.py</pre>

      <p class="step"><strong>3.</strong> Verifica que el servidor esta corriendo:</p>
      <pre>curl http://localhost:9090/api/ping</pre>
    </div>

    <button class="retry-btn" :disabled="retrying" @click="retry">
      {{ retrying ? 'Comprobando...' : 'Reintentar conexion' }}
    </button>
  </div>
</template>

<style scoped>
.offline {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  padding: 40px 20px;
  background: var(--bg);
  color: var(--text2);
  text-align: center;
}
.offline-icon {
  font-size: 48px;
  margin-bottom: 12px;
}
.offline h1 {
  font-size: 22px;
  color: var(--text);
  margin: 0 0 12px;
}
.offline-desc {
  max-width: 480px;
  line-height: 1.6;
  margin: 0 0 28px;
}
.offline-desc code {
  background: var(--surface2);
  padding: 2px 6px;
  border-radius: 3px;
  font-size: 0.9em;
}
.offline-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 24px 28px;
  max-width: 520px;
  width: 100%;
  text-align: left;
}
.offline-card h2 {
  font-size: 14px;
  color: var(--text);
  margin: 0 0 16px;
}
.step {
  font-size: 13px;
  color: var(--text2);
  margin: 0 0 8px;
}
.step strong {
  color: var(--text);
}
.offline-card pre {
  background: var(--bg);
  padding: 10px 12px;
  border-radius: var(--radius);
  overflow-x: auto;
  margin: 0 0 16px;
  color: var(--text);
  font-size: 12px;
  line-height: 1.5;
}
.offline-card pre:last-child {
  margin-bottom: 0;
}
.retry-btn {
  margin-top: 24px;
  padding: 8px 24px;
  border: none;
  border-radius: var(--radius);
  background: var(--accent);
  color: #fff;
  font-size: 13px;
  cursor: pointer;
  transition: background var(--transition);
}
.retry-btn:hover:not(:disabled) {
  background: var(--accent-hover);
}
.retry-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
