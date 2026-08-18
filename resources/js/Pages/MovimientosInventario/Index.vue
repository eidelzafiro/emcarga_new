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
import Textarea from 'primevue/textarea'
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ items: Object, filters: Object })
const toast = useToast()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)
const form = ref({})
const title = 'Movimientos de Inventario'

function baseForm() {
  return {
    folio: null,
    id_almacen: null,
    id_suministrador: null,
    fecha_movimiento: null,
    factura: null,
    fecha_factura: null,
    importe_mn: null,
    importe_me: null,
    observaciones: '',
  }
}

watch(search, () => {
  router.get(route('movimientos-inventario.index'), { search: search.value }, { preserveState: true, replace: true })
})

function onPage(event) {
  router.get(route('movimientos-inventario.index'), { page: event.page + 1, search: search.value }, { preserveState: true, replace: true })
}

function openCreate() {
  editing.value = null
  form.value = baseForm()
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  form.value = Object.keys(baseForm()).reduce((acc, k) => { acc[k] = item[k] ?? (k === 'observaciones' ? '' : null); return acc }, {})
  showForm.value = true
}

function submit() {
  const url = editing.value ? route('movimientos-inventario.update', editing.value.id) : route('movimientos-inventario.store')
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

      <DataTable :value="items.data" striped-rows paginator :rows="50" :total-records="items.total" :lazy="true" :first="(items.current_page - 1) * items.per_page" @page="onPage" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros">
        <Column field="folio" header="Folio" />
        <Column field="fecha_movimiento" header="Fecha Mov." />
        <Column field="id_almacen" header="Almacén" />
        <Column field="id_suministrador" header="Suministrador" />
        <Column field="factura" header="Factura" />
        <Column field="importe_mn" header="Importe MN" />
        <Column field="importe_me" header="Importe ME" />
        <Column field="observaciones" header="Observaciones" />
        <Column header="Acciones" style="width: 120px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
              <Button icon="pi pi-trash" rounded text severity="danger" @click="router.delete(route('movimientos-inventario.destroy', data.id))" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar Movimiento' : 'Nuevo Movimiento'" modal style="width: 620px">
      <form @submit.prevent="submit" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block mb-1 font-medium">Folio</label>
            <InputText v-model="form.folio" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Fecha Movimiento</label>
            <InputText v-model="form.fecha_movimiento" type="date" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Almacén</label>
            <InputNumber v-model="form.id_almacen" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Suministrador</label>
            <InputNumber v-model="form.id_suministrador" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Factura</label>
            <InputText v-model="form.factura" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Fecha Factura</label>
            <InputText v-model="form.fecha_factura" type="date" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Importe MN</label>
            <InputNumber v-model="form.importe_mn" :min="0" :max-fraction-digits="2" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Importe ME</label>
            <InputNumber v-model="form.importe_me" :min="0" :max-fraction-digits="2" class="w-full" />
          </div>
        </div>
        <div>
          <label class="block mb-1 font-medium">Observaciones</label>
          <Textarea v-model="form.observaciones" class="w-full" :rows="3" />
        </div>
        <div class="flex gap-2 justify-end">
          <Button label="Cancelar" severity="secondary" @click="showForm = false" />
          <Button label="Guardar" type="submit" icon="pi pi-save" />
        </div>
      </form>
    </Dialog>
  </AppLayout>
</template>
