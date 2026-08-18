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

const props = defineProps({ estados: Object, tarjetas: Array, filters: Object })
const toast = useToast()
const showForm = ref(false)
const editing = ref(null)
const form = ref({ id_tarjeta: null, fecha_movimiento: null, saldo_mn: null, saldo_mlc: null, comprobante: '', observaciones: '' })
const title = 'Estados Tarjetas'

function onPage(event) {
  router.get(route('estados-tarjetas.index'), { page: event.page + 1 }, { preserveState: true, replace: true })
}

function openCreate() {
  editing.value = null
  form.value = { id_tarjeta: null, fecha_movimiento: null, saldo_mn: null, saldo_mlc: null, comprobante: '', observaciones: '' }
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  form.value = {
    id_tarjeta: item.id_tarjeta,
    fecha_movimiento: item.fecha_movimiento ? item.fecha_movimiento.slice(0, 10) : null,
    saldo_mn: item.saldo_mn, saldo_mlc: item.saldo_mlc, comprobante: item.comprobante || '', observaciones: item.observaciones || '',
  }
  showForm.value = true
}

function submit() {
  const url = editing.value ? route('estados-tarjetas.update', editing.value.id) : route('estados-tarjetas.store')
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

      <DataTable :value="estados.data" striped-rows paginator :rows="20" :total-records="estados.total" :lazy="true" :first="(estados.current_page - 1) * estados.per_page" @page="onPage" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros">
        <Column header="Tarjeta">
          <template #body="{ data }">{{ data.tarjeta?.numero }} {{ data.tarjeta?.descripcion }}</template>
        </Column>
        <Column field="fecha_movimiento" header="Fecha" />
        <Column header="Entrega">
          <template #body="{ data }">{{ data.entrega?.name }}</template>
        </Column>
        <Column header="Recibe">
          <template #body="{ data }">{{ data.recibe?.name }}</template>
        </Column>
        <Column field="saldo_mn" header="Saldo MN" />
        <Column field="saldo_mlc" header="Saldo MLC" />
        <Column field="comprobante" header="Comprobante" />
        <Column header="Acciones" style="width: 120px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
              <Button icon="pi pi-trash" rounded text severity="danger" @click="router.delete(route('estados-tarjetas.destroy', data.id))" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar Estado' : 'Nuevo Estado'" modal style="width: 500px">
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block mb-1 font-medium">Tarjeta</label>
          <Select v-model="form.id_tarjeta" :options="tarjetas" optionLabel="numero" optionValue="id" filter placeholder="Seleccione la tarjeta..." class="w-full" :showClear="true" required />
        </div>
        <div>
          <label class="block mb-1 font-medium">Fecha de movimiento</label>
          <InputText v-model="form.fecha_movimiento" type="date" class="w-full" required />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block mb-1 font-medium">Saldo MN</label>
            <InputNumber v-model="form.saldo_mn" :max-fraction-digits="2" class="w-full" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Saldo MLC</label>
            <InputNumber v-model="form.saldo_mlc" :max-fraction-digits="2" class="w-full" required />
          </div>
        </div>
        <div>
          <label class="block mb-1 font-medium">Comprobante</label>
          <InputText v-model="form.comprobante" maxlength="50" class="w-full" />
        </div>
        <div>
          <label class="block mb-1 font-medium">Observaciones</label>
          <InputText v-model="form.observaciones" class="w-full" />
        </div>
        <div class="flex gap-2 justify-end">
          <Button label="Cancelar" severity="secondary" @click="showForm = false" />
          <Button label="Guardar" type="submit" icon="pi pi-save" />
        </div>
      </form>
    </Dialog>
  </AppLayout>
</template>
