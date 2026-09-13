<script setup>
import { ref, computed } from 'vue'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import Select from 'primevue/select'

defineProps({ title: String, mesOperaciones: String })

// Módulos REALES de reportes del sistema (los mismos que el módulo Reportes).
// Al elegir uno se embebe su página, que ya trae su combo de reportes y sus
// variables (mes, fecha, chofer, dimensión, etc.).
const modulos = [
  { key: 'tecnicas', label: 'Técnica', icono: 'pi pi-wrench', ruta: 'reportes.tecnicas' },
  { key: 'combustibles', label: 'Combustibles', icono: 'pi pi-tint', ruta: 'reportes.combustibles' },
  { key: 'facturacion', label: 'Facturación', icono: 'pi pi-file-invoice', ruta: 'reportes.facturacion' },
  { key: 'documentos', label: 'Documentos', icono: 'pi pi-folder', ruta: 'reportes.documentos' },
  { key: 'salarios', label: 'Nómina y Salarios', icono: 'pi pi-money-bill', ruta: 'reportes.salarios' },
  { key: 'resumen', label: 'Ingresos e Indicadores', icono: 'pi pi-chart-line', ruta: 'reportes.resumen' },
  { key: 'modelo1', label: 'Modelo 1', icono: 'pi pi-table', ruta: 'reportes.modelo1.filtros' },
]

const modulo = ref('tecnicas')
const cargados = ref({ tecnicas: true })

const moduloSel = computed(() => modulos.find((m) => m.key === modulo.value) || null)

function activar() {
  cargados.value[modulo.value] = true
}

function urlEmbed(m) {
  return route(m.ruta, {}, false) + '?embed=1'
}
</script>

<template>
  <AppLayout :title="title || 'Reportes'">
    <div class="p-4 max-w-5xl">
      <h1 class="text-2xl font-bold mb-1">Reportes</h1>
      <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
        Seleccione el módulo; dentro se elige el reporte y solo se muestran las
        variables que ese reporte necesita.
      </p>

      <div class="flex flex-col gap-1 max-w-md mb-4">
        <label class="text-sm font-semibold">Módulo</label>
        <Select
          v-model="modulo"
          :options="modulos"
          optionLabel="label"
          optionValue="key"
          placeholder="Seleccione el módulo"
          class="w-full"
          @change="activar"
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

    <!-- Se conservan los iframes ya abiertos (solo se oculta el inactivo). -->
    <div
      v-for="m in modulos"
      :key="m.key"
      :style="{ display: modulo === m.key ? 'block' : 'none' }"
    >
      <iframe
        v-if="cargados[m.key]"
        :src="urlEmbed(m)"
        class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900"
        style="height: calc(100vh - 250px)"
      />
    </div>
  </AppLayout>
</template>
