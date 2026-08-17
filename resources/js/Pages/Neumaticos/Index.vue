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
import Textarea from 'primevue/textarea'
import { useConfirm } from 'primevue/useconfirm'

const props = defineProps({ title: String, neumaticos: Object, filtros: Object, filters: Object })
const confirmDialog = useConfirm()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)
const showMov = ref(false)
const showRetiro = ref(false)
const movNeumatico = ref(null)
const kms = ref(0)

const estados = props.filtros?.estados || ['activo', 'recauchado', 'regular', 'nuevo', 'baja']

const baseForm = () => ({
  folio: 'AUTOMATICO',
  marca: '',
  modelo: '',
  medida: '',
  id_tractivo: null,
  fecha_instalacion: new Date().toISOString().slice(0, 10),
  fecha_retiro: null,
  kilometraje: null,
  precio_mn: null,
  precio_me: null,
  id_posicion: null,
  fecha_fabricacion: null,
  balanceada: false,
  profinicial: null,
  estado: 'activo',
})

const form = ref(baseForm())
const movForm = ref({ id_tractivo: null, fecha_montaje: null, km_instalado: null, id_posicion: null, observaciones: '' })
const retiroForm = ref({ fecha_retiro: null, km_retirado: null, id_tipo_rotura: null, id_rotura: null })

watch(search, () => {
  router.get(route('neumaticos.index'), { search: search.value }, { preserveState: true, replace: true })
})

const onPage = (event) => {
  router.get(route('neumaticos.index'), { page: event.page + 1, search: search.value }, { preserveState: true, replace: true })
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
    medida: item.medida ?? '',
    id_tractivo: item.id_tractivo ?? null,
    fecha_instalacion: item.fecha_instalacion ?? null,
    fecha_retiro: item.fecha_retiro ?? null,
    kilometraje: item.kilometraje ?? null,
    precio_mn: item.precio_mn ?? null,
    precio_me: item.precio_me ?? null,
    id_posicion: item.id_posicion ?? null,
    fecha_fabricacion: item.fecha_fabricacion ?? null,
    balanceada: item.balanceada ?? false,
    profinicial: item.profinicial ?? null,
    estado: item.estado || 'activo',
  }
  showForm.value = true
}

function submit() {
  const payload = { ...form.value }
  const url = editing.value ? route('neumaticos.update', { neumatico: editing.value.id }) : route('neumaticos.store')
  router[editing.value ? 'put' : 'post'](url, payload, { onSuccess: () => { showForm.value = false } })
}

function destroy(item) {
  confirmDialog.require({
    message: `¿Eliminar el neumático ${item.folio ?? item.id}?`,
    header: 'Eliminar Neumático',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Eliminar', rejectLabel: 'Volver', acceptClass: 'p-button-danger',
    accept: () => router.delete(route('neumaticos.destroy', { neumatico: item.id })),
  })
}

function openMov(item) {
  movNeumatico.value = item
  movForm.value = { id_tractivo: null, fecha_montaje: new Date().toISOString().slice(0, 10), km_instalado: null, id_posicion: null, observaciones: '' }
  showMov.value = true
}

function submitMov() {
  router.post(route('neumaticos.movimiento', { neumatico: movNeumatico.value.id }), { ...movForm.value }, {
    onSuccess: () => { showMov.value = false },
  })
}

function openRetiro(item) {
  movNeumatico.value = item
  retiroForm.value = { fecha_retiro: new Date().toISOString().slice(0, 10), km_retirado: null, id_tipo_rotura: null, id_rotura: null }
  showRetiro.value = true
}

function submitRetiro() {
  router.post(route('neumaticos.retirar', { neumatico: movNeumatico.value.id }), { ...retiroForm.value }, {
    onSuccess: () => { showRetiro.value = false },
  })
}
</script>

<template>
  <AppLayout>
    <Toolbar class="mb-3">
      <template #start><h2 class="text-xl font-bold m-0">{{ title ?? 'Neumáticos' }}</h2></template>
      <template #end>
        <div class="flex gap-2">
          <InputText v-model="search" placeholder="Buscar por folio o marca..." class="w-64" />
          <Button icon="pi pi-plus" label="Nuevo neumático" @click="openCreate" />
        </div>
      </template>
    </Toolbar>

    <DataTable :value="neumaticos.data" paginator :rows="neumaticos.per_page" :totalRecords="neumaticos.total"
      :rowsPerPageOptions="[10, 20, 50]" :first="(neumaticos.current_page - 1) * neumaticos.per_page"
      @page="onPage" stripedRows class="p-datatable-sm"
      paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
      currentPageReportTemplate="Total: {totalRecords} registros">
      <Column field="folio" header="Folio" sortable style="width:120px" />
      <Column field="marca" header="Marca" sortable />
      <Column field="medida" header="Medida" />
      <Column field="tractivo.descripcion" header="Tractivo" />
      <Column field="id_posicion" header="Posición">
        <template #body="{ data }">{{ data.posicion?.nombre ?? data.id_posicion }}</template>
      </Column>
      <Column field="kilometraje" header="Km" />
      <Column field="estado" header="Estado" style="width:120px">
        <template #body="{ data }">
          <Tag :value="data.estado || 'activo'" :severity="data.estado === 'baja' ? 'danger' : (data.estado === 'activo' ? 'success' : 'warn')" />
        </template>
      </Column>
      <Column header="Acciones" style="width:200px">
        <template #body="{ data }">
          <Button icon="pi pi-pencil" text rounded severity="info" title="Editar" @click="openEdit(data)" />
          <Button icon="pi pi-arrows-alt" text rounded severity="warn" title="Mover" @click="openMov(data)" :disabled="data.estado === 'baja'" />
          <Button icon="pi pi-times" text rounded severity="danger" title="Retirar" @click="openRetiro(data)" :disabled="data.estado === 'baja'" />
          <Button icon="pi pi-trash" text rounded severity="danger" title="Eliminar" @click="destroy(data)" />
        </template>
      </Column>
    </DataTable>

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar neumático' : 'Nuevo neumático'" :style="{ width: '620px' }" modal>
      <div class="grid grid-cols-2 gap-3 mt-2">
        <div class="flex flex-col gap-1">
          <label class="text-sm font-medium">Folio</label>
          <InputText v-model="form.folio" />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm font-medium">Estado</label>
          <Select v-model="form.estado" :options="estados" class="w-full" />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm font-medium">Marca</label>
          <InputText v-model="form.marca" />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm font-medium">Modelo</label>
          <InputText v-model="form.modelo" />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm font-medium">Medida</label>
          <InputText v-model="form.medida" />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm font-medium">Fecha instalación</label>
          <InputText type="date" v-model="form.fecha_instalacion" />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm font-medium">Fecha fabricación</label>
          <InputText type="date" v-model="form.fecha_fabricacion" />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm font-medium">Precio MN</label>
          <InputText type="number" v-model="form.precio_mn" />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm font-medium">Precio MLC</label>
          <InputText type="number" v-model="form.precio_me" />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm font-medium">Prof. inicial</label>
          <InputText type="number" v-model="form.profinicial" />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm font-medium">Balanceada</label>
          <Select v-model="form.balanceada" :options="[{label:'SI',value:true},{label:'NO',value:false}]" optionLabel="label" optionValue="value" class="w-full" />
        </div>
      </div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" text @click="showForm = false" />
        <Button label="Guardar" icon="pi pi-check" @click="submit" />
      </template>
    </Dialog>

    <Dialog v-model:visible="showMov" header="Registrar movimiento" :style="{ width: '480px' }" modal>
      <div class="flex flex-col gap-3 mt-2">
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Tractivo destino</label><InputText v-model="movForm.id_tractivo" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Fecha montaje</label><InputText type="date" v-model="movForm.fecha_montaje" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Km instalado</label><InputText type="number" v-model="movForm.km_instalado" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Observaciones</label><Textarea v-model="movForm.observaciones" rows="2" /></div>
      </div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" text @click="showMov = false" />
        <Button label="Registrar" icon="pi pi-check" @click="submitMov" />
      </template>
    </Dialog>

    <Dialog v-model:visible="showRetiro" header="Retirar neumático" :style="{ width: '480px' }" modal>
      <div class="flex flex-col gap-3 mt-2">
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Fecha retiro</label><InputText type="date" v-model="retiroForm.fecha_retiro" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Km retirado</label><InputText type="number" v-model="retiroForm.km_retirado" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Tipo de rotura *</label><InputText v-model="retiroForm.id_tipo_rotura" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Código de rotura *</label><InputText v-model="retiroForm.id_rotura" /></div>
      </div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" text @click="showRetiro = false" />
        <Button label="Retirar" icon="pi pi-check" severity="danger" @click="submitRetiro" />
      </template>
    </Dialog>
  </AppLayout>
</template>
