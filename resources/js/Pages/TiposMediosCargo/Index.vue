<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Select from 'primevue/select'
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ items: Object, medios: Array, cargos: Array, filters: Object })
const toast = useToast()
const showForm = ref(false)
const editing = ref(null)
const form = ref({ id_medio_proteccion: null, id_cargo: null })
const title = 'Tipos de Medios por Cargo'

function onPage(event) {
  router.get(route('tipos-medios-cargo.index'), { page: event.page + 1 }, { preserveState: true, replace: true })
}

function openCreate() {
  editing.value = null
  form.value = { id_medio_proteccion: null, id_cargo: null }
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  form.value = { id_medio_proteccion: item.id_medio_proteccion, id_cargo: item.id_cargo }
  showForm.value = true
}

function submit() {
  const url = editing.value ? route('tipos-medios-cargo.update', editing.value.id) : route('tipos-medios-cargo.store')
  const method = editing.value ? 'put' : 'post'
  router[method](url, form.value, {
    onSuccess: () => { showForm.value = false; toast.add({ severity: 'success', summary: editing.value ? 'Actualizada' : 'Creada', life: 3000 }) },
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
      </Toolbar>

      <DataTable :value="items.data" striped-rows paginator :rows="20" :total-records="items.total" :lazy="true" :first="(items.current_page - 1) * items.per_page" @page="onPage" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros">
        <Column header="Medio de Protección">
          <template #body="{ data }">{{ data.medioProteccion?.nombre }}</template>
        </Column>
        <Column header="Cargo">
          <template #body="{ data }">{{ data.cargo?.nombre }}</template>
        </Column>
        <Column header="Acciones" style="width: 120px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
              <Button icon="pi pi-trash" rounded text severity="danger" @click="router.delete(route('tipos-medios-cargo.destroy', data.id))" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar Asignación' : 'Nueva Asignación'" modal style="width: 450px">
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block mb-1 font-medium">Medio de Protección</label>
          <Select v-model="form.id_medio_proteccion" :options="medios" optionLabel="nombre" optionValue="id" filter placeholder="Seleccione el medio..." class="w-full" :showClear="true" required />
        </div>
        <div>
          <label class="block mb-1 font-medium">Cargo</label>
          <Select v-model="form.id_cargo" :options="cargos" optionLabel="nombre" optionValue="id" filter placeholder="Seleccione el cargo..." class="w-full" :showClear="true" required />
        </div>
        <div class="flex gap-2 justify-end">
          <Button label="Cancelar" severity="secondary" @click="showForm = false" />
          <Button label="Guardar" type="submit" icon="pi pi-save" />
        </div>
      </form>
    </Dialog>
  </AppLayout>
</template>
