<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import Checkbox from 'primevue/checkbox'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ items: Object, filters: Object })
const toast = useToast()
const search = ref('')
const showForm = ref(false)
const editing = ref(null)
const form = ref({ mensaje: '', fecha_emision: null, fecha_vencimiento: null, vencida: false })
const title = 'Alertas'

function onPage(event) {
  router.get(route('alertas.index'), { page: event.page + 1 }, { preserveState: true, replace: true })
}

function openCreate() {
  editing.value = null
  form.value = { mensaje: '', fecha_emision: null, fecha_vencimiento: null, vencida: false }
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  form.value = {
    mensaje: item.mensaje,
    fecha_emision: item.fecha_emision ? item.fecha_emision.slice(0, 10) : null,
    fecha_vencimiento: item.fecha_vencimiento ? item.fecha_vencimiento.slice(0, 10) : null,
    vencida: Boolean(item.vencida),
  }
  showForm.value = true
}

function submit() {
  const url = editing.value ? route('alertas.update', editing.value.id) : route('alertas.store')
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
        <Column field="mensaje" header="Mensaje" />
        <Column field="fecha_emision" header="Emisión" />
        <Column field="fecha_vencimiento" header="Vencimiento" />
        <Column header="Vencida">
          <template #body="{ data }">
            <span v-if="data.vencida" class="pi pi-check text-red-500"></span>
            <span v-else class="pi pi-minus text-gray-400"></span>
          </template>
        </Column>
        <Column header="Usuario">
          <template #body="{ data }">{{ data.user?.name }}</template>
        </Column>
        <Column header="Acciones" style="width: 120px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
              <Button icon="pi pi-trash" rounded text severity="danger" @click="router.delete(route('alertas.destroy', data.id))" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar Alerta' : 'Nueva Alerta'" modal style="width: 500px">
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block mb-1 font-medium">Mensaje</label>
          <InputText v-model="form.mensaje" class="w-full" required />
        </div>
        <div>
          <label class="block mb-1 font-medium">Fecha de emisión</label>
          <InputText v-model="form.fecha_emision" type="date" class="w-full" />
        </div>
        <div>
          <label class="block mb-1 font-medium">Fecha de vencimiento</label>
          <InputText v-model="form.fecha_vencimiento" type="date" class="w-full" />
        </div>
        <div class="flex items-center gap-2">
          <Checkbox v-model="form.vencida" :binary="true" input-id="vencida" />
          <label for="vencida">Vencida</label>
        </div>
        <div class="flex gap-2 justify-end">
          <Button label="Cancelar" severity="secondary" @click="showForm = false" />
          <Button label="Guardar" type="submit" icon="pi pi-save" />
        </div>
      </form>
    </Dialog>
  </AppLayout>
</template>
