<script setup>
const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: '' },
  readonly: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue', 'browse'])
</script>

<template>
  <div class="path-selector">
    <input
      type="text"
      :value="modelValue"
      :placeholder="placeholder"
      :readonly="readonly"
      @input="emit('update:modelValue', $event.target.value)"
    >
    <button class="btn btn-secondary btn-sm" @click="emit('browse')">Explorar</button>
  </div>
</template>
