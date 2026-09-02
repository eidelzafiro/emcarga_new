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
import DatePicker from 'primevue/datepicker'
import Select from 'primevue/select'
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import { useConfirm } from 'primevue/useconfirm'

const props = defineProps({ title: String, motores: Object, filters: Object, filtros: Object })
const confirmDialog = useConfirm()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)

const estados = [
  { label: 'disponible', value: 'disponible' },
  { label: 'nuevo', value: 'nuevo' },
  { label: 'trabajando', value: 'trabajando' },
  { label: 'reparado', value: 'reparado' },
  { label: 'regular', value: 'regular' },
  { label: 'baja', value: 'baja' },
]

const baseForm = () => ({
  codigo: '',
  descripcion: '',
  marca: '',
  modelo: '',
  numero_serie: '',
  cpl: '',
  caballaje: null,
  cantidad_lubricante: null,
  numero_tiempos: null,
  numero_cilindros: null,
  kms_acumulados: null,
  capacidad_carter: null,
  fecha_instalacion: null,
  fecha_baja: null,
  id_lubricante: null,
  id_pais: null,
  id_tractivo: null,
  estado: 'disponible',
})

const form = ref(baseForm())

watch(search, () => {
  router.get(route('motores.index'), { search: search.value, estado: props.filters?.estado ?? '' }, { preserveState: true, replace: true })
})

const onPage = (event) => {
  router.get(route('motores.index'), {
    page: event.page + 1,
    per_page: event.rows,
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
    cpl: item.cpl ?? '',
    caballaje: item.caballaje ?? null,
    cantidad_lubricante: item.cantidad_lubricante ?? null,
    numero_tiempos: item.numero_tiempos ?? null,
    numero_cilindros: item.numero_cilindros ?? null,
    kms_acumulados: item.kms_acumulados ?? null,
    capacidad_carter: item.capacidad_carter ?? null,
    fecha_instalacion: item.fecha_instalacion ?? null,
    fecha_baja: item.fecha_baja ?? null,
    id_lubricante: item.id_lubricante ?? null,
    id_pais: item.id_pais ?? null,
    id_tractivo: item.id_tractivo ?? null,
    estado: item.estado || 'disponible',
  }
  showForm.value = true
}

function submit() {
  const payload = { ...form.value }
  const url = editing.value
    ? route('motores.update', { motore: editing.value.id })
    : route('motores.store')
  router[editing.value ? 'put' : 'post'](url, payload, {
    onSuccess: () => {
      showForm.value = false
    },
  })
}

// Baja SIN cambio: el motor queda fuera de servicio y el tractivo inactivo.
function darBaja(item) {
  confirmDialog.require({
    message: `¿Dar de baja el motor ${item.codigo ?? item.id}? El tractivo asociado quedará INACTIVO hasta que se instale otro motor.`,
    header: 'Baja de Motor',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Dar de baja',
    rejectLabel: 'Volver',
    acceptClass: 'p-button-danger',
    accept: () => router.post(route('motores.baja', { motore: item.id })),
  })
}

function destroy(item) {
  confirmDialog.require({
    message: `¿Eliminar el motor ${item.codigo ?? item.id}?`,
    header: 'Eliminar Motor',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Eliminar',
    rejectLabel: 'Volver',
    acceptClass: 'p-button-danger',
    accept: () => router.delete(route('motores.destroy', { motore: item.id })),
  })
}

const estadoSeverity = (e) => e === 'disponible' ? 'success' : e === 'baja' ? 'danger' : e === 'trabajando' ? 'info' : 'warn'
</script>

<template>
  <AppLayout>
    <Toolbar class="mb-3">
      <template #start>
        <h2 class="text-xl font-bold m-0">{{ title ?? 'Motores' }}</h2>
      </template>
      <template #end>
        <div class="flex gap-2">
          <InputText v-model="search" placeholder="Buscar por código o descripción..." class="w-64" />
          <Button icon="pi pi-plus" label="Nuevo motor" @click="openCreate" />
        </div>
      </template>
    </Toolbar>

    <DataTable :value="motores.data" paginator :rows="motores.per_page" :totalRecords="motores.total"
      :rowsPerPageOptions="[10, 20, 50]" :first="(motores.current_page - 1) * motores.per_page"
      @page="onPage" stripedRows class="p-datatable-sm"
      paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
      currentPageReportTemplate="Total: {totalRecords} registros">
      <Column field="codigo" header="Código" sortable style="width:130px" />
      <Column field="descripcion" header="Descripción" sortable />
      <Column field="marca" header="Marca" sortable />
      <Column field="modelo" header="Modelo" />
      <Column field="numero_serie" header="N° Serie" />
      <Column field="caballaje" header="CV" style="width:70px" />
      <Column field="tractivo.descripcion" header="Tractivo">
        <template #body="{ data }">{{ data.tractivo?.descripcion || '—' }}</template>
      </Column>
      <Column field="kms_acumulados" header="Kms" style="width:100px" />
      <Column field="estado" header="Estado" style="width:120px">
        <template #body="{ data }">
          <Tag :value="data.estado || 'disponible'" :severity="estadoSeverity(data.estado)" />
        </template>
      </Column>
      <Column header="Acciones" style="width:150px">
        <template #body="{ data }">
          <Button icon="pi pi-pencil" text rounded severity="info" @click="openEdit(data)" v-tooltip.top="'Editar'" />
          <Button icon="pi pi-minus-circle" text rounded severity="warning"
            :disabled="data.estado === 'baja'" @click="darBaja(data)" v-tooltip.top="'Dar de baja'" />
          <Button icon="pi pi-trash" text rounded severity="danger" @click="destroy(data)" v-tooltip.top="'Eliminar'" />
        </template>
      </Column>
    </DataTable>

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar motor' : 'Nuevo motor'"
      :style="{ width: '760px' }" modal>
      <div class="grid grid-cols-3 gap-3 mt-2">
        <div class="col-span-3 border-b border-gray-200 dark:border-gray-700 pb-1 mb-1 text-sm font-semibold text-gray-600 dark:text-gray-300">Identificación</div>
        <div class="flex flex-col gap-1">
          <label for="codigo" class="text-sm font-medium">Código</label>
          <InputText id="codigo" v-model="form.codigo" placeholder="M-..." />
        </div>
        <div class="flex flex-col gap-1">
          <label for="estado" class="text-sm font-medium">Estado</label>
          <Select id="estado" v-model="form.estado" :options="estados" optionLabel="label"
            optionValue="value" class="w-full" />
        </div>
        <div class="flex flex-col gap-1">
          <label for="id_tractivo" class="text-sm font-medium">Tractivo</label>
          <Select id="id_tractivo" v-model="form.id_tractivo" :options="filtros?.tractivos ?? []"
            optionLabel="descripcion" optionValue="id" class="w-full" showClear
            :optionDisabled="(t) => !!form.id_tractivo && t.id !== form.id_tractivo" filter />
        </div>
        <div class="flex flex-col gap-1 col-span-3">
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
        <div class="flex flex-col gap-1">
          <label for="numero_serie" class="text-sm font-medium">N° Serie *</label>
          <InputText id="numero_serie" v-model="form.numero_serie" />
        </div>

        <div class="col-span-3 border-b border-gray-200 dark:border-gray-700 pb-1 mb-1 text-sm font-semibold text-gray-600 dark:text-gray-300">Ficha técnica</div>
        <div class="flex flex-col gap-1">
          <label for="cpl" class="text-sm font-medium">CPL</label>
          <InputText id="cpl" v-model="form.cpl" />
        </div>
        <div class="flex flex-col gap-1">
          <label for="caballaje" class="text-sm font-medium">Caballaje (CV)</label>
          <InputNumber id="caballaje" v-model="form.caballaje" class="w-full" />
        </div>
        <div class="flex flex-col gap-1">
          <label for="cantidad_lubricante" class="text-sm font-medium">Cant. lubricante</label>
          <InputNumber id="cantidad_lubricante" v-model="form.cantidad_lubricante" mode="decimal" :minFractionDigits="0" :maxFractionDigits="3" class="w-full" />
        </div>
        <div class="flex flex-col gap-1">
          <label for="numero_tiempos" class="text-sm font-medium">N° tiempos</label>
          <InputNumber id="numero_tiempos" v-model="form.numero_tiempos" class="w-full" />
        </div>
        <div class="flex flex-col gap-1">
          <label for="numero_cilindros" class="text-sm font-medium">N° cilindros</label>
          <InputNumber id="numero_cilindros" v-model="form.numero_cilindros" class="w-full" />
        </div>
        <div class="flex flex-col gap-1">
          <label for="capacidad_carter" class="text-sm font-medium">Capacidad cárter</label>
          <InputNumber id="capacidad_carter" v-model="form.capacidad_carter" mode="decimal" :minFractionDigits="0" :maxFractionDigits="3" class="w-full" />
        </div>
        <div class="flex flex-col gap-1">
          <label for="kms_acumulados" class="text-sm font-medium">Kms acumulados</label>
          <InputNumber id="kms_acumulados" v-model="form.kms_acumulados" mode="decimal" :minFractionDigits="0" :maxFractionDigits="2" class="w-full" />
        </div>

        <div class="col-span-3 border-b border-gray-200 dark:border-gray-700 pb-1 mb-1 text-sm font-semibold text-gray-600 dark:text-gray-300">Lubricación / Fechas</div>
        <div class="flex flex-col gap-1">
          <label for="id_lubricante" class="text-sm font-medium">Lubricante</label>
          <Select id="id_lubricante" v-model="form.id_lubricante" :options="filtros?.lubricantes ?? []"
            optionLabel="nombre" optionValue="id" class="w-full" showClear />
        </div>
        <div class="flex flex-col gap-1">
          <label for="id_pais" class="text-sm font-medium">País</label>
          <Select id="id_pais" v-model="form.id_pais" :options="(filtros?.paises ?? []).map(p => ({ id: p.id, nombre: p.nombre }))"
            optionLabel="nombre" optionValue="id" class="w-full" showClear filter />
        </div>
        <div class="flex flex-col gap-1">
          <label for="fecha_instalacion" class="text-sm font-medium">Fecha instalación</label>
          <DatePicker id="fecha_instalacion" v-model="form.fecha_instalacion" dateFormat="yy-mm-dd" showIcon class="w-full" />
        </div>
        <div class="flex flex-col gap-1">
          <label for="fecha_baja" class="text-sm font-medium">Fecha baja</label>
          <DatePicker id="fecha_baja" v-model="form.fecha_baja" dateFormat="yy-mm-dd" showIcon class="w-full" />
        </div>
      </div>
      <p class="text-xs text-gray-500 dark:text-gray-400 mt-3">
        Sin tractivo asignado el estado es obligatoriamente <b>disponible</b>. Los cambios de motor del tractivo
        se realizan mediante la Orden de Taller.
      </p>
      <template #footer>
        <Button label="Cancelar" severity="secondary" text @click="showForm = false" />
        <Button label="Guardar" icon="pi pi-check" @click="submit" />
      </template>
    </Dialog>
  </AppLayout>
</template>
