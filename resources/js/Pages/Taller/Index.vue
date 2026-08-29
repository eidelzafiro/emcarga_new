<script setup>
import { ref, watch, onMounted } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
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
const filtroTractivo = ref(props.filters?.id_tractivo || null)
const filtroEstado = ref(props.filters?.estado || null)
const filtroMotivo = ref(props.filters?.id_motivo_entrada || null)
const showForm = ref(false)
const editing = ref(null)
const current = ref(null)
const showOperacion = ref(false)
const showGasto = ref(false)
const showMovimiento = ref(false)
const showCierre = ref(false)
const showEditOperacion = ref(false)
const showEditGasto = ref(false)
const showEditMovimiento = ref(false)
const editingItem = ref(null)
const formError = ref('')

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
  router.get(route('taller.index'), { search: search.value, id_tractivo: filtroTractivo.value, estado: filtroEstado.value, id_motivo_entrada: filtroMotivo.value }, { preserveState: true, replace: true })
})
watch([filtroTractivo, filtroEstado, filtroMotivo], () => {
  router.get(route('taller.index'), { search: search.value, id_tractivo: filtroTractivo.value, estado: filtroEstado.value, id_motivo_entrada: filtroMotivo.value }, { preserveState: true, replace: true })
})

onMounted(() => {
  if (route().queryParams.nuevo === '1') {
    openCreate();
    const tractivoParam = route().queryParams.tractivo;
    if (tractivoParam) {
      form.value.id_tractivo = Number(tractivoParam);
    }
    window.history.replaceState({}, '', route('taller.index'));
  }
});

function openCreate() {
  editing.value = null
  formError.value = ''
  form.value = baseForm()
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  formError.value = ''
  form.value = {
    numero: item.numero ?? '',
    id_tractivo: item.id_tractivo ?? null,
    id_tipo_mantenimiento: item.id_tipo_mantenimiento ?? null,
    id_motivo_entrada: item.id_motivo_entrada ?? null,
    id_clasificacion: item.id_clasificacion ?? null,
    fecha_ingreso: fmtFecha(item.fecha_ingreso),
    hora_ingreso: item.hora_ingreso ?? null,
    fecha_salida: fmtFecha(item.fecha_salida),
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
  formError.value = ''
  const payload = { ...form.value }
  const url = editing.value ? route('taller.update', { ordene: editing.value.id }) : route('taller.store')
  router[editing.value ? 'put' : 'post'](url, payload, {
    onSuccess: () => { showForm.value = false },
    onError: (e) => { formError.value = Object.values(e).flat().join(' ') || 'Error al guardar' },
  })
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
  formError.value = ''
  cierreForm.value = { fecha_salida: new Date().toISOString().slice(0, 10), hora_salida: null }
  showCierre.value = true
}

function submitCerrar() {
  formError.value = ''
  router.post(route('taller.cerrar', { ordene: current.value.id }), { ...cierreForm.value }, {
    onSuccess: () => { showCierre.value = false },
    onError: (e) => { formError.value = Object.values(e).flat().join(' ') || 'Error al cerrar' },
  })
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
  formError.value = ''
  operacionForm.value = { id_tipo_operacion: null, id_operario: null, id_operario2: null, id_operario3: null, fecha_inicio: null, hora_inicio: null, fecha_final: null, hora_final: null, id_nave: null, id_valla: null }
  showOperacion.value = true
}

function submitOperacion() {
  formError.value = ''
  router.post(route('taller.operaciones', { ordene: current.value.id }), { ...operacionForm.value }, {
    onSuccess: () => { showOperacion.value = false },
    onError: (e) => { formError.value = Object.values(e).flat().join(' ') || 'Error al agregar operación' },
  })
}

function openEditOperacion(op) {
  editingItem.value = op
  formError.value = ''
  operacionForm.value = {
    id_tipo_operacion: op.id_tipo_operacion ?? null,
    id_operario: op.id_operario ?? null,
    id_operario2: op.id_operario2 ?? null,
    id_operario3: op.id_operario3 ?? null,
    fecha_inicio: fmtFecha(op.fecha_inicio),
    hora_inicio: op.hora_inicio ?? null,
    fecha_final: fmtFecha(op.fecha_final),
    hora_final: op.hora_final ?? null,
    id_nave: op.id_nave ?? null,
    id_valla: op.id_valla ?? null,
  }
  showEditOperacion.value = true
}

function submitEditOperacion() {
  formError.value = ''
  router.put(route('taller.operaciones.update', { ordene: editingItem.value.id_orden_taller, operacione: editingItem.value.id }), { ...operacionForm.value }, {
    onSuccess: () => { showEditOperacion.value = false },
    onError: (e) => { formError.value = Object.values(e).flat().join(' ') || 'Error al actualizar operación' },
  })
}

function deleteOperacion(op) {
  confirmDialog.require({
    message: '¿Eliminar esta operación?',
    header: 'Eliminar Operación', icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Eliminar', rejectLabel: 'Volver', acceptClass: 'p-button-danger',
    accept: () => router.delete(route('taller.operaciones.destroy', { ordene: op.id_orden_taller, operacione: op.id })),
  })
}

function openGasto(item) {
  current.value = item
  formError.value = ''
  gastoForm.value = { importe_me: 0, vale: '', id_tipo_agregado: null, nombre: '', cantidad: 1, codigo_pieza: '', motivo: '', id_motor: null }
  showGasto.value = true
}

function submitGasto() {
  formError.value = ''
  router.post(route('taller.gastos', { ordene: current.value.id }), { ...gastoForm.value }, {
    onSuccess: () => { showGasto.value = false },
    onError: (e) => { formError.value = Object.values(e).flat().join(' ') || 'Error al agregar pieza' },
  })
}

function openEditGasto(g) {
  editingItem.value = g
  formError.value = ''
  gastoForm.value = {
    importe_me: g.importe_me ?? 0,
    vale: g.vale ?? '',
    id_tipo_agregado: g.id_tipo_agregado ?? null,
    nombre: g.nombre ?? '',
    cantidad: g.cantidad ?? 1,
    codigo_pieza: g.codigo_pieza ?? '',
    motivo: g.motivo ?? '',
    id_motor: g.id_motor ?? null,
  }
  showEditGasto.value = true
}

function submitEditGasto() {
  formError.value = ''
  router.put(route('taller.gastos.update', { ordene: editingItem.value.id_orden_taller, gasto: editingItem.value.id }), { ...gastoForm.value }, {
    onSuccess: () => { showEditGasto.value = false },
    onError: (e) => { formError.value = Object.values(e).flat().join(' ') || 'Error al actualizar pieza' },
  })
}

function deleteGasto(g) {
  confirmDialog.require({
    message: '¿Eliminar esta pieza/recurso?',
    header: 'Eliminar Pieza', icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Eliminar', rejectLabel: 'Volver', acceptClass: 'p-button-danger',
    accept: () => router.delete(route('taller.gastos.destroy', { ordene: g.id_orden_taller, gasto: g.id })),
  })
}

function openMovimiento(item) {
  current.value = item
  formError.value = ''
  movimientoForm.value = { id_nave: null, id_valla: null, fecha_inicio: null, hora_inicio: null, fecha_final: null, hora_final: null, observaciones: '' }
  showMovimiento.value = true
}

function submitMovimiento() {
  formError.value = ''
  router.post(route('taller.movimientos', { ordene: current.value.id }), { ...movimientoForm.value }, {
    onSuccess: () => { showMovimiento.value = false },
    onError: (e) => { formError.value = Object.values(e).flat().join(' ') || 'Error al registrar movimiento' },
  })
}

function openEditMovimiento(m) {
  editingItem.value = m
  formError.value = ''
  movimientoForm.value = {
    id_nave: m.id_nave ?? null,
    id_valla: m.id_valla ?? null,
    fecha_inicio: fmtFecha(m.fecha_inicio),
    hora_inicio: m.hora_inicio ?? null,
    fecha_final: fmtFecha(m.fecha_final),
    hora_final: m.hora_final ?? null,
    observaciones: m.observaciones ?? '',
  }
  showEditMovimiento.value = true
}

function submitEditMovimiento() {
  formError.value = ''
  router.put(route('taller.movimientos.update', { ordene: editingItem.value.id_orden_taller, movimiento: editingItem.value.id }), { ...movimientoForm.value }, {
    onSuccess: () => { showEditMovimiento.value = false },
    onError: (e) => { formError.value = Object.values(e).flat().join(' ') || 'Error al actualizar movimiento' },
  })
}

function deleteMovimiento(m) {
  confirmDialog.require({
    message: '¿Eliminar este movimiento?',
    header: 'Eliminar Movimiento', icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Eliminar', rejectLabel: 'Volver', acceptClass: 'p-button-danger',
    accept: () => router.delete(route('taller.movimientos.destroy', { ordene: m.id_orden_taller, movimiento: m.id })),
  })
}

function estadoSeverity(estado) {
  if (estado === 'abierta') return 'warn'
  if (estado === 'cerrada') return 'success'
  return 'danger'
}

// El cast `date` de Laravel serializa a ISO completo; el <input type="date">
// y la tarjeta solo necesitan YYYY-MM-DD.
function fmtFecha(v) {
  if (!v) return null
  return String(v).slice(0, 10)
}
</script>

<template>
  <AppLayout>
    <Toolbar class="mb-3">
      <template #start>
        <div class="flex items-center gap-3">
          <Link v-if="route().queryParams.origen" :href="route('tecnico.dashboard')" class="inline-flex items-center gap-1 text-sm text-blue-600 hover:underline dark:text-blue-400">
            <i class="pi pi-arrow-left"></i> Volver a Pizarra
          </Link>
          <h2 class="text-xl font-bold m-0">{{ title ?? 'Taller' }}</h2>
        </div>
      </template>
      <template #end>
        <div class="flex gap-2">
          <InputText v-model="search" placeholder="Buscar por número o diagnóstico..." class="w-64" />
          <Button icon="pi pi-plus" label="Nueva orden" @click="openCreate" />
        </div>
      </template>
    </Toolbar>

    <div class="flex flex-wrap items-end gap-3 mb-3">
      <div class="flex flex-col gap-1">
        <label class="text-xs font-medium text-gray-600 dark:text-gray-300">Vehículo en taller</label>
        <Select v-model="filtroTractivo" :options="tractivos" optionLabel="descripcion" optionValue="id" class="w-56" placeholder="Todos" filter showClear />
      </div>
      <div class="flex flex-col gap-1">
        <label class="text-xs font-medium text-gray-600 dark:text-gray-300">Estado</label>
        <Select v-model="filtroEstado" :options="filtros?.estados || ['abierta','cerrada','cancelada']" class="w-40" placeholder="Todos" showClear />
      </div>
      <div class="flex flex-col gap-1">
        <label class="text-xs font-medium text-gray-600 dark:text-gray-300">Motivo</label>
        <Select v-model="filtroMotivo" :options="motivos" optionLabel="nombre" optionValue="id" class="w-56" placeholder="Todos" filter showClear />
      </div>
    </div>

    <div v-if="formError" class="mb-3 p-3 rounded bg-red-50 text-red-700 border border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-800">
      {{ formError }}
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
      <div v-for="ot in ordenes.data" :key="ot.id"
           class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 flex flex-col gap-3">
        <div class="flex items-start justify-between gap-2">
          <div>
            <div class="flex items-center gap-2">
              <span class="text-lg font-bold">{{ ot.numero ?? ('#' + ot.id) }}</span>
              <Tag :value="ot.estado" :severity="estadoSeverity(ot.estado)" />
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-300">{{ ot.tractivo?.descripcion || 'Sin tractivo' }}</div>
          </div>
          <div class="flex gap-1">
            <Button icon="pi pi-pencil" text rounded severity="info" title="Editar" @click="openEdit(ot)" />
            <Button icon="pi pi-trash" text rounded severity="danger" title="Eliminar" @click="destroy(ot)" />
          </div>
        </div>

        <div class="text-sm space-y-1 text-gray-700 dark:text-gray-200">
          <div><span class="font-medium">Entrada:</span> {{ fmtFecha(ot.fecha_ingreso) || '-' }} <span class="text-gray-500">{{ ot.hora_ingreso || '' }}</span></div>
          <div><span class="font-medium">Motivo:</span> {{ ot.motivo_entrada?.nombre || '-' }}</div>
          <div><span class="font-medium">Clasif.:</span> {{ ot.clasificacion?.nombre || '-' }}</div>
          <div><span class="font-medium">Km:</span> {{ ot.kilometraje ?? '-' }} <span class="text-gray-500">· Comb.: {{ ot.combtaller ?? 0 }}</span></div>
        </div>

        <div class="flex flex-wrap gap-1">
          <Button icon="pi pi-wrench" label="Operaciones" size="small" severity="warn" :disabled="ot.estado !== 'abierta'" @click="openOperacion(ot)" />
          <Button icon="pi pi-box" label="Piezas" size="small" severity="secondary" :disabled="ot.estado !== 'abierta'" @click="openGasto(ot)" />
          <Button icon="pi pi-arrows-alt" label="Movimiento" size="small" severity="secondary" :disabled="ot.estado !== 'abierta'" @click="openMovimiento(ot)" />
        </div>
        <div class="flex flex-wrap gap-1">
          <Button icon="pi pi-check" label="Cerrar" size="small" severity="success" :disabled="ot.estado !== 'abierta'" @click="openCerrar(ot)" />
          <Button icon="pi pi-times" label="Cancelar" size="small" severity="danger" :disabled="ot.estado === 'cancelada'" @click="cancelar(ot)" />
        </div>

        <Accordion class="mt-1">
          <AccordionPanel value="0">
            <AccordionHeader>Operaciones ({{ ot.operaciones?.length || 0 }})</AccordionHeader>
            <AccordionContent>
              <div v-if="ot.operaciones?.length" class="space-y-1 text-sm">
                <div v-for="op in ot.operaciones" :key="op.id" class="border-b border-gray-100 dark:border-gray-700 py-1 flex items-start justify-between gap-2">
                  <span>
                    <span class="font-medium">{{ op.tipo_operacion?.nombre || ('Op #' + op.id_tipo_operacion) }}</span>
                    <span class="text-gray-500"> · {{ fmtFecha(op.fecha_inicio) || '-' }} → {{ fmtFecha(op.fecha_final) || '-' }} · {{ op.tiempo }} h</span>
                  </span>
                  <span class="flex gap-1 shrink-0">
                    <Button icon="pi pi-pencil" text rounded severity="info" title="Editar" @click="openEditOperacion(op)" />
                    <Button icon="pi pi-trash" text rounded severity="danger" title="Eliminar" @click="deleteOperacion(op)" />
                  </span>
                </div>
              </div>
              <div v-else class="text-sm text-gray-500">Sin operaciones.</div>
            </AccordionContent>
          </AccordionPanel>
          <AccordionPanel value="1">
            <AccordionHeader>Piezas ({{ ot.gastos?.length || 0 }})</AccordionHeader>
            <AccordionContent>
              <div v-if="ot.gastos?.length" class="space-y-1 text-sm">
                <div v-for="g in ot.gastos" :key="g.id" class="border-b border-gray-100 dark:border-gray-700 py-1 flex items-start justify-between gap-2">
                  <span>
                    <span class="font-medium">{{ g.nombre || ('Agregado #' + g.id_tipo_agregado) }}</span>
                    <span class="text-gray-500"> · {{ g.cantidad }} · {{ g.codigo_pieza || '-' }}</span>
                  </span>
                  <span class="flex gap-1 shrink-0">
                    <Button icon="pi pi-pencil" text rounded severity="info" title="Editar" @click="openEditGasto(g)" />
                    <Button icon="pi pi-trash" text rounded severity="danger" title="Eliminar" @click="deleteGasto(g)" />
                  </span>
                </div>
              </div>
              <div v-else class="text-sm text-gray-500">Sin piezas.</div>
            </AccordionContent>
          </AccordionPanel>
          <AccordionPanel value="2">
            <AccordionHeader>Movimientos ({{ ot.movimientos?.length || 0 }})</AccordionHeader>
            <AccordionContent>
              <div v-if="ot.movimientos?.length" class="space-y-1 text-sm">
                <div v-for="m in ot.movimientos" :key="m.id" class="border-b border-gray-100 dark:border-gray-700 py-1 flex items-start justify-between gap-2">
                  <span>
                    <span class="font-medium">{{ fmtFecha(m.fecha_inicio) || '-' }} → {{ fmtFecha(m.fecha_final) || '-' }}</span>
                    <span class="text-gray-500"> · {{ m.tiempo }} h</span>
                  </span>
                  <span class="flex gap-1 shrink-0">
                    <Button icon="pi pi-pencil" text rounded severity="info" title="Editar" @click="openEditMovimiento(m)" />
                    <Button icon="pi pi-trash" text rounded severity="danger" title="Eliminar" @click="deleteMovimiento(m)" />
                  </span>
                </div>
              </div>
              <div v-else class="text-sm text-gray-500">Sin movimientos.</div>
            </AccordionContent>
          </AccordionPanel>
        </Accordion>
      </div>
    </div>

    <div v-if="ordenes.last_page > 1" class="flex justify-center mt-4">
      <Button v-for="p in ordenes.last_page" :key="p" :label="String(p)" size="small"
              :outlined="p !== ordenes.current_page" @click="router.get(route('taller.index'), { page: p, search: search.value }, { preserveState: true })" />
    </div>

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar orden' : 'Nueva orden de taller'" :style="{ width: '620px' }" modal>
      <div v-if="formError" class="mb-3 text-sm text-red-600">{{ formError }}</div>
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
      <div v-if="formError" class="mb-3 text-sm text-red-600">{{ formError }}</div>
      <div class="grid grid-cols-2 gap-3 mt-2">
        <div class="flex flex-col gap-1 col-span-2"><label class="text-sm font-medium">Operación *</label>
          <Select v-model="operacionForm.id_tipo_operacion" :options="tiposOperacion" optionLabel="nombre" optionValue="id" class="w-full" filter />
        </div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Operario 1</label>
          <Select v-model="operacionForm.id_operario" :options="operarios" optionLabel="nombre" optionValue="id" class="w-full" filter />
        </div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Operario 2</label>
          <Select v-model="operacionForm.id_operario2" :options="operarios" optionLabel="nombre" optionValue="id" class="w-full" filter />
        </div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Operario 3</label>
          <Select v-model="operacionForm.id_operario3" :options="operarios" optionLabel="nombre" optionValue="id" class="w-full" filter />
        </div>
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
      <div v-if="formError" class="mb-3 text-sm text-red-600">{{ formError }}</div>
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
      <div v-if="formError" class="mb-3 text-sm text-red-600">{{ formError }}</div>
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

    <Dialog v-model:visible="showEditOperacion" header="Editar operación" :style="{ width: '540px' }" modal>
      <div v-if="formError" class="mb-3 text-sm text-red-600">{{ formError }}</div>
      <div class="grid grid-cols-2 gap-3 mt-2">
        <div class="flex flex-col gap-1 col-span-2"><label class="text-sm font-medium">Operación *</label>
          <Select v-model="operacionForm.id_tipo_operacion" :options="tiposOperacion" optionLabel="nombre" optionValue="id" class="w-full" filter />
        </div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Operario 1</label>
          <Select v-model="operacionForm.id_operario" :options="operarios" optionLabel="nombre" optionValue="id" class="w-full" filter />
        </div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Operario 2</label>
          <Select v-model="operacionForm.id_operario2" :options="operarios" optionLabel="nombre" optionValue="id" class="w-full" filter />
        </div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Operario 3</label>
          <Select v-model="operacionForm.id_operario3" :options="operarios" optionLabel="nombre" optionValue="id" class="w-full" filter />
        </div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Nave</label>
          <Select v-model="operacionForm.id_nave" :options="naves" optionLabel="nombre" optionValue="id" class="w-full" />
        </div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Fecha inicio</label><InputText type="date" v-model="operacionForm.fecha_inicio" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Hora inicio</label><InputText v-model="operacionForm.hora_inicio" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Fecha final</label><InputText type="date" v-model="operacionForm.fecha_final" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Hora final</label><InputText v-model="operacionForm.hora_final" /></div>
      </div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" text @click="showEditOperacion = false" />
        <Button label="Guardar" icon="pi pi-check" @click="submitEditOperacion" />
      </template>
    </Dialog>

    <Dialog v-model:visible="showEditGasto" header="Editar pieza / recurso de almacén" :style="{ width: '540px' }" modal>
      <div v-if="formError" class="mb-3 text-sm text-red-600">{{ formError }}</div>
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
        <Button label="Cancelar" severity="secondary" text @click="showEditGasto = false" />
        <Button label="Guardar" icon="pi pi-check" @click="submitEditGasto" />
      </template>
    </Dialog>

    <Dialog v-model:visible="showEditMovimiento" header="Editar movimiento en taller" :style="{ width: '480px' }" modal>
      <div v-if="formError" class="mb-3 text-sm text-red-600">{{ formError }}</div>
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
        <Button label="Cancelar" severity="secondary" text @click="showEditMovimiento = false" />
        <Button label="Guardar" icon="pi pi-check" @click="submitEditMovimiento" />
      </template>
    </Dialog>

    <Dialog v-model:visible="showCierre" header="Cerrar orden" :style="{ width: '420px' }" modal>
      <div v-if="formError" class="mb-3 text-sm text-red-600">{{ formError }}</div>
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
