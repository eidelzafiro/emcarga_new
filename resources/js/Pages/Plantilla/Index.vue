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
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ items: Object, areas: Array, cargos: Array, filters: Object })
const toast = useToast()
const confirm = useConfirm()
const search = ref(props.filters?.search || '')
const idArea = ref(props.filters?.id_area || null)
const showForm = ref(false)
const editing = ref(null)
const title = 'Plantilla de Puestos'

function baseForm() {
  return { id_cargo: null, id_area: null, aprobada: 0, cubierta: 0, cubierta2: 0, propuesta: 0, v_necesidad: 0, necesidad: 0 }
}
const form = ref(baseForm())

watch(search, () => reload())
watch(idArea, () => reload())

function reload() {
  router.get(route('plantilla.index'), { search: search.value, id_area: idArea.value || '' }, { preserveState: true, replace: true })
}

function onPage(event) {
  router.get(route('plantilla.index'), { page: event.page + 1, search: search.value, id_area: idArea.value || '' }, { preserveState: true, replace: true })
}

function openCreate() {
  editing.value = null
  form.value = baseForm()
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  form.value = {
    id_cargo: item.id_cargo,
    id_area: item.id_area,
    aprobada: item.aprobada,
    cubierta: item.cubierta,
    cubierta2: item.cubierta2,
    propuesta: item.propuesta,
    v_necesidad: item.v_necesidad,
    necesidad: item.necesidad,
  }
  showForm.value = true
}

function submit() {
  const url = editing.value ? route('plantilla.update', editing.value.id) : route('plantilla.store')
  const method = editing.value ? 'put' : 'post'
  router[method](url, form.value, {
    onSuccess: () => { showForm.value = false; toast.add({ severity: 'success', summary: editing.value ? 'Actualizado' : 'Creado', life: 3000 }) },
    onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
  })
}

function confirmEliminar(item) {
  confirm.require({
    message: `¿Eliminar el puesto "${item.cargo?.nombre}" de la plantilla?`,
    header: 'Eliminar puesto',
    acceptLabel: 'Sí, eliminar',
    rejectLabel: 'No',
    accept: () => {
      router.delete(route('plantilla.destroy', item.id), {
        onSuccess: () => toast.add({ severity: 'success', summary: 'Eliminado', life: 3000 }),
        onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
      })
    },
  })
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
          <div class="flex items-center gap-3">
            <Select v-model="idArea" :options="areas" optionLabel="nombre" optionValue="id" placeholder="Filtrar por área" class="w-56" :showClear="true" />
            <InputText v-model="search" placeholder="Buscar..." />
          </div>
        </template>
      </Toolbar>

      <DataTable :value="items.data" striped-rows paginator :rows="20" :total-records="items.total" :lazy="true" :first="(items.current_page - 1) * items.per_page" @page="onPage" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros">
        <Column header="Área">
          <template #body="{ data }">{{ data.area?.nombre }}</template>
        </Column>
        <Column header="Cargo">
          <template #body="{ data }">{{ data.cargo?.nombre }}</template>
        </Column>
        <Column field="aprobada" header="Aprobada" />
        <Column field="cubierta" header="Cubierta" />
        <Column field="propuesta" header="Propuesta" />
        <Column field="v_necesidad" header="V. Necesidad" />
        <Column field="necesidad" header="Necesidad" />
        <Column header="Acciones" style="width: 120px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
              <Button icon="pi pi-trash" rounded text severity="danger" @click="confirmEliminar(data)" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar Puesto' : 'Nuevo Puesto'" modal style="width: 560px">
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block mb-1 font-medium">Área</label>
          <Select v-model="form.id_area" :options="areas" optionLabel="nombre" optionValue="id" filter class="w-full" required />
        </div>
        <div>
          <label class="block mb-1 font-medium">Cargo</label>
          <Select v-model="form.id_cargo" :options="cargos" optionLabel="nombre" optionValue="id" filter class="w-full" required />
        </div>
        <div class="grid grid-cols-3 gap-4">
          <div>
            <label class="block mb-1 font-medium">Aprobada</label>
            <InputNumber v-model="form.aprobada" :min="0" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Cubierta</label>
            <InputNumber v-model="form.cubierta" :min="0" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Propuesta</label>
            <InputNumber v-model="form.propuesta" :min="0" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">V. Necesidad</label>
            <InputNumber v-model="form.v_necesidad" :min="0" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Necesidad</label>
            <InputNumber v-model="form.necesidad" :min="0" class="w-full" />
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
