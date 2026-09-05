<script setup>
import Button from 'primevue/button'

const props = defineProps({
  modelValue: { type: String, default: 'tabla' },
})
const emit = defineEmits(['update:modelValue'])
</script>

<template>
  <div class="inline-flex border border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden">
    <Button
      icon="pi pi-list"
      :severity="modelValue === 'tabla' ? 'primary' : 'secondary'"
      :text="modelValue !== 'tabla'"
      rounded
      class="!rounded-r-none !border-0 !px-2.5 !py-1.5"
      @click="emit('update:modelValue', 'tabla')"
      v-tooltip.top="'Vista tabla'"
    />
    <Button
      icon="pi pi-th-large"
      :severity="modelValue === 'tarjetas' ? 'primary' : 'secondary'"
      :text="modelValue !== 'tarjetas'"
      rounded
      class="!rounded-l-none !border-0 !px-2.5 !py-1.5"
      @click="emit('update:modelValue', 'tarjetas')"
      v-tooltip.top="'Vista tarjetas'"
    />
  </div>
</template>
