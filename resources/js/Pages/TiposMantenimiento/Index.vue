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
import ToggleSwitch from 'primevue/toggleswitch'
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import { useConfirm } from 'primevue/useconfirm'

const props = defineProps({ title: String, tipos: Object, filters: Object })
const confirmDialog = useConfirm()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)

const baseForm = () => ({
  nombre: '',
  frecuencia: null,
  kms_max: null,
  mtto_base: null,
  holgura: null,
  mttos: '',
  activo: true,
})

const form = ref(baseForm())

watch(search, () => {
  router.get(route('tipos-mantenimiento.index'), { search: search.value }, { preserveState: true, replace: true })
})

const onPage = (event) => {
  router.get(route('tipos-mantenimiento.index'), { page: event.page + 1, per_page: event.rows, search: search.value }, { preserveState: true, replace: true })
}

function openCreate() {
  editing.value = null
  form.value = baseForm()
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  form.value = {
    nombre: item.nombre ?? '',
    frecuencia: item.frecuencia ?? null,
    kms_max: item.kms_max ?? null,
    mtto_base: item.mtto_base ?? null,
    holgura: item.holgura ?? null,
    mttos: item.mttos ?? '',
    activo: item.activo ?? true,
  }
  showForm.value = true
}

function submit() {
  const payload = { ...form.value }
  const url = editing.value
    ? route('tipos-mantenimiento.update', { tipos_mantenimiento: editing.value.id })
    : route('tipos-mantenimiento.store')
  router[editing.value ? 'put' : 'post'](url, payload, { onSuccess: () => { showForm.value = false } })
}

function destroy(item) {
  confirmDialog.require({
    message: `¿Eliminar el tipo de mantenimiento ${item.nombre}? Se eliminarán también sus líneas de plan.`,
    header: 'Eliminar Tipo de Mantenimiento',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Eliminar',
    rejectLabel: 'Volver',
    acceptClass: 'p-button-danger',
    accept: () => router.delete(route('tipos-mantenimiento.destroy', { tipos_mantenimiento: item.id })),
  })
}
</script>

<template>
  <AppLayout>
    <Toolbar class="mb-3">
      <template #start>
        <h2 class="text-xl font-bold m-0">{{ title ?? 'Tipos de Mantenimiento' }}</h2>
      </template>
      <template #end>
        <div class="flex gap-2">
          <InputText v-model="search" placeholder="Buscar por nombre..." class="w-64" />
          <Button icon="pi pi-plus" label="Nuevo tipo" @click="openCreate" />
        </div>
      </template>
    </Toolbar>

    <DataTable :value="tipos.data" paginator :rows="tipos.per_page" :totalRecords="tipos.total"
      :rowsPerPageOptions="[10, 20, 50]" :first="(tipos.current_page - 1) * tipos.per_page"
      @page="onPage" stripedRows class="p-datatable-sm"
      paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
      currentPageReportTemplate="Total: {totalRecords} registros">
      <Column field="nombre" header="Nombre" sortable />
      <Column field="frecuencia" header="Frecuencia (km)" />
      <Column field="kms_max" header="Kms máx" />
      <Column field="mtto_base" header="Mtto base" />
      <Column field="holgura" header="Holgura" />
      <Column field="lineas_count" header="Líneas">
        <template #body="{ data }">{{ data.lineas_count ?? 0 }}</template>
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

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar tipo de mantenimiento' : 'Nuevo tipo de mantenimiento'" :style="{ width: '620px' }" modal>
      <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mt-2">
        <div class="flex flex-col gap-1 col-span-2 md:col-span-3">
          <label class="text-sm font-medium">Nombre *</label>
          <InputText v-model="form.nombre" />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm font-medium">Frecuencia (km)</label>
          <InputNumber v-model="form.frecuencia" class="w-full" />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm font-medium">Kms máx</label>
          <InputNumber v-model="form.kms_max" class="w-full" />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm font-medium">Mtto base</label>
          <InputNumber v-model="form.mtto_base" class="w-full" />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm font-medium">Holgura</label>
          <InputNumber v-model="form.holgura" class="w-full" />
        </div>
        <div class="flex flex-col gap-1 col-span-2 md:col-span-2">
          <label class="text-sm font-medium">Mantenimientos (ciclo)</label>
          <InputText v-model="form.mttos" placeholder="ej. 5000 5000 15000 5000 5000 30000" />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm font-medium">Activo</label>
          <ToggleSwitch v-model="form.activo" />
        </div>
      </div>
      <p class="text-xs text-gray-500 dark:text-gray-400 mt-3">
        Al guardar se regeneran automáticamente las líneas del plan de mantenimiento
        (desde <b>frecuencia</b> hasta <b>kms máx</b>, aplicando <b>mtto base</b> y el ciclo <b>mttos</b>).
      </p>
      <template #footer>
        <Button label="Cancelar" severity="secondary" text @click="showForm = false" />
        <Button label="Guardar" icon="pi pi-check" @click="submit" />
      </template>
    </Dialog>
  </AppLayout>
</template>
