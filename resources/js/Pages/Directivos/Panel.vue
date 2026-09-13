<script setup>
import { ref } from 'vue'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import Select from 'primevue/select'

defineProps({ title: String })

// Panel del perfil DIRECTIVOS: dashboards de los módulos operativos + una vista
// unificada de reportes (combo de módulo → página real del módulo con su combo
// de reportes y variables). Todo en iframes ?embed=1 SIN anidar.
const tabs = [
  { key: 'tecnica', label: 'Técnica', icono: 'pi pi-wrench', tipo: 'modulo', modulo: 'tecnica' },
  { key: 'operativos', label: 'Operativos', icono: 'pi pi-truck', tipo: 'modulo', modulo: 'operativos' },
  { key: 'comercial', label: 'Comercial', icono: 'pi pi-briefcase', tipo: 'modulo', modulo: 'comercial' },
  { key: 'contabilidad', label: 'Contabilidad', icono: 'pi pi-calculator', tipo: 'modulo', modulo: 'contabilidad' },
  { key: 'reportes', label: 'Reportes', icono: 'pi pi-chart-bar', tipo: 'reportes' },
]

// Módulos REALES de reportes (mismos que el módulo Reportes).
const reportesModulos = [
  { key: 'tecnicas', label: 'Técnica', icono: 'pi pi-wrench', ruta: 'reportes.tecnicas' },
  { key: 'combustibles', label: 'Combustibles', icono: 'pi pi-tint', ruta: 'reportes.combustibles' },
  { key: 'facturacion', label: 'Facturación', icono: 'pi pi-file-invoice', ruta: 'reportes.facturacion' },
  { key: 'documentos', label: 'Documentos', icono: 'pi pi-folder', ruta: 'reportes.documentos' },
  { key: 'salarios', label: 'Nómina y Salarios', icono: 'pi pi-money-bill', ruta: 'reportes.salarios' },
  { key: 'resumen', label: 'Ingresos e Indicadores', icono: 'pi pi-chart-line', ruta: 'reportes.resumen' },
  { key: 'modelo1', label: 'Modelo 1', icono: 'pi pi-table', ruta: 'reportes.modelo1.filtros' },
]

const activo = ref('tecnica')
const cargados = ref({ tecnica: true })

const moduloReporte = ref('tecnicas')
const reportesCargados = ref({ tecnicas: true })

function activar(key) {
  activo.value = key
  cargados.value[key] = true
}

function activarReporte() {
  reportesCargados.value[moduloReporte.value] = true
}

// URL relativa (mismo origen) para no bloquear el iframe si la app se sirve con
// un host distinto al APP_URL (CSP frame-ancestors / X-Frame-Options).
function urlModulo(tab) {
  return route('dashboard.modulo', { modulo: tab.modulo }, false) + '?embed=1'
}

function urlReporte(m) {
  return route(m.ruta, {}, false) + '?embed=1'
}
</script>

<template>
  <AppLayout :title="title || 'Panel Directivos'">
    <div class="flex flex-wrap items-center gap-2 mb-4">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        type="button"
        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold border transition-colors"
        :class="activo === tab.key
          ? 'bg-amber-600 text-white border-amber-600 shadow-sm'
          : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/60'"
        @click="activar(tab.key)"
      >
        <i :class="tab.icono" />
        {{ tab.label }}
      </button>

      <!-- Combo de módulo de reportes, en la misma barra para no restar altura -->
      <div v-if="activo === 'reportes'" class="flex items-center gap-2 ml-auto">
        <i class="pi pi-filter text-gray-400" />
        <Select
          v-model="moduloReporte"
          :options="reportesModulos"
          optionLabel="label"
          optionValue="key"
          placeholder="Módulo de reportes"
          class="w-64"
          @change="activarReporte"
        >
          <template #option="slotProps">
            <div class="flex items-center gap-2">
              <i :class="slotProps.option.icono" />
              <span>{{ slotProps.option.label }}</span>
            </div>
          </template>
        </Select>
      </div>
    </div>

    <template v-for="tab in tabs" :key="tab.key">
      <!-- Dashboards de módulo -->
      <div
        v-if="tab.tipo === 'modulo'"
        :style="{ display: activo === tab.key ? 'block' : 'none' }"
      >
        <iframe
          v-if="cargados[tab.key]"
          :src="urlModulo(tab)"
          class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900"
          style="height: calc(100vh - 180px)"
        />
      </div>

      <!-- Reportes: página real del módulo embebida -->
      <div
        v-else
        :style="{ display: activo === tab.key ? 'block' : 'none' }"
      >
        <div
          v-for="m in reportesModulos"
          :key="m.key"
          :style="{ display: moduloReporte === m.key ? 'block' : 'none' }"
        >
          <iframe
            v-if="reportesCargados[m.key]"
            :src="urlReporte(m)"
            class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900"
            style="height: calc(100vh - 180px)"
          />
        </div>
      </div>
    </template>
  </AppLayout>
</template>
