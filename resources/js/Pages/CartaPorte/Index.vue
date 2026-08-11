<script setup>
import { ref, computed, watch } from 'vue'
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
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'
import SplitButton from 'primevue/splitbutton'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ cartas: Object, catalogos: Object, filters: Object })
const toast = useToast()
const title = 'Carta de Porte'

const search = ref(props.filters?.search || '')
const estado = ref(props.filters?.estado || 'todas')
const recepcion = ref(props.filters?.recepcionadas || 'todas')
const canceladas = ref(props.filters?.canceladas === 'si' ? 'si' : 'no')
const hoja = ref(props.filters?.hoja || null)

const tractivosCat = computed(() => props.catalogos?.tractivos || [])
const arrastresCat = computed(() => props.catalogos?.arrastres || [])
const choferOptions = computed(() => (props.catalogos?.choferes || []).map(c => ({ id: c.id, label: `${c.nombre} ${c.apellidos || ''}`.trim() })))
const lugaresCat = computed(() => props.catalogos?.lugares || [])
const clientesCat = computed(() => props.catalogos?.clientes || [])
const productosCat = computed(() => props.catalogos?.productos || [])
const tiposCargasCat = computed(() => props.catalogos?.tiposCargas || [])
const turnosCat = computed(() => props.catalogos?.turnos || [])
const buquesCat = computed(() => props.catalogos?.buques || [])
const monedasCat = computed(() => props.catalogos?.monedas || [])
const hojasCat = computed(() => (props.catalogos?.hojasRuta || []).map(h => ({ ...h, label: `${h.numero}${h.tractivo_codigo ? ` (${h.tractivo_codigo})` : ''}${h.chofer_nombre ? ` • ${h.chofer_nombre}` : ''}` })))

const showEmision = ref(false)
const editandoId = ref(null)
const form = ref({})
const selectedRow = ref(null)

function nowDate() { return new Date().toISOString().slice(0, 10) }

const formInicial = () => ({
  numero: '',
  fecha_emision: nowDate(),
  fecha_parte: nowDate(),
  id_hoja_ruta: null,
  id_solicitud: null,
  id_chofer: null,
  id_chofer2: null,
  id_tractivo: null,
  id_arrastre: null,
  id_cliente: null,
  id_turno: null,
  id_buque: null,
  id_lugar_origen: null,
  id_lugar_destino: null,
  id_tipo_carga: null,
  id_tipo_carga2: null,
  id_producto: null,
  id_producto2: null,
  peso1: null,
  peso2: null,
  toneladas: null,
  distancia: null,
  tarifa_km: null,
  total_flete: null,
  conduce: '',
  notas: '',
  id_moneda: null,
  imprimir: false,
  frecepcion: null,
})

function info({ row }) { return row }
const selectedCarta = computed(() => selectedRow.value || null)

function openEmision() {
  editandoId.value = null
  form.value = { ...formInicial() }
  showEmision.value = true
}

function openEdicion(carta) {
  editandoId.value = carta.id
  form.value = {
    numero: carta.numero,
    fecha_emision: soloFecha(carta.fecha_emision),
    fecha_parte: soloFecha(carta.fecha_parte),
    id_hoja_ruta: carta.id_hoja_ruta,
    id_solicitud: carta.id_solicitud,
    id_chofer: carta.id_chofer,
    id_chofer2: carta.id_chofer2,
    id_tractivo: carta.id_tractivo,
    id_arrastre: carta.id_arrastre,
    id_cliente: carta.id_cliente,
    id_turno: carta.id_turno,
    id_buque: carta.id_buque,
    id_lugar_origen: carta.id_lugar_origen,
    id_lugar_destino: carta.id_lugar_destino,
    id_tipo_carga: carta.id_tipo_carga,
    id_tipo_carga2: carta.id_tipo_carga2,
    id_producto: carta.id_producto,
    id_producto2: carta.id_producto2,
    peso1: Number(carta.peso1) || null,
    peso2: Number(carta.peso2) || null,
    toneladas: Number(carta.toneladas) || null,
    distancia: Number(carta.distancia) || null,
    tarifa_km: Number(carta.tarifa_km) || null,
    total_flete: Number(carta.total_flete) || null,
    conduce: carta.conduce || '',
    notas: carta.notas || '',
    id_moneda: carta.id_moneda,
    imprimir: Boolean(carta.imprimir),
  }
  showEmision.value = true
}

function aplicarHojaRuta(event) {
  const hr = hojasCat.value.find(h => h.id === event)
  if (!hr) return
  form.value.id_chofer = form.value.id_chofer || hr.id_chofer || null
  form.value.id_chofer2 = form.value.id_chofer2 || hr.id_chofer2 || null
  form.value.id_tractivo = form.value.id_tractivo || hr.id_tractivo || null
  form.value.id_arrastre = form.value.id_arrastre || hr.id_arrastre || null
  if (!form.value.id_cliente && props.catalogos?.hojasRuta?.length) {
    const src = props.catalogos.hojasRuta.find(h => h.id === event)
    form.value.id_cliente = form.value.id_cliente || src?.id_cliente || null
  }
}

async function buscarDistancia() {
  if (!form.value.id_lugar_origen || !form.value.id_lugar_destino) return
  try {
    const res = await fetch(route('carta-porte.obtener-distancia'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '' },
      body: JSON.stringify({ id_lugar_origen: form.value.id_lugar_origen, id_lugar_destino: form.value.id_lugar_destino }),
    })
    const json = await res.json()
    if (json.distancia) form.value.distancia = Number(json.distancia)
  } catch (e) { /* silencioso */ }
}

watch([() => form.value.id_lugar_origen, () => form.value.id_lugar_destino], buscarDistancia)
watch([() => form.value.distancia, () => form.value.tarifa_km], ([d, t]) => {
  if (d !== null && d !== undefined && t) {
    form.value.total_flete = Math.round(Number(d) * Number(t) * 100) / 100
  }
})

async function validarFolio() {  if (!form.value.numero || !form.value.fecha_emision) return
  try {
    const res = await fetch(route('carta-porte.validar-folio'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '' },
      body: JSON.stringify({ numero: form.value.numero, fecha_emision: form.value.fecha_emision, excluir: editandoId.value }),
    })
    const json = await res.json()
    if (!json.disponible) {
      toast.add({ severity: 'warn', summary: 'Folio ocupado', detail: `El folio ${form.value.numero} ya está registrado en este mes.`, life: 5000 })
    }
  } catch (e) { /* silencioso */ }
}

function onPage(event) {
  router.get(route('carta-porte.index'), { page: event.page + 1, search: search.value, estado: estado.value, recepcionadas: recepcion.value, canceladas: canceladas.value, hoja: hoja.value }, { preserveState: true, replace: true })
}

watch([search, estado, recepcion, canceladas, hoja], () => {
  router.get(route('carta-porte.index'), { search: search.value, estado: estado.value, recepcionadas: recepcion.value, canceladas: canceladas.value, hoja: hoja.value }, { preserveState: true, replace: true })
})

function submitEmision() {
  if (editandoId.value) {
    router.put(route('carta-porte.update', { carta: editandoId.value }), form.value, { preserveScroll: true })
  } else {
    router.post(route('carta-porte.store'), form.value, { preserveScroll: true })
  }
  showEmision.value = false
}

function recepcionar(carta) {
  router.post(route('carta-porte.recepcionar', { carta: carta.id }), {}, { preserveScroll: true })
}

function cancelar(carta) {
  if (window.confirm(`¿Desea cancelar la carta de porte ${carta.numero}?`)) {
    router.delete(route('carta-porte.destroy', { carta: carta.id }), { data: { operacion: 'cancelar' }, preserveScroll: true })
  }
}

function eliminar(carta) {
  if (window.confirm(`¿Desea eliminar la carta de porte ${carta.numero}?`)) {
    router.delete(route('carta-porte.destroy', { carta: carta.id }), { data: { operacion: 'eliminar' }, preserveScroll: true })
  }
}

function soloFecha(v) { return v ? String(v).slice(0, 10) : '' }

function choferNombre(c) { return c ? `${c.nombre || ''} ${c.apellidos || ''}`.trim() : '—' }
function fmtNum(v) {
  if (v === null || v === undefined || v === '') return '—'
  return Number(v).toLocaleString('es-CU', { maximumFractionDigits: 2 })
}
function fmtFcierre(carta) {
  if (!carta.hoja_ruta?.fecha_cierre) return 'S/CERRAR'
  return soloFecha(carta.hoja_ruta.fecha_cierre)
}
function unidadDe(carta) {
  return carta.hoja_ruta?.entidad?.abreviatura || carta.hoja_ruta?.entidad?.nombre || '—'
}
function fechaRecep(carta) { return carta.fecha_recepcion ? soloFecha(carta.fecha_recepcion) : null }
</script>

<template>
  <AppLayout :title="title">
    <div class="card">
      <Toolbar class="mb-4">
        <template #start>
          <div class="flex gap-2 items-center">
            <Button label="Adicionar" icon="pi pi-plus" severity="success" @click="openEmision" />
            <Button label="Recepcionar" icon="pi pi-star" severity="info" :disabled="!selectedCarta || !selectedCarta.fecha_recepcion" :title="!selectedCarta?.fecha_recepcion ? 'Seleccione una carta pendiente de recepción' : ''" @click="recepcionar(selectedCarta)" />
            <SplitButton label="Cancelar / Eliminar" icon="pi pi-ban" severity="danger" :disabled="!selectedCarta" @click="cancelar(selectedCarta)">
              <template #dropdown>
                <div class="p-2 flex flex-col gap-1">
                  <Button label="Cancelar" icon="pi pi-ban" severity="warning" text @click="cancelar(selectedCarta)" />
                  <Button label="Eliminar" icon="pi pi-trash" severity="danger" text @click="eliminar(selectedCarta)" />
                </div>
              </template>
            </SplitButton>
          </div>
        </template>
        <template #end>
          <div class="flex gap-2 items-center">
            <Select v-model="hoja" :options="hojasCat" optionLabel="label" optionValue="id" filter placeholder="Hoja de Ruta" class="w-52" :showClear="true" />
            <Select v-model="estado" :options="[{ label: 'Todas', value: 'todas' }, { label: 'Emitida', value: 'emitida' }, { label: 'Recepcionada', value: 'recepcionada' }, { label: 'Facturada', value: 'facturada' }, { label: 'Cancelada', value: 'cancelada' }]" optionLabel="label" optionValue="value" class="w-40" />
            <Select v-model="recepcion" :options="[{ label: 'Recepción: Todas', value: 'todas' }, { label: 'Recepción: Sí', value: 'si' }, { label: 'Recepción: No', value: 'no' }]" optionLabel="label" optionValue="value" class="w-44" />
            <InputText v-model="search" placeholder="Buscar folio, cliente, chofer, tractivo..." class="w-56" />
          </div>
        </template>
      </Toolbar>

      <DataTable :value="cartas.data" stripedRows paginator :rows="20" :total-records="cartas.total" :lazy="true" :first="(cartas.current_page - 1) * cartas.per_page" @page="onPage" paginatorTemplate="FirstPageLink PrevPageLink NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros" size="small" :scrollable="true" scrollHeight="flex" selection-mode="single" v-model:selection="selectedRow">
        <Column selection-mode="single" headerStyle="width: 3rem" />
        <Column header="CARTA PORTE" :style="{ minWidth: '150px', whiteSpace: 'nowrap' }">
          <template #body="{ data }">
            <div>
              <div class="flex justify-end gap-1">
                <i v-if="fechaRecep(data)" class="pi pi-star-fill" title="Recepcionada" :style="{ color: '#f5b301' }"></i>
                <i v-else class="pi pi-star" title="Sin recepción" style="color:#cbd5e1"></i>
                <i v-if="data.aforos_exists" class="pi pi-star-fill" title="Aforada" :style="{ color: '#f5b301' }"></i>
                <i v-else class="pi pi-star" title="Sin aforo" style="color:#cbd5e1"></i>
                <i v-if="data.facturas_exists" class="pi pi-star-fill" title="Facturada" :style="{ color: '#f5b301' }"></i>
                <i v-else class="pi pi-star" title="Sin factura" style="color:#cbd5e1"></i>
              </div>
              <div v-if="data.cancelada" class="text-lg font-bold text-red-600">{{ data.numero }}</div>
              <template v-else>
                <div class="text-sm font-bold" style="color:#2563eb">{{ data.numero }}</div>
                <div class="text-sm" style="color:#2563eb">HR-{{ data.hoja_ruta?.numero || '—' }}</div>
                <div class="text-sm font-semibold" style="color:#dc2626">{{ fmtFcierre(data) }}</div>
                <div class="text-xs text-surface-600">{{ soloFecha(data.fecha_emision) }}</div>
              </template>
            </div>
          </template>
        </Column>
        <Column header="ENCABEZADO" :style="{ minWidth: '230px' }">
          <template #body="{ data }">
            <div v-if="data.cancelada" class="font-semibold underline">CARTA DE PORTE CANCELADA</div>
            <template v-else>
              <div class="text-sm font-bold" style="color:#2563eb">{{ data.cliente?.nombre || '—' }}</div>
              <div class="text-sm"><b>ORIGEN:&nbsp;</b>{{ data.lugar_origen?.nombre || '—' }}</div>
              <div class="text-sm"><b>DESTINO:&nbsp;</b>{{ data.lugar_destino?.nombre || '—' }}</div>
            </template>
          </template>
        </Column>
        <Column header="DETALLES" :style="{ minWidth: '250px' }">
          <template #body="{ data }">
            <div v-if="data.cancelada">
              <div class="font-semibold">NOTAS CANCELACION</div>
              <div class="text-xs whitespace-normal">{{ data.notas || '—' }}</div>
            </div>
            <template v-else>
              <div class="text-sm font-bold" style="color:#2563eb">{{ choferNombre(data.chofer) }}</div>
              <div v-if="data.chofer2" class="text-sm font-bold" style="color:#2563eb">{{ choferNombre(data.chofer2) }}</div>
              <div class="text-sm"><b>TRACTIVO:&nbsp;</b>{{ data.tractivo?.codigo || '—' }}&nbsp;<b>ARRASTRE:&nbsp;</b>{{ data.arrastre?.codigo || '—' }}</div>
            </template>
          </template>
        </Column>
        <Column header="UNIDAD" :style="{ minWidth: '120px' }">
          <template #body="{ data }">{{ unidadDe(data) }}</template>
        </Column>
      </DataTable>
    </div>

    <!-- Emisión -->
    <Dialog v-model:visible="showEmision" :header="editandoId ? `Editar Carta de Porte ${form.numero}` : 'Nueva Carta de Porte'" modal :style="{ width: '880px' }">
      <form @submit.prevent="submitEmision" class="space-y-4">
        <fieldset class="border rounded p-3">
          <legend class="font-semibold px-2">DATOS DE LA EMISION</legend>
          <div class="grid grid-cols-2 lg:grid-cols-3 gap-3">
            <div>
              <label class="block mb-1 font-medium">Fecha</label>
              <input v-model="form.fecha_emision" type="date" class="w-full border rounded p-2" required @blur="validarFolio" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Folio</label>
              <InputText v-model="form.numero" class="w-full" required @blur="validarFolio" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Hoja de Ruta</label>
              <Select v-model="form.id_hoja_ruta" :options="hojasCat" optionLabel="label" optionValue="id" filter class="w-full" :showClear="true" @change="aplicarHojaRuta($event.value)" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Conduce</label>
              <InputText v-model="form.conduce" class="w-full" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Chofer</label>
              <Select v-model="form.id_chofer" :options="choferOptions" optionLabel="label" optionValue="id" filter class="w-full" :showClear="true" />
            </div>
            <div>
              <label class="block mb-1 font-medium">2do Chofer</label>
              <Select v-model="form.id_chofer2" :options="choferOptions" optionLabel="label" optionValue="id" filter class="w-full" :showClear="true" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Tractivo</label>
              <Select v-model="form.id_tractivo" :options="tractivosCat" optionLabel="codigo" optionValue="id" filter class="w-full" :showClear="true" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Arrastre</label>
              <Select v-model="form.id_arrastre" :options="arrastresCat" optionLabel="codigo" optionValue="id" filter class="w-full" :showClear="true" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Cliente</label>
              <Select v-model="form.id_cliente" :options="clientesCat" optionLabel="nombre" optionValue="id" filter class="w-full" :showClear="true" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Turno</label>
              <Select v-model="form.id_turno" :options="turnosCat" optionLabel="nombre" optionValue="id" filter class="w-full" :showClear="true" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Buque</label>
              <Select v-model="form.id_buque" :options="buquesCat" optionLabel="nombre" optionValue="id" filter class="w-full" :showClear="true" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Moneda</label>
              <Select v-model="form.id_moneda" :options="monedasCat" optionLabel="nombre" optionValue="id" filter class="w-full" :showClear="true" />
            </div>
          </div>
        </fieldset>

        <fieldset class="border rounded p-3">
          <legend class="font-semibold px-2">DATOS DE LA TRANSPORTACION</legend>
          <div class="grid grid-cols-2 lg:grid-cols-3 gap-3">
            <div>
              <label class="block mb-1 font-medium">Origen</label>
              <Select v-model="form.id_lugar_origen" :options="lugaresCat" optionLabel="nombre" optionValue="id" filter class="w-full" :showClear="true" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Destino</label>
              <Select v-model="form.id_lugar_destino" :options="lugaresCat" optionLabel="nombre" optionValue="id" filter class="w-full" :showClear="true" />
            </div>
            <div>
              <label class="block mb-1 font-medium">KMS</label>
              <div class="flex gap-2 items-end">
                <InputNumber v-model="form.distancia" :min="0" :max-fraction-digits="2" class="w-full" />
              </div>
            </div>
            <div>
              <label class="block mb-1 font-medium">Tipo Carga 1</label>
              <Select v-model="form.id_tipo_carga" :options="tiposCargasCat" optionLabel="nombre" optionValue="id" filter class="w-full" :showClear="true" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Tipo Carga 2</label>
              <Select v-model="form.id_tipo_carga2" :options="tiposCargasCat" optionLabel="nombre" optionValue="id" filter class="w-full" :showClear="true" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Producto 1</label>
              <Select v-model="form.id_producto" :options="productosCat" optionLabel="nombre" optionValue="id" filter class="w-full" :showClear="true" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Producto 2</label>
              <Select v-model="form.id_producto2" :options="productosCat" optionLabel="nombre" optionValue="id" filter class="w-full" :showClear="true" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Peso 1</label>
              <InputNumber v-model="form.peso1" :min="0" :max-fraction-digits="2" class="w-full" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Peso 2</label>
              <InputNumber v-model="form.peso2" :min="0" :max-fraction-digits="2" class="w-full" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Tarifa KM</label>
              <InputNumber v-model="form.tarifa_km" :min="0" :max-fraction-digits="2" class="w-full" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Total Flete</label>
              <InputNumber v-model="form.total_flete" :min="0" :max-fraction-digits="2" class="w-full" disabled />
            </div>
            <div class="flex items-end gap-4">
              <label class="flex items-center gap-2"><Checkbox v-model="form.imprimir" :binary="true" /> Imprimir notas en la CP</label>
            </div>
          </div>
          <div class="mt-3">
            <label class="block mb-1 font-medium">Notas</label>
            <Textarea v-model="form.notas" rows="2" class="w-full" />
          </div>
        </fieldset>

        <div class="flex justify-end gap-2">
          <Button label="Cancelar" severity="secondary" type="button" @click="showEmision = false" />
          <Button label="Guardar" icon="pi pi-check" type="submit" />
        </div>
      </form>
    </Dialog>
  </AppLayout>
</template>