<script setup>
defineProps({
  title: { type: String, required: true },
})
</script>

<template>
  <div class="main-header">
    <h2>{{ title }}</h2>
    <div class="main-header-actions">
      <slot />
    </div>
  </div>
</template>
