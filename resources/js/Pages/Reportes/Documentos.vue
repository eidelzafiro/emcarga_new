<script setup>
import { ref, computed } from 'vue'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import DatePicker from 'primevue/datepicker'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import Button from 'primevue/button'
import Card from 'primevue/card'

const props = defineProps({
  title: { type: String, default: 'Reportes de Documentos' },
  reportes: { type: Array, default: () => [] },
  mesOperaciones: { type: String, default: '' },
})

// Mes de operaciones por defecto (o el mes actual).
const base = props.mesOperaciones ? new Date(props.mesOperaciones + 'T00:00:00') : new Date()
const fechaMes = ref(new Date(base.getFullYear(), base.getMonth(), 1))
const fecha = ref(new Date(base.getFullYear(), base.getMonth(), base.getDate()))
const consecutivoDesde = ref(null)
const consecutivoHasta = ref(null)

const reporte = ref(null)

const reporteSel = computed(() => props.reportes.find((r) => r.id === reporte.value) || null)

// Variable del reporte elegido: mes | fecha | consecutivo.
const variable = computed(() => (reporteSel.value?.variable || '').toLowerCase())

function ymd(d) {
  if (!d) return ''
  const dt = d instanceof Date ? d : new Date(d)
  const mm = String(dt.getMonth() + 1).padStart(2, '0')
  const dd = String(dt.getDate()).padStart(2, '0')
  return `${dt.getFullYear()}-${mm}-${dd}`
}

function ym(d) {
  const dt = d instanceof Date ? d : new Date(d)
  return `${dt.getFullYear()}-${String(dt.getMonth() + 1).padStart(2, '0')}`
}

function generar() {
  if (!reporteSel.value) return
  const params = new URLSearchParams()
  if (variable.value === 'mes') {
    params.set('mes', ym(fechaMes.value))
  } else if (variable.value === 'fecha') {
    params.set('fecha', ymd(fecha.value))
  } else if (variable.value === 'consecutivo') {
    if (consecutivoDesde.value != null) params.set('consecutivo_desde', consecutivoDesde.value)
    if (consecutivoHasta.value != null) params.set('consecutivo_hasta', consecutivoHasta.value)
  }
  window.open(route('reportes.documentos.generar', reporteSel.value.id) + '?' + params.toString(), '_blank')
}
</script>

<template>
  <AppLayout :title="title">
    <div class="p-4 max-w-4xl">
      <h1 class="text-2xl font-bold mb-1">{{ title }}</h1>
      <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
        Reportes de cartas de porte y hojas de ruta. Elige el reporte; solo se
        activan las variables que usa (mes, fecha o rango de consecutivo).
      </p>

      <Card>
        <template #content>
          <div class="flex flex-col gap-4">
            <div class="flex flex-col gap-1">
              <label class="text-sm font-semibold">Reporte</label>
              <Select
                v-model="reporte"
                :options="reportes"
                optionLabel="nombre"
                optionValue="id"
                placeholder="Seleccione el reporte"
                class="w-full"
                filter
              />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="flex flex-col gap-1" :class="{ 'opacity-40 pointer-events-none': variable !== 'mes' }">
                <label class="text-sm font-semibold">Mes</label>
                <DatePicker
                  v-model="fechaMes"
                  view="month"
                  date-format="mm/yy"
                  class="w-full"
                  :disabled="variable !== 'mes'"
                />
              </div>

              <div class="flex flex-col gap-1" :class="{ 'opacity-40 pointer-events-none': variable !== 'fecha' }">
                <label class="text-sm font-semibold">Fecha</label>
                <DatePicker
                  v-model="fecha"
                  date-format="dd/mm/yy"
                  class="w-full"
                  :disabled="variable !== 'fecha'"
                />
              </div>

              <div class="flex flex-col gap-1" :class="{ 'opacity-40 pointer-events-none': variable !== 'consecutivo' }">
                <label class="text-sm font-semibold">Folio desde</label>
                <InputNumber
                  v-model="consecutivoDesde"
                  placeholder="Desde"
                  class="w-full"
                  :use-grouping="false"
                  :disabled="variable !== 'consecutivo'"
                />
              </div>

              <div class="flex flex-col gap-1" :class="{ 'opacity-40 pointer-events-none': variable !== 'consecutivo' }">
                <label class="text-sm font-semibold">Folio hasta</label>
                <InputNumber
                  v-model="consecutivoHasta"
                  placeholder="Hasta"
                  class="w-full"
                  :use-grouping="false"
                  :disabled="variable !== 'consecutivo'"
                />
              </div>
            </div>

            <div class="flex gap-2 pt-2">
              <Button
                label="Generar PDF"
                icon="pi pi-file-pdf"
                :disabled="!reporteSel"
                @click="generar"
              />
            </div>
          </div>
        </template>
      </Card>
    </div>
  </AppLayout>
</template>
