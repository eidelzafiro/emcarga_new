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
import { useToast } from 'primevue/usetoast'

const props = defineProps({ items: Object, clientes: Array, productos: Array, lugares: Array, embalajes: Array, filters: Object })
const toast = useToast()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)
const form = ref({
  fecha_demanda: null, id_cliente: null, id_producto: null, id_origen: null, id_destino: null,
  id_embalaje: null, viajes: null, kms_totales: null, kms_carga: null,
  tiempo_demanda: null, tiempo_aceptacion: null, observaciones: '', estado: 'activa',
})
const title = 'Demandas'

watch(search, () => {
  router.get(route('demandas.index'), { search: search.value }, { preserveState: true, replace: true })
})

function onPage(event) {
  router.get(route('demandas.index'), { page: event.page + 1, search: search.value }, { preserveState: true, replace: true })
}

function openCreate() {
  editing.value = null
  form.value = {
    fecha_demanda: null, id_cliente: null, id_producto: null, id_origen: null, id_destino: null,
    id_embalaje: null, viajes: null, kms_totales: null, kms_carga: null,
    tiempo_demanda: null, tiempo_aceptacion: null, observaciones: '', estado: 'activa',
  }
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  form.value = {
    fecha_demanda: item.fecha_demanda ? item.fecha_demanda.slice(0, 10) : null,
    id_cliente: item.id_cliente, id_producto: item.id_producto, id_origen: item.id_origen,
    id_destino: item.id_destino, id_embalaje: item.id_embalaje, viajes: item.viajes,
    kms_totales: item.kms_totales, kms_carga: item.kms_carga, tiempo_demanda: item.tiempo_demanda,
    tiempo_aceptacion: item.tiempo_aceptacion, observaciones: item.observaciones || '', estado: item.estado,
  }
  showForm.value = true
}

function submit() {
  const url = editing.value ? route('demandas.update', editing.value.id) : route('demandas.store')
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
        <template #end>
          <InputText v-model="search" placeholder="Buscar..." />
        </template>
      </Toolbar>

      <DataTable :value="items.data" striped-rows paginator :rows="20" :total-records="items.total" :lazy="true" :first="(items.current_page - 1) * items.per_page" @page="onPage" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros">
        <Column field="fecha_demanda" header="Fecha" />
        <Column header="Cliente">
          <template #body="{ data }">{{ data.cliente?.nombre }}</template>
        </Column>
        <Column header="Producto">
          <template #body="{ data }">{{ data.producto?.nombre }}</template>
        </Column>
        <Column header="Origen">
          <template #body="{ data }">{{ data.origen?.nombre }}</template>
        </Column>
        <Column header="Destino">
          <template #body="{ data }">{{ data.destino?.nombre }}</template>
        </Column>
        <Column field="viajes" header="Viajes" />
        <Column field="kms_totales" header="KMS Totales" />
        <Column field="estado" header="Estado" />
        <Column header="Acciones" style="width: 120px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
              <Button icon="pi pi-trash" rounded text severity="danger" @click="router.delete(route('demandas.destroy', data.id))" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar Demanda' : 'Nueva Demanda'" modal style="width: 600px">
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block mb-1 font-medium">Fecha de demanda</label>
          <InputText v-model="form.fecha_demanda" type="date" class="w-full" required />
        </div>
        <div>
          <label class="block mb-1 font-medium">Cliente</label>
          <Select v-model="form.id_cliente" :options="clientes" optionLabel="nombre" optionValue="id" filter placeholder="Seleccione el cliente..." class="w-full" :showClear="true" required />
        </div>
        <div>
          <label class="block mb-1 font-medium">Producto</label>
          <Select v-model="form.id_producto" :options="productos" optionLabel="nombre" optionValue="id" filter placeholder="Seleccione el producto..." class="w-full" :showClear="true" required />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block mb-1 font-medium">Origen</label>
            <Select v-model="form.id_origen" :options="lugares" optionLabel="nombre" optionValue="id" filter placeholder="Origen..." class="w-full" :showClear="true" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Destino</label>
            <Select v-model="form.id_destino" :options="lugares" optionLabel="nombre" optionValue="id" filter placeholder="Destino..." class="w-full" :showClear="true" required />
          </div>
        </div>
        <div>
          <label class="block mb-1 font-medium">Embalaje</label>
          <Select v-model="form.id_embalaje" :options="embalajes" optionLabel="nombre" optionValue="id" filter placeholder="Seleccione el embalaje..." class="w-full" :showClear="true" required />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block mb-1 font-medium">Viajes</label>
            <InputNumber v-model="form.viajes" :min="0" class="w-full" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">KMS Totales</label>
            <InputNumber v-model="form.kms_totales" :min="0" :max-fraction-digits="2" class="w-full" required />
          </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block mb-1 font-medium">KMS Carga</label>
            <InputNumber v-model="form.kms_carga" :min="0" :max-fraction-digits="2" class="w-full" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Tiempo demanda</label>
            <InputNumber v-model="form.tiempo_demanda" :min="0" :max-fraction-digits="2" class="w-full" required />
          </div>
        </div>
        <div>
          <label class="block mb-1 font-medium">Tiempo aceptación</label>
          <InputNumber v-model="form.tiempo_aceptacion" :min="0" :max-fraction-digits="2" class="w-full" required />
        </div>
        <div>
          <label class="block mb-1 font-medium">Observaciones</label>
          <InputText v-model="form.observaciones" class="w-full" />
        </div>
        <div>
          <label class="block mb-1 font-medium">Estado</label>
          <Select v-model="form.estado" :options="['activa', 'completada', 'cancelada']" placeholder="Estado..." class="w-full" required />
        </div>
        <div class="flex gap-2 justify-end">
          <Button label="Cancelar" severity="secondary" @click="showForm = false" />
          <Button label="Guardar" type="submit" icon="pi pi-save" />
        </div>
      </form>
    </Dialog>
  </AppLayout>
</template>
