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

const props = defineProps({ distancias: Object, lugares: Array, filters: Object })
const toast = useToast()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)
const form = ref({ id_lugar_origen: null, id_lugar_destino: null, distancia_km: null, activo: true })
const title = 'Distancias'

watch(search, () => {
  router.get(route('distancias.index'), { search: search.value }, { preserveState: true, replace: true })
})

function onPage(event) {
  router.get(route('distancias.index'), { page: event.page + 1, search: search.value }, { preserveState: true, replace: true })
}

function openCreate() {
  editing.value = null
  form.value = { id_lugar_origen: null, id_lugar_destino: null, distancia_km: null, activo: true }
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  form.value = { id_lugar_origen: item.id_lugar_origen, id_lugar_destino: item.id_lugar_destino, distancia_km: item.distancia_km, activo: Boolean(item.activo) }
  showForm.value = true
}

function submit() {
  const url = editing.value ? route('distancias.update', editing.value.id) : route('distancias.store')
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

      <DataTable :value="distancias.data" striped-rows paginator :rows="20" :total-records="distancias.total" :lazy="true" :first="(distancias.current_page - 1) * distancias.per_page" @page="onPage" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros">
        <Column header="Origen">
          <template #body="{ data }">{{ data.origen?.nombre }}</template>
        </Column>
        <Column header="Destino">
          <template #body="{ data }">{{ data.destino?.nombre }}</template>
        </Column>
        <Column field="distancia_km" header="KMS" />
        <Column header="Acciones" style="width: 120px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
              <Button icon="pi pi-trash" rounded text severity="danger" @click="router.delete(route('distancias.destroy', data.id))" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar Distancia' : 'Nueva Distancia'" modal style="width: 500px">
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block mb-1 font-medium">Origen</label>
          <Select v-model="form.id_lugar_origen" :options="lugares" optionLabel="nombre" optionValue="id" filter placeholder="Seleccione el origen..." class="w-full" :showClear="true" required />
        </div>
        <div>
          <label class="block mb-1 font-medium">Destino</label>
          <Select v-model="form.id_lugar_destino" :options="lugares" optionLabel="nombre" optionValue="id" filter placeholder="Seleccione el destino..." class="w-full" :showClear="true" required />
        </div>
        <div>
          <label class="block mb-1 font-medium">KMS</label>
          <InputNumber v-model="form.distancia_km" :min="0" :max-fraction-digits="2" class="w-full" required />
        </div>
        <div class="flex gap-2 justify-end">
          <Button label="Cancelar" severity="secondary" @click="showForm = false" />
          <Button label="Guardar" type="submit" icon="pi pi-save" />
        </div>
      </form>
    </Dialog>
  </AppLayout>
</template>