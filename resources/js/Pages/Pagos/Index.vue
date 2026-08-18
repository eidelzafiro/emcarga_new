<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ pagos: Object, tiposDocumento: Array, monedas: Array, filters: Object })
const toast = useToast()
const search = ref(props.filters?.search || '')
const estado = ref(props.filters?.estado || null)
const showForm = ref(false)
const editing = ref(null)
const form = ref({ id_tipo_documento: null, id_moneda: null, fecha_pago: null, numero_documento: '', monto: null, concepto: '', estado: 'pendiente' })
const title = 'Pagos'
const estados = [
  { label: 'Pendiente', value: 'pendiente' },
  { label: 'Aprobado', value: 'aprobado' },
  { label: 'Rechazado', value: 'rechazado' },
]

function cargar() {
  router.get(route('pagos.index'), { search: search.value, estado: estado.value || undefined }, { preserveState: true, replace: true })
}

watch(search, cargar)
watch(estado, cargar)

function onPage(event) {
  router.get(route('pagos.index'), { page: event.page + 1, search: search.value, estado: estado.value || undefined }, { preserveState: true, replace: true })
}

function openCreate() {
  editing.value = null
  form.value = { id_tipo_documento: null, id_moneda: null, fecha_pago: null, numero_documento: '', monto: null, concepto: '', estado: 'pendiente' }
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  form.value = {
    id_tipo_documento: item.id_tipo_documento,
    id_moneda: item.id_moneda,
    fecha_pago: item.fecha_pago ? item.fecha_pago.slice(0, 10) : null,
    numero_documento: item.numero_documento || '',
    monto: item.monto !== null ? Number(item.monto) : null,
    concepto: item.concepto || '',
    estado: item.estado,
  }
  showForm.value = true
}

function submit() {
  const url = editing.value ? route('pagos.update', editing.value.id) : route('pagos.store')
  const method = editing.value ? 'put' : 'post'
  router[method](url, form.value, {
    onSuccess: () => { showForm.value = false; toast.add({ severity: 'success', summary: editing.value ? 'Actualizado' : 'Creado', life: 3000 }) },
    onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
  })
}

function estadoBadge(e) {
  if (e === 'aprobado') return 'success'
  if (e === 'rechazado') return 'danger'
  return 'warn'
}
</script>

<template>
  <AppLayout :title="title">
    <div class="card">
      <Toolbar class="mb-4">
        <template #start>
          <Button label="Nuevo" icon="pi pi-plus" severity="success" @click="openCreate" />
        </template>
        <template #end>
          <div class="flex items-center gap-2 flex-wrap">
            <Select v-model="estado" :options="estados" optionLabel="label" optionValue="value" placeholder="Estado" class="w-44" :showClear="true" />
            <InputText v-model="search" placeholder="Buscar..." />
          </div>
        </template>
      </Toolbar>

      <DataTable :value="pagos.data" striped-rows paginator :rows="20" :total-records="pagos.total" :lazy="true" :first="(pagos.current_page - 1) * pagos.per_page" @page="onPage" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros">
        <Column header="Fecha" :style="{ width: '130px' }">
          <template #body="{ data }">{{ data.fecha_pago }}</template>
        </Column>
        <Column header="Tipo Documento">
          <template #body="{ data }">{{ data.tipo_documento?.nombre || '—' }}</template>
        </Column>
        <Column field="numero_documento" header="Nº Documento" />
        <Column header="Monto">
          <template #body="{ data }">
            <span class="font-semibold">{{ Number(data.monto).toFixed(2) }} {{ data.moneda?.codigo || '' }}</span>
          </template>
        </Column>
        <Column field="concepto" header="Concepto" />
        <Column header="Estado" :style="{ width: '120px' }">
          <template #body="{ data }">
            <span class="inline-flex items-center gap-1">
              <i :class="['pi text-xs', { 'pi-check-circle text-green-600': data.estado === 'aprobado', 'pi-times-circle text-red-500': data.estado === 'rechazado', 'pi-clock text-amber-500': data.estado === 'pendiente' }]" />
              <span class="capitalize">{{ data.estado }}</span>
            </span>
          </template>
        </Column>
        <Column header="Acciones" style="width: 120px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
              <Button icon="pi pi-trash" rounded text severity="danger" @click="router.delete(route('pagos.destroy', data.id))" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar Pago' : 'Nuevo Pago'" modal style="width: 600px">
      <form @submit.prevent="submit" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block mb-1 font-medium">Fecha de Pago</label>
            <InputText v-model="form.fecha_pago" type="date" class="w-full" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Monto</label>
            <InputNumber v-model="form.monto" :min="0" :max-fraction-digits="2" class="w-full" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Tipo de Documento</label>
            <Select v-model="form.id_tipo_documento" :options="tiposDocumento" optionLabel="nombre" optionValue="id" filter placeholder="Seleccione..." class="w-full" :showClear="true" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Moneda</label>
            <Select v-model="form.id_moneda" :options="monedas" optionLabel="codigo" optionValue="id" filter placeholder="Seleccione..." class="w-full" :showClear="true" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Nº Documento</label>
            <InputText v-model="form.numero_documento" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Estado</label>
            <Select v-model="form.estado" :options="estados" optionLabel="label" optionValue="value" class="w-full" required />
          </div>
        </div>
        <div>
          <label class="block mb-1 font-medium">Concepto</label>
          <InputText v-model="form.concepto" class="w-full" />
        </div>
        <div class="flex gap-2 justify-end">
          <Button label="Cancelar" severity="secondary" @click="showForm = false" />
          <Button label="Guardar" type="submit" icon="pi pi-save" />
        </div>
      </form>
    </Dialog>
  </AppLayout>
</template>
