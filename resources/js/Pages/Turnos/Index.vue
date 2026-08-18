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
import Checkbox from 'primevue/checkbox'
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ items: Object, filters: Object })
const toast = useToast()
const confirm = useConfirm()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)
const title = 'Turnos de Trabajo'

function baseForm() {
  return { codigo: '', nombre: '', hora_entrada: '', hora_salida: '', dias_descanso: null, activo: true }
}
const form = ref(baseForm())

watch(search, () => {
  router.get(route('turnos.index'), { search: search.value }, { preserveState: true, replace: true })
})

function onPage(event) {
  router.get(route('turnos.index'), { page: event.page + 1, search: search.value }, { preserveState: true, replace: true })
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
    nombre: item.nombre,
    hora_entrada: item.hora_entrada?.slice(0, 5) ?? '',
    hora_salida: item.hora_salida?.slice(0, 5) ?? '',
    dias_descanso: item.dias_descanso,
    activo: Boolean(item.activo),
  }
  showForm.value = true
}

function submit() {
  const url = editing.value ? route('turnos.update', editing.value.id) : route('turnos.store')
  const method = editing.value ? 'put' : 'post'
  router[method](url, form.value, {
    onSuccess: () => { showForm.value = false; toast.add({ severity: 'success', summary: editing.value ? 'Actualizado' : 'Creado', life: 3000 }) },
    onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
  })
}

function confirmEliminar(item) {
  confirm.require({
    message: `¿Eliminar el turno "${item.nombre}"?`,
    header: 'Eliminar turno',
    acceptLabel: 'Sí, eliminar',
    rejectLabel: 'No',
    accept: () => {
      router.delete(route('turnos.destroy', item.id), {
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
          <InputText v-model="search" placeholder="Buscar..." />
        </template>
      </Toolbar>

      <DataTable :value="items.data" striped-rows paginator :rows="20" :total-records="items.total" :lazy="true" :first="(items.current_page - 1) * items.per_page" @page="onPage" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros">
        <Column field="codigo" header="Código" />
        <Column field="nombre" header="Nombre" />
        <Column header="Entrada">
          <template #body="{ data }">{{ data.hora_entrada?.slice(0, 5) }}</template>
        </Column>
        <Column header="Salida">
          <template #body="{ data }">{{ data.hora_salida?.slice(0, 5) }}</template>
        </Column>
        <Column field="dias_descanso" header="Días descanso" />
        <Column header="Activo">
          <template #body="{ data }">
            <Checkbox :model-value="Boolean(data.activo)" :binary="true" disabled />
          </template>
        </Column>
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

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar Turno' : 'Nuevo Turno'" modal style="width: 500px">
      <form @submit.prevent="submit" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block mb-1 font-medium">Código</label>
            <InputText v-model="form.codigo" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Nombre</label>
            <InputText v-model="form.nombre" class="w-full" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Hora entrada</label>
            <InputText v-model="form.hora_entrada" type="time" class="w-full" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Hora salida</label>
            <InputText v-model="form.hora_salida" type="time" class="w-full" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Días descanso</label>
            <InputNumber v-model="form.dias_descanso" :min="0" :max="7" class="w-full" />
          </div>
          <div class="flex items-end pb-1">
            <label class="flex items-center gap-2">
              <Checkbox v-model="form.activo" :binary="true" />
              Activo
            </label>
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
