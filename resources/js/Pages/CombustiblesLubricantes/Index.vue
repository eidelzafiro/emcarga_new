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

const props = defineProps({ items: Object, cargas: Array, tractivos: Array, lubricantes: Array, causas: Array, filters: Object })
const toast = useToast()
const search = ref(props.filters?.id_tractivo || '')
const showForm = ref(false)
const editing = ref(null)
const form = ref({ id_carga: null, id_tractivo: null, id_tipo_lubricante: null, id_causa: null, fecha: null, folio: null, cantidad: null, importe_mn: null, observaciones: '' })
const title = 'Comb. Lubricantes'

watch(search, () => {
  router.get(route('combustibles-lubricantes.index'), { id_tractivo: search.value }, { preserveState: true, replace: true })
})

function onPage(event) {
  router.get(route('combustibles-lubricantes.index'), { page: event.page + 1, id_tractivo: search.value }, { preserveState: true, replace: true })
}

function openCreate() {
  editing.value = null
  form.value = { id_carga: null, id_tractivo: null, id_tipo_lubricante: null, id_causa: null, fecha: null, folio: null, cantidad: null, importe_mn: null, observaciones: '' }
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  form.value = {
    id_carga: item.id_carga,
    id_tractivo: item.id_tractivo,
    id_tipo_lubricante: item.id_tipo_lubricante,
    id_causa: item.id_causa,
    fecha: item.fecha,
    folio: item.folio,
    cantidad: item.cantidad,
    importe_mn: item.importe_mn,
    observaciones: item.observaciones || '',
  }
  showForm.value = true
}

function submit() {
  const url = editing.value ? route('combustibles-lubricantes.update', editing.value.id) : route('combustibles-lubricantes.store')
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
          <Select v-model="search" :options="tractivos" optionLabel="codigo" optionValue="id" filter placeholder="Filtrar por tractivo..." class="w-64" :showClear="true" @change="router.get(route('combustibles-lubricantes.index'), { id_tractivo: search }, { preserveState: true, replace: true })" />
        </template>
      </Toolbar>

      <DataTable :value="items.data" striped-rows paginator :rows="20" :total-records="items.total" :lazy="true" :first="(items.current_page - 1) * items.per_page" @page="onPage" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros">
        <Column field="fecha" header="Fecha" />
        <Column field="folio" header="Folio" />
        <Column header="Tractivo">
          <template #body="{ data }">{{ data.tractivo?.codigo }}</template>
        </Column>
        <Column header="Lubricante">
          <template #body="{ data }">{{ data.tipo_lubricante?.nombre }}</template>
        </Column>
        <Column header="Causa">
          <template #body="{ data }">{{ data.causa?.nombre }}</template>
        </Column>
        <Column field="cantidad" header="Cantidad" />
        <Column field="importe_mn" header="Importe MN" />
        <Column header="Acciones" style="width: 120px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
              <Button icon="pi pi-trash" rounded text severity="danger" @click="router.delete(route('combustibles-lubricantes.destroy', data.id))" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar Comb. Lubricante' : 'Nuevo Comb. Lubricante'" modal style="width: 600px">
      <form @submit.prevent="submit" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block mb-1 font-medium">Carga</label>
            <Select v-model="form.id_carga" :options="cargas" optionLabel="numero" optionValue="id" filter placeholder="Seleccione la carga..." class="w-full" :showClear="true" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Tractivo</label>
            <Select v-model="form.id_tractivo" :options="tractivos" optionLabel="codigo" optionValue="id" filter placeholder="Seleccione el tractivo..." class="w-full" :showClear="true" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Lubricante</label>
            <Select v-model="form.id_tipo_lubricante" :options="lubricantes" optionLabel="nombre" optionValue="id" filter placeholder="Seleccione el lubricante..." class="w-full" :showClear="true" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Causa</label>
            <Select v-model="form.id_causa" :options="causas" optionLabel="nombre" optionValue="id" filter placeholder="Seleccione la causa..." class="w-full" :showClear="true" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Fecha</label>
            <InputText v-model="form.fecha" type="date" class="w-full" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Folio</label>
            <InputText v-model="form.folio" class="w-full" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Cantidad</label>
            <InputNumber v-model="form.cantidad" :min="0" :max-fraction-digits="2" class="w-full" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Importe MN</label>
            <InputNumber v-model="form.importe_mn" :min="0" :max-fraction-digits="2" class="w-full" required />
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
