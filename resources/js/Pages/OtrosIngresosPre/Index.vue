<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ items: Object, cartasPorte: Array, tiposIngreso: Array, filters: Object })
const toast = useToast()
const showForm = ref(false)
const editing = ref(null)
const form = ref({ id_carta_porte: null, id_tipo_ingreso: null, cantidad: null, importe_mn: null })
const title = 'Otros Ingresos'

function onPage(event) {
  router.get(route('otros-ingresos-pre.index'), { page: event.page + 1 }, { preserveState: true, replace: true })
}

function openCreate() {
  editing.value = null
  form.value = { id_carta_porte: null, id_tipo_ingreso: null, cantidad: null, importe_mn: null }
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  form.value = { id_carta_porte: item.id_carta_porte, id_tipo_ingreso: item.id_tipo_ingreso, cantidad: item.cantidad, importe_mn: item.importe_mn }
  showForm.value = true
}

function submit() {
  const url = editing.value ? route('otros-ingresos-pre.update', editing.value.id) : route('otros-ingresos-pre.store')
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

      <DataTable :value="items.data" striped-rows paginator :rows="20" :total-records="items.total" :lazy="true" :first="(items.current_page - 1) * items.per_page" @page="onPage" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros">
        <Column header="Carta de Porte">
          <template #body="{ data }">{{ data.cartaPorte?.numero }}</template>
        </Column>
        <Column header="Tipo de Ingreso">
          <template #body="{ data }">{{ data.tipoIngreso?.nombre }}</template>
        </Column>
        <Column field="cantidad" header="Cantidad" />
        <Column field="importe_mn" header="Importe MN" />
        <Column header="Acciones" style="width: 120px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
              <Button icon="pi pi-trash" rounded text severity="danger" @click="router.delete(route('otros-ingresos-pre.destroy', data.id))" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar Ingreso' : 'Nuevo Ingreso'" modal style="width: 500px">
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block mb-1 font-medium">Carta de Porte</label>
          <Select v-model="form.id_carta_porte" :options="cartasPorte" optionLabel="numero" optionValue="id" filter placeholder="Seleccione la carta..." class="w-full" :showClear="true" required />
        </div>
        <div>
          <label class="block mb-1 font-medium">Tipo de Ingreso</label>
          <Select v-model="form.id_tipo_ingreso" :options="tiposIngreso" optionLabel="nombre" optionValue="id" filter placeholder="Seleccione el tipo..." class="w-full" :showClear="true" required />
        </div>
        <div>
          <label class="block mb-1 font-medium">Cantidad</label>
          <InputNumber v-model="form.cantidad" :min="0" class="w-full" required />
        </div>
        <div>
          <label class="block mb-1 font-medium">Importe MN</label>
          <InputNumber v-model="form.importe_mn" :min="0" :max-fraction-digits="2" class="w-full" required />
        </div>
        <div class="flex gap-2 justify-end">
          <Button label="Cancelar" severity="secondary" @click="showForm = false" />
          <Button label="Guardar" type="submit" icon="pi pi-save" />
        </div>
      </form>
    </Dialog>
  </AppLayout>
</template>
