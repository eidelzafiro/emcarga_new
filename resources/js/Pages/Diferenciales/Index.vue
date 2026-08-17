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
import Tag from 'primevue/tag'
import { useConfirm } from 'primevue/useconfirm'

const props = defineProps({ title: String, diferenciales: Object, filters: Object })
const confirmDialog = useConfirm()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)

const estados = [
  { label: 'disponible', value: 'disponible' },
  { label: 'nuevo', value: 'nuevo' },
  { label: 'reparado', value: 'reparado' },
  { label: 'regular', value: 'regular' },
  { label: 'trabajando', value: 'trabajando' },
]

const baseForm = () => ({
  codigo: '',
  descripcion: '',
  marca: '',
  modelo: '',
  numero_serie: '',
  estado: 'disponible',
  durabilidad: null,
  relacion: '',
  ancho: null,
  cantidad_lubricante: null,
  cantidad: null,
  kms_acumulados: null,
  capacidad_carter: null,
})

const form = ref(baseForm())

watch(search, () => {
  router.get(route('diferenciales.index'), { search: search.value }, { preserveState: true, replace: true })
})

const onPage = (event) => {
  router.get(route('diferenciales.index'), {
    page: event.page + 1,
    search: search.value,
  }, { preserveState: true, replace: true })
}

function openCreate() {
  editing.value = null
  form.value = baseForm()
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  form.value = {
    codigo: item.codigo ?? '',
    descripcion: item.descripcion ?? '',
    marca: item.marca ?? '',
    modelo: item.modelo ?? '',
    numero_serie: item.numero_serie ?? '',
    estado: item.estado || 'disponible',
    durabilidad: item.durabilidad ?? null,
    relacion: item.relacion ?? '',
    ancho: item.ancho ?? null,
    cantidad_lubricante: item.cantidad_lubricante ?? null,
    cantidad: item.cantidad ?? null,
    kms_acumulados: item.kms_acumulados ?? null,
    capacidad_carter: item.capacidad_carter ?? null,
  }
  showForm.value = true
}

function submit() {
  const payload = { ...form.value }
  const url = editing.value
    ? route('diferenciales.update', { diferencial: editing.value.id })
    : route('diferenciales.store')
  router[editing.value ? 'put' : 'post'](url, payload, {
    onSuccess: () => {
      showForm.value = false
    },
  })
}

function destroy(item) {
  confirmDialog.require({
    message: `¿Eliminar el diferencial ${item.codigo ?? item.id}?`,
    header: 'Eliminar Diferencial',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Eliminar',
    rejectLabel: 'Volver',
    acceptClass: 'p-button-danger',
    accept: () => router.delete(route('diferenciales.destroy', { diferencial: item.id })),
  })
}
</script>

<template>
  <AppLayout>
    <Toolbar class="mb-3">
      <template #start>
        <h2 class="text-xl font-bold m-0">{{ title ?? 'Diferenciales' }}</h2>
      </template>
      <template #end>
        <div class="flex gap-2">
          <InputText v-model="search" placeholder="Buscar por código o descripción..." class="w-64" />
          <Button icon="pi pi-plus" label="Nuevo diferencial" @click="openCreate" />
        </div>
      </template>
    </Toolbar>

    <DataTable :value="diferenciales.data" paginator :rows="diferenciales.per_page" :totalRecords="diferenciales.total"
      :rowsPerPageOptions="[10, 20, 50]" :first="(diferenciales.current_page - 1) * diferenciales.per_page"
      @page="onPage" stripedRows class="p-datatable-sm"
      paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
      currentPageReportTemplate="Total: {totalRecords} registros">
      <Column field="codigo" header="Código" sortable style="width:130px" />
      <Column field="descripcion" header="Descripción" sortable />
      <Column field="marca" header="Marca" sortable />
      <Column field="modelo" header="Modelo" />
      <Column field="durabilidad" header="Durabilidad" />
      <Column field="relacion" header="Relación" />
      <Column field="ancho" header="Ancho" />
      <Column field="tractivo.descripcion" header="Tractivo" />
      <Column field="estado" header="Estado" style="width:120px">
        <template #body="{ data }">
          <Tag :value="data.estado || 'disponible'" :severity="data.estado === 'disponible' ? 'success' : 'warn'" />
        </template>
      </Column>
      <Column header="Acciones" style="width:120px">
        <template #body="{ data }">
          <Button icon="pi pi-pencil" text rounded severity="info" @click="openEdit(data)" />
          <Button icon="pi pi-trash" text rounded severity="danger" @click="destroy(data)" />
        </template>
      </Column>
    </DataTable>

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar diferencial' : 'Nuevo diferencial'"
      :style="{ width: '520px' }" modal>
      <div class="grid grid-cols-2 gap-3 mt-2">
        <div class="flex flex-col gap-1">
          <label for="codigo" class="text-sm font-medium">Código *</label>
          <InputText id="codigo" v-model="form.codigo" />
        </div>
        <div class="flex flex-col gap-1">
          <label for="estado" class="text-sm font-medium">Estado</label>
          <Select id="estado" v-model="form.estado" :options="estados" optionLabel="label"
            optionValue="value" class="w-full" />
        </div>
        <div class="flex flex-col gap-1 col-span-2">
          <label for="descripcion" class="text-sm font-medium">Descripción *</label>
          <InputText id="descripcion" v-model="form.descripcion" />
        </div>
        <div class="flex flex-col gap-1">
          <label for="marca" class="text-sm font-medium">Marca</label>
          <InputText id="marca" v-model="form.marca" />
        </div>
        <div class="flex flex-col gap-1">
          <label for="modelo" class="text-sm font-medium">Modelo</label>
          <InputText id="modelo" v-model="form.modelo" />
        </div>
        <div class="flex flex-col gap-1 col-span-2">
          <label for="numero_serie" class="text-sm font-medium">N° Serie</label>
          <InputText id="numero_serie" v-model="form.numero_serie" />
        </div>
      </div>
      <div class="mt-4 pt-3 border-t border-surface-200 dark:border-surface-700">
        <h3 class="text-sm font-semibold mb-2 text-surface-700 dark:text-surface-200">Ficha técnica</h3>
        <div class="grid grid-cols-2 gap-3">
          <div class="flex flex-col gap-1">
            <label for="durabilidad" class="text-sm font-medium">Durabilidad</label>
            <InputNumber id="durabilidad" v-model="form.durabilidad" mode="decimal" :minFractionDigits="0"
              :maxFractionDigits="2" class="w-full" />
          </div>
          <div class="flex flex-col gap-1">
            <label for="relacion" class="text-sm font-medium">Relación</label>
            <InputText id="relacion" v-model="form.relacion" />
          </div>
          <div class="flex flex-col gap-1">
            <label for="ancho" class="text-sm font-medium">Ancho</label>
            <InputNumber id="ancho" v-model="form.ancho" mode="decimal" :minFractionDigits="0"
              :maxFractionDigits="2" class="w-full" />
          </div>
          <div class="flex flex-col gap-1">
            <label for="cantidad_lubricante" class="text-sm font-medium">Cant. lubricante</label>
            <InputNumber id="cantidad_lubricante" v-model="form.cantidad_lubricante" mode="decimal"
              :minFractionDigits="0" :maxFractionDigits="3" class="w-full" />
          </div>
          <div class="flex flex-col gap-1">
            <label for="cantidad" class="text-sm font-medium">Cantidad</label>
            <InputNumber id="cantidad" v-model="form.cantidad" mode="decimal" :minFractionDigits="0"
              :maxFractionDigits="3" class="w-full" />
          </div>
          <div class="flex flex-col gap-1">
            <label for="kms_acumulados" class="text-sm font-medium">Kms acumulados</label>
            <InputNumber id="kms_acumulados" v-model="form.kms_acumulados" mode="decimal" :minFractionDigits="0"
              :maxFractionDigits="2" class="w-full" />
          </div>
          <div class="flex flex-col gap-1 col-span-2">
            <label for="capacidad_carter" class="text-sm font-medium">Capacidad cárter</label>
            <InputNumber id="capacidad_carter" v-model="form.capacidad_carter" mode="decimal"
              :minFractionDigits="0" :maxFractionDigits="3" class="w-full" />
          </div>
        </div>
      </div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" text @click="showForm = false" />
        <Button label="Guardar" icon="pi pi-check" @click="submit" />
      </template>
    </Dialog>
  </AppLayout>
</template>