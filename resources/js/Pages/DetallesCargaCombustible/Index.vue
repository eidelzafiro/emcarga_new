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
import Select from 'primevue/select'
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ detalles: Object, cargas: Array, tractivos: Array, bolsas: Array, filters: Object })
const toast = useToast()
const search = ref(props.filters?.id_carga || '')
const showForm = ref(false)
const editing = ref(null)
const form = ref({ id_carga: null, id_tractivo: null, id_bolsa: null, fecha_movimiento: null, comprobante: null, importe_mn: null, importe_mlc: null, observaciones: '' })
const title = 'Detalles Carga Combustible'

watch(search, () => {
  router.get(route('detalles-carga-combustible.index'), { id_carga: search.value }, { preserveState: true, replace: true })
})

function onPage(event) {
  router.get(route('detalles-carga-combustible.index'), { page: event.page + 1, id_carga: search.value }, { preserveState: true, replace: true })
}

function openCreate() {
  editing.value = null
  form.value = { id_carga: null, id_tractivo: null, id_bolsa: null, fecha_movimiento: null, comprobante: null, importe_mn: null, importe_mlc: null, observaciones: '' }
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  form.value = {
    id_carga: item.id_carga,
    id_tractivo: item.id_tractivo,
    id_bolsa: item.id_bolsa,
    fecha_movimiento: item.fecha_movimiento,
    comprobante: item.comprobante,
    importe_mn: item.importe_mn,
    importe_mlc: item.importe_mlc,
    observaciones: item.observaciones || '',
  }
  showForm.value = true
}

function submit() {
  const url = editing.value ? route('detalles-carga-combustible.update', editing.value.id) : route('detalles-carga-combustible.store')
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
          <Select v-model="search" :options="cargas" optionLabel="numero" optionValue="id" filter placeholder="Filtrar por carga..." class="w-64" :showClear="true" @change="router.get(route('detalles-carga-combustible.index'), { id_carga: search }, { preserveState: true, replace: true })" />
        </template>
      </Toolbar>

      <DataTable :value="detalles.data" striped-rows paginator :rows="20" :total-records="detalles.total" :lazy="true" :first="(detalles.current_page - 1) * detalles.per_page" @page="onPage" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros">
        <Column header="Carga">
          <template #body="{ data }">{{ data.carga?.numero }}</template>
        </Column>
        <Column header="Tractivo">
          <template #body="{ data }">{{ data.tractivo?.codigo }}</template>
        </Column>
        <Column header="Bolsa">
          <template #body="{ data }">{{ data.bolsa?.nombre }}</template>
        </Column>
        <Column field="fecha_movimiento" header="Fecha Mov." />
        <Column field="comprobante" header="Comprobante" />
        <Column field="importe_mn" header="Importe MN" />
        <Column field="importe_mlc" header="Importe MLC" />
        <Column header="Acciones" style="width: 120px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
              <Button icon="pi pi-trash" rounded text severity="danger" @click="router.delete(route('detalles-carga-combustible.destroy', data.id))" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar Detalle' : 'Nuevo Detalle'" modal style="width: 600px">
      <form @submit.prevent="submit" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block mb-1 font-medium">Carga</label>
            <Select v-model="form.id_carga" :options="cargas" optionLabel="numero" optionValue="id" filter placeholder="Seleccione la carga..." class="w-full" :showClear="true" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Tractivo</label>
            <Select v-model="form.id_tractivo" :options="tractivos" optionLabel="codigo" optionValue="id" filter placeholder="Seleccione el tractivo..." class="w-full" :showClear="true" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Bolsa</label>
            <Select v-model="form.id_bolsa" :options="bolsas" optionLabel="nombre" optionValue="id" filter placeholder="Seleccione la bolsa..." class="w-full" :showClear="true" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Fecha Movimiento</label>
            <InputText v-model="form.fecha_movimiento" type="date" class="w-full" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Comprobante</label>
            <InputText v-model="form.comprobante" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Importe MN</label>
            <InputNumber v-model="form.importe_mn" :min="0" :max-fraction-digits="2" class="w-full" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Importe MLC</label>
            <InputNumber v-model="form.importe_mlc" :min="0" :max-fraction-digits="2" class="w-full" required />
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
