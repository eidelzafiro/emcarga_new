<script setup>
import { ref, computed, watch } from 'vue'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import DatePicker from 'primevue/datepicker'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Button from 'primevue/button'
import Card from 'primevue/card'

const props = defineProps({
  title: { type: String, default: 'Reportes' },
  // { agrupador: [ {id, nombre, variable, filtro, puede_excel, ...} ] }
  grupos: { type: Object, default: () => ({}) },
  opciones: { type: Object, default: () => ({}) },
  mesOperaciones: { type: String, default: '' },
})

function nombreAgrupador(tipo) {
  return String(tipo).replace(/^[0-9]+\./, '').trim()
}

const agrupadores = computed(() =>
  Object.keys(props.grupos)
    .map((t) => ({ key: t, label: nombreAgrupador(t) }))
    .sort((a, b) => a.label.localeCompare(b.label)),
)

const agrupadorSel = ref(agrupadores.value[0]?.key ?? null)
const reporteSel = ref(null)

const reportes = computed(() => props.grupos[agrupadorSel.value] ?? [])

watch(agrupadorSel, () => { reporteSel.value = null })

const reporteObj = computed(() => reportes.value.find((r) => r.id === reporteSel.value) || null)
const tipo = computed(() => (reporteObj.value?.filtro || 'directo').toLowerCase())

const opcionesValor = computed(() => {
  if (!reporteObj.value) return []
  const variable = (reporteObj.value.variable || '').toLowerCase()
  return props.opciones[tipo.value] || props.opciones[variable] || []
})
const esSelect = computed(() => opcionesValor.value.length > 0 && tipo.value !== 'directo')

const base = props.mesOperaciones ? new Date(props.mesOperaciones + 'T00:00:00') : new Date()
const fechaMes = ref(new Date(base.getFullYear(), base.getMonth(), 1))
const fecha = ref(new Date(base.getFullYear(), base.getMonth(), base.getDate()))
const desde = ref(null)
const hasta = ref(null)
const consecutivoDesde = ref(null)
const consecutivoHasta = ref(null)
const valor = ref(null)

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

function generar(formato = 'pdf') {
  if (!reporteObj.value) return

  // Reporte con ruta dedicada (listados de codificadores, resumen, etc.):
  // se abre directo, propagando las variables de mes/fecha si aplica.
  if (reporteObj.value.url) {
    const q = new URLSearchParams()
    if (tipo.value === 'mes') q.set('mes', ym(fechaMes.value))
    else if (tipo.value === 'fecha') q.set('fecha', ymd(fecha.value))
    const extra = q.toString()
    const sep = reporteObj.value.url.includes('?') ? '&' : '?'
    window.open(reporteObj.value.url + (extra ? sep + extra : ''), '_blank')
    return
  }

  const params = new URLSearchParams()
  if (tipo.value === 'mes') {
    params.set('filtros[mes]', ym(fechaMes.value))
  } else if (tipo.value === 'fecha') {
    params.set('filtros[fecha]', ymd(fecha.value))
  } else if (tipo.value === 'consecutivo') {
    if (consecutivoDesde.value != null) params.set('filtros[consecutivo_desde]', consecutivoDesde.value)
    if (consecutivoHasta.value != null) params.set('filtros[consecutivo_hasta]', consecutivoHasta.value)
  } else if (valor.value !== null && valor.value !== '') {
    const variable = (reporteObj.value.variable || '').toLowerCase()
    const clave = variable === 'tractivo2' ? 'tractivo'
      : variable === 'nombrecompleto2' ? 'nombrecompleto'
        : variable === '' ? 'valor' : variable
    params.set(`filtros[${clave}]`, valor.value)
  }
  if (desde.value) params.set('filtros[desde]', ymd(desde.value))
  if (hasta.value) params.set('filtros[hasta]', ymd(hasta.value))
  if (formato === 'excel') params.set('filtros[formato]', 'excel')

  window.open(route('reportes.generar', reporteObj.value.id) + '?' + params.toString(), '_blank')
}
</script>

<template>
  <AppLayout :title="title">
    <div class="p-4 max-w-4xl">
      <h1 class="text-2xl font-bold mb-1">Reportes</h1>
      <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
        Seleccione el agrupador y el reporte; solo se activan las variables que
        ese reporte necesita.
      </p>

      <Card>
        <template #content>
          <div class="flex flex-col gap-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="flex flex-col gap-1">
                <label class="text-sm font-semibold">Agrupador</label>
                <Select
                  v-model="agrupadorSel"
                  :options="agrupadores"
                  optionLabel="label"
                  optionValue="key"
                  placeholder="Seleccione el agrupador"
                  class="w-full"
                  filter
                />
              </div>

              <div class="flex flex-col gap-1">
                <label class="text-sm font-semibold">Reporte</label>
                <Select
                  v-model="reporteSel"
                  :options="reportes"
                  optionLabel="nombre"
                  optionValue="id"
                  placeholder="Seleccione el reporte"
                  class="w-full"
                  filter
                  :disabled="!agrupadorSel"
                />
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="flex flex-col gap-1" :class="{ 'opacity-40 pointer-events-none': tipo !== 'mes' }">
                <label class="text-sm font-semibold">Mes</label>
                <DatePicker v-model="fechaMes" view="month" date-format="mm/yy" class="w-full" :disabled="tipo !== 'mes'" />
              </div>

              <div class="flex flex-col gap-1" :class="{ 'opacity-40 pointer-events-none': tipo !== 'fecha' }">
                <label class="text-sm font-semibold">Fecha</label>
                <DatePicker v-model="fecha" date-format="dd/mm/yy" class="w-full" :disabled="tipo !== 'fecha'" />
              </div>

              <div class="flex flex-col gap-1" :class="{ 'opacity-40 pointer-events-none': tipo !== 'consecutivo' }">
                <label class="text-sm font-semibold">Folio desde</label>
                <InputNumber v-model="consecutivoDesde" placeholder="Desde" class="w-full" :use-grouping="false" :disabled="tipo !== 'consecutivo'" />
              </div>

              <div class="flex flex-col gap-1" :class="{ 'opacity-40 pointer-events-none': tipo !== 'consecutivo' }">
                <label class="text-sm font-semibold">Folio hasta</label>
                <InputNumber v-model="consecutivoHasta" placeholder="Hasta" class="w-full" :use-grouping="false" :disabled="tipo !== 'consecutivo'" />
              </div>

              <div
                class="flex flex-col gap-1 md:col-span-2"
                :class="{ 'opacity-40 pointer-events-none': ['mes', 'fecha', 'consecutivo'].includes(tipo) }"
              >
                <label class="text-sm font-semibold">
                  {{ reporteObj?.variable ? `Valor (${reporteObj.variable})` : 'Valor' }}
                </label>
                <Select
                  v-if="esSelect"
                  v-model="valor"
                  :options="opcionesValor"
                  optionLabel="label"
                  optionValue="id"
                  placeholder="Seleccione..."
                  class="w-full"
                  filter
                  :disabled="['mes', 'fecha', 'consecutivo'].includes(tipo)"
                />
                <InputText
                  v-else
                  v-model="valor"
                  placeholder="Escriba el valor..."
                  class="w-full"
                  :disabled="['mes', 'fecha', 'consecutivo'].includes(tipo)"
                />
              </div>

              <div class="flex flex-col gap-1">
                <label class="text-sm font-semibold">Desde (opcional)</label>
                <DatePicker v-model="desde" date-format="dd/mm/yy" class="w-full" showButtonBar />
              </div>
              <div class="flex flex-col gap-1">
                <label class="text-sm font-semibold">Hasta (opcional)</label>
                <DatePicker v-model="hasta" date-format="dd/mm/yy" class="w-full" showButtonBar />
              </div>
            </div>

            <div class="flex gap-2 pt-2">
              <Button label="PDF" icon="pi pi-file-pdf" :disabled="!reporteObj" @click="generar('pdf')" />
              <Button
                label="Excel"
                icon="pi pi-file-excel"
                severity="success"
                :disabled="!reporteObj || !reporteObj.puede_excel"
                @click="generar('excel')"
              />
            </div>
          </div>
        </template>
      </Card>
    </div>
  </AppLayout>
</template>
