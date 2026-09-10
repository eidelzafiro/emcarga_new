<script setup>
import { ref, computed, watch } from 'vue'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import DatePicker from 'primevue/datepicker'
import Select from 'primevue/select'
import Button from 'primevue/button'
import Card from 'primevue/card'

const props = defineProps({
  title: { type: String, default: 'Salarios y Prenóminas' },
  mes: { type: Number, required: true },
  ano: { type: Number, required: true },
  choferes: { type: Array, default: () => [] },
  tiposIncidencia: { type: Array, default: () => [] },
  tiposPenalizacion: { type: Array, default: () => [] },
  sistemasPago: { type: Array, default: () => [] },
})

const fechaMes = ref(new Date(props.ano, props.mes - 1, 1))
const reporte = ref(null)
const tipoIncidencia = ref(props.tiposIncidencia?.[0]?.origen_id ?? null)
const tipoPenalizacion = ref(props.tiposPenalizacion?.[0]?.id ?? null)
const sistemaPago = ref(props.sistemasPago?.[0]?.id ?? null)
const choferId = ref(null)

const mes = computed(() => fechaMes.value.getMonth() + 1)
const ano = computed(() => fechaMes.value.getFullYear())

/**
 * Catálogo de TODOS los reportes de RRHH. Cada reporte declara las
 * variables que usa para que la tarjeta inactivite el resto:
 *   mes        — siempre activo (selector de mes/año)
 *   incidencia — tipo de incidencia (reportes de incidencias)
 *   penalizacion — tipo de penalización
 *   sistema    — tipo de sistema de pago
 *   chofer     — chofer específico (Modelo 1)
 */
const REPORTES = [
  { id: 'prenomina-choferes', nombre: 'Prenómina Salario Choferes', ruta: 'reportes.prenomina-choferes', formatos: ['pdf', 'excel'] },
  { id: 'prenomina-admin', nombre: 'Prenómina Salario Administrativo', ruta: 'reportes.prenomina-administrativo', formatos: ['pdf', 'excel'] },
  { id: 'salario-choferes', nombre: 'Salario Choferes (nómina)', ruta: 'reportes.salario-choferes', formatos: ['pdf'] },
  { id: 'modelo1', nombre: 'Modelo 1 — Control Diario de Transportaciones', ruta: 'reportes.modelo1', formatos: ['pdf', 'excel'], vars: ['chofer'] },
  { id: 'adicionales', nombre: 'Datos p/Nóminas (Adicionales)', ruta: 'reportes.adicionales', formatos: ['pdf'] },
  { id: 'nocturnidad', nombre: 'Datos p/Nóminas (Nocturnidad)', ruta: 'reportes.nocturnidad', formatos: ['pdf'] },
  { id: 'pago-administrativo', nombre: 'Datos p/Nóminas Pago Administrativo Holguín', ruta: 'reportes.pago-administrativo', formatos: ['pdf'] },
  { id: 'resumen-tiempos', nombre: 'Resumen de los Tiempos Choferes', ruta: 'reportes.resumen-tiempos-choferes', formatos: ['pdf'] },
  { id: 'analisis-salario', nombre: 'Análisis del Salario Transportación', ruta: 'reportes.analisis-salario-transportacion', formatos: ['pdf'] },
  { id: 'control-diario-admin', nombre: 'SC-4-05 Control Diario (Administrativos)', ruta: 'reportes.control-diario-administrativo', formatos: ['pdf'] },
  { id: 'control-diario-choferes', nombre: 'SC-4-05 Control Diario Choferes Transportación', ruta: 'reportes.control-diario-choferes', formatos: ['pdf'] },
  { id: 'incidencias', nombre: 'Prenómina Incidencias al Tiempo Trabajado', ruta: 'reportes.incidencias', formatos: ['pdf'], vars: ['incidencia'] },
  { id: 'cumpleanos', nombre: 'Listado de Cumpleaños del Mes', ruta: 'reportes.cumpleanos', formatos: ['pdf'] },
  { id: 'licencia', nombre: 'Personal con Licencia de Conducción', ruta: 'reportes.licencia-conduccion', formatos: ['pdf'] },
  { id: 'versat', nombre: 'Exportación al VERSAT (CSV ZIP)', ruta: 'reportes.exportar-versat', formatos: ['excel'] },
]

const reporteSel = computed(() => REPORTES.find((r) => r.id === reporte.value) || null)

function usa(varName) {
  return (reporteSel.value?.vars || []).includes(varName)
}

watch(reporte, () => {
  // Al cambiar de reporte, resetear las variables que no usa.
  if (!usa('chofer')) choferId.value = null
})

function abrir(formato) {
  if (!reporteSel.value) return
  let nombreRuta = reporteSel.value.ruta
  if (formato === 'excel' && nombreRuta === 'reportes.modelo1') nombreRuta = 'reportes.modelo1-excel'
  if (formato === 'excel' && nombreRuta === 'reportes.prenomina-choferes') nombreRuta = 'reportes.prenomina-choferes-excel'
  if (formato === 'excel' && nombreRuta === 'reportes.prenomina-administrativo') nombreRuta = 'reportes.prenomina-administrativo-excel'

  const params = { mes: mes.value, ano: ano.value }
  if (usa('incidencia') && tipoIncidencia.value) params.tipo_incidencia = tipoIncidencia.value
  if (usa('chofer') && choferId.value) params.id_bolsa = choferId.value

  const url = route(nombreRuta) + '?' + new URLSearchParams(params).toString()
  window.open(url, '_blank')
}
</script>

<template>
  <AppLayout :title="title">
    <div class="p-4 max-w-4xl">
      <h1 class="text-2xl font-bold mb-1">{{ title }}</h1>
      <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
        Selecciona el mes y el reporte; las variables que no corresponden al
        reporte elegido se desactivan automáticamente.
      </p>

      <Card>
        <template #content>
          <div class="flex flex-col gap-4">
            <!-- Reporte -->
            <div class="flex flex-col gap-1">
              <label class="text-sm font-semibold">Reporte</label>
              <Select
                v-model="reporte"
                :options="REPORTES"
                optionLabel="nombre"
                optionValue="id"
                placeholder="Seleccione el reporte"
                class="w-full"
                filter
              />
            </div>

            <!-- Variables -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="flex flex-col gap-1">
                <label class="text-sm font-semibold">Mes de operaciones</label>
                <DatePicker
                  v-model="fechaMes"
                  view="month"
                  date-format="mm/yy"
                  input-id="salarios-mes"
                  class="w-full"
                />
              </div>

              <div class="flex flex-col gap-1" :class="{ 'opacity-40 pointer-events-none': !usa('incidencia') }">
                <label class="text-sm font-semibold">Tipo de incidencia</label>
                <Select
                  v-model="tipoIncidencia"
                  :options="tiposIncidencia"
                  optionLabel="nombre"
                  optionValue="origen_id"
                  placeholder="Ninguna en el mes"
                  class="w-full"
                  :disabled="!usa('incidencia')"
                />
                <span class="text-xs text-gray-500 dark:text-gray-400">Solo tipos con incidencias en el mes.</span>
              </div>

              <div class="flex flex-col gap-1" :class="{ 'opacity-40 pointer-events-none': !usa('penalizacion') }">
                <label class="text-sm font-semibold">Tipo de penalización</label>
                <Select
                  v-model="tipoPenalizacion"
                  :options="tiposPenalizacion"
                  optionLabel="nombre"
                  optionValue="id"
                  placeholder="Seleccione"
                  class="w-full"
                  :disabled="!usa('penalizacion')"
                />
              </div>

              <div class="flex flex-col gap-1" :class="{ 'opacity-40 pointer-events-none': !usa('sistema') }">
                <label class="text-sm font-semibold">Sistema de pago</label>
                <Select
                  v-model="sistemaPago"
                  :options="sistemasPago"
                  optionLabel="nombre"
                  optionValue="id"
                  placeholder="Seleccione"
                  class="w-full"
                  :disabled="!usa('sistema')"
                />
              </div>

              <div class="flex flex-col gap-1" :class="{ 'opacity-40 pointer-events-none': !usa('chofer') }">
                <label class="text-sm font-semibold">Chofer (Modelo 1)</label>
                <Select
                  v-model="choferId"
                  :options="choferes"
                  optionLabel="nombre"
                  optionValue="id"
                  placeholder="Todos los choferes"
                  class="w-full"
                  filter
                  showClear
                  :disabled="!usa('chofer')"
                />
              </div>
            </div>

            <!-- Botonera -->
            <div class="flex gap-2 pt-2">
              <Button
                label="PDF"
                icon="pi pi-file-pdf"
                :disabled="!reporteSel || !reporteSel.formatos.includes('pdf')"
                @click="abrir('pdf')"
              />
              <Button
                label="Excel"
                icon="pi pi-file-excel"
                severity="success"
                :disabled="!reporteSel || !reporteSel.formatos.includes('excel')"
                @click="abrir('excel')"
              />
            </div>
          </div>
        </template>
      </Card>
    </div>
  </AppLayout>
</template>
