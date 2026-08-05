<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import ToggleSwitch from 'primevue/toggleswitch'
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ items: Object, filters: Object })
const toast = useToast()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)
const form = ref({ nombre: '', activo: true })

const baseForm = () => ({
  nombre: '',
  confecciona_nombre: '',
  confecciona_cargo: '',
  revisa_nombre: '',
  revisa_cargo: '',
  aprueba_nombre: '',
  aprueba_cargo: '',
  activo: true,
})

watch(search, () => {
  router.get(route('firmas.index'), { search: search.value }, { preserveState: true, replace: true })
})

function onPage(event) {
  router.get(route('firmas.index'), { page: event.page + 1, search: search.value }, { preserveState: true, replace: true })
}

function openCreate() {
  editing.value = null
  form.value = { ...baseForm() }
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  form.value = {
    nombre: item.nombre,
    confecciona_nombre: item.confecciona_nombre || '',
    confecciona_cargo: item.confecciona_cargo || '',
    revisa_nombre: item.revisa_nombre || '',
    revisa_cargo: item.revisa_cargo || '',
    aprueba_nombre: item.aprueba_nombre || '',
    aprueba_cargo: item.aprueba_cargo || '',
    activo: Boolean(item.activo),
  }
  showForm.value = true
}

function submit() {
  const url = editing.value ? route('firmas.update', editing.value.id) : route('firmas.store')
  const method = editing.value ? 'put' : 'post'
  router[method](url, form.value, {
    onSuccess: () => { showForm.value = false; toast.add({ severity: 'success', summary: editing.value ? 'Actualizado' : 'Creado', life: 3000 }) },
    onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
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
        <Column field="nombre" header="Nombre" sortable />
        <Column field="confecciona_nombre" header="Confecciona" />
        <Column field="revisa_nombre" header="Revisa" />
        <Column field="aprueba_nombre" header="Aprueba" />
        <Column field="activo" header="Activo">
          <template #body="{ data }">
            <Tag :value="data.activo ? 'Sí' : 'No'" :severity="data.activo ? 'success' : 'danger'" />
          </template>
        </Column>
        <Column header="Acciones" style="width: 120px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
              <Button icon="pi pi-trash" rounded text severity="danger" @click="router.delete(route('firmas.destroy', data.id))" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar Firma' : 'Nueva Firma'" modal style="width: 600px">
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block mb-1 font-medium">Nombre</label>
          <InputText v-model="form.nombre" class="w-full" required />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block mb-1 font-medium">Confecciona - Nombre</label>
            <InputText v-model="form.confecciona_nombre" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Confecciona - Cargo</label>
            <InputText v-model="form.confecciona_cargo" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Revisa - Nombre</label>
            <InputText v-model="form.revisa_nombre" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Revisa - Cargo</label>
            <InputText v-model="form.revisa_cargo" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Aprueba - Nombre</label>
            <InputText v-model="form.aprueba_nombre" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Aprueba - Cargo</label>
            <InputText v-model="form.aprueba_cargo" class="w-full" />
          </div>
        </div>
        <div class="flex items-center gap-2">
          <ToggleSwitch v-model="form.activo" inputId="activo" />
          <label for="activo" class="font-medium">Activo</label>
        </div>
        <div class="flex gap-2 justify-end">
          <Button label="Cancelar" severity="secondary" @click="showForm = false" />
          <Button label="Guardar" type="submit" icon="pi pi-save" />
        </div>
      </form>
    </Dialog>
  </AppLayout>
</template>
