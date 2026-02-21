<script setup>
const props = defineProps({
  modelValue: { type: Boolean, default: false },
  label: { type: String, default: '' },
})
const emit = defineEmits(['update:modelValue'])

function toggle() {
  emit('update:modelValue', !props.modelValue)
}
</script>

<template>
  <div class="toggle-row">
    <div class="toggle-switch" :class="{ on: modelValue }" @click="toggle"></div>
    <span class="toggle-label" v-if="label">{{ label }}</span>
  </div>
</template>
