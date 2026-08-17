<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import InputNumber from 'primevue/inputnumber'
import DatePicker from 'primevue/datepicker'
import ToggleSwitch from 'primevue/toggleswitch'
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import { useConfirm } from 'primevue/useconfirm'

const props = defineProps({ tarjetas: Object, filters: Object, filtros: Object, tiposCombustibles: Array, monedas: Array })
const confirmDialog = useConfirm()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)

const estados = ['activa', 'inactiva', 'cancelada']

const baseForm = () => ({
  numero: '',
  descripcion: '',
  idmonedas: null,
  idtipocombustibles: null,
  idempleado: null,
  idtractivos: null,
  id_entidad: null,
  saldo_actual: null,
  fcompra: null,
  fvence: null,
  inactiva: false,
  estado: 'activa',
})

const form = ref(baseForm())

watch(search, () => {
  router.get(route('tarjetas.index'), { search: search.value }, { preserveState: true, replace: true })
})

const onPage = (event) => {
  router.get(route('tarjetas.index'), {
    page: event.page + 1,
    search: search.value,
  }, { preserveState: true, replace: true })
}

function openCreate() {
  editing.value = null
  form.value = baseForm()
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  form.value = {
    numero: item.numero ?? '',
    descripcion: item.descripcion ?? '',
    idmonedas: item.idmonedas,
    idtipocombustibles: item.idtipocombustibles,
    idempleado: item.idempleado,
    idtractivos: item.idtractivos,
    id_entidad: item.id_entidad,
    saldo_actual: Number(item.saldo_actual),
    fcompra: item.fcompra ? new Date(item.fcompra) : null,
    fvence: item.fvence ? new Date(item.fvence) : null,
    inactiva: Boolean(item.inactiva),
    estado: item.estado || 'activa',
  }
  showForm.value = true
}

function submit() {
  const payload = { ...form.value }
  const url = editing.value ? route('tarjetas.update', { tarjeta: editing.value.id }) : route('tarjetas.store')
  router[editing.value ? 'put' : 'post'](url, payload, {
    onSuccess: () => { showForm.value = false },
  })
}

function destroy(item) {
  confirmDialog.require({
    message: `¿Eliminar la tarjeta ${item.numero}?`,
    header: 'Eliminar Tarjeta',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Eliminar',
    rejectLabel: 'Volver',
    acceptClass: 'p-button-danger',
    accept: () => router.delete(route('tarjetas.destroy', { tarjeta: item.id })),
  })
}

const fmt = (n) => n?.toLocaleString('es-CU', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
</script>

<template>
  <AppLayout>
    <Toolbar class="mb-3">
      <template #start>
        <h2 class="text-xl font-bold m-0">Tarjetas de Combustible</h2>
      </template>
      <template #end>
        <div class="flex gap-2">
          <Select v-model="filters.id_tipo_combustible" :options="[{ id: null, nombre: 'Todos' }, ...tiposCombustibles]" optionLabel="nombre" optionValue="id" placeholder="Tipo" class="w-44" @change="router.get(route('tarjetas.index'), { id_tipo_combustible: filters.id_tipo_combustible }, { preserveState: true, replace: true })" />
          <Select v-model="filters.estado" :options="[{ value: null, label: 'Todos' }, ...estados.map(e => ({ value: e, label: e }))]" optionLabel="label" optionValue="value" placeholder="Estado" class="w-40" @change="router.get(route('tarjetas.index'), { estado: filters.estado }, { preserveState: true, replace: true })" />
          <InputText v-model="search" placeholder="Buscar por número..." class="w-56" />
          <Button icon="pi pi-plus" label="Nueva tarjeta" @click="openCreate" />
        </div>
      </template>
    </Toolbar>

    <DataTable :value="tarjetas.data" paginator :rows="tarjetas.per_page" :totalRecords="tarjetas.total"
      :rowsPerPageOptions="[10, 20, 50]" :first="(tarjetas.current_page - 1) * tarjetas.per_page"
      @page="onPage" stripedRows class="p-datatable-sm"
      paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
      currentPageReportTemplate="Total: {totalRecords} registros">
      <Column field="numero" header="Número" sortable style="width:140px" />
      <Column field="tipoCombustible.nombre" header="Combustible" />
      <Column field="moneda.codigo" header="Moneda" />
      <Column field="empleado.nombre" header="Empleado">
        <template #body="{ data }">{{ data.empleado?.nombre }} {{ data.empleado?.apellidos }}</template>
      </Column>
      <Column field="tractivo.codigo" header="Tractivo" />
      <Column field="entidad.abreviatura" header="Entidad" />
      <Column field="saldo_actual" header="Saldo">
        <template #body="{ data }">{{ fmt(data.saldo_actual) }}</template>
      </Column>
      <Column field="estado" header="Estado" style="width:120px">
        <template #body="{ data }">
          <Tag :value="data.estado" :severity="data.estado === 'activa' ? 'success' : 'danger'" />
        </template>
      </Column>
      <Column header="Acciones" style="width:120px">
        <template #body="{ data }">
          <div class="flex gap-1">
            <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
            <Button icon="pi pi-trash" rounded text severity="danger" @click="destroy(data)" />
          </div>
        </template>
      </Column>
    </DataTable>

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar Tarjeta' : 'Nueva Tarjeta'" modal style="width: 640px">
      <form @submit.prevent="submit" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block mb-1 font-medium">Número</label>
            <InputText v-model="form.numero" class="w-full" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Descripción</label>
            <InputText v-model="form.descripcion" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Tipo de Combustible</label>
            <Select v-model="form.idtipocombustibles" :options="tiposCombustibles" optionLabel="nombre" optionValue="id" placeholder="Seleccione..." class="w-full" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Moneda</label>
            <Select v-model="form.idmonedas" :options="monedas" optionLabel="codigo" optionValue="id" placeholder="Seleccione..." class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Empleado</label>
            <Select v-model="form.idempleado" :options="filtros.empleados" optionLabel="nombre" optionValue="id" placeholder="Seleccione..." class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Tractivo</label>
            <Select v-model="form.idtractivos" :options="filtros.tractivos" optionLabel="codigo" optionValue="id" placeholder="Seleccione..." class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Entidad</label>
            <Select v-model="form.id_entidad" :options="filtros.entidades" optionLabel="abreviatura" optionValue="id" placeholder="Seleccione..." class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Saldo Actual</label>
            <InputNumber v-model="form.saldo_actual" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Fecha Compra</label>
            <DatePicker v-model="form.fcompra" dateFormat="dd/mm/yy" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Fecha Vencimiento</label>
            <DatePicker v-model="form.fvence" dateFormat="dd/mm/yy" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Estado</label>
            <Select v-model="form.estado" :options="estados.map(e => ({ label: e, value: e }))" optionLabel="label" optionValue="value" class="w-full" />
          </div>
          <div class="flex items-center gap-2">
            <ToggleSwitch v-model="form.inactiva" />
            <label class="font-medium">Inactiva</label>
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
