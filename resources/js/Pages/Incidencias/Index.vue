<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
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

const props = defineProps({ items: Array, agrupadas: Array, empleados: Array, tiposIncidencias: Array, filters: Object, fechaOperaciones: String })
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
  id_bolsa: null, id_tipo_incidencia: null,
  fecha_inicio: new Date(fechaOps.value),
  fecha_fin: null, periodo_actual: null, importe: 0,
})

const form = ref(baseForm())

const empleadoOptions = computed(() => props.empleados?.map(e => ({ value: e.id, label: e.nombrecompleto })) || [])
const tipoOptions = computed(() => props.tiposIncidencias?.map(t => ({ value: t.id, label: t.nombre })) || [])

function formatDate(d) {
  if (!d) return '—'
  const date = new Date(d)
  if (isNaN(date)) return '—'
  return date.toLocaleDateString('es-CU', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

watch(search, () => {
  router.get(route('incidencias.index'), { search: search.value }, { preserveState: true, replace: true })
})

function openCreate() {
  editing.value = null
  form.value = { ...baseForm() }
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  form.value = {
    id_bolsa: item.id_bolsa,
    id_tipo_incidencia: item.id_tipo_incidencia,
    fecha_inicio: item.fecha_inicio ? new Date(item.fecha_inicio) : null,
    fecha_fin: item.fecha_fin ? new Date(item.fecha_fin) : null,
    periodo_actual: item.periodo_actual,
    importe: item.importe,
  }
  showForm.value = true
}

function submit() {
  const payload = { ...form.value }
  payload.fecha_inicio = payload.fecha_inicio ? new Date(payload.fecha_inicio).toISOString().split('T')[0] : null
  payload.fecha_fin = payload.fecha_fin ? new Date(payload.fecha_fin).toISOString().split('T')[0] : null

  const url = editing.value ? route('incidencias.update', { incidencia: editing.value.id }) : route('incidencias.store')
  router[editing.value ? 'put' : 'post'](url, payload, {
    onSuccess: () => {
      toast.add({ severity: 'success', summary: editing.value ? 'Actualizado' : 'Creado', life: 3000 })
      showForm.value = false
    },
    onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
  })
}

const totalIncidencias = computed(() => props.agrupadas?.reduce((s, g) => s + g.total, 0) || 0)
</script>

<template>
  <AppLayout title="Incidencias">
    <div class="card">
      <Toolbar class="mb-4">
        <template #start>
          <Button label="Nueva" icon="pi pi-plus" severity="success" @click="openCreate" />
          <span class="ml-3 text-sm text-gray-600 dark:text-gray-400 font-semibold">
            {{ mesAnio }} · {{ totalIncidencias }} incidencias
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
                <i class="pi pi-tag text-blue-500"></i>
                <span class="font-bold text-blue-800 dark:text-blue-300">{{ grupo.tipo }}</span>
                <span class="ml-auto text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300 font-mono">
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
                      <th class="text-left py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">Inicio</th>
                      <th class="text-left py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">Fin</th>
                      <th class="text-right py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">Periodo</th>
                      <th class="text-right py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">Importe</th>
                      <th class="text-right py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">Acciones</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="item in grupo.items" :key="item.id" class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                      <td class="py-2 px-3">{{ item.bolsa?.nombrecompleto }}</td>
                      <td class="py-2 px-3">{{ formatDate(item.fecha_inicio) }}</td>
                      <td class="py-2 px-3">{{ formatDate(item.fecha_fin) }}</td>
                      <td class="py-2 px-3 text-right font-mono">{{ item.periodo_actual }}</td>
                      <td class="py-2 px-3 text-right font-mono">{{ parseFloat(item.importe).toFixed(2) }}</td>
                      <td class="py-2 px-3 text-right">
                        <div class="flex gap-1 justify-end">
                          <Button icon="pi pi-pencil" rounded text size="small" severity="info" @click="openEdit(item)" />
                          <Button icon="pi pi-trash" rounded text size="small" severity="danger"
                            @click="router.delete(route('incidencias.destroy', { incidencia: item.id }))" />
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
        <p class="text-gray-500 dark:text-gray-400">No hay incidencias para este mes</p>
      </div>
    </div>

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar Incidencia' : 'Nueva Incidencia'" modal style="width:550px">
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block mb-1 font-medium">Empleado</label>
          <Select v-model="form.id_bolsa" :options="empleadoOptions" optionLabel="label" optionValue="value" placeholder="Seleccione..." class="w-full" required filter :filterFields="['label']" />
        </div>
        <div>
          <label class="block mb-1 font-medium">Tipo de Incidencia</label>
          <Select v-model="form.id_tipo_incidencia" :options="tipoOptions" optionLabel="label" optionValue="value" placeholder="Seleccione..." class="w-full" required filter />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block mb-1 font-medium">Fecha Inicial</label>
            <DatePicker v-model="form.fecha_inicio" dateFormat="dd/mm/yy" class="w-full" :minDate="primerDiaMes" :maxDate="ultimoDiaMes" :month-navigator="false" :year-navigator="false" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Fecha Final</label>
            <DatePicker v-model="form.fecha_fin" dateFormat="dd/mm/yy" class="w-full" :minDate="primerDiaMes" :maxDate="ultimoDiaMes" :month-navigator="false" :year-navigator="false" />
          </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block mb-1 font-medium">Periodo Actual</label>
            <InputNumber v-model="form.periodo_actual" class="w-full" :minFractionDigits="2" :maxFractionDigits="2" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Importe</label>
            <InputNumber v-model="form.importe" class="w-full" :minFractionDigits="2" :maxFractionDigits="2" required />
          </div>
        </div>
        <div class="flex gap-2 justify-end">
          <Button label="Cancelar" severity="secondary" @click="showForm = false" />
          <Button label="Guardar" type="submit" icon="pi pi-save" />
        </div>
      </form>
    </Dialog>
  </AppLayout>
</template>
