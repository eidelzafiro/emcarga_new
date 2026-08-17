<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import { useConfirm } from 'primevue/useconfirm'

const props = defineProps({ title: String, baterias: Object, filtros: Object, filters: Object })
const confirmDialog = useConfirm()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)
const showMov = ref(false)
const showBaja = ref(false)
const current = ref(null)

const motivos = props.filtros?.motivos_baja || []
const destinos = props.filtros?.destinos || []

const baseForm = () => ({
  folio: 'AUTOMATICO',
  marca: '',
  modelo: '',
  id_tractivo: null,
  fecha_instalacion: new Date().toISOString().slice(0, 10),
  voltaje: null,
  amperaje: null,
  precio_mn: null,
  precio_me: null,
  estado: 'activa',
})

const form = ref(baseForm())
const movForm = ref({ id_tractivo: null, fecha_movimiento: null, id_destino: null, observaciones: '' })
const bajaForm = ref({ fecha_baja: null, id_motivo_baja: null, id_destino: null })

watch(search, () => {
  router.get(route('baterias.index'), { search: search.value }, { preserveState: true, replace: true })
})

const onPage = (event) => {
  router.get(route('baterias.index'), { page: event.page + 1, search: search.value }, { preserveState: true, replace: true })
}

function openCreate() {
  editing.value = null
  form.value = baseForm()
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  form.value = {
    folio: item.folio ?? '',
    marca: item.marca ?? '',
    modelo: item.modelo ?? '',
    id_tractivo: item.id_tractivo ?? null,
    fecha_instalacion: item.fecha_instalacion ?? null,
    voltaje: item.voltaje ?? null,
    amperaje: item.amperaje ?? null,
    precio_mn: item.precio_mn ?? null,
    precio_me: item.precio_me ?? null,
    estado: item.estado || 'activa',
  }
  showForm.value = true
}

function submit() {
  const payload = { ...form.value }
  const url = editing.value ? route('baterias.update', { bateria: editing.value.id }) : route('baterias.store')
  router[editing.value ? 'put' : 'post'](url, payload, { onSuccess: () => { showForm.value = false } })
}

function destroy(item) {
  confirmDialog.require({
    message: `¿Eliminar la batería ${item.folio ?? item.id}?`,
    header: 'Eliminar Batería', icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Eliminar', rejectLabel: 'Volver', acceptClass: 'p-button-danger',
    accept: () => router.delete(route('baterias.destroy', { bateria: item.id })),
  })
}

function openMov(item) {
  current.value = item
  movForm.value = { id_tractivo: null, fecha_movimiento: new Date().toISOString().slice(0, 10), id_destino: null, observaciones: '' }
  showMov.value = true
}

function submitMov() {
  router.post(route('baterias.movimiento', { bateria: current.value.id }), { ...movForm.value }, { onSuccess: () => { showMov.value = false } })
}

function openBaja(item) {
  current.value = item
  bajaForm.value = { fecha_baja: new Date().toISOString().slice(0, 10), id_motivo_baja: null, id_destino: null }
  showBaja.value = true
}

function submitBaja() {
  router.post(route('baterias.baja', { bateria: current.value.id }), { ...bajaForm.value }, { onSuccess: () => { showBaja.value = false } })
}
</script>

<template>
  <AppLayout>
    <Toolbar class="mb-3">
      <template #start><h2 class="text-xl font-bold m-0">{{ title ?? 'Baterías' }}</h2></template>
      <template #end>
        <div class="flex gap-2">
          <InputText v-model="search" placeholder="Buscar por folio o marca..." class="w-64" />
          <Button icon="pi pi-plus" label="Nueva batería" @click="openCreate" />
        </div>
      </template>
    </Toolbar>

    <DataTable :value="baterias.data" paginator :rows="baterias.per_page" :totalRecords="baterias.total"
      :rowsPerPageOptions="[10, 20, 50]" :first="(baterias.current_page - 1) * baterias.per_page"
      @page="onPage" stripedRows class="p-datatable-sm"
      paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
      currentPageReportTemplate="Total: {totalRecords} registros">
      <Column field="folio" header="Folio" sortable style="width:120px" />
      <Column field="marca" header="Marca" sortable />
      <Column field="voltaje" header="Voltaje" />
      <Column field="amperaje" header="Amperaje" />
      <Column field="tractivo.descripcion" header="Tractivo" />
      <Column field="estado" header="Estado" style="width:110px">
        <template #body="{ data }">
          <Tag :value="data.estado || 'activa'" :severity="data.estado === 'baja' ? 'danger' : 'success'" />
        </template>
      </Column>
      <Column header="Acciones" style="width:200px">
        <template #body="{ data }">
          <Button icon="pi pi-pencil" text rounded severity="info" title="Editar" @click="openEdit(data)" :disabled="data.estado === 'baja'" />
          <Button icon="pi pi-arrows-alt" text rounded severity="warn" title="Mover" @click="openMov(data)" :disabled="data.estado === 'baja'" />
          <Button icon="pi pi-times" text rounded severity="danger" title="Dar de baja" @click="openBaja(data)" :disabled="data.estado === 'baja'" />
          <Button icon="pi pi-trash" text rounded severity="danger" title="Eliminar" @click="destroy(data)" />
        </template>
      </Column>
    </DataTable>

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar batería' : 'Nueva batería'" :style="{ width: '600px' }" modal>
      <div class="grid grid-cols-2 gap-3 mt-2">
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Folio</label><InputText v-model="form.folio" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Estado</label><Select v-model="form.estado" :options="['activa','baja']" class="w-full" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Marca</label><InputText v-model="form.marca" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Modelo</label><InputText v-model="form.modelo" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Voltaje</label><InputText type="number" v-model="form.voltaje" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Amperaje</label><InputText type="number" v-model="form.amperaje" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Precio MN</label><InputText type="number" v-model="form.precio_mn" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Precio MLC</label><InputText type="number" v-model="form.precio_me" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Fecha instalación</label><InputText type="date" v-model="form.fecha_instalacion" /></div>
      </div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" text @click="showForm = false" />
        <Button label="Guardar" icon="pi pi-check" @click="submit" />
      </template>
    </Dialog>

    <Dialog v-model:visible="showMov" header="Registrar movimiento de batería" :style="{ width: '460px' }" modal>
      <div class="flex flex-col gap-3 mt-2">
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Tractivo</label><InputText v-model="movForm.id_tractivo" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Fecha movimiento</label><InputText type="date" v-model="movForm.fecha_movimiento" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Destino</label>
          <Select v-model="movForm.id_destino" :options="destinos" optionLabel="nombre" optionValue="id" class="w-full" />
        </div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Observaciones</label><InputText v-model="movForm.observaciones" /></div>
      </div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" text @click="showMov = false" />
        <Button label="Registrar" icon="pi pi-check" @click="submitMov" />
      </template>
    </Dialog>

    <Dialog v-model:visible="showBaja" header="Dar de baja batería" :style="{ width: '460px' }" modal>
      <div class="flex flex-col gap-3 mt-2">
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Fecha baja</label><InputText type="date" v-model="bajaForm.fecha_baja" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Motivo de baja *</label>
          <Select v-model="bajaForm.id_motivo_baja" :options="motivos" optionLabel="nombre" optionValue="id" class="w-full" />
        </div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Destino</label>
          <Select v-model="bajaForm.id_destino" :options="destinos" optionLabel="nombre" optionValue="id" class="w-full" />
        </div>
      </div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" text @click="showBaja = false" />
        <Button label="Dar de baja" icon="pi pi-check" severity="danger" @click="submitBaja" />
      </template>
    </Dialog>
  </AppLayout>
</template>
