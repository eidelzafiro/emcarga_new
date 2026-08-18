<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
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
const title = 'Pagos Adicionales de Cargo'

function baseForm() {
  return {
    id_cargo: null,
    id_tipo_pago_adicional: null,
    monto: null,
  }
}

watch(search, () => {
  router.get(route('pagos-adicionales-cargo.index'), { search: search.value }, { preserveState: true, replace: true })
})

function onPage(event) {
  router.get(route('pagos-adicionales-cargo.index'), { page: event.page + 1, search: search.value }, { preserveState: true, replace: true })
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
  const url = editing.value ? route('pagos-adicionales-cargo.update', editing.value.id) : route('pagos-adicionales-cargo.store')
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
      </Toolbar>

      <DataTable :value="items.data" striped-rows paginator :rows="50" :total-records="items.total" :lazy="true" :first="(items.current_page - 1) * items.per_page" @page="onPage" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros">
        <Column field="id" header="ID" />
        <Column field="id_cargo" header="Cargo" />
        <Column field="id_tipo_pago_adicional" header="Tipo Pago Adicional" />
        <Column field="monto" header="Monto" />
        <Column header="Acciones" style="width: 120px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
              <Button icon="pi pi-trash" rounded text severity="danger" @click="router.delete(route('pagos-adicionales-cargo.destroy', data.id))" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar Pago Adicional' : 'Nuevo Pago Adicional'" modal style="width: 480px">
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block mb-1 font-medium">Cargo</label>
          <InputNumber v-model="form.id_cargo" class="w-full" />
        </div>
        <div>
          <label class="block mb-1 font-medium">Tipo Pago Adicional</label>
          <InputNumber v-model="form.id_tipo_pago_adicional" class="w-full" />
        </div>
        <div>
          <label class="block mb-1 font-medium">Monto</label>
          <InputNumber v-model="form.monto" :min="0" :max-fraction-digits="2" class="w-full" />
        </div>
        <div class="flex gap-2 justify-end">
          <Button label="Cancelar" severity="secondary" @click="showForm = false" />
          <Button label="Guardar" type="submit" icon="pi pi-save" />
        </div>
      </form>
    </Dialog>
  </AppLayout>
</template>
