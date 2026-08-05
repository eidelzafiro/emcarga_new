<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ items: Object, empleados: Array, tiposIncidencias: Array, filters: Object })
const toast = useToast()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)

const baseForm = () => ({
  id_bolsa: null, id_tipo_incidencia: null, fecha_inicio: null,
  fecha_fin: null, periodo_actual: null, importe: 0,
})

const form = ref(baseForm())

const empleadoOptions = computed(() => props.empleados?.map(e => ({ value: e.id, label: e.nombrecompleto })) || [])
const tipoOptions = computed(() => props.tiposIncidencias?.map(t => ({ value: t.id, label: t.nombre })) || [])

watch(search, () => {
  router.get(route('incidencias.index'), { search: search.value }, { preserveState: true, replace: true })
})

const onPage = (event) => {
  router.get(route('incidencias.index'), { page: event.page + 1, search: search.value }, { preserveState: true, replace: true })
}

function calcularImporte() {
  const pactual = parseFloat(form.value.periodo_actual) || 0
  const tarifa = parseFloat(form.value.tarifa) || 0
  form.value.importe = Math.round((tarifa * pactual) * 100) / 100
}

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

const activosCount = computed(() => {
  if (!props.items?.data) return null
  return props.items.data.filter(i => i.bolsa?.activo !== false).length
})
</script>

<template>
  <AppLayout title="Incidencias">
    <div class="card">
      <Toolbar class="mb-4">
        <template #start>
          <Button label="Nuevo" icon="pi pi-plus" severity="success" @click="openCreate" />
          <span v-if="items.total !== undefined" class="ml-3 text-xs text-gray-500">
            {{ items.total }} registros · {{ activosCount }} activos
          </span>
        </template>
        <template #end>
          <InputText v-model="search" placeholder="Buscar..." />
        </template>
      </Toolbar>

      <DataTable :value="items.data" striped-rows paginator :rows="20" :total-records="items.total"
        :lazy="true" :first="(items.current_page - 1) * items.per_page" @page="onPage" class="text-sm" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros">
        <Column field="bolsa.nombrecompleto" header="Empleado" sortable />
        <Column field="tipo_incidencia.nombre" header="Incidencia" />
        <Column field="fecha_inicio" header="Inicio" />
        <Column field="fecha_fin" header="Final" />
        <Column field="periodo_actual" header="Período" />
        <Column field="importe" header="Importe">
          <template #body="{ data }">${{ parseFloat(data.importe).toFixed(2) }}</template>
        </Column>
        <Column header="Acciones" style="width:100px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
              <Button icon="pi pi-trash" rounded text severity="danger"
                @click="router.delete(route('incidencias.destroy', { incidencia: data.id }))" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar Incidencia' : 'Nueva Incidencia'" modal style="width:550px">
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block mb-1 font-medium">Empleado</label>
          <Select v-model="form.id_bolsa" :options="empleadoOptions" optionLabel="label" optionValue="value" placeholder="Seleccione..." class="w-full" required filter :filterFields="['label']" />
        </div>
        <div>
          <label class="block mb-1 font-medium">Clave / Tipo Incidencia</label>
          <Select v-model="form.id_tipo_incidencia" :options="tipoOptions" optionLabel="label" optionValue="value" placeholder="Seleccione..." class="w-full" required filter />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block mb-1 font-medium">Fecha Inicial</label>
            <DatePicker v-model="form.fecha_inicio" dateFormat="yy/mm/dd" class="w-full" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Fecha Final</label>
            <DatePicker v-model="form.fecha_fin" dateFormat="yy/mm/dd" class="w-full" />
          </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block mb-1 font-medium">Período Actual</label>
            <InputNumber v-model="form.periodo_actual" class="w-full" :minFractionDigits="2" :maxFractionDigits="2" required @keyup.enter="calcularImporte" />
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
