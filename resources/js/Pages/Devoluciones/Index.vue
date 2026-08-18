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
const title = 'Devoluciones'

function baseForm() {
  return {
    id_carta_porte: null,
    id_cliente: null,
    id_cliente_mm: null,
    id_tractivo: null,
    id_empleado: null,
    fecha: null,
    aumento_flete_mn: null,
    aumento_flete_me: null,
    aumento_demora: null,
    aumento_salario: null,
    aumento_alquiler: null,
    aumento_izaje: null,
    disminucion_flete_mn: null,
    disminucion_flete_me: null,
    disminucion_demora: null,
    disminucion_salario: null,
    disminucion_alquiler: null,
    disminucion_izaje: null,
    observaciones: '',
  }
}

watch(search, () => {
  router.get(route('devoluciones.index'), { search: search.value }, { preserveState: true, replace: true })
})

function onPage(event) {
  router.get(route('devoluciones.index'), { page: event.page + 1, search: search.value }, { preserveState: true, replace: true })
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
  const url = editing.value ? route('devoluciones.update', editing.value.id) : route('devoluciones.store')
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
        <Column field="fecha" header="Fecha" />
        <Column field="id_carta_porte" header="Carta Porte" />
        <Column field="id_cliente" header="Cliente" />
        <Column field="id_tractivo" header="Tractivo" />
        <Column field="aumento_flete_mn" header="Aum. Flete MN" />
        <Column field="disminucion_flete_mn" header="Dism. Flete MN" />
        <Column field="observaciones" header="Observaciones" />
        <Column header="Acciones" style="width: 120px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
              <Button icon="pi pi-trash" rounded text severity="danger" @click="router.delete(route('devoluciones.destroy', data.id))" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar Devolución' : 'Nueva Devolución'" modal style="width: 760px">
      <form @submit.prevent="submit" class="space-y-4">
        <div class="grid grid-cols-3 gap-4">
          <div>
            <label class="block mb-1 font-medium">Carta Porte</label>
            <InputNumber v-model="form.id_carta_porte" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Cliente</label>
            <InputNumber v-model="form.id_cliente" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Cliente MM</label>
            <InputNumber v-model="form.id_cliente_mm" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Tractivo</label>
            <InputNumber v-model="form.id_tractivo" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Empleado</label>
            <InputNumber v-model="form.id_empleado" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Fecha</label>
            <InputText v-model="form.fecha" type="date" class="w-full" />
          </div>
        </div>
        <div class="grid grid-cols-3 gap-4">
          <div>
            <label class="block mb-1 font-medium">Aumento Flete MN</label>
            <InputNumber v-model="form.aumento_flete_mn" :min="0" :max-fraction-digits="2" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Aumento Flete ME</label>
            <InputNumber v-model="form.aumento_flete_me" :min="0" :max-fraction-digits="2" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Aumento Demora</label>
            <InputNumber v-model="form.aumento_demora" :min="0" :max-fraction-digits="2" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Aumento Salario</label>
            <InputNumber v-model="form.aumento_salario" :min="0" :max-fraction-digits="2" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Aumento Alquiler</label>
            <InputNumber v-model="form.aumento_alquiler" :min="0" :max-fraction-digits="2" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Aumento Izaje</label>
            <InputNumber v-model="form.aumento_izaje" :min="0" :max-fraction-digits="2" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Dism. Flete MN</label>
            <InputNumber v-model="form.disminucion_flete_mn" :min="0" :max-fraction-digits="2" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Dism. Flete ME</label>
            <InputNumber v-model="form.disminucion_flete_me" :min="0" :max-fraction-digits="2" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Dism. Demora</label>
            <InputNumber v-model="form.disminucion_demora" :min="0" :max-fraction-digits="2" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Dism. Salario</label>
            <InputNumber v-model="form.disminucion_salario" :min="0" :max-fraction-digits="2" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Dism. Alquiler</label>
            <InputNumber v-model="form.disminucion_alquiler" :min="0" :max-fraction-digits="2" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Dism. Izaje</label>
            <InputNumber v-model="form.disminucion_izaje" :min="0" :max-fraction-digits="2" class="w-full" />
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
