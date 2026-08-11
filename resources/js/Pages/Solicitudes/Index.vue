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
import Checkbox from 'primevue/checkbox'
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import { useToast } from 'primevue/usetoast'

const props = defineProps({
  solicitudes: Object,
  clientes: Array,
  lugares: Array,
  productos: Array,
  tiposCargas: Array,
  monedas: Array,
  catalogosCarta: Object,
  filters: Object,
})
const toast = useToast()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)
const form = ref({})
const showCarta = ref(false)
const carta = ref({})
const cartaSolicitud = ref(null)
const title = 'Solicitudes de Servicio'

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

function fmtDmy(v) {
  const d = toDate(v)
  if (!d) return v ?? ''
  return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()}`
}

function baseForm() {
  const hoy = new Date()
  return {
    numero: '',
    fecha_solicitud: fmtDate(hoy),
    fecha_planificada: null,
    id_cliente: null,
    id_lugar_origen: null,
    id_lugar_destino: null,
    id_producto: null,
    id_producto2: null,
    id_tipo_carga: null,
    id_tipo_carga2: null,
    peso1: null,
    peso2: null,
    distancia: null,
    notas: '',
  }
}

watch(search, () => {
  router.get(route('solicitudes.index'), { search: search.value }, { preserveState: true, replace: true })
})

function onPage(event) {
  router.get(route('solicitudes.index'), { page: event.page + 1, search: search.value }, { preserveState: true, replace: true })
}

function openCreate() {
  editing.value = null
  form.value = baseForm()
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  form.value = {
    numero: item.numero,
    fecha_solicitud: fmtDate(item.fecha_solicitud),
    fecha_planificada: fmtDate(item.fecha_planificada),
    id_cliente: item.id_cliente,
    id_lugar_origen: item.id_lugar_origen,
    id_lugar_destino: item.id_lugar_destino,
    id_producto: item.id_producto,
    id_producto2: item.id_producto2,
    id_tipo_carga: item.id_tipo_carga,
    id_tipo_carga2: item.id_tipo_carga2,
    peso1: item.peso1,
    peso2: item.peso2,
    distancia: item.distancia,
    notas: item.notas ?? '',
  }
  showForm.value = true
}

function duplicar(item) {
  if (window.confirm(`¿Duplicar la solicitud ${item.numero}?`)) {
    router.post(route('solicitudes.duplicar', item.id))
  }
}

function abrirCarta(item) {
  cartaSolicitud.value = item
  const cc = props.catalogosCarta || {}
  carta.value = {
    numero: '',
    ingreso_mt: item.toneladas_pendientes ?? null,
    toneladas: item.toneladas_pendientes ?? null,
    peso1: item.toneladas_pendientes ?? null,
    peso2: null,
    fecha_parte: fmtDate(new Date()),
    fecha_emision: fmtDate(new Date()),
    id_hoja_ruta: null,
    id_solicitud: item.id,
    id_cliente: item.id_cliente,
    id_lugar_origen: item.id_lugar_origen,
    id_lugar_destino: item.id_lugar_destino,
    id_producto: item.id_producto,
    id_producto2: item.id_producto2,
    id_tipo_carga: item.id_tipo_carga,
    id_tipo_carga2: item.id_tipo_carga2,
    id_turno: null,
    id_buque: null,
    id_tractivo: null,
    id_arrastre: null,
    id_chofer: null,
    id_chofer2: null,
    distancia: item.distancia ?? null,
    conduce: '',
    notas: '',
    imprimir: false,
  }
  showCarta.value = true
}

function registrarCarta() {
  if (!cartaSolicitud.value) return
  const payload = { ...carta.value }
  delete payload.ingreso_mt
  router.post(route('solicitudes.carta-porte', cartaSolicitud.value.id), payload, {
    onSuccess: () => {
      showCarta.value = false
      toast.add({ severity: 'success', summary: 'Carta de porte registrada', life: 3000 })
    },
    onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
  })
}

const cc = () => props.catalogosCarta || {}
const hojasCarta = () => (cc().hojasRuta || []).map(h => ({ ...h, label: `${h.numero}${h.tractivo_codigo ? ` (${h.tractivo_codigo})` : ''}${h.chofer_nombre ? ` • ${h.chofer_nombre}` : ''}` }))
const choferesCarta = () => (cc().choferes || []).map(c => ({ ...c, label: `${c.nombre} ${c.apellidos || ''}`.trim() }))
const tractivosCarta = () => cc().tractivos || []
const arrastresCarta = () => cc().arrastres || []
const turnosCarta = () => cc().turnos || []
const buquesCarta = () => cc().buques || []

function aplicarHojaCarta(event) {
  const hr = hojasCarta().find(h => h.id === event)
  if (!hr) return
  carta.value.id_chofer = carta.value.id_chofer || hr.id_chofer || null
  carta.value.id_chofer2 = carta.value.id_chofer2 || hr.id_chofer2 || null
  carta.value.id_tractivo = carta.value.id_tractivo || hr.id_tractivo || null
  carta.value.id_arrastre = carta.value.id_arrastre || hr.id_arrastre || null
}

function validarFolioCarta() {
  if (!carta.value.numero) return
  fetch(route('carta-porte.validar-folio'), {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '' },
    body: JSON.stringify({ numero: carta.value.numero, fecha_emision: carta.value.fecha_emision || fmtDate(new Date()) }),
  })
    .then(r => r.json())
    .then((json) => {
      if (!json.disponible) toast.add({ severity: 'warn', summary: 'Folio ocupado', detail: `El folio ${carta.value.numero} ya está registrado en este mes.`, life: 5000 })
    })
    .catch(() => {})
}

function submit() {
  const url = editing.value ? route('solicitudes.update', editing.value.id) : route('solicitudes.store')
  const method = editing.value ? 'put' : 'post'
  router[method](url, form.value, {
    onSuccess: () => {
      showForm.value = false
      toast.add({ severity: 'success', summary: editing.value ? 'Actualizado' : 'Creado', life: 3000 })
    },
    onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
  })
}

function eliminar(item) {
  if (window.confirm(`¿Eliminar la solicitud ${item.numero}?`)) {
    router.delete(route('solicitudes.destroy', item.id))
  }
}

const cumplimiento = (s) => s.estado_cumplimiento ?? 'pendiente'
const cumplimientoLabel = (s) => (s.estado_cumplimiento === 'realizada' ? 'realizada' : s.estado_cumplimiento === 'en_proceso' ? 'en proceso' : s.estado ?? 'pendiente')
const cumplimientoSeverity = (s) => {
  const c = cumplimiento(s)
  if (c === 'realizada') return 'success'
  if (c === 'en_proceso') return 'info'
  return 'secondary'
}
const fmtNum = (v) => (v == null ? '' : Number(v).toLocaleString('es', { maximumFractionDigits: 2 }))
</script>

<template>
  <AppLayout :title="title">
    <div class="card">
      <Toolbar class="mb-4">
        <template #start>
          <Button label="Nueva" icon="pi pi-plus" severity="success" @click="openCreate" />
        </template>
        <template #end>
          <InputText v-model="search" placeholder="Buscar por N° o cliente..." />
        </template>
      </Toolbar>

      <DataTable
        :value="solicitudes.data"
        striped-rows
        paginator
        :rows="20"
        :total-records="solicitudes.total"
        :lazy="true"
        :first="(solicitudes.current_page - 1) * solicitudes.per_page"
        @page="onPage"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
        currentPageReportTemplate="Total: {totalRecords} registros"
      >
        <Column field="numero" header="N°" sortable />
        <Column field="fecha_solicitud" header="Fecha">
          <template #body="{ data }">{{ fmtDmy(data.fecha_solicitud) }}</template>
        </Column>
        <Column field="fecha_planificada" header="Planificada">
          <template #body="{ data }">{{ fmtDmy(data.fecha_planificada) }}</template>
        </Column>
        <Column header="Cliente">
          <template #body="{ data }">{{ data.cliente?.nombre }}</template>
        </Column>
        <Column header="Origen">
          <template #body="{ data }">{{ data.lugar_origen?.nombre }}</template>
        </Column>
        <Column header="Destino">
          <template #body="{ data }">{{ data.lugar_destino?.nombre }}</template>
        </Column>
        <Column header="Producto / Carga">
          <template #body="{ data }">
            <div>{{ data.producto?.nombre }}</div>
            <template v-if="data.producto2?.nombre || data.peso2">
              <div class="text-xs text-surface-400 mt-0.5">
                {{ data.producto2?.nombre || 'Carga 2' }} — {{ fmtNum(data.peso2) }} tns
              </div>
            </template>
          </template>
        </Column>
        <Column header="T. Carga">
          <template #body="{ data }">
            <div>{{ data.tipo_carga?.nombre }}</div>
            <div v-if="data.tipo_carga2?.nombre" class="text-xs text-surface-400 mt-0.5">{{ data.tipo_carga2.nombre }}</div>
          </template>
        </Column>
        <Column header="Toneladas">
          <template #body="{ data }">
            <div>Total: {{ fmtNum(data.toneladas_total ?? data.peso1) }}</div>
            <template v-if="data.estado_cumplimiento && data.estado_cumplimiento !== 'pendiente'">
              <div class="text-xs text-blue-500">Ejecutado: {{ fmtNum(data.toneladas_ejecutadas) }}</div>
              <div class="text-xs text-surface-500">Pendiente: {{ fmtNum(data.toneladas_pendientes) }}</div>
            </template>
          </template>
        </Column>
        <Column header="Kms">
          <template #body="{ data }">{{ data.distancia }}</template>
        </Column>
        <Column header="Valor Total">
          <template #body="{ data }">{{ data.valor_total }}</template>
        </Column>
        <Column header="Estado">
          <template #body="{ data }">
            <Tag :value="cumplimientoLabel(data)" :severity="cumplimientoSeverity(data)" />
          </template>
        </Column>
        <Column header="Acciones" style="width: 160px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="pi pi-copy" rounded text severity="secondary" tooltip="Duplicar solicitud" @click="duplicar(data)" />
              <Button
                icon="pi pi-truck"
                rounded
                text
                severity="info"
                tooltip="Registrar carta de porte"
                :disabled="cumplimiento(data) === 'realizada'"
                @click="abrirCarta(data)"
              />
              <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
              <Button icon="pi pi-trash" rounded text severity="danger" @click="eliminar(data)" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar Solicitud' : 'Nueva Solicitud'" modal style="width: 760px">
      <form @submit.prevent="submit" class="space-y-4 overflow-y-auto max-h-[75vh]">
        <div v-if="editing" class="grid grid-cols-2 gap-4">
          <div>
            <label class="block mb-1 font-medium">N&deg; Solicitud</label>
            <InputText v-model="form.numero" class="w-full" disabled />
          </div>
        </div>

        <div class="text-sm font-semibold text-surface-500 mb-1">Datos generales</div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block mb-1 font-medium text-sm">Fecha de solicitud</label>
            <DatePicker v-model="form.fecha_solicitud" dateFormat="yy-mm-dd" showIcon class="w-full" required />
          </div>
          <div>
            <label class="block mb-1 font-medium text-sm">Fecha planificada</label>
            <DatePicker v-model="form.fecha_planificada" dateFormat="yy-mm-dd" showIcon class="w-full" />
          </div>
          <div class="col-span-2">
            <label class="block mb-1 font-medium text-sm">Cliente</label>
            <Select v-model="form.id_cliente" :options="clientes" optionLabel="nombre" optionValue="id" filter placeholder="Cliente..." class="w-full" :showClear="true" required />
          </div>
          <div>
            <label class="block mb-1 font-medium text-sm">Lugar de origen</label>
            <Select v-model="form.id_lugar_origen" :options="lugares" optionLabel="nombre" optionValue="id" filter placeholder="Origen..." class="w-full" :showClear="true" />
          </div>
          <div>
            <label class="block mb-1 font-medium text-sm">Lugar de destino</label>
            <Select v-model="form.id_lugar_destino" :options="lugares" optionLabel="nombre" optionValue="id" filter placeholder="Destino..." class="w-full" :showClear="true" />
          </div>
          <div>
            <label class="block mb-1 font-medium text-sm">Distancia (Kms)</label>
            <InputText v-model="form.distancia" type="number" min="0" class="w-full" />
          </div>
        </div>

        <div class="text-sm font-semibold text-surface-500 -mb-1">Carga 1</div>
        <div class="grid grid-cols-3 gap-4">
          <div class="col-span-2">
            <label class="block mb-1 font-medium text-sm">Producto</label>
            <Select v-model="form.id_producto" :options="productos" optionLabel="nombre" optionValue="id" filter placeholder="Producto..." class="w-full" :showClear="true" />
          </div>
          <div>
            <label class="block mb-1 font-medium text-sm">Tons</label>
            <InputText v-model="form.peso1" type="number" step="0.01" min="0" class="w-full" />
          </div>
          <div class="col-span-3">
            <label class="block mb-1 font-medium text-sm">Tipo de carga</label>
            <Select v-model="form.id_tipo_carga" :options="tiposCargas" optionLabel="nombre" optionValue="id" filter placeholder="Tipo de carga..." class="w-full" :showClear="true" />
          </div>
        </div>

        <div class="text-sm font-semibold text-surface-500 -mb-1">Carga 2</div>
        <div class="grid grid-cols-3 gap-4">
          <div class="col-span-2">
            <label class="block mb-1 font-medium text-sm">Producto</label>
            <Select v-model="form.id_producto2" :options="productos" optionLabel="nombre" optionValue="id" filter placeholder="Producto..." class="w-full" :showClear="true" />
          </div>
          <div>
            <label class="block mb-1 font-medium text-sm">Tons</label>
            <InputText v-model="form.peso2" type="number" step="0.01" min="0" class="w-full" />
          </div>
          <div class="col-span-3">
            <label class="block mb-1 font-medium text-sm">Tipo de carga</label>
            <Select v-model="form.id_tipo_carga2" :options="tiposCargas" optionLabel="nombre" optionValue="id" filter placeholder="Tipo de carga..." class="w-full" :showClear="true" />
          </div>
        </div>

        <div>
          <label class="block mb-1 font-medium text-sm">Notas</label>
          <Textarea v-model="form.notas" rows="2" class="w-full" />
        </div>

        <div class="flex gap-2 justify-end">
          <Button label="Cancelar" severity="secondary" @click="showForm = false" />
          <Button label="Guardar" type="submit" icon="pi pi-save" />
        </div>
      </form>
    </Dialog>

    <Dialog v-model:visible="showCarta" :header="`Ejecutar Carta de Porte — ${cartaSolicitud?.numero || ''}`" modal style="width: 880px">
      <div v-if="cartaSolicitud">
        <form @submit.prevent="registrarCarta" class="space-y-4 overflow-y-auto max-h-[75vh]">
          <div class="text-sm bg-surface-50 p-2 rounded flex gap-6">
            <div><strong>N°:</strong> {{ cartaSolicitud.numero }}</div>
            <div><strong>Cliente:</strong> {{ cartaSolicitud.cliente?.nombre }}</div>
            <div><strong>Pendientes:</strong> {{ fmtNum(cartaSolicitud.toneladas_pendientes ?? cartaSolicitud.peso1) }} de {{ fmtNum(cartaSolicitud.toneladas_total ?? cartaSolicitud.peso1) }} tns</div>
          </div>

          <fieldset class="border rounded p-3">
            <legend class="font-semibold px-2">DATOS DE LA EMISION</legend>
            <div class="grid grid-cols-2 lg:grid-cols-3 gap-3">
              <div>
                <label class="block mb-1 font-medium">Fecha</label>
                <DatePicker v-model="carta.fecha_emision" dateFormat="yy-mm-dd" showIcon class="w-full" @date-select="validarFolioCarta" />
              </div>
              <div>
                <label class="block mb-1 font-medium">Folio</label>
                <InputText v-model="carta.numero" class="w-full" required @blur="validarFolioCarta" />
              </div>
              <div>
                <label class="block mb-1 font-medium">Hoja de Ruta</label>
                <Select v-model="carta.id_hoja_ruta" :options="hojasCarta()" optionLabel="label" optionValue="id" filter class="w-full" :showClear="true" @change="aplicarHojaCarta($event.value)" />
              </div>
              <div>
                <label class="block mb-1 font-medium">Chofer</label>
                <Select v-model="carta.id_chofer" :options="choferesCarta()" optionLabel="label" optionValue="id" filter class="w-full" :showClear="true" />
              </div>
              <div>
                <label class="block mb-1 font-medium">2do Chofer</label>
                <Select v-model="carta.id_chofer2" :options="choferesCarta()" optionLabel="label" optionValue="id" filter class="w-full" :showClear="true" />
              </div>
              <div>
                <label class="block mb-1 font-medium">Conduce</label>
                <InputText v-model="carta.conduce" class="w-full" />
              </div>
              <div>
                <label class="block mb-1 font-medium">Tractivo</label>
                <Select v-model="carta.id_tractivo" :options="tractivosCarta()" optionLabel="codigo" optionValue="id" filter class="w-full" :showClear="true" />
              </div>
              <div>
                <label class="block mb-1 font-medium">Arrastre</label>
                <Select v-model="carta.id_arrastre" :options="arrastresCarta()" optionLabel="codigo" optionValue="id" filter class="w-full" :showClear="true" />
              </div>
              <div>
                <label class="block mb-1 font-medium">Turno</label>
                <Select v-model="carta.id_turno" :options="turnosCarta()" optionLabel="nombre" optionValue="id" filter class="w-full" :showClear="true" />
              </div>
              <div>
                <label class="block mb-1 font-medium">Buque</label>
                <Select v-model="carta.id_buque" :options="buquesCarta()" optionLabel="nombre" optionValue="id" filter class="w-full" :showClear="true" />
              </div>
            </div>
          </fieldset>

          <fieldset class="border rounded p-3">
            <legend class="font-semibold px-2">DATOS DE LA TRANSPORTACION</legend>
            <div class="grid grid-cols-2 lg:grid-cols-3 gap-3">
              <div>
                <label class="block mb-1 font-medium">Origen</label>
                <Select v-model="carta.id_lugar_origen" :options="lugares" optionLabel="nombre" optionValue="id" filter class="w-full" :showClear="true" />
              </div>
              <div>
                <label class="block mb-1 font-medium">Destino</label>
                <Select v-model="carta.id_lugar_destino" :options="lugares" optionLabel="nombre" optionValue="id" filter class="w-full" :showClear="true" />
              </div>
              <div>
                <label class="block mb-1 font-medium">KMS</label>
                <InputText v-model="carta.distancia" type="number" min="0" class="w-full" />
              </div>
              <div>
                <label class="block mb-1 font-medium">Tipo Carga 1</label>
                <Select v-model="carta.id_tipo_carga" :options="tiposCargas" optionLabel="nombre" optionValue="id" filter class="w-full" :showClear="true" />
              </div>
              <div>
                <label class="block mb-1 font-medium">Tipo Carga 2</label>
                <Select v-model="carta.id_tipo_carga2" :options="tiposCargas" optionLabel="nombre" optionValue="id" filter class="w-full" :showClear="true" />
              </div>
              <div>
                <label class="block mb-1 font-medium">Producto 1</label>
                <Select v-model="carta.id_producto" :options="productos" optionLabel="nombre" optionValue="id" filter class="w-full" :showClear="true" />
              </div>
              <div>
                <label class="block mb-1 font-medium">Producto 2</label>
                <Select v-model="carta.id_producto2" :options="productos" optionLabel="nombre" optionValue="id" filter class="w-full" :showClear="true" />
              </div>
              <div>
                <label class="block mb-1 font-medium">Peso 1 (tns)</label>
                <InputText v-model="carta.peso1" type="number" step="0.01" min="0" class="w-full" />
              </div>
              <div>
                <label class="block mb-1 font-medium">Peso 2 (tns)</label>
                <InputText v-model="carta.peso2" type="number" step="0.01" min="0" class="w-full" />
              </div>
              <div class="flex items-end gap-3">
                <label class="flex items-center gap-2"><Checkbox v-model="carta.imprimir" :binary="true" /> Imprimir notas en la CP</label>
              </div>
            </div>
            <div class="mt-3">
              <label class="block mb-1 font-medium">Notas</label>
              <Textarea v-model="carta.notas" rows="2" class="w-full" />
            </div>
          </fieldset>

          <div class="flex gap-2 justify-end">
            <Button label="Cancelar" severity="secondary" type="button" @click="showCarta = false" />
            <Button label="Registrar" type="submit" icon="pi pi-check" />
          </div>
        </form>
      </div>
    </Dialog>
  </AppLayout>
</template>