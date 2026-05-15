<script setup lang="ts">
import { computed } from 'vue'

type Variant = 'neutral' | 'success' | 'warning' | 'danger' | 'info'

const props = withDefaults(defineProps<{
  variant?: Variant
}>(), {
  variant: 'neutral',
})

const classes = computed(() => {
  const base = 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium'
  const variant = {
    neutral: 'bg-slate-100 text-slate-700',
    success: 'bg-emerald-100 text-emerald-700',
    warning: 'bg-amber-100 text-amber-800',
    danger: 'bg-red-100 text-red-700',
    info: 'bg-blue-100 text-blue-700',
  }[props.variant]

  return `${base} ${variant}`
})
</script>

<template>
  <span :class="classes"><slot /></span>
</template>
