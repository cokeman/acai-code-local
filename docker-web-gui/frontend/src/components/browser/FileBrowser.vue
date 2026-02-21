<script setup>
import { ref, watch } from 'vue'
import BaseModal from '../common/BaseModal.vue'
import { api } from '../../composables/useApi.js'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  mode: { type: String, default: 'dir' },
  title: { type: String, default: 'Explorar' },
  startPath: { type: String, default: '/' },
  ext: { type: String, default: '' },
})
const emit = defineEmits(['update:modelValue', 'select'])

const currentPath = ref('/')
const parentPath = ref(null)
const items = ref([])
const loading = ref(false)
const selectedFile = ref(null)

watch(() => props.modelValue, (open) => {
  if (open) {
    currentPath.value = props.startPath || '/'
    selectedFile.value = null
    browse(currentPath.value)
  }
})

async function browse(path) {
  loading.value = true
  selectedFile.value = null
  try {
    let data
    if (props.mode === 'file') {
      const params = new URLSearchParams({ path })
      if (props.ext) params.set('ext', props.ext)
      data = await api(`/api/browse-files?${params}`)
    } else {
      data = await api(`/api/browse?path=${encodeURIComponent(path)}`)
    }
    currentPath.value = data.current || path
    parentPath.value = data.parent || null
    items.value = data.entries || []
  } catch {
    items.value = []
  } finally {
    loading.value = false
  }
}

function navigateUp() {
  if (parentPath.value) {
    browse(parentPath.value)
  }
}

function onItemClick(item) {
  if (item.type === 'dir') {
    browse(item.path)
  } else {
    selectedFile.value = item.path
  }
}

function onItemDblClick(item) {
  if (item.type === 'file') {
    selectAndClose(item.path)
  }
}

function selectCurrent() {
  if (props.mode === 'dir') {
    selectAndClose(currentPath.value)
  } else if (selectedFile.value) {
    selectAndClose(selectedFile.value)
  }
}

function selectAndClose(path) {
  emit('select', path)
  emit('update:modelValue', false)
}

function close() {
  emit('update:modelValue', false)
}
</script>

<template>
  <BaseModal
    :model-value="modelValue"
    @update:model-value="emit('update:modelValue', $event)"
    :title="title"
    max-width="600px"
  >
    <div class="browser-path">{{ currentPath }}</div>

    <div v-if="loading" style="text-align: center; padding: 24px;">
      <span class="spinner"></span>
    </div>

    <ul v-else class="browser-list">
      <li v-if="parentPath" class="browser-item" @click="navigateUp">
        <span class="browser-icon">&#128193;</span>
        <span>..</span>
      </li>
      <li
        v-for="item in items"
        :key="item.path"
        class="browser-item"
        :class="{ selected: item.type === 'file' && selectedFile === item.path }"
        @click="onItemClick(item)"
        @dblclick="onItemDblClick(item)"
      >
        <span class="browser-icon">{{ item.type === 'dir' ? '\uD83D\uDCC1' : '\uD83D\uDCC4' }}</span>
        <span>{{ item.name }}</span>
        <span v-if="item.size != null" class="browser-size">{{ item.size }}</span>
      </li>
      <li v-if="items.length === 0 && !parentPath" class="browser-item" style="color: var(--text3);">
        Directorio vacio
      </li>
    </ul>

    <div class="browser-actions">
      <button class="btn btn-secondary" @click="close">Cancelar</button>
      <button
        class="btn btn-primary"
        :disabled="mode === 'file' && !selectedFile"
        @click="selectCurrent"
      >
        Seleccionar
      </button>
    </div>
  </BaseModal>
</template>
