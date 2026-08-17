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
import Accordion from 'primevue/accordion'
import AccordionPanel from 'primevue/accordionpanel'
import AccordionHeader from 'primevue/accordionheader'
import AccordionContent from 'primevue/accordioncontent'
import { useConfirm } from 'primevue/useconfirm'

const props = defineProps({ title: String, ordenes: Object, filtros: Object, filters: Object })
const confirmDialog = useConfirm()
const search = ref(props.filters?.search || '')
const expandedRows = ref({})
const showForm = ref(false)
const editing = ref(null)
const current = ref(null)
const showOperacion = ref(false)
const showGasto = ref(false)
const showMovimiento = ref(false)
const showCierre = ref(false)

const motivos = props.filtros?.motivos_entrada || []
const clasificaciones = props.filtros?.clasificaciones || []
const tiposOperacion = props.filtros?.tipos_operaciones || []
const tiposAgregados = props.filtros?.tipos_agregados || []
const naves = props.filtros?.naves || []
const vallas = props.filtros?.vallas || []
const tractivos = props.filtros?.tractivos || []
const operarios = props.filtros?.operarios || []

const baseForm = () => ({
  numero: '',
  id_tractivo: null,
  id_tipo_mantenimiento: null,
  id_motivo_entrada: null,
  id_clasificacion: null,
  fecha_ingreso: new Date().toISOString().slice(0, 10),
  hora_ingreso: null,
  fecha_salida: null,
  hora_salida: null,
  kilometraje: null,
  notas: '',
  ot_largo_plazo: null,
  combtaller: 0,
  id_motor: null,
  id_taller: null,
})

const form = ref(baseForm())
const operacionForm = ref({ id_tipo_operacion: null, id_operario: null, id_operario2: null, id_operario3: null, fecha_inicio: null, hora_inicio: null, fecha_final: null, hora_final: null, id_nave: null, id_valla: null })
const gastoForm = ref({ importe_me: 0, vale: '', id_tipo_agregado: null, nombre: '', cantidad: 1, codigo_pieza: '', motivo: '', id_motor: null })
const movimientoForm = ref({ id_nave: null, id_valla: null, fecha_inicio: null, hora_inicio: null, fecha_final: null, hora_final: null, observaciones: '' })
const cierreForm = ref({ fecha_salida: null, hora_salida: null })

watch(search, () => {
  router.get(route('taller.index'), { search: search.value }, { preserveState: true, replace: true })
})

const onPage = (event) => {
  router.get(route('taller.index'), { page: event.page + 1, search: search.value }, { preserveState: true, replace: true })
}

function openCreate() {
  editing.value = null
  form.value = baseForm()
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  form.value = {
    numero: item.numero ?? '',
    id_tractivo: item.id_tractivo ?? null,
    id_tipo_mantenimiento: item.id_tipo_mantenimiento ?? null,
    id_motivo_entrada: item.id_motivo_entrada ?? null,
    id_clasificacion: item.id_clasificacion ?? null,
    fecha_ingreso: item.fecha_ingreso ?? null,
    hora_ingreso: item.hora_ingreso ?? null,
    fecha_salida: item.fecha_salida ?? null,
    hora_salida: item.hora_salida ?? null,
    kilometraje: item.kilometraje ?? null,
    notas: item.notas ?? '',
    ot_largo_plazo: item.ot_largo_plazo ?? null,
    combtaller: item.combtaller ?? 0,
    id_motor: item.id_motor ?? null,
    id_taller: item.id_taller ?? null,
  }
  showForm.value = true
}

function submit() {
  const payload = { ...form.value }
  const url = editing.value ? route('taller.update', { ordene: editing.value.id }) : route('taller.store')
  router[editing.value ? 'put' : 'post'](url, payload, { onSuccess: () => { showForm.value = false } })
}

function destroy(item) {
  confirmDialog.require({
    message: `¿Eliminar la orden ${item.numero ?? item.id}?`,
    header: 'Eliminar Orden', icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Eliminar', rejectLabel: 'Volver', acceptClass: 'p-button-danger',
    accept: () => router.delete(route('taller.destroy', { ordene: item.id })),
  })
}

function openCerrar(item) {
  current.value = item
  cierreForm.value = { fecha_salida: new Date().toISOString().slice(0, 10), hora_salida: null }
  showCierre.value = true
}

function submitCerrar() {
  router.post(route('taller.cerrar', { ordene: current.value.id }), { ...cierreForm.value }, { onSuccess: () => { showCierre.value = false } })
}

function cancelar(item) {
  confirmDialog.require({
    message: `¿Cancelar la orden ${item.numero ?? item.id}?`,
    header: 'Cancelar Orden', icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Cancelar', rejectLabel: 'Volver', acceptClass: 'p-button-danger',
    accept: () => router.post(route('taller.cancelar', { ordene: item.id })),
  })
}

function openOperacion(item) {
  current.value = item
  operacionForm.value = { id_tipo_operacion: null, id_operario: null, id_operario2: null, id_operario3: null, fecha_inicio: null, hora_inicio: null, fecha_final: null, hora_final: null, id_nave: null, id_valla: null }
  showOperacion.value = true
}

function submitOperacion() {
  router.post(route('taller.operaciones', { ordene: current.value.id }), { ...operacionForm.value }, { onSuccess: () => { showOperacion.value = false } })
}

function openGasto(item) {
  current.value = item
  gastoForm.value = { importe_me: 0, vale: '', id_tipo_agregado: null, nombre: '', cantidad: 1, codigo_pieza: '', motivo: '', id_motor: null }
  showGasto.value = true
}

function submitGasto() {
  router.post(route('taller.gastos', { ordene: current.value.id }), { ...gastoForm.value }, { onSuccess: () => { showGasto.value = false } })
}

function openMovimiento(item) {
  current.value = item
  movimientoForm.value = { id_nave: null, id_valla: null, fecha_inicio: null, hora_inicio: null, fecha_final: null, hora_final: null, observaciones: '' }
  showMovimiento.value = true
}

function submitMovimiento() {
  router.post(route('taller.movimientos', { ordene: current.value.id }), { ...movimientoForm.value }, { onSuccess: () => { showMovimiento.value = false } })
}
</script>

<template>
  <AppLayout>
    <Toolbar class="mb-3">
      <template #start><h2 class="text-xl font-bold m-0">{{ title ?? 'Taller' }}</h2></template>
      <template #end>
        <div class="flex gap-2">
          <InputText v-model="search" placeholder="Buscar por número o diagnóstico..." class="w-64" />
          <Button icon="pi pi-plus" label="Nueva orden" @click="openCreate" />
        </div>
      </template>
    </Toolbar>

    <DataTable :value="ordenes.data" paginator :rows="ordenes.per_page" :totalRecords="ordenes.total"
      :rowsPerPageOptions="[10, 20, 50]" :first="(ordenes.current_page - 1) * ordenes.per_page"
      @page="onPage" stripedRows class="p-datatable-sm"
      paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
      currentPageReportTemplate="Total: {totalRecords} registros" expandedRows
      dataKey="id" v-model:expanded-rows="expandedRows">
      <Column expander style="width:40px" />
      <Column field="numero" header="N°" sortable style="width:100px" />
      <Column field="tractivo.descripcion" header="Tractivo" />
      <Column field="fecha_ingreso" header="Entrada" />
      <Column field="motivo_entrada.nombre" header="Motivo" />
      <Column field="clasificacion.nombre" header="Clasif." style="width:100px" />
      <Column field="estado" header="Estado" style="width:110px">
        <template #body="{ data }">
          <Tag :value="data.estado" :severity="data.estado === 'abierta' ? 'warn' : (data.estado === 'cerrada' ? 'success' : 'danger')" />
        </template>
      </Column>
      <Column header="Acciones" style="width:280px">
        <template #body="{ data }">
          <Button icon="pi pi-pencil" text rounded severity="info" title="Editar" @click="openEdit(data)" />
          <Button icon="pi pi-wrench" text rounded severity="warn" title="Operaciones" @click="openOperacion(data)" :disabled="data.estado !== 'abierta'" />
          <Button icon="pi pi-box" text rounded severity="secondary" title="Piezas" @click="openGasto(data)" :disabled="data.estado !== 'abierta'" />
          <Button icon="pi pi-arrows-alt" text rounded severity="secondary" title="Movimiento" @click="openMovimiento(data)" :disabled="data.estado !== 'abierta'" />
          <Button icon="pi pi-check" text rounded severity="success" title="Cerrar" @click="openCerrar(data)" :disabled="data.estado !== 'abierta'" />
          <Button icon="pi pi-times" text rounded severity="danger" title="Cancelar" @click="cancelar(data)" :disabled="data.estado === 'cancelada'" />
          <Button icon="pi pi-trash" text rounded severity="danger" title="Eliminar" @click="destroy(data)" />
        </template>
      </Column>
      <template #expansion="{ data }">
        <div class="p-3">
          <Accordion>
            <AccordionPanel value="0">
              <AccordionHeader>Operaciones</AccordionHeader>
              <AccordionContent>
              <DataTable :value="data.operaciones" size="small">
                <Column field="tipo_operacion" header="Operación"><template #body="{ data: d }">{{ d.id_tipo_operacion }}</template></Column>
                <Column field="fecha_inicio" header="Inicio" />
                <Column field="hora_inicio" header="H. Inicio" />
                <Column field="fecha_final" header="Final" />
                <Column field="tiempo" header="Tiempo" />
              </DataTable>
              </AccordionContent>
            </AccordionPanel>
            <AccordionPanel value="1">
              <AccordionHeader>Piezas / Recursos de almacén</AccordionHeader>
              <AccordionContent>
              <DataTable :value="data.gastos" size="small">
                <Column field="vale" header="Vale" />
                <Column field="codigo_pieza" header="Código" />
                <Column field="nombre" header="Nombre" />
                <Column field="cantidad" header="Cant." />
                <Column field="motivo" header="Motivo" />
              </DataTable>
              </AccordionContent>
            </AccordionPanel>
            <AccordionPanel value="2">
              <AccordionHeader>Movimientos en taller</AccordionHeader>
              <AccordionContent>
              <DataTable :value="data.movimientos" size="small">
                <Column field="fecha_inicio" header="Inicio" />
                <Column field="id_nave" header="Nave" />
                <Column field="id_valla" header="Valla" />
                <Column field="tiempo" header="Tiempo" />
              </DataTable>
              </AccordionContent>
            </AccordionPanel>
          </Accordion>
        </div>
      </template>
    </DataTable>

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar orden' : 'Nueva orden de taller'" :style="{ width: '620px' }" modal>
      <div class="grid grid-cols-2 gap-3 mt-2">
        <div class="flex flex-col gap-1 col-span-2"><label class="text-sm font-medium">Tractivo *</label>
          <Select v-model="form.id_tractivo" :options="tractivos" optionLabel="descripcion" optionValue="id" class="w-full" filter />
        </div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">N°</label><InputText v-model="form.numero" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Motivo entrada</label>
          <Select v-model="form.id_motivo_entrada" :options="motivos" optionLabel="nombre" optionValue="id" class="w-full" />
        </div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Clasificación</label>
          <Select v-model="form.id_clasificacion" :options="clasificaciones" optionLabel="nombre" optionValue="id" class="w-full" />
        </div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Fecha ingreso *</label><InputText type="date" v-model="form.fecha_ingreso" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Hora ingreso</label><InputText v-model="form.hora_ingreso" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Kilometraje</label><InputText type="number" v-model="form.kilometraje" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Combustible taller</label><InputText type="number" v-model="form.combtaller" /></div>
        <div class="flex flex-col gap-1 col-span-2"><label class="text-sm font-medium">Notas</label><Textarea v-model="form.notas" rows="2" /></div>
      </div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" text @click="showForm = false" />
        <Button label="Guardar" icon="pi pi-check" @click="submit" />
      </template>
    </Dialog>

    <Dialog v-model:visible="showOperacion" header="Agregar operación" :style="{ width: '540px' }" modal>
      <div class="grid grid-cols-2 gap-3 mt-2">
        <div class="flex flex-col gap-1 col-span-2"><label class="text-sm font-medium">Operación *</label>
          <Select v-model="operacionForm.id_tipo_operacion" :options="tiposOperacion" optionLabel="nombre" optionValue="id" class="w-full" filter />
        </div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Operario 1</label><InputText v-model="operacionForm.id_operario" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Operario 2</label><InputText v-model="operacionForm.id_operario2" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Operario 3</label><InputText v-model="operacionForm.id_operario3" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Nave</label>
          <Select v-model="operacionForm.id_nave" :options="naves" optionLabel="nombre" optionValue="id" class="w-full" />
        </div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Fecha inicio</label><InputText type="date" v-model="operacionForm.fecha_inicio" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Hora inicio</label><InputText v-model="operacionForm.hora_inicio" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Fecha final</label><InputText type="date" v-model="operacionForm.fecha_final" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Hora final</label><InputText v-model="operacionForm.hora_final" /></div>
      </div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" text @click="showOperacion = false" />
        <Button label="Guardar" icon="pi pi-check" @click="submitOperacion" />
      </template>
    </Dialog>

    <Dialog v-model:visible="showGasto" header="Agregar pieza / recurso de almacén" :style="{ width: '540px' }" modal>
      <div class="grid grid-cols-2 gap-3 mt-2">
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Tipo agregado</label>
          <Select v-model="gastoForm.id_tipo_agregado" :options="tiposAgregados" optionLabel="nombre" optionValue="id" class="w-full" />
        </div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Cantidad</label><InputText type="number" v-model="gastoForm.cantidad" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Nombre</label><InputText v-model="gastoForm.nombre" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Código pieza</label><InputText v-model="gastoForm.codigo_pieza" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Vale</label><InputText v-model="gastoForm.vale" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Importe MLC</label><InputText type="number" v-model="gastoForm.importe_me" /></div>
        <div class="flex flex-col gap-1 col-span-2"><label class="text-sm font-medium">Motivo</label><InputText v-model="gastoForm.motivo" /></div>
      </div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" text @click="showGasto = false" />
        <Button label="Guardar" icon="pi pi-check" @click="submitGasto" />
      </template>
    </Dialog>

    <Dialog v-model:visible="showMovimiento" header="Registrar movimiento en taller" :style="{ width: '480px' }" modal>
      <div class="grid grid-cols-2 gap-3 mt-2">
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Nave</label>
          <Select v-model="movimientoForm.id_nave" :options="naves" optionLabel="nombre" optionValue="id" class="w-full" />
        </div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Valla</label>
          <Select v-model="movimientoForm.id_valla" :options="vallas" optionLabel="nombre" optionValue="id" class="w-full" />
        </div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Fecha inicio</label><InputText type="date" v-model="movimientoForm.fecha_inicio" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Hora inicio</label><InputText v-model="movimientoForm.hora_inicio" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Fecha final</label><InputText type="date" v-model="movimientoForm.fecha_final" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Hora final</label><InputText v-model="movimientoForm.hora_final" /></div>
        <div class="flex flex-col gap-1 col-span-2"><label class="text-sm font-medium">Observaciones</label><Textarea v-model="movimientoForm.observaciones" rows="2" /></div>
      </div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" text @click="showMovimiento = false" />
        <Button label="Guardar" icon="pi pi-check" @click="submitMovimiento" />
      </template>
    </Dialog>

    <Dialog v-model:visible="showCierre" header="Cerrar orden" :style="{ width: '420px' }" modal>
      <div class="grid grid-cols-2 gap-3 mt-2">
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Fecha salida</label><InputText type="date" v-model="cierreForm.fecha_salida" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Hora salida</label><InputText v-model="cierreForm.hora_salida" /></div>
      </div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" text @click="showCierre = false" />
        <Button label="Cerrar" icon="pi pi-check" severity="success" @click="submitCerrar" />
      </template>
    </Dialog>
  </AppLayout>
</template>
