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
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'

const props = defineProps({ title: String, motores: Object, filters: Object })
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)

const estados = [
  { label: 'disponible', value: 'disponible' },
  { label: 'nuevo', value: 'nuevo' },
  { label: 'reparado', value: 'reparado' },
  { label: 'regular', value: 'regular' },
  { label: 'trabajando', value: 'trabajando' },
]

const baseForm = () => ({
  codigo: '',
  descripcion: '',
  marca: '',
  modelo: '',
  numero_serie: '',
  estado: 'disponible',
})

const form = ref(baseForm())

watch(search, () => {
  router.get(route('motores.index'), { search: search.value }, { preserveState: true, replace: true })
})

const onPage = (event) => {
  router.get(route('motores.index'), {
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
    codigo: item.codigo ?? '',
    descripcion: item.descripcion ?? '',
    marca: item.marca ?? '',
    modelo: item.modelo ?? '',
    numero_serie: item.numero_serie ?? '',
    estado: item.estado || 'disponible',
  }
  showForm.value = true
}

function submit() {
  const payload = { ...form.value }
  const url = editing.value
    ? route('motores.update', { motore: editing.value.id })
    : route('motores.store')
  router[editing.value ? 'put' : 'post'](url, payload, {
    onSuccess: () => {
      showForm.value = false
    },
  })
}

function destroy(item) {
  if (!confirm(`¿Eliminar el motor ${item.codigo ?? item.id}?`)) return
  router.delete(route('motores.destroy', { motore: item.id }))
}
</script>

<template>
  <AppLayout>
    <Toolbar class="mb-3">
      <template #start>
        <h2 class="text-xl font-bold m-0">{{ title ?? 'Motores' }}</h2>
      </template>
      <template #end>
        <div class="flex gap-2">
          <InputText v-model="search" placeholder="Buscar por código o descripción..." class="w-64" />
          <Button icon="pi pi-plus" label="Nuevo motor" @click="openCreate" />
        </div>
      </template>
    </Toolbar>

    <DataTable :value="motores.data" paginator :rows="motores.per_page" :totalRecords="motores.total"
      :rowsPerPageOptions="[10, 20, 50]" :first="(motores.current_page - 1) * motores.per_page"
      @page="onPage" stripedRows class="p-datatable-sm"
      paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
      currentPageReportTemplate="Total: {totalRecords} registros">
      <Column field="codigo" header="Código" sortable style="width:130px" />
      <Column field="descripcion" header="Descripción" sortable />
      <Column field="marca" header="Marca" sortable />
      <Column field="modelo" header="Modelo" />
      <Column field="tractivo.descripcion" header="Tractivo" />
      <Column field="estado" header="Estado" style="width:120px">
        <template #body="{ data }">
          <Tag :value="data.estado || 'disponible'" :severity="data.estado === 'disponible' ? 'success' : 'warn'" />
        </template>
      </Column>
      <Column header="Acciones" style="width:120px">
        <template #body="{ data }">
          <Button icon="pi pi-pencil" text rounded severity="info" @click="openEdit(data)" />
          <Button icon="pi pi-trash" text rounded severity="danger" @click="destroy(data)" />
        </template>
      </Column>
    </DataTable>

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar motor' : 'Nuevo motor'"
      :style="{ width: '520px' }" modal>
      <div class="grid grid-cols-2 gap-3 mt-2">
        <div class="flex flex-col gap-1">
          <label for="codigo" class="text-sm font-medium">Código *</label>
          <InputText id="codigo" v-model="form.codigo" />
        </div>
        <div class="flex flex-col gap-1">
          <label for="estado" class="text-sm font-medium">Estado</label>
          <Select id="estado" v-model="form.estado" :options="estados" optionLabel="label"
            optionValue="value" class="w-full" />
        </div>
        <div class="flex flex-col gap-1 col-span-2">
          <label for="descripcion" class="text-sm font-medium">Descripción *</label>
          <InputText id="descripcion" v-model="form.descripcion" />
        </div>
        <div class="flex flex-col gap-1">
          <label for="marca" class="text-sm font-medium">Marca</label>
          <InputText id="marca" v-model="form.marca" />
        </div>
        <div class="flex flex-col gap-1">
          <label for="modelo" class="text-sm font-medium">Modelo</label>
          <InputText id="modelo" v-model="form.modelo" />
        </div>
        <div class="flex flex-col gap-1 col-span-2">
          <label for="numero_serie" class="text-sm font-medium">N° Serie</label>
          <InputText id="numero_serie" v-model="form.numero_serie" />
        </div>
      </div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" text @click="showForm = false" />
        <Button label="Guardar" icon="pi pi-check" @click="submit" />
      </template>
    </Dialog>
  </AppLayout>
</template>
