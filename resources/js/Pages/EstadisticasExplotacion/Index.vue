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
import { useToast } from 'primevue/usetoast'

const props = defineProps({ items: Object, filters: Object })
const toast = useToast()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)
const form = ref({})
const title = 'Estad. Explotación'

function baseForm() {
  return {
    id_hoja_ruta: null,
    fecha_indicadores: null,
    viajes: null,
    kms_carga: null,
    kms_vacio: null,
    kms_total: null,
    toneladas_posibles: null,
    toneladas_reales: null,
    trafico_posible: null,
    trafico_producido: null,
  }
}

watch(search, () => {
  router.get(route('estadisticas-explotacion.index'), { search: search.value }, { preserveState: true, replace: true })
})

function onPage(event) {
  router.get(route('estadisticas-explotacion.index'), { page: event.page + 1, search: search.value }, { preserveState: true, replace: true })
}

function openCreate() {
  editing.value = null
  form.value = baseForm()
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  form.value = Object.keys(baseForm()).reduce((acc, k) => { acc[k] = item[k] ?? null; return acc }, {})
  showForm.value = true
}

function submit() {
  const url = editing.value ? route('estadisticas-explotacion.update', editing.value.id) : route('estadisticas-explotacion.store')
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
        <Column field="id" header="ID" />
        <Column field="id_hoja_ruta" header="Hoja Ruta" />
        <Column field="fecha_indicadores" header="Fecha" />
        <Column field="viajes" header="Viajes" />
        <Column field="kms_carga" header="KMS Carga" />
        <Column field="kms_vacio" header="KMS Vacío" />
        <Column field="kms_total" header="KMS Total" />
        <Column field="toneladas_reales" header="Ton. Reales" />
        <Column field="trafico_producido" header="Tráf. Producido" />
        <Column header="Acciones" style="width: 120px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
              <Button icon="pi pi-trash" rounded text severity="danger" @click="router.delete(route('estadisticas-explotacion.destroy', data.id))" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar Estadística' : 'Nueva Estadística'" modal style="width: 620px">
      <form @submit.prevent="submit" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block mb-1 font-medium">Hoja de Ruta</label>
            <InputNumber v-model="form.id_hoja_ruta" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Fecha Indicadores</label>
            <InputText v-model="form.fecha_indicadores" type="date" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Viajes</label>
            <InputNumber v-model="form.viajes" :min="0" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">KMS Carga</label>
            <InputNumber v-model="form.kms_carga" :min="0" :max-fraction-digits="2" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">KMS Vacío</label>
            <InputNumber v-model="form.kms_vacio" :min="0" :max-fraction-digits="2" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">KMS Total</label>
            <InputNumber v-model="form.kms_total" :min="0" :max-fraction-digits="2" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Toneladas Posibles</label>
            <InputNumber v-model="form.toneladas_posibles" :min="0" :max-fraction-digits="2" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Toneladas Reales</label>
            <InputNumber v-model="form.toneladas_reales" :min="0" :max-fraction-digits="2" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Tráfico Posible</label>
            <InputNumber v-model="form.trafico_posible" :min="0" :max-fraction-digits="2" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Tráfico Producido</label>
            <InputNumber v-model="form.trafico_producido" :min="0" :max-fraction-digits="2" class="w-full" />
          </div>
        </div>
        <div class="flex gap-2 justify-end">
          <Button label="Cancelar" severity="secondary" @click="showForm = false" />
          <Button label="Guardar" type="submit" icon="pi pi-save" />
        </div>
      </form>
    </Dialog>
  </AppLayout>
</template>
