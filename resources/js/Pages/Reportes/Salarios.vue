<script setup>
import { ref, computed } from 'vue'
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
})

const fechaMes = ref(new Date(props.ano, props.mes - 1, 1))
const tipoPrenomina = ref('choferes')
const choferId = ref(null)

const mes = computed(() => fechaMes.value.getMonth() + 1)
const ano = computed(() => fechaMes.value.getFullYear())

const tipos = [
  { id: 'choferes', nombre: 'Salario Choferes' },
  { id: 'administrativo', nombre: 'Salario Administrativo' },
]

// Reportes de nómina RRHH (rutas reportes.*), todos anclados al mes seleccionado.
const reportesNomina = [
  { ruta: 'reportes.adicionales', nombre: 'Datos p/Nóminas (Adicionales)', icono: 'pi pi-file-pdf' },
  { ruta: 'reportes.nocturnidad', nombre: 'Datos p/Nóminas (Nocturnidad)', icono: 'pi pi-file-pdf' },
  { ruta: 'reportes.pago-administrativo', nombre: 'Datos p/Nóminas Pago Administrativo Holguín', icono: 'pi pi-file-pdf' },
  { ruta: 'reportes.resumen-tiempos-choferes', nombre: 'Resumen de los Tiempos Choferes', icono: 'pi pi-file-pdf' },
  { ruta: 'reportes.analisis-salario-transportacion', nombre: 'Análisis del Salario Transportación', icono: 'pi pi-file-pdf' },
  { ruta: 'reportes.control-diario-administrativo', nombre: 'SC-4-05 Control Diario (Administrativo)', icono: 'pi pi-file-pdf' },
  { ruta: 'reportes.control-diario-choferes', nombre: 'SC-4-05 Control Diario Choferes', icono: 'pi pi-file-pdf' },
  { ruta: 'reportes.incidencias', nombre: 'Prenómina Incidencias al Tiempo Trabajado', icono: 'pi pi-file-pdf', extra: true },
  { ruta: 'reportes.cumpleanos', nombre: 'Listado de Cumpleaños del Mes', icono: 'pi pi-file-pdf' },
  { ruta: 'reportes.licencia-conduccion', nombre: 'Personal con Licencia de Conducción', icono: 'pi pi-file-pdf' },
]

function abrir(nombreRuta, extra = {}) {
  const params = { mes: mes.value, ano: ano.value, ...extra }
  const url = route(nombreRuta) + '?' + new URLSearchParams(params).toString()
  window.open(url, '_blank')
}

function generarPrenomina(formato) {
  const sufijo = tipoPrenomina.value === 'administrativo' ? '-administrativo' : '-choferes'
  const nombreRuta = formato === 'pdf'
    ? `reportes.prenomina${sufijo}`
    : `reportes.prenomina${sufijo}-excel`
  abrir(nombreRuta)
}

function generarModelo1(formato) {
  const nombreRuta = formato === 'pdf' ? 'reportes.modelo1' : 'reportes.modelo1-excel'
  const extra = choferId.value ? { id_bolsa: choferId.value } : {}
  abrir(nombreRuta, extra)
}

const tipoIncidencia = ref(props.tiposIncidencia?.[0]?.origen_id ?? 1)

function generarReporteNomina(r) {
  const extra = r.extra ? { tipo_incidencia: tipoIncidencia.value } : {}
  abrir(r.ruta, extra)
}
</script>

<template>
  <AppLayout :title="title">
    <div class="p-4">
      <h1 class="text-2xl font-bold mb-1">{{ title }}</h1>
      <p class="text-sm text-gray-500 mb-4">
        Selecciona el mes de operaciones para emitir las prenóminas de salario
        (choferes o administrativo) y el Modelo 1 de control diario.
      </p>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 max-w-4xl">
        <Card>
          <template #content>
            <div class="flex flex-col gap-4">
              <div class="flex flex-col gap-1">
                <label class="text-sm font-semibold">Mes de operaciones</label>
                <DatePicker
                  v-model="fechaMes"
                  view="month"
                  date-format="mm/yy"
                  input-id="salarios-mes"
                />
              </div>

              <div class="flex flex-col gap-1">
                <label class="text-sm font-semibold">Tipo de prenómina</label>
                <Select
                  v-model="tipoPrenomina"
                  :options="tipos"
                  option-label="nombre"
                  option-value="id"
                  class="w-full"
                />
              </div>

              <div class="flex gap-2">
                <Button label="PDF" icon="pi pi-file-pdf" @click="generarPrenomina('pdf')" />
                <Button label="Excel" icon="pi pi-file-excel" severity="success" @click="generarPrenomina('excel')" />
              </div>
            </div>
          </template>
        </Card>

        <Card>
          <template #content>
            <div class="flex flex-col gap-4">
              <h2 class="font-semibold">Modelo 1 - Control diario</h2>
              <p class="text-xs text-gray-500">
                Emite el Modelo 1 de un chofer con transportaciones en el mes, o
                de todos los choferes del mes.
              </p>

              <div class="flex flex-col gap-1">
                <label class="text-sm font-semibold">Chofer</label>
                <Select
                  v-model="choferId"
                  :options="choferes"
                  option-label="nombre"
                  option-value="id"
                  placeholder="Todos los choferes"
                  filter
                  show-clear
                  class="w-full"
                />
                <span class="text-xs text-gray-500">Opcional: deja vacío para todos los choferes del mes.</span>
              </div>

              <div class="flex gap-2">
                <Button label="PDF" icon="pi pi-file-pdf" @click="generarModelo1('pdf')" />
                <Button label="Excel" icon="pi pi-file-excel" severity="success" @click="generarModelo1('excel')" />
              </div>
            </div>
          </template>
        </Card>
      </div>

      <!-- Reportes de nómina RRHH -->
      <Card class="mt-4">
        <template #content>
          <div class="flex flex-col gap-4">
            <div>
              <h2 class="font-semibold">Reportes de Nómina</h2>
              <p class="text-xs text-gray-500">
                Reportes de Recursos Humanos del mes seleccionado (entidad activa).
                El mes sale del selector superior.
              </p>
            </div>

            <div v-if="tipoPrenomina === 'choferes'" class="flex flex-col gap-1 max-w-xs">
              <label class="text-sm font-semibold">Tipo de incidencia (para el reporte de incidencias)</label>
              <Select v-model="tipoIncidencia" :options="tiposIncidencia" option-label="nombre" option-value="origen_id" class="w-full" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-2">
              <Button
                v-for="r in reportesNomina"
                :key="r.ruta"
                :label="r.nombre"
                :icon="r.icono"
                severity="secondary"
                outlined
                class="text-left whitespace-nowrap overflow-hidden"
                @click="generarReporteNomina(r)"
              />
            </div>
          </div>
        </template>
      </Card>
    </div>
  </AppLayout>
</template>
