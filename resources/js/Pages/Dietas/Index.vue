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
import Checkbox from 'primevue/checkbox'
import Tag from 'primevue/tag'
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ dietas: Object, filtros: Object, filters: Object, fechaOperaciones: String })
const toast = useToast()
const confirm = useConfirm()
const search = ref(props.filters?.search || '')
const soloCanceladas = ref(props.filters?.canceladas === '1')
const showForm = ref(false)
const editing = ref(null)
const showLiquidar = ref(false)
const liquidarForm = ref({ f_liquidacion: null, folio_caja: null })
const liquidarTarget = ref(null)
const title = 'Dietas'

function baseForm() {
  return {
    id_bolsa: null,
    id_hoja_ruta: null,
    folio: '',
    fecha: null,
    monto: null,
    anticipo: null,
    f_anticipo: null,
    alimentos: null,
    hospedaje: null,
    otros: null,
    id_monedas: null,
    id_tractivo: null,
    tipo_dieta: '',
    estado: 'pendiente',
  }
}
const form = ref(baseForm())

watch(search, () => reload())
watch(soloCanceladas, () => reload())

function reload() {
  router.get(route('dietas.index'), {
    search: search.value,
    canceladas: soloCanceladas.value ? '1' : '0',
  }, { preserveState: true, replace: true })
}

function onPage(event) {
  router.get(route('dietas.index'), {
    page: event.page + 1,
    search: search.value,
    canceladas: soloCanceladas.value ? '1' : '0',
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
    id_bolsa: item.id_bolsa,
    id_hoja_ruta: item.id_hoja_ruta,
    folio: item.folio ?? '',
    fecha: item.fecha,
    monto: Number(item.monto),
    anticipo: item.anticipo != null ? Number(item.anticipo) : null,
    f_anticipo: item.f_anticipo,
    alimentos: item.alimentos != null ? Number(item.alimentos) : null,
    hospedaje: item.hospedaje != null ? Number(item.hospedaje) : null,
    otros: item.otros != null ? Number(item.otros) : null,
    id_monedas: item.id_monedas,
    id_tractivo: item.id_tractivo,
    tipo_dieta: item.tipo_dieta ?? '',
    estado: item.estado ?? 'pendiente',
  }
  showForm.value = true
}

function submit() {
  const url = editing.value ? route('dietas.update', editing.value.id) : route('dietas.store')
  const method = editing.value ? 'put' : 'post'
  router[method](url, form.value, {
    onSuccess: () => { showForm.value = false; toast.add({ severity: 'success', summary: editing.value ? 'Actualizada' : 'Creada', life: 3000 }) },
    onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
  })
}

function openLiquidar(item) {
  liquidarTarget.value = item
  liquidarForm.value = { f_liquidacion: item.f_liquidacion ?? null, folio_caja: item.folio_caja ?? null }
  showLiquidar.value = true
}

function liquidar() {
  router.post(route('dietas.liquidar', liquidarTarget.value.id), liquidarForm.value, {
    onSuccess: () => { showLiquidar.value = false; toast.add({ severity: 'success', summary: 'Liquidada', life: 3000 }) },
    onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
  })
}

function confirmCancelar(item) {
  confirm.require({
    message: `¿Cancelar la dieta ${item.folio ?? item.id}?`,
    header: 'Cancelar dieta',
    acceptLabel: 'Sí, cancelar',
    rejectLabel: 'No',
    accept: () => {
      router.post(route('dietas.cancelar', item.id), {}, {
        onSuccess: () => toast.add({ severity: 'success', summary: 'Cancelada', life: 3000 }),
        onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
      })
    },
  })
}

function confirmEliminar(item) {
  confirm.require({
    message: `¿Eliminar la dieta ${item.folio ?? item.id}?`,
    header: 'Eliminar dieta',
    acceptLabel: 'Sí, eliminar',
    rejectLabel: 'No',
    accept: () => {
      router.delete(route('dietas.destroy', item.id), {
        onSuccess: () => toast.add({ severity: 'success', summary: 'Eliminada', life: 3000 }),
        onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
      })
    },
  })
}
</script>

<template>
  <AppLayout :title="title">
    <div class="card">
      <Toolbar class="mb-4">
        <template #start>
          <Button label="Nueva" icon="pi pi-plus" severity="success" @click="openCreate" />
        </template>
        <template #end>
          <div class="flex items-center gap-3">
            <label class="flex items-center gap-2 text-sm">
              <Checkbox v-model="soloCanceladas" :binary="true" />
              Solo canceladas
            </label>
            <InputText v-model="search" placeholder="Buscar..." />
          </div>
        </template>
      </Toolbar>

      <DataTable :value="dietas.data" striped-rows paginator :rows="20" :total-records="dietas.total" :lazy="true" :first="(dietas.current_page - 1) * dietas.per_page" @page="onPage" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros">
        <Column header="Fecha">
          <template #body="{ data }">{{ data.fecha }}</template>
        </Column>
        <Column field="folio" header="Folio" />
        <Column header="Empleado">
          <template #body="{ data }">{{ data.bolsa?.nombrecompleto }}</template>
        </Column>
        <Column header="HR">
          <template #body="{ data }">{{ data.hoja_ruta?.numero }}</template>
        </Column>
        <Column header="Tractivo">
          <template #body="{ data }">{{ data.tractivo?.codigo || data.hoja_ruta?.tractivo?.codigo }}</template>
        </Column>
        <Column field="monto" header="Monto">
          <template #body="{ data }">{{ data.monto }}</template>
        </Column>
        <Column header="Moneda">
          <template #body="{ data }">{{ data.moneda?.nombre }}</template>
        </Column>
        <Column header="Anticipo">
          <template #body="{ data }">{{ data.anticipo }}</template>
        </Column>
        <Column header="Liq.">
          <template #body="{ data }">
            <span v-if="data.f_liquidacion" class="text-green-600">{{ data.f_liquidacion }}</span>
            <span v-else class="text-amber-600">Pendiente</span>
          </template>
        </Column>
        <Column header="Estado">
          <template #body="{ data }">
            <Tag v-if="data.cancelada" severity="danger" value="Cancelada" />
            <Tag v-else-if="data.f_liquidacion" severity="success" value="Liquidada" />
            <Tag v-else severity="warn" value="Activa" />
          </template>
        </Column>
        <Column header="Acciones" style="width: 160px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button v-if="!data.cancelada" icon="pi pi-pencil" rounded text severity="info" v-tooltip="'Editar'" @click="openEdit(data)" />
              <Button v-if="!data.cancelada && !data.f_liquidacion" icon="pi pi-check" rounded text severity="success" v-tooltip="'Liquidar'" @click="openLiquidar(data)" />
              <Button v-if="!data.cancelada && !data.f_liquidacion" icon="pi pi-ban" rounded text severity="warn" v-tooltip="'Cancelar'" @click="confirmCancelar(data)" />
              <Button icon="pi pi-trash" rounded text severity="danger" v-tooltip="'Eliminar'" @click="confirmEliminar(data)" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar Dieta' : 'Nueva Dieta'" modal style="width: 720px">
      <form @submit.prevent="submit" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block mb-1 font-medium">Empleado</label>
            <Select v-model="form.id_bolsa" :options="filtros.bolsas" optionLabel="nombrecompleto" optionValue="id" filter class="w-full" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Hoja de Ruta</label>
            <Select v-model="form.id_hoja_ruta" :options="filtros.hojasRuta" optionLabel="numero" optionValue="id" filter class="w-full" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Tractivo</label>
            <Select v-model="form.id_tractivo" :options="filtros.tractivos" optionLabel="codigo" optionValue="id" filter class="w-full" :showClear="true" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Fecha</label>
            <InputText v-model="form.fecha" type="date" class="w-full" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Folio</label>
            <InputText v-model="form.folio" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Moneda</label>
            <Select v-model="form.id_monedas" :options="filtros.monedas" optionLabel="nombre" optionValue="id" class="w-full" :showClear="true" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Monto</label>
            <InputNumber v-model="form.monto" :min="0" :max-fraction-digits="2" class="w-full" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Anticipo</label>
            <InputNumber v-model="form.anticipo" :min="0" :max-fraction-digits="2" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Fecha anticipo</label>
            <InputText v-model="form.f_anticipo" type="date" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Tipo dieta</label>
            <InputText v-model="form.tipo_dieta" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Alimentos</label>
            <InputNumber v-model="form.alimentos" :min="0" :max-fraction-digits="2" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Hospedaje</label>
            <InputNumber v-model="form.hospedaje" :min="0" :max-fraction-digits="2" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Otros</label>
            <InputNumber v-model="form.otros" :min="0" :max-fraction-digits="2" class="w-full" />
          </div>
        </div>
        <div class="flex gap-2 justify-end">
          <Button label="Cancelar" severity="secondary" @click="showForm = false" />
          <Button label="Guardar" type="submit" icon="pi pi-save" />
        </div>
      </form>
    </Dialog>

    <Dialog v-model:visible="showLiquidar" header="Liquidar Dieta" modal style="width: 420px">
      <form @submit.prevent="liquidar" class="space-y-4">
        <div>
          <label class="block mb-1 font-medium">Fecha liquidación</label>
          <InputText v-model="liquidarForm.f_liquidacion" type="date" class="w-full" required />
        </div>
        <div>
          <label class="block mb-1 font-medium">Folio de caja</label>
          <InputNumber v-model="liquidarForm.folio_caja" :min="0" class="w-full" />
        </div>
        <div class="flex gap-2 justify-end">
          <Button label="Cancelar" severity="secondary" @click="showLiquidar = false" />
          <Button label="Liquidar" type="submit" icon="pi pi-check" />
        </div>
      </form>
    </Dialog>
  </AppLayout>
</template>
