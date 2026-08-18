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
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import ToggleSwitch from 'primevue/toggleswitch'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ items: Object, filters: Object })
const toast = useToast()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)
const form = ref({ id_empleado: null, fecha_inicio: null, tiempo: null, motivo: '', activo: true })
const title = 'Desc. Empleados'

watch(search, () => {
  router.get(route('descuentos-empleados.index'), { search: search.value }, { preserveState: true, replace: true })
})

function onPage(event) {
  router.get(route('descuentos-empleados.index'), { page: event.page + 1, search: search.value }, { preserveState: true, replace: true })
}

function openCreate() {
  editing.value = null
  form.value = { id_empleado: null, fecha_inicio: null, tiempo: null, motivo: '', activo: true }
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  form.value = {
    id_empleado: item.id_empleado,
    fecha_inicio: item.fecha_inicio ? item.fecha_inicio.slice(0, 10) : null,
    tiempo: item.tiempo ?? null,
    motivo: item.motivo || '',
    activo: Boolean(item.activo),
  }
  showForm.value = true
}

function submit() {
  const url = editing.value ? route('descuentos-empleados.update', editing.value.id) : route('descuentos-empleados.store')
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
        <Column field="id_empleado" header="Empleado" />
        <Column header="Fecha Inicio" :style="{ width: '130px' }">
          <template #body="{ data }">{{ data.fecha_inicio }}</template>
        </Column>
        <Column field="tiempo" header="Tiempo" />
        <Column field="motivo" header="Motivo" />
        <Column field="activo" header="Activo" :style="{ width: '100px' }">
          <template #body="{ data }">
            <i :class="data.activo ? 'pi pi-check text-green-600' : 'pi pi-times text-red-500'" />
          </template>
        </Column>
        <Column header="Acciones" style="width: 120px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
              <Button icon="pi pi-trash" rounded text severity="danger" @click="router.delete(route('descuentos-empleados.destroy', data.id))" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar Descuento de Empleado' : 'Nuevo Descuento de Empleado'" modal style="width: 550px">
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block mb-1 font-medium">Empleado (ID)</label>
          <InputNumber v-model="form.id_empleado" :min="0" class="w-full" required />
        </div>
        <div>
          <label class="block mb-1 font-medium">Fecha de Inicio</label>
          <InputText v-model="form.fecha_inicio" type="date" class="w-full" />
        </div>
        <div>
          <label class="block mb-1 font-medium">Tiempo</label>
          <InputNumber v-model="form.tiempo" :min="0" :max-fraction-digits="2" class="w-full" />
        </div>
        <div>
          <label class="block mb-1 font-medium">Motivo</label>
          <InputText v-model="form.motivo" class="w-full" />
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
