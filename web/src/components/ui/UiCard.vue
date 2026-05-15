<script setup lang="ts">
withDefaults(defineProps<{
  padded?: boolean
}>(), {
  padded: true,
})
</script>

<template>
  <section :class="['rounded-xl border border-slate-200 bg-white shadow-sm', padded ? 'p-6' : '']">
    <slot />
  </section>
</template>
