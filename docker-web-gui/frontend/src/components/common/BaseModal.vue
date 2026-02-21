<script setup>
const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: '' },
  maxWidth: { type: String, default: '800px' },
})
const emit = defineEmits(['update:modelValue'])

function close() {
  emit('update:modelValue', false)
}

function onOverlayClick(e) {
  if (e.target === e.currentTarget) close()
}
</script>

<template>
  <Teleport to="body">
    <div
      class="modal-overlay"
      :class="{ open: modelValue }"
      @click="onOverlayClick"
      @keydown.esc="close"
    >
      <div class="modal" :style="{ maxWidth }">
        <div class="modal-header">
          <span>{{ title }}</span>
          <button class="modal-close" @click="close">&times;</button>
        </div>
        <div class="modal-body">
          <slot />
        </div>
      </div>
    </div>
  </Teleport>
</template>
