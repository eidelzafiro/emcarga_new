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

const props = defineProps({ title: String, registros: Object, filtros: Object, filters: Object })
const confirmDialog = useConfirm()
const showForm = ref(false)
const editing = ref(null)

const tiposOperacion = props.filtros?.tipos_operacion || ['RELLENO', 'MTTO', 'O.CAUSAS']
const lubricantes = props.filtros?.lubricantes || []
const tractivos = props.filtros?.tractivos || []

const baseForm = () => ({
  id_tractivo: null,
  fecha_cambio: new Date().toISOString().slice(0, 10),
  tipo_operacion: 'RELLENO',
  litros_motor: 0, litros_transmision: 0, litros_direccion: 0, litros_hidraulico: 0,
  liquido_freno: 0, agua_refrigerada: 0, grasa_rollete: 0, grasa_copillas: 0,
  id_lub_motor: null, id_lub_transmision: null, id_lub_hidraulico: null, id_lub_direccion: null,
  id_grasa_rollete: null, id_grasa_copillas: null, id_liquido_freno: null, id_agua: null,
})

const form = ref(baseForm())

const sistemas = [
  { key: 'motor', label: 'Motor', litros: 'litros_motor', lub: 'id_lub_motor' },
  { key: 'transmision', label: 'Transmisión', litros: 'litros_transmision', lub: 'id_lub_transmision' },
  { key: 'direccion', label: 'Dirección', litros: 'litros_direccion', lub: 'id_lub_direccion' },
  { key: 'hidraulico', label: 'Hidráulico', litros: 'litros_hidraulico', lub: 'id_lub_hidraulico' },
  { key: 'freno', label: 'Líq. Freno', litros: 'liquido_freno', lub: 'id_liquido_freno' },
  { key: 'agua', label: 'Agua', litros: 'agua_refrigerada', lub: 'id_agua' },
  { key: 'rollete', label: 'Grasa Rollete', litros: 'grasa_rollete', lub: 'id_grasa_rollete' },
  { key: 'copillas', label: 'Grasa Copillas', litros: 'grasa_copillas', lub: 'id_grasa_copillas' },
]

function openCreate() {
  editing.value = null
  form.value = baseForm()
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  form.value = {
    id_tractivo: item.id_tractivo ?? null,
    fecha_cambio: item.fecha_cambio ?? null,
    tipo_operacion: item.tipo_operacion || 'RELLENO',
    litros_motor: item.litros_motor ?? 0, litros_transmision: item.litros_transmision ?? 0,
    litros_direccion: item.litros_direccion ?? 0, litros_hidraulico: item.litros_hidraulico ?? 0,
    liquido_freno: item.liquido_freno ?? 0, agua_refrigerada: item.agua_refrigerada ?? 0,
    grasa_rollete: item.grasa_rollete ?? 0, grasa_copillas: item.grasa_copillas ?? 0,
    id_lub_motor: item.id_lub_motor ?? null, id_lub_transmision: item.id_lub_transmision ?? null,
    id_lub_hidraulico: item.id_lub_hidraulico ?? null, id_lub_direccion: item.id_lub_direccion ?? null,
    id_grasa_rollete: item.id_grasa_rollete ?? null, id_grasa_copillas: item.id_grasa_copillas ?? null,
    id_liquido_freno: item.id_liquido_freno ?? null, id_agua: item.id_agua ?? null,
  }
  showForm.value = true
}

function submit() {
  const url = editing.value
    ? route('control-lubricante.update', { control_lubricante: editing.value.id })
    : route('control-lubricante.store')
  router[editing.value ? 'put' : 'post'](url, { ...form.value }, { onSuccess: () => { showForm.value = false } })
}

function destroy(item) {
  confirmDialog.require({
    message: `¿Eliminar el registro del ${item.fecha_cambio}?`,
    header: 'Eliminar registro', icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Eliminar', rejectLabel: 'Volver', acceptClass: 'p-button-danger',
    accept: () => router.delete(route('control-lubricante.destroy', { control_lubricante: item.id })),
  })
}

function totalSistema(item) {
  const nums = [item.litros_motor, item.litros_transmision, item.litros_direccion, item.litros_hidraulico,
    item.liquido_freno, item.agua_refrigerada, item.grasa_rollete, item.grasa_copillas]
  return nums.reduce((a, b) => a + Number(b || 0), 0)
}
</script>

<template>
  <AppLayout>
    <Toolbar class="mb-3">
      <template #start><h2 class="text-xl font-bold m-0">{{ title ?? 'Control de Lubricantes (CT-7)' }}</h2></template>
      <template #end>
        <div class="flex gap-2">
          <Button icon="pi pi-plus" label="Nuevo registro" @click="openCreate" />
        </div>
      </template>
    </Toolbar>

    <DataTable :value="registros.data" paginator :rows="registros.per_page" :totalRecords="registros.total"
      :rowsPerPageOptions="[10, 20, 50]" :first="(registros.current_page - 1) * registros.per_page"
      @page="onPage" stripedRows class="p-datatable-sm"
      paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
      currentPageReportTemplate="Total: {totalRecords} registros">
      <Column field="fecha_cambio" header="Fecha" sortable style="width:110px" />
      <Column field="tractivo.descripcion" header="Tractivo" />
      <Column field="tipo_operacion" header="Operación" style="width:120px">
        <template #body="{ data }">
          <Tag :value="data.tipo_operacion" :severity="data.tipo_operacion === 'RELLENO' ? 'info' : (data.tipo_operacion === 'MTTO' ? 'warn' : 'danger')" />
        </template>
      </Column>
      <Column field="litros_motor" header="Motor" />
      <Column field="litros_transmision" header="Transm." />
      <Column field="litros_hidraulico" header="Hidr." />
      <Column field="grasa_rollete" header="G. Rollete" />
      <Column field="grasa_copillas" header="G. Copillas" />
      <Column header="Total" style="width:90px">
        <template #body="{ data }">{{ totalSistema(data) }}</template>
      </Column>
      <Column header="Acciones" style="width:120px">
        <template #body="{ data }">
          <Button icon="pi pi-pencil" text rounded severity="info" @click="openEdit(data)" />
          <Button icon="pi pi-trash" text rounded severity="danger" @click="destroy(data)" />
        </template>
      </Column>
    </DataTable>

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar registro' : 'Nuevo registro de lubricante'" :style="{ width: '760px' }" modal>
      <div class="grid grid-cols-3 gap-3 mt-2">
        <div class="flex flex-col gap-1 col-span-2"><label class="text-sm font-medium">Tractivo</label>
          <Select v-model="form.id_tractivo" :options="tractivos" optionLabel="descripcion" optionValue="id" class="w-full" filter />
        </div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Tipo operación</label>
          <Select v-model="form.tipo_operacion" :options="tiposOperacion" class="w-full" />
        </div>
        <div class="flex flex-col gap-1 col-span-3"><label class="text-sm font-medium">Fecha</label><InputText type="date" v-model="form.fecha_cambio" /></div>
      </div>

      <div class="grid grid-cols-2 gap-3 mt-4">
        <div v-for="s in sistemas" :key="s.key" class="border rounded p-2 flex flex-col gap-1">
          <label class="text-sm font-semibold">{{ s.label }}</label>
          <div class="flex gap-2">
            <InputText type="number" v-model="form[s.litros]" placeholder="Cantidad" class="w-1/2" />
            <Select v-model="form[s.lub]" :options="lubricantes" optionLabel="nombre" optionValue="id" placeholder="Tipo" class="w-1/2" />
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
