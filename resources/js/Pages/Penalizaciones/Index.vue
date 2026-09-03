<script setup>
import { ref, computed, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import Accordion from 'primevue/accordion'
import AccordionPanel from 'primevue/accordionpanel'
import AccordionHeader from 'primevue/accordionheader'
import AccordionContent from 'primevue/accordioncontent'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ items: Array, agrupadas: Array, empleados: Array, tiposPenalizaciones: Array, areas: Array, pagosAdicionales: Array, filters: Object, fechaOperaciones: String })
const toast = useToast()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)

const fechaOps = computed(() => props.fechaOperaciones ? new Date(props.fechaOperaciones + 'T00:00:00') : new Date())
const primerDiaMes = computed(() => {
  const f = new Date(fechaOps.value)
  f.setDate(1)
  f.setHours(0, 0, 0, 0)
  return f
})
const ultimoDiaMes = computed(() => {
  const f = new Date(fechaOps.value)
  f.setMonth(f.getMonth() + 1, 0)
  f.setHours(23, 59, 59, 999)
  return f
})
const mesAnio = computed(() => {
  return fechaOps.value.toLocaleDateString('es-CU', { month: 'long', year: 'numeric' })
})

const baseForm = () => ({
  id_bolsa: null, id_tipo_penalizacion: null, id_area_penalizada: null, id_pago_adicional: null,
  fecha: new Date(fechaOps.value), importe: 0,
})

const form = ref(baseForm())

// Estado para filtros en cascada
const empleadoSeleccionado = ref(null)
const tiposFiltrados = ref([])
const pagosFiltrados = ref([])

const empleadoOptions = computed(() => props.empleados?.map(e => ({
  value: e.id,
  label: e.nombrecompleto,
  id_area: e.id_area,
  area_nombre: e.area?.nombre || '',
  tipo_salario: e.cargo?.tipo_salario,
})) || [])

const tipoOptions = computed(() => {
  const tipos = editing.value ? props.tiposPenalizaciones : tiposFiltrados.value
  return tipos?.map(t => {
    const label = t.porcentaje ? `${t.nombre} (${t.porcentaje}%)` : t.nombre
    return { value: t.id, label, porcentaje: t.porcentaje }
  }) || []
})

const areaOptions = computed(() => {
  if (!empleadoSeleccionado.value) return []
  return props.areas?.filter(a => {
    // Mostrar solo áreas de la misma entidad que el empleado
    return true // Se filtra por el backend
  }).map(a => ({ value: a.id, label: a.nombre })) || []
})

const pagoOptions = computed(() => {
  const pagos = editing.value ? props.pagosAdicionales : pagosFiltrados.value
  return pagos?.map(p => ({ value: p.id, label: p.nombre })) || []
})

function formatDate(d) {
  if (!d) return '—'
  const date = new Date(d)
  if (isNaN(date)) return '—'
  return date.toLocaleDateString('es-CU', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

watch(search, () => {
  router.get(route('penalizaciones.index'), { search: search.value }, { preserveState: true, replace: true })
})

// Cuando se selecciona un empleado, cargar sus datos en cascada
async function onEmpleadoSelect(event) {
  if (!event.value) {
    empleadoSeleccionado.value = null
    tiposFiltrados.value = []
    pagosFiltrados.value = []
    form.value.id_tipo_penalizacion = null
    form.value.id_pago_adicional = null
    form.value.id_area_penalizada = null
    form.value.importe = 0
    return
  }

  try {
    const params = new URLSearchParams({ id_bolsa: event.value })
    const response = await fetch(route('penalizaciones.obtener-empleado') + '?' + params.toString())
    const data = await response.json()
    empleadoSeleccionado.value = data.empleado
    tiposFiltrados.value = data.tiposPenalizaciones || []
    pagosFiltrados.value = data.pagosAdicionales || []

    // Auto-asignar área del empleado
    form.value.id_area_penalizada = data.empleado.id_area || null

    // Reset dependientes
    form.value.id_tipo_penalizacion = null
    form.value.id_pago_adicional = null
    form.value.importe = 0
  } catch (error) {
    console.error('Error cargando empleado:', error)
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo cargar la información del empleado', life: 3000 })
  }
}

function onTipoSelect(event) {
  if (event.value) {
    const tipo = tipoOptions.value.find(t => t.value === event.value)
    if (tipo?.porcentaje) form.value.importe = tipo.porcentaje

    // Auto-asignar pago adicional desde el tipo seleccionado
    const tipoCompleto = tiposFiltrados.value.find(t => t.id === event.value)
    if (tipoCompleto?.tipo_pago_adicional_id) {
      form.value.id_pago_adicional = tipoCompleto.tipo_pago_adicional_id
    }
  }
}

function openCreate() {
  editing.value = null
  form.value = { ...baseForm() }
  empleadoSeleccionado.value = null
  tiposFiltrados.value = props.tiposPenalizaciones || []
  pagosFiltrados.value = props.pagosAdicionales || []
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  form.value = {
    id_bolsa: item.id_bolsa,
    id_tipo_penalizacion: item.id_tipo_penalizacion,
    id_area_penalizada: item.id_area_penalizada || null,
    id_pago_adicional: item.id_pago_adicional || null,
    fecha: item.fecha ? new Date(item.fecha) : null,
    importe: item.importe,
  }
  // En edición, cargar todos los tipos y pagos
  tiposFiltrados.value = props.tiposPenalizaciones || []
  pagosFiltrados.value = props.pagosAdicionales || []
  showForm.value = true
}

function submit() {
  const payload = { ...form.value }
  payload.fecha = payload.fecha ? new Date(payload.fecha).toISOString().split('T')[0] : null

  const url = editing.value ? route('penalizaciones.update', { penalizacione: editing.value.id }) : route('penalizaciones.store')
  router[editing.value ? 'put' : 'post'](url, payload, {
    onSuccess: () => {
      toast.add({ severity: 'success', summary: editing.value ? 'Actualizado' : 'Creado', life: 3000 })
      showForm.value = false
    },
    onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
  })
}

const totalPenalizaciones = computed(() => props.agrupadas?.reduce((s, g) => s + g.total, 0) || 0)
</script>

<template>
  <AppLayout title="Penalizaciones">
    <div class="card">
      <Toolbar class="mb-4">
        <template #start>
          <Button label="Nueva" icon="pi pi-plus" severity="success" @click="openCreate" />
          <span class="ml-3 text-sm text-gray-600 dark:text-gray-400 font-semibold">
            {{ mesAnio }} · {{ totalPenalizaciones }} penalizaciones
          </span>
        </template>
        <template #end>
          <InputText v-model="search" placeholder="Buscar empleado o tipo..." />
        </template>
      </Toolbar>

      <div v-if="agrupadas && agrupadas.length">
        <Accordion :multiple="true" :activeIndex="[0]">
          <AccordionPanel v-for="grupo in agrupadas" :key="grupo.tipo" :value="grupo.tipo">
            <AccordionHeader>
              <div class="flex items-center gap-3 w-full">
                <i class="pi pi-exclamation-triangle text-amber-500"></i>
                <span class="font-bold text-amber-800 dark:text-amber-300">{{ grupo.tipo }}</span>
                <span class="ml-auto text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300 font-mono">
                  {{ grupo.total }}
                </span>
              </div>
            </AccordionHeader>
            <AccordionContent>
              <div class="overflow-x-auto">
                <table class="w-full text-sm">
                  <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                      <th class="text-left py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">Empleado</th>
                      <th class="text-left py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">Area Penalizada</th>
                      <th class="text-left py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">Pago Adicional</th>
                      <th class="text-left py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">Fecha</th>
                      <th class="text-right py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">% Penalización</th>
                      <th class="text-right py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">Acciones</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="item in grupo.items" :key="item.id" class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                      <td class="py-2 px-3">{{ item.bolsa?.nombrecompleto }}</td>
                      <td class="py-2 px-3">{{ item.area_penalizada?.nombre || '—' }}</td>
                      <td class="py-2 px-3">{{ item.pago_adicional?.nombre || '—' }}</td>
                      <td class="py-2 px-3">{{ formatDate(item.fecha) }}</td>
                      <td class="py-2 px-3 text-right font-mono">{{ item.importe }}%</td>
                      <td class="py-2 px-3 text-right">
                        <div class="flex gap-1 justify-end">
                          <Button icon="pi pi-pencil" rounded text size="small" severity="info" @click="openEdit(item)" />
                          <Button icon="pi pi-trash" rounded text size="small" severity="danger"
                            @click="router.delete(route('penalizaciones.destroy', { penalizacione: item.id }))" />
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </AccordionContent>
          </AccordionPanel>
        </Accordion>
      </div>
      <div v-else class="text-center py-12">
        <i class="pi pi-inbox text-4xl text-gray-300 dark:text-gray-600 block mb-3" />
        <p class="text-gray-500 dark:text-gray-400">No hay penalizaciones para este mes</p>
      </div>
    </div>

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar Penalización' : 'Nueva Penalización'" modal style="width:550px">
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block mb-1 font-medium">Fecha</label>
          <DatePicker v-model="form.fecha" dateFormat="dd/mm/yy" class="w-full" :minDate="primerDiaMes" :maxDate="ultimoDiaMes" :month-navigator="false" :year-navigator="false" required />
        </div>
        <div>
          <label class="block mb-1 font-medium">Empleado</label>
          <Select v-model="form.id_bolsa" :options="empleadoOptions" optionLabel="label" optionValue="value" placeholder="Seleccione..." class="w-full" required filter :filterFields="['label']" @change="onEmpleadoSelect" />
        </div>
        <div v-if="empleadoSeleccionado" class="p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 text-sm">
          <div class="flex items-center gap-2 text-blue-700 dark:text-blue-300">
            <i class="pi pi-user"></i>
            <span class="font-semibold">{{ empleadoSeleccionado.nombre }}</span>
          </div>
          <div class="mt-1 text-blue-600 dark:text-blue-400 text-xs">
            Area: {{ empleadoSeleccionado.area || '—' }} · Cargo: {{ empleadoSeleccionado.cargo || '—' }}
          </div>
        </div>
        <div>
          <label class="block mb-1 font-medium">Tipo de Penalización</label>
          <Select v-model="form.id_tipo_penalizacion" :options="tipoOptions" optionLabel="label" optionValue="value" placeholder="Seleccione..." class="w-full" required filter @change="onTipoSelect" :disabled="!form.id_bolsa" />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block mb-1 font-medium">Area Penalizada</label>
            <Select v-model="form.id_area_penalizada" :options="areaOptions" optionLabel="label" optionValue="value" placeholder="Seleccione..." class="w-full" filter showClear :disabled="!form.id_bolsa" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Pago Adicional</label>
            <Select v-model="form.id_pago_adicional" :options="pagoOptions" optionLabel="label" optionValue="value" placeholder="Seleccione..." class="w-full" filter showClear :disabled="!form.id_bolsa" />
          </div>
        </div>
        <div>
          <label class="block mb-1 font-medium">% Penalización</label>
          <InputNumber v-model="form.importe" class="w-full" :minFractionDigits="2" :maxFractionDigits="2" required suffix="%" />
        </div>
        <div class="flex gap-2 justify-end">
          <Button label="Cancelar" severity="secondary" @click="showForm = false" />
          <Button label="Guardar" type="submit" icon="pi pi-save" />
        </div>
      </form>
    </Dialog>
  </AppLayout>
</template>
