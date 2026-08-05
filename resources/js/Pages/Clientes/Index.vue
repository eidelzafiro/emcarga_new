<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import DatePicker from 'primevue/datepicker'
import Select from 'primevue/select'
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ clientes: Object, organismos: Array, monedas: Array, filters: Object })
const toast = useToast()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)
const form = ref({})
const title = 'Clientes'

const pad = (n) => String(n).padStart(2, '0')

function toDate(v) {
  if (!v) return null
  if (v instanceof Date) return v
  if (typeof v === 'string') {
    const m = v.match(/^(\d{4})-(\d{2})-(\d{2})/)
    if (m) return new Date(+m[1], +m[2] - 1, +m[3])
  }
  const d = new Date(v)
  return isNaN(d) ? null : d
}

function fmtDate(v) {
  const d = toDate(v)
  if (!d) return null
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
}

function addYears(v, years) {
  const d = toDate(v)
  if (!d) return null
  return new Date(d.getFullYear() + years, d.getMonth(), d.getDate())
}

function fmtDmy(v) {
  const d = toDate(v)
  if (!d) return v ?? ''
  return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()}`
}

function baseForm() {
  const hoy = new Date()
  return {
    codigo: '', nombre: '', nrocontrato: '', codreup: '', idorganismos: null, idmonedas: null,
    nit: '', direccion: '', email: '', emailfacturacion: '', notas: '', agenciamn: '', ctaimn: '',
    telefono: '', contacto: '', razon_social: '', activo: true,
    falta: fmtDate(hoy), fvencimiento: fmtDate(addYears(hoy, 2)),
  }
}

watch(search, () => {
  router.get(route('clientes.index'), { search: search.value }, { preserveState: true, replace: true })
})

function onPage(event) {
  router.get(route('clientes.index'), { page: event.page + 1, search: search.value }, { preserveState: true, replace: true })
}

function onFaltaChange() {
  form.value.fvencimiento = fmtDate(addYears(form.value.falta, 2))
}

function openCreate() {
  editing.value = null
  form.value = baseForm()
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  form.value = {
    codigo: item.codigo, nombre: item.nombre, nrocontrato: item.nrocontrato, codreup: item.codreup,
    idorganismos: item.idorganismos, idmonedas: item.idmonedas, nit: item.nit, direccion: item.direccion,
    email: item.email, emailfacturacion: item.emailfacturacion, notas: item.notas, agenciamn: item.agenciamn,
    ctaimn: item.ctamn, telefono: item.telefono, contacto: item.contacto, razon_social: item.razon_social,
    activo: Boolean(item.activo), falta: fmtDate(item.falta), fvencimiento: fmtDate(item.fvencimiento),
  }
  showForm.value = true
}

function submit() {
  const url = editing.value ? route('clientes.update', editing.value.id) : route('clientes.store')
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

      <DataTable :value="clientes.data" striped-rows paginator :rows="20" :total-records="clientes.total" :lazy="true" :first="(clientes.current_page - 1) * clientes.per_page" @page="onPage" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros">
        <Column header="Organismo">
          <template #body="{ data }">{{ data.organismo?.abreviatura }}</template>
        </Column>
        <Column field="nrocontrato" header="Folio" />
        <Column field="codigo" header="Código" sortable />
        <Column field="nombre" header="Nombre del cliente" sortable />
        <Column header="Mon">
          <template #body="{ data }">{{ data.moneda?.nombre }}</template>
        </Column>
        <Column header="Alta">
          <template #body="{ data }">{{ fmtDmy(data.falta) }}</template>
        </Column>
        <Column header="Vence">
          <template #body="{ data }">{{ fmtDmy(data.fvencimiento) }}</template>
        </Column>
        <Column field="activo" header="Activo" style="width: 80px">
          <template #body="{ data }">
            <Tag :value="data.activo ? 'Sí' : 'No'" :severity="data.activo ? 'success' : 'danger'" />
          </template>
        </Column>
        <Column header="Acciones" style="width: 120px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
              <Button icon="pi pi-trash" rounded text severity="danger" @click="router.delete(route('clientes.destroy', data.id))" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar Cliente' : 'Nuevo Cliente'" modal style="width: 640px">
      <form @submit.prevent="submit" class="space-y-4 overflow-y-auto max-h-[70vh]">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block mb-1 font-medium">Fecha Alta</label>
            <DatePicker v-model="form.falta" dateFormat="yy-mm-dd" showIcon class="w-full" required @update:modelValue="onFaltaChange" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Vence</label>
            <DatePicker v-model="form.fvencimiento" dateFormat="yy-mm-dd" showIcon class="w-full" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Código</label>
            <InputText v-model="form.codigo" class="w-full" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Contrato</label>
            <InputText v-model="form.nrocontrato" class="w-full" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">REEUP</label>
            <InputText v-model="form.codreup" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Organismo</label>
            <Select v-model="form.idorganismos" :options="organismos" optionLabel="abreviatura" optionValue="id" filter placeholder="Organismo..." class="w-full" :showClear="true" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Moneda</label>
            <Select v-model="form.idmonedas" :options="monedas" optionLabel="nombre" optionValue="id" filter placeholder="Moneda..." class="w-full" :showClear="true" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">NIT</label>
            <InputText v-model="form.nit" class="w-full" required />
          </div>
          <div class="col-span-2">
            <label class="block mb-1 font-medium">Nombre</label>
            <InputText v-model="form.nombre" class="w-full" required />
          </div>
          <div class="col-span-2">
            <label class="block mb-1 font-medium">Dirección</label>
            <Textarea v-model="form.direccion" rows="2" class="w-full" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Email</label>
            <InputText v-model="form.email" type="email" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Email Facturación</label>
            <InputText v-model="form.emailfacturacion" type="email" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Agencia MN</label>
            <InputText v-model="form.agenciamn" class="w-full" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Cuenta MN</label>
            <InputText v-model="form.ctamn" class="w-full" required />
          </div>
          <div class="col-span-2">
            <label class="block mb-1 font-medium">Notas</label>
            <Textarea v-model="form.notas" rows="2" class="w-full" />
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