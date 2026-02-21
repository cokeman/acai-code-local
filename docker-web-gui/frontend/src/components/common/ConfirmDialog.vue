<script setup>
import BaseModal from './BaseModal.vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: 'Confirmar' },
  message: { type: String, default: '' },
  confirmLabel: { type: String, default: 'Confirmar' },
  confirmClass: { type: String, default: 'btn btn-danger' },
})
const emit = defineEmits(['update:modelValue', 'confirm'])

function close() {
  emit('update:modelValue', false)
}

function confirm() {
  emit('confirm')
  close()
}
</script>

<template>
  <BaseModal :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)" :title="title" max-width="450px">
    <div class="confirm-text">{{ message }}</div>
    <div style="display: flex; gap: 8px; justify-content: flex-end;">
      <button class="btn btn-secondary" @click="close">Cancelar</button>
      <button :class="confirmClass" @click="confirm">{{ confirmLabel }}</button>
    </div>
  </BaseModal>
</template>
