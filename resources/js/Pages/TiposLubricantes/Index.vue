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
import ToggleSwitch from 'primevue/toggleswitch'
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import { useConfirm } from 'primevue/useconfirm'

const props = defineProps({ title: String, lubricantes: Object, tipos: Array, filters: Object })
const confirmDialog = useConfirm()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)

const tiposOptions = computed(() => (props.tipos ?? []).map(t => ({ value: t.nombre, label: t.nombre })))

const baseForm = () => ({
  codigo: '',
  nombre: '',
  tipo: null,
  viscosidad: '',
  costo_litro: 0,
  activo: true,
})

const form = ref(baseForm())

watch(search, () => {
  router.get(route('tipos-lubricantes.index'), { search: search.value }, { preserveState: true, replace: true })
})

const onPage = (event) => {
  router.get(route('tipos-lubricantes.index'), { page: event.page + 1, per_page: event.rows, search: search.value }, { preserveState: true, replace: true })
}

function openCreate() {
  editing.value = null
  form.value = baseForm()
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  form.value = {
    codigo: item.codigo ?? '',
    nombre: item.nombre ?? '',
    tipo: item.tipo ?? null,
    viscosidad: item.viscosidad ?? '',
    costo_litro: item.costo_litro ?? 0,
    activo: item.activo ?? true,
  }
  showForm.value = true
}

function submit() {
  const payload = { ...form.value }
  const url = editing.value
    ? route('tipos-lubricantes.update', { tipos_lubricante: editing.value.id })
    : route('tipos-lubricantes.store')
  router[editing.value ? 'put' : 'post'](url, payload, { onSuccess: () => { showForm.value = false } })
}

function destroy(item) {
  confirmDialog.require({
    message: `¿Eliminar el lubricante ${item.nombre}?`,
    header: 'Eliminar Lubricante',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Eliminar',
    rejectLabel: 'Volver',
    acceptClass: 'p-button-danger',
    accept: () => router.delete(route('tipos-lubricantes.destroy', { tipos_lubricante: item.id })),
  })
}
</script>

<template>
  <AppLayout>
    <Toolbar class="mb-3">
      <template #start>
        <h2 class="text-xl font-bold m-0">{{ title ?? 'Tipos de Lubricantes' }}</h2>
      </template>
      <template #end>
        <div class="flex gap-2">
          <InputText v-model="search" placeholder="Buscar por nombre o código..." class="w-64" />
          <Button icon="pi pi-plus" label="Nuevo lubricante" @click="openCreate" />
        </div>
      </template>
    </Toolbar>

    <DataTable lazy :value="lubricantes.data" paginator :rows="lubricantes.per_page" :totalRecords="lubricantes.total"
      :rowsPerPageOptions="[10, 20, 50]" :first="(lubricantes.current_page - 1) * lubricantes.per_page"
      @page="onPage" stripedRows class="p-datatable-sm"
      paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
      currentPageReportTemplate="Total: {totalRecords} registros">
      <Column field="codigo" header="Código" />
      <Column field="nombre" header="Nombre" sortable />
      <Column field="tipo" header="Tipo">
        <template #body="{ data }">
          <Tag v-if="data.tipo" :value="data.tipo" severity="info" />
          <span v-else class="text-gray-400">—</span>
        </template>
      </Column>
      <Column field="viscosidad" header="Viscosidad" />
      <Column field="costo_litro" header="Costo/L">
        <template #body="{ data }">{{ Number(data.costo_litro ?? 0).toFixed(2) }}</template>
      </Column>
      <Column field="activo" header="Activo" style="width:90px">
        <template #body="{ data }">
          <Tag :value="data.activo ? 'Sí' : 'No'" :severity="data.activo ? 'success' : 'danger'" />
        </template>
      </Column>
      <Column header="Acciones" style="width:110px">
        <template #body="{ data }">
          <Button icon="pi pi-pencil" text rounded severity="info" @click="openEdit(data)" v-tooltip.top="'Editar'" />
          <Button icon="pi pi-trash" text rounded severity="danger" @click="destroy(data)" v-tooltip.top="'Eliminar'" />
        </template>
      </Column>
    </DataTable>

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar lubricante' : 'Nuevo lubricante'" :style="{ width: '560px' }" modal>
      <div class="grid grid-cols-2 gap-3 mt-2">
        <div class="flex flex-col gap-1">
          <label class="text-sm font-medium">Código</label>
          <InputText v-model="form.codigo" />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm font-medium">Nombre *</label>
          <InputText v-model="form.nombre" />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm font-medium">Clasificación (tipo)</label>
          <Select v-model="form.tipo" :options="tiposOptions" optionLabel="label" optionValue="value" class="w-full" showClear filter placeholder="Motor / Transmisión / Hidráulico..." />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm font-medium">Viscosidad</label>
          <InputText v-model="form.viscosidad" placeholder="ej. 20W50" />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm font-medium">Costo por litro</label>
          <InputNumber v-model="form.costo_litro" mode="currency" currency="CUP" :minFractionDigits="2" class="w-full" />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm font-medium">Activo</label>
          <ToggleSwitch v-model="form.activo" />
        </div>
      </div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" text @click="showForm = false" />
        <Button label="Guardar" icon="pi pi-check" @click="submit" />
      </template>
    </Dialog>
  </AppLayout>
</template>
