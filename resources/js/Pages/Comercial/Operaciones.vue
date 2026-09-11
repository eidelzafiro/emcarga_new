<script setup>
import { ref } from 'vue'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'

defineProps({ title: String })

// Cada pestaña embebe el CRUD completo en un iframe (?embed=1 oculta el
// layout). Los iframes ya abiertos se conservan con v-show, de modo que al
// cambiar de pestaña no se cierra ni se recarga lo demás.
const tabs = [
  { key: 'hr', label: 'Hojas de Ruta', icono: 'pi pi-truck', ruta: 'hojas-ruta.index' },
  { key: 'solicitudes', label: 'Solicitudes', icono: 'pi pi-clipboard', ruta: 'solicitudes.index' },
  { key: 'cp', label: 'Cartas de Porte', icono: 'pi pi-file', ruta: 'carta-porte.index' },
  { key: 'aforos', label: 'Aforos', icono: 'pi pi-file-check', ruta: 'aforos.index' },
  { key: 'facturas', label: 'Facturación', icono: 'pi pi-dollar', ruta: 'facturas.index' },
]

const activo = ref('hr')
const cargados = ref({ hr: true })

function activar(key) {
  activo.value = key
  cargados.value[key] = true
}

function urlEmbed(tab) {
  return route(tab.ruta) + '?embed=1'
}
</script>

<template>
  <AppLayout :title="title || 'Comercial'">
    <div class="flex flex-wrap gap-2 mb-4">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        type="button"
        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold border transition-colors"
        :class="activo === tab.key
          ? 'bg-cyan-600 text-white border-cyan-600 shadow-sm'
          : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/60'"
        @click="activar(tab.key)"
      >
        <i :class="tab.icono" />
        {{ tab.label }}
      </button>
    </div>

    <div v-for="tab in tabs" :key="tab.key" v-show="activo === tab.key">
      <iframe
        v-if="cargados[tab.key]"
        :src="urlEmbed(tab)"
        class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900"
        style="height: calc(100vh - 190px)"
      />
    </div>
  </AppLayout>
</template>
