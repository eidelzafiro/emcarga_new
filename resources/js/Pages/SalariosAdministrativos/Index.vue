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

const props = defineProps({ items: Object, filters: Object })
const toast = useToast()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)
const form = ref({ fecha: null, feriados: null, irregular: null, cpl: null, alimentos_extra: null, dias_taller: null, h_extra: null, imp_h_extra: null, observaciones: '', estado: 'borrador' })
const title = 'Salarios Administrativos'
const estados = [
  { label: 'Borrador', value: 'borrador' },
  { label: 'Aprobado', value: 'aprobado' },
  { label: 'Cerrado', value: 'cerrado' },
]

watch(search, () => {
  router.get(route('salarios-administrativos.index'), { search: search.value }, { preserveState: true, replace: true })
})

function onPage(event) {
  router.get(route('salarios-administrativos.index'), { page: event.page + 1, search: search.value }, { preserveState: true, replace: true })
}

function openCreate() {
  editing.value = null
  form.value = { fecha: null, feriados: null, irregular: null, cpl: null, alimentos_extra: null, dias_taller: null, h_extra: null, imp_h_extra: null, observaciones: '', estado: 'borrador' }
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  form.value = {
    fecha: item.fecha ? item.fecha.slice(0, 10) : null,
    feriados: item.feriados ?? null,
    irregular: item.irregular ?? null,
    cpl: item.cpl ?? null,
    alimentos_extra: item.alimentos_extra ?? null,
    dias_taller: item.dias_taller ?? null,
    h_extra: item.h_extra ?? null,
    imp_h_extra: item.imp_h_extra ?? null,
    observaciones: item.observaciones || '',
    estado: item.estado,
  }
  showForm.value = true
}

function submit() {
  const url = editing.value ? route('salarios-administrativos.update', editing.value.id) : route('salarios-administrativos.store')
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

      <DataTable :value="items.data" striped-rows paginator :rows="20" :total-records="items.total" :lazy="true" :first="(items.current_page - 1) * items.per_page" @page="onPage" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros">
        <Column header="Fecha" :style="{ width: '120px' }">
          <template #body="{ data }">{{ data.fecha }}</template>
        </Column>
        <Column field="feriados" header="Feriados" />
        <Column field="irregular" header="Irregular" />
        <Column field="cpl" header="CPL" />
        <Column field="alimentos_extra" header="Alim. Extra" />
        <Column field="dias_taller" header="Días Taller" />
        <Column field="h_extra" header="H. Extra" />
        <Column field="imp_h_extra" header="Imp. H. Extra" />
        <Column header="Estado" :style="{ width: '110px' }">
          <template #body="{ data }">
            <span class="capitalize">{{ data.estado }}</span>
          </template>
        </Column>
        <Column header="Acciones" style="width: 120px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
              <Button icon="pi pi-trash" rounded text severity="danger" @click="router.delete(route('salarios-administrativos.destroy', data.id))" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar Salario Administrativo' : 'Nuevo Salario Administrativo'" modal style="width: 650px">
      <form @submit.prevent="submit" class="space-y-4">
        <div class="grid grid-cols-3 gap-4">
          <div class="col-span-3">
            <label class="block mb-1 font-medium">Fecha</label>
            <InputText v-model="form.fecha" type="date" class="w-full" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Feriados</label>
            <InputNumber v-model="form.feriados" :min="0" :max-fraction-digits="2" class="w-full" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Irregular</label>
            <InputNumber v-model="form.irregular" :min="0" :max-fraction-digits="2" class="w-full" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">CPL</label>
            <InputNumber v-model="form.cpl" :min="0" :max-fraction-digits="2" class="w-full" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Alimentos Extra</label>
            <InputNumber v-model="form.alimentos_extra" :min="0" :max-fraction-digits="2" class="w-full" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Días Taller</label>
            <InputNumber v-model="form.dias_taller" :min="0" :max-fraction-digits="2" class="w-full" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">H. Extra</label>
            <InputNumber v-model="form.h_extra" :min="0" :max-fraction-digits="2" class="w-full" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Imp. H. Extra</label>
            <InputNumber v-model="form.imp_h_extra" :min="0" :max-fraction-digits="2" class="w-full" required />
          </div>
          <div class="col-span-3">
            <label class="block mb-1 font-medium">Estado</label>
            <Select v-model="form.estado" :options="estados" optionLabel="label" optionValue="value" class="w-full" required />
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
