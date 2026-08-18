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

const props = defineProps({ salarios: Object, bolsas: Array, areas: Array, cargos: Array, filters: Object })
const toast = useToast()
const search = ref(props.filters?.search || '')
const mes = ref(props.filters?.mes || null)
const ano = ref(props.filters?.ano || null)
const showForm = ref(false)
const editing = ref(null)
const form = ref({ mes: null, ano: null, id_bolsa: null, numero_nomina: '', salario_base: null, observaciones: '', estado: 'borrador' })
const title = 'Salarios'
const meses = [
  { label: 'Enero', value: 1 },
  { label: 'Febrero', value: 2 },
  { label: 'Marzo', value: 3 },
  { label: 'Abril', value: 4 },
  { label: 'Mayo', value: 5 },
  { label: 'Junio', value: 6 },
  { label: 'Julio', value: 7 },
  { label: 'Agosto', value: 8 },
  { label: 'Septiembre', value: 9 },
  { label: 'Octubre', value: 10 },
  { label: 'Noviembre', value: 11 },
  { label: 'Diciembre', value: 12 },
]
const estados = [
  { label: 'Borrador', value: 'borrador' },
  { label: 'Aprobado', value: 'aprobado' },
  { label: 'Cerrado', value: 'cerrado' },
]

function cargar() {
  router.get(route('salarios.index'), { search: search.value, mes: mes.value || undefined, ano: ano.value || undefined }, { preserveState: true, replace: true })
}

watch(search, cargar)
watch(mes, cargar)
watch(ano, cargar)

function onPage(event) {
  router.get(route('salarios.index'), { page: event.page + 1, search: search.value, mes: mes.value || undefined, ano: ano.value || undefined }, { preserveState: true, replace: true })
}

function nombreMes(m) {
  const encontrado = meses.find(x => x.value === Number(m))
  return encontrado ? encontrado.label : m
}

function openCreate() {
  editing.value = null
  form.value = { mes: null, ano: null, id_bolsa: null, numero_nomina: '', salario_base: null, observaciones: '', estado: 'borrador' }
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  form.value = {
    mes: item.mes !== null ? Number(item.mes) : null,
    ano: item.ano,
    id_bolsa: item.id_bolsa,
    numero_nomina: item.numero_nomina || '',
    salario_base: item.salario_base !== null ? Number(item.salario_base) : null,
    observaciones: item.observaciones || '',
    estado: item.estado,
  }
  showForm.value = true
}

function submit() {
  const url = editing.value ? route('salarios.update', editing.value.id) : route('salarios.store')
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
          <div class="flex items-center gap-2 flex-wrap">
            <Select v-model="mes" :options="meses" optionLabel="label" optionValue="value" placeholder="Mes" class="w-40" :showClear="true" />
            <InputNumber v-model="ano" :min="2000" placeholder="Año" class="w-28" />
            <InputText v-model="search" placeholder="Nº nómina..." />
          </div>
        </template>
      </Toolbar>

      <DataTable :value="salarios.data" striped-rows paginator :rows="20" :total-records="salarios.total" :lazy="true" :first="(salarios.current_page - 1) * salarios.per_page" @page="onPage" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros">
        <Column header="Periodo" :style="{ width: '130px' }">
          <template #body="{ data }">{{ nombreMes(data.mes) }} {{ data.ano }}</template>
        </Column>
        <Column field="numero_nomina" header="Nº Nómina" />
        <Column header="Bolsa">
          <template #body="{ data }">{{ data.bolsa?.nombre || '—' }}</template>
        </Column>
        <Column header="Área">
          <template #body="{ data }">{{ data.area?.nombre || '—' }}</template>
        </Column>
        <Column header="Cargo">
          <template #body="{ data }">{{ data.cargo?.nombre || '—' }}</template>
        </Column>
        <Column header="Salario Base">
          <template #body="{ data }">{{ Number(data.salario_base).toFixed(2) }}</template>
        </Column>
        <Column header="Estado" :style="{ width: '110px' }">
          <template #body="{ data }">
            <span class="capitalize">{{ data.estado }}</span>
          </template>
        </Column>
        <Column header="Acciones" style="width: 120px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
              <Button icon="pi pi-trash" rounded text severity="danger" @click="router.delete(route('salarios.destroy', data.id))" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar Salario' : 'Nuevo Salario'" modal style="width: 600px">
      <form @submit.prevent="submit" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block mb-1 font-medium">Mes</label>
            <Select v-model="form.mes" :options="meses" optionLabel="label" optionValue="value" class="w-full" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Año</label>
            <InputNumber v-model="form.ano" :min="2000" class="w-full" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Bolsa</label>
            <Select v-model="form.id_bolsa" :options="bolsas" optionLabel="nombre" optionValue="id" filter placeholder="Seleccione..." class="w-full" :showClear="true" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Nº Nómina</label>
            <InputText v-model="form.numero_nomina" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Salario Base</label>
            <InputNumber v-model="form.salario_base" :min="0" :max-fraction-digits="2" class="w-full" required />
          </div>
          <div>
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
