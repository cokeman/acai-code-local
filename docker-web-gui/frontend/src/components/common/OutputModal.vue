<script setup>
import BaseModal from './BaseModal.vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: 'Resultado' },
  content: { type: String, default: '' },
})
const emit = defineEmits(['update:modelValue', 'close'])

function close() {
  emit('update:modelValue', false)
  emit('close')
}
</script>

<template>
  <BaseModal :model-value="modelValue" @update:model-value="close" :title="title" max-width="650px">
    <div class="log-output">{{ content }}</div>
  </BaseModal>
</template>
