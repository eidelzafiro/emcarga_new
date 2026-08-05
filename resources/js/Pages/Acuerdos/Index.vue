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
import ToggleSwitch from 'primevue/toggleswitch'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ acuerdos: Object, clientes: Array, lugares: Array, productos: Array, filters: Object })
const toast = useToast()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)
const form = ref({})
const title = 'Precios por acuerdo'

function baseForm() {
  return { id_cliente: null, id_lugar_origen: null, id_lugar_destino: null, id_producto: null, tarifa_ton: null, importe: null, activo: true }
}

watch(search, () => {
  router.get(route('acuerdos.index'), { search: search.value }, { preserveState: true, replace: true })
})

function onPage(event) {
  router.get(route('acuerdos.index'), { page: event.page + 1, search: search.value }, { preserveState: true, replace: true })
}

function openCreate() {
  editing.value = null
  form.value = baseForm()
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  form.value = {
    id_cliente: item.id_cliente,
    id_lugar_origen: item.id_lugar_origen,
    id_lugar_destino: item.id_lugar_destino,
    id_producto: item.id_producto,
    tarifa_ton: item.tarifa_ton,
    importe: item.importe,
    activo: Boolean(item.activo),
  }
  showForm.value = true
}

function submit() {
  const url = editing.value ? route('acuerdos.update', editing.value.id) : route('acuerdos.store')
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

      <DataTable :value="acuerdos.data" striped-rows paginator :rows="20" :total-records="acuerdos.total" :lazy="true" :first="(acuerdos.current_page - 1) * acuerdos.per_page" @page="onPage" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros">
        <Column header="Cliente">
          <template #body="{ data }">{{ data.cliente?.nombre }}</template>
        </Column>
        <Column header="Origen">
          <template #body="{ data }">{{ data.origen?.nombre }}</template>
        </Column>
        <Column header="Destino">
          <template #body="{ data }">{{ data.destino?.nombre }}</template>
        </Column>
        <Column header="Producto">
          <template #body="{ data }">{{ data.producto?.nombre }}</template>
        </Column>
        <Column field="tarifa_ton" header="Tarifa tons-MN" />
        <Column field="importe" header="Importe" />
        <Column field="activo" header="Activo" style="width: 80px">
          <template #body="{ data }">
            <i :class="data.activo ? 'pi pi-check text-green-600' : 'pi pi-times text-red-500'" />
          </template>
        </Column>
        <Column header="Acciones" style="width: 120px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
              <Button icon="pi pi-trash" rounded text severity="danger" @click="router.delete(route('acuerdos.destroy', data.id))" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar Precio por Acuerdo' : 'Nuevo Precio por Acuerdo'" modal style="width: 550px">
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block mb-1 font-medium">Cliente</label>
          <Select v-model="form.id_cliente" :options="clientes" optionLabel="nombre" optionValue="id" filter placeholder="Seleccione el cliente..." class="w-full" :showClear="true" required />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block mb-1 font-medium">Origen</label>
            <Select v-model="form.id_lugar_origen" :options="lugares" optionLabel="nombre" optionValue="id" filter placeholder="Seleccione el origen..." class="w-full" :showClear="true" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Destino</label>
            <Select v-model="form.id_lugar_destino" :options="lugares" optionLabel="nombre" optionValue="id" filter placeholder="Seleccione el destino..." class="w-full" :showClear="true" required />
          </div>
        </div>
        <div>
          <label class="block mb-1 font-medium">Producto</label>
          <Select v-model="form.id_producto" :options="productos" optionLabel="nombre" optionValue="id" filter placeholder="Seleccione el producto..." class="w-full" :showClear="true" required />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block mb-1 font-medium">Tarifa tons-MN</label>
            <InputNumber v-model="form.tarifa_ton" :min="0" :max-fraction-digits="2" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Importe</label>
            <InputNumber v-model="form.importe" :min="0" :max-fraction-digits="2" class="w-full" />
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