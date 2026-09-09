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
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ items: Object, filters: Object })
const toast = useToast()
const confirm = useConfirm()

const MESES = [
  { id: 1, nombre: 'Enero' }, { id: 2, nombre: 'Febrero' }, { id: 3, nombre: 'Marzo' },
  { id: 4, nombre: 'Abril' }, { id: 5, nombre: 'Mayo' }, { id: 6, nombre: 'Junio' },
  { id: 7, nombre: 'Julio' }, { id: 8, nombre: 'Agosto' }, { id: 9, nombre: 'Septiembre' },
  { id: 10, nombre: 'Octubre' }, { id: 11, nombre: 'Noviembre' }, { id: 12, nombre: 'Diciembre' },
]

const filtroMes = ref(props.filters?.mes || null)
const filtroAno = ref(props.filters?.ano || null)
const showForm = ref(false)
const editing = ref(null)

function baseForm() {
  return { mes: null, ano: new Date().getFullYear(), cds: 0 }
}
const form = ref(baseForm())

watch([filtroMes, filtroAno], () => reload())

function reload() {
  router.get(route('cds.index'), {
    mes: filtroMes.value || '',
    ano: filtroAno.value || '',
  }, { preserveState: true, replace: true })
}

function onPage(event) {
  router.get(route('cds.index'), {
    page: event.page + 1,
    mes: filtroMes.value || '',
    ano: filtroAno.value || '',
  }, { preserveState: true, replace: true })
}

function openCreate() {
  editing.value = null
  form.value = { ...baseForm(), mes: filtroMes.value || null, ano: filtroAno.value || new Date().getFullYear() }
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  form.value = { mes: item.mes, ano: item.ano, cds: Number(item.cds) }
  showForm.value = true
}

function submit() {
  const url = editing.value ? route('cds.update', editing.value.id) : route('cds.store')
  const method = editing.value ? 'put' : 'post'
  router[method](url, form.value, {
    onSuccess: () => { showForm.value = false; toast.add({ severity: 'success', summary: editing.value ? 'Actualizado' : 'Creado', life: 3000 }) },
    onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
  })
}

function confirmEliminar(item) {
  confirm.require({
    message: `¿Eliminar el CDS de ${nombreMes(item.mes)} ${item.ano}?`,
    header: 'Eliminar CDS',
    acceptLabel: 'Sí, eliminar',
    rejectLabel: 'No',
    accept: () => {
      router.delete(route('cds.destroy', item.id), {
        onSuccess: () => toast.add({ severity: 'success', summary: 'Eliminado', life: 3000 }),
        onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
      })
    },
  })
}

function nombreMes(id) {
  return MESES.find((m) => m.id === Number(id))?.nombre || id
}
</script>

<template>
  <AppLayout :title="title">
    <div class="card">
      <Toolbar class="mb-4">
        <template #start>
          <Button label="Nuevo CDS" icon="pi pi-plus" severity="success" @click="openCreate()" />
        </template>
        <template #end>
          <div class="flex items-center gap-3">
            <Select v-model="filtroMes" :options="MESES" optionLabel="nombre" optionValue="id" placeholder="Mes" class="w-40" showClear />
            <InputText v-model="filtroAno" placeholder="Año" class="w-24" />
          </div>
        </template>
      </Toolbar>

      <DataTable :value="items.data" paginator lazy :rows="items.per_page"
        :total-records="items.total" @page="onPage" dataKey="id"
        :rows-per-page-options="[10, 25, 50, 100]"
        stripedRows class="p-datatable-sm">
        <Column field="id_entidad" header="Entidad">
          <template #body="{ data }">
            <div class="font-medium">{{ data.entidad?.abreviatura || data.entidad?.nombre || '—' }}</div>
            <div class="text-xs text-gray-500">{{ data.entidad?.nombre }}</div>
          </template>
        </Column>
        <Column field="mes" header="Mes">
          <template #body="{ data }">{{ nombreMes(data.mes) }}</template>
        </Column>
        <Column field="ano" header="Año" />
        <Column field="cds" header="CDS" class="text-right">
          <template #body="{ data }">
            <span class="font-mono font-semibold text-blue-700 dark:text-blue-300">{{ Number(data.cds).toFixed(4) }}</span>
          </template>
        </Column>
        <Column header="Acciones" style="width: 8rem">
          <template #body="{ data }">
            <div class="flex gap-1 justify-end">
              <Button icon="pi pi-pencil" rounded text size="small" @click="openEdit(data)" />
              <Button icon="pi pi-trash" rounded text severity="danger" size="small" @click="confirmEliminar(data)" />
            </div>
          </template>
        </Column>
      </DataTable>

      <Dialog v-model:visible="showForm" :header="editing ? 'Editar CDS' : 'Nuevo CDS'" modal class="w-96">
        <div class="flex flex-col gap-4">
          <div>
            <label class="block text-sm font-medium mb-1">Mes</label>
            <Select v-model="form.mes" :options="MESES" optionLabel="nombre" optionValue="id" placeholder="Seleccione" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Año</label>
            <InputNumber v-model="form.ano" :use-grouping="false" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Coeficiente CDS</label>
            <InputNumber v-model="form.cds" :min="0" :maxFractionDigits="6" mode="decimal" class="w-full" />
          </div>
          <div class="flex justify-end gap-2 pt-2">
            <Button label="Cancelar" text @click="showForm = false" />
            <Button label="Guardar" @click="submit()" />
          </div>
        </div>
      </Dialog>
    </div>
  </AppLayout>
</template>
