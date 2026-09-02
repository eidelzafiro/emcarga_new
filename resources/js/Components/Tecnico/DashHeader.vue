<template>
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Panel Técnico</h1>
      <p class="text-gray-500 dark:text-gray-400 text-sm mt-1 flex items-center gap-2">
        <span class="font-medium text-gray-700 dark:text-gray-300">{{ entidad }}</span>
        <span class="text-gray-300 dark:text-gray-600">·</span>
        <span>{{ mesLabel }}</span>
      </p>
    </div>
    <div class="flex items-center gap-2">
      <div class="flex items-center gap-1 rounded-md border border-gray-200 dark:border-gray-700 overflow-hidden">
        <button class="px-2 py-1 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700" title="Mes anterior" @click="$emit('prev')">
          <i class="pi pi-chevron-left text-xs" />
        </button>
        <button class="px-2 py-1 text-xs text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700" @click="$emit('hoy')">Hoy</button>
        <button class="px-2 py-1 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700" title="Mes siguiente" @click="$emit('next')">
          <i class="pi pi-chevron-right text-xs" />
        </button>
      </div>
      <span class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
        <span class="w-2 h-2 rounded-full" :class="conectado ? 'bg-emerald-500' : 'bg-red-500'" />
        {{ conectado ? 'En vivo' : 'Desconectado' }}
      </span>
      <span v-if="ultima" class="text-xs text-gray-400 dark:text-gray-500">· {{ ultima }}</span>
    </div>
  </div>
</template>

<script setup>
defineProps({
  mesLabel: String,
  entidad: String,
  conectado: Boolean,
  ultima: { type: String, default: null },
});
defineEmits(['prev', 'next', 'hoy']);
</script>
