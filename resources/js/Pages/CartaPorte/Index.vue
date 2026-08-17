<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import Checkbox from 'primevue/checkbox'
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'
import Paginator from 'primevue/paginator'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { formatDate } from '@/Utils/date'

const props = defineProps({ cartas: Object, catalogos: Object, filters: Object, filtros: Object, cartaEditar: Object, fechaOperaciones: String })
const toast = useToast()
const confirmDialog = useConfirm()
const title = 'Carta de Porte'

// Rango del mes de la fecha de operaciones activa para los selectores de fecha.
const fechaOp = () => (props.fechaOperaciones ? new Date(props.fechaOperaciones.slice(0, 10)) : new Date())
const minFecha = new Date(fechaOp().getFullYear(), fechaOp().getMonth(), 1)
const maxFecha = new Date(fechaOp().getFullYear(), fechaOp().getMonth() + 1, 0)

const search = ref(props.filters?.search || '')
const equipo = ref(props.filters?.equipo || null)
const chofer = ref(props.filters?.chofer || null)
const cliente = ref(props.filters?.cliente || null)

const tractivosCat = computed(() => props.catalogos?.tractivos || [])
const arrastresCat = computed(() => props.catalogos?.arrastres || [])
const choferOptions = computed(() => (props.catalogos?.choferes || []).map(c => ({ id: c.id, label: `${c.nombre} ${c.apellidos || ''}`.trim() })))
const lugaresCat = computed(() => props.catalogos?.lugares || [])
const clientesCat = computed(() => props.catalogos?.clientes || [])
const productosCat = computed(() => props.catalogos?.productos || [])
const tiposCargasCat = computed(() => props.catalogos?.tiposCargas || [])
const hojasCat = computed(() => (props.catalogos?.hojasRuta || []).map(h => ({ ...h, label: `${h.numero}${h.tractivo_codigo ? ` (${h.tractivo_codigo})` : ''}${h.chofer_nombre ? ` • ${h.chofer_nombre}` : ''}` })))

// Opciones de filtros: solo lo que tiene cartas este mes, con combinaciones reales
const filtrosTractivos = computed(() => props.filtros?.tractivos || [])
const filtrosChoferes = computed(() => (props.filtros?.choferes || []).map(c => ({ id: c.id, label: `${c.nombre} ${c.apellidos || ''}`.trim() })))
const filtrosClientes = computed(() => props.filtros?.clientes || [])
const combinaciones = computed(() => props.filtros?.combinaciones || [])

// Filtros dependientes: cada selector restringe a las combinaciones reales del mes.
function idsRelacionados(extraer, usarCliente = true, usarChofer = true, usarTractivo = true) {
  const set = new Set()
  for (const r of combinaciones.value) {
    if (usarCliente && cliente.value && r.cliente !== cliente.value) continue
    if (usarChofer && chofer.value && r.chofer !== chofer.value && r.chofer2 !== chofer.value) continue
    if (usarTractivo && equipo.value && r.tractivo !== equipo.value) continue
    const id = extraer(r)
    if (id != null) set.add(id)
  }
  return set
}

const opcionesClientes = computed(() => {
  if (!chofer.value && !equipo.value) return filtrosClientes.value
  const ids = idsRelacionados(r => r.cliente, false, true, true)
  return filtrosClientes.value.filter(c => ids.has(c.id))
})
const opcionesChoferes = computed(() => {
  if (!cliente.value && !equipo.value) return filtrosChoferes.value
  const ids = idsRelacionados(r => r.chofer, true, false, true)
  const ids2 = idsRelacionados(r => r.chofer2, true, false, true)
  const unidos = new Set([...ids, ...ids2])
  return filtrosChoferes.value.filter(c => unidos.has(c.id))
})
const opcionesTractivos = computed(() => {
  if (!cliente.value && !chofer.value) return filtrosTractivos.value
  const ids = idsRelacionados(r => r.tractivo, true, true, false)
  return filtrosTractivos.value.filter(t => ids.has(t.id))
})

watch(cliente, () => {
  if (chofer.value && !opcionesChoferes.value.some(c => c.id === chofer.value)) chofer.value = null
  if (equipo.value && !opcionesTractivos.value.some(t => t.id === equipo.value)) equipo.value = null
})
watch(chofer, () => {
  if (cliente.value && !opcionesClientes.value.some(c => c.id === cliente.value)) cliente.value = null
  if (equipo.value && !opcionesTractivos.value.some(t => t.id === equipo.value)) equipo.value = null
})
watch(equipo, () => {
  if (cliente.value && !opcionesClientes.value.some(c => c.id === cliente.value)) cliente.value = null
  if (chofer.value && !opcionesChoferes.value.some(c => c.id === chofer.value)) chofer.value = null
})

const showEmision = ref(false)
const editandoId = ref(null)
const form = ref({})

const showCancelar = ref(false)
const cancelarCarta = ref(null)
const cancelarNotas = ref('')

function nowDate() { return new Date().toISOString().slice(0, 10) }

const formInicial = () => ({
  numero: '',
  fecha_emision: nowDate(),
  fecha_parte: nowDate(),
  id_hoja_ruta: null,
  id_solicitud: null,
  // Datos generales (se sincronizan con la solicitud)
  id_cliente: null,
  id_lugar_origen: null,
  id_lugar_destino: null,
  id_producto: null,
  id_producto2: null,
  id_tipo_carga: null,
  id_tipo_carga2: null,
  id_moneda: 1,
  peso1: null,
  peso2: null,
  toneladas: null,
  conduce: '',
  notas: '',
  imprimir: false,
  frecepcion: null,
})

function openEmision() {
  editandoId.value = null
  form.value = { ...formInicial() }
  showEmision.value = true
}

function openEdicion(carta) {
  editandoId.value = carta.id
  const sol = props.catalogos?.solicitudes?.find(s => s.id === carta.id_solicitud)
  form.value = {
    numero: carta.numero,
    fecha_emision: soloFecha(carta.fecha_emision),
    fecha_parte: soloFecha(carta.fecha_parte),
    id_hoja_ruta: carta.id_hoja_ruta,
    id_solicitud: carta.id_solicitud,
    id_cliente: sol?.id_cliente ?? carta.cliente?.id ?? null,
    id_lugar_origen: sol?.id_lugar_origen ?? null,
    id_lugar_destino: sol?.id_lugar_destino ?? null,
    id_producto: sol?.id_producto ?? null,
    id_producto2: sol?.id_producto2 ?? null,
    id_tipo_carga: sol?.id_tipo_carga ?? null,
    id_tipo_carga2: sol?.id_tipo_carga2 ?? null,
    id_moneda: sol?.id_moneda ?? carta.id_moneda ?? 1,
    peso1: Number(carta.peso1) || null,
    peso2: Number(carta.peso2) || null,
    toneladas: Number(carta.toneladas) || null,
    conduce: carta.conduce || '',
    notas: carta.notas || '',
    imprimir: Boolean(carta.imprimir),
  }
  showEmision.value = true
}

// Equipo/choferes derivados de la HR; cliente/tipos/productos de la solicitud (Fase 4d)
const hrSeleccionada = computed(() => hojasCat.value.find(h => h.id === form.value.id_hoja_ruta) || null)
const solicitudesCat = computed(() => (props.catalogos?.solicitudes || []).map(s => ({ id: s.id, label: `${s.numero}${s.cliente_nombre ? ` • ${s.cliente_nombre}` : ''}` })))
const solicitudSeleccionada = computed(() => (props.catalogos?.solicitudes || []).find(s => s.id === form.value.id_solicitud) || null)

// Al elegir una solicitud se rellenan los datos generales editables.
function aplicarSolicitud() {
  const sol = solicitudSeleccionada.value
  if (!sol) return
  form.value.id_cliente = sol.id_cliente ?? null
  form.value.id_lugar_origen = sol.id_lugar_origen ?? null
  form.value.id_lugar_destino = sol.id_lugar_destino ?? null
  form.value.id_producto = sol.id_producto ?? null
  form.value.id_producto2 = sol.id_producto2 ?? null
  form.value.id_tipo_carga = sol.id_tipo_carga ?? null
  form.value.id_tipo_carga2 = sol.id_tipo_carga2 ?? null
  form.value.id_moneda = sol.id_moneda ?? 1
}

function aplicarHojaRuta(event) {
  const hr = hojasCat.value.find(h => h.id === event)
  if (!hr) return
  // El equipo/choferes se derivan de la HR seleccionada (no se persisten en la carta)
  hrEquipo.value = hr
}

const hrEquipo = ref(null)

async function buscarDistancia() {
  const sol = solicitudSeleccionada.value
  const hr = hrSeleccionada.value
  // La distancia se calcula de la HR (kms_disponible) o se deja manual
  if (hr?.kms_disponible != null) form.value.distancia = Number(hr.kms_disponible)
}async function validarFolio() {  if (!form.value.numero || !form.value.fecha_emision) return
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
  router.get(route('carta-porte.index'), { page: event.page + 1, search: search.value, equipo: equipo.value, chofer: chofer.value, cliente: cliente.value }, { preserveState: true, replace: true })
}

watch([search, equipo, chofer, cliente], () => {
  router.get(route('carta-porte.index'), { search: search.value, equipo: equipo.value, chofer: chofer.value, cliente: cliente.value }, { preserveState: true, replace: true })
})

function submitEmision() {
  const ref = fechaOp()
  const enMes = (v) => {
    if (!v) return true
    const d = new Date(v.slice(0, 10))
    return d.getFullYear() === ref.getFullYear() && d.getMonth() === ref.getMonth()
  }
  if (!enMes(form.value.fecha_emision) || !enMes(form.value.fecha_parte)) {
    const mes = String(ref.getMonth() + 1).padStart(2, '0')
    toast.add({ severity: 'warn', summary: 'Fecha fuera del mes', detail: `Las fechas deben estar dentro del mes de operaciones (${ref.getFullYear()}-${mes}).`, life: 5000 })
    return
  }
  if (editandoId.value) {
    router.put(route('carta-porte.update', { carta: editandoId.value }), form.value, { preserveScroll: true })
  } else {
    router.post(route('carta-porte.store'), form.value, { preserveScroll: true })
  }
  showEmision.value = false
}

// Abrir edición de una carta que viene señalada desde otra página (ej. Hojas de Ruta).
// immediate: true para que se abra también cuando la página monta con la carta ya cargada.
watch(() => props.cartaEditar, (c) => {
  if (c) openEdicion(c)
}, { immediate: true })

function recepcionar(carta) {
  router.post(route('carta-porte.recepcionar', { carta: carta.id }), {}, { preserveScroll: true })
}

function openCancelar(carta) {
  cancelarCarta.value = carta
  cancelarNotas.value = ''
  showCancelar.value = true
}

function confirmCancelar() {
  if (!cancelarCarta.value) return
  const id = cancelarCarta.value.id
  showCancelar.value = false
  router.delete(route('carta-porte.destroy', { carta: id }), {
    data: { operacion: 'cancelar', notas: cancelarNotas.value },
    preserveScroll: true,
  })
}

function cancelar(carta) {
  openCancelar(carta)
}

function eliminar(carta) {
  confirmDialog.require({
    message: `¿Desea eliminar la carta de porte ${carta.numero}?`,
    header: 'Eliminar Carta de Porte',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Eliminar',
    rejectLabel: 'Volver',
    acceptClass: 'p-button-danger',
    accept: () => {
      router.delete(route('carta-porte.destroy', { carta: carta.id }), { data: { operacion: 'eliminar' }, preserveScroll: true })
    },
  })
}

function soloFecha(v) { return v ? String(v).slice(0, 10) : '' }

function choferNombre(c) { return c ? `${c.nombre || ''} ${c.apellidos || ''}`.trim() : '—' }
function iniciales(nombre) {
  return String(nombre || '')
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map(w => w[0])
    .join('')
    .toUpperCase() || '?'
}
function fmtNum(v) {
  if (v === null || v === undefined || v === '') return '—'
  return Number(v).toLocaleString('es-CU', { maximumFractionDigits: 2 })
}
function fmtFcierre(carta) {
  if (!carta.hoja_ruta?.fecha_cierre) return 'S/CERRAR'
  return formatDate(carta.hoja_ruta.fecha_cierre)
}
function fechaRecep(carta) { return carta.fecha_recepcion ? formatDate(carta.fecha_recepcion) : null }

// Aforo de una carta de porte:
// - Facturada: solo ver/imprimir (no modificar).
// - Aforada (sin facturar): ir a edición del aforo.
// - Sin aforar: ir al formulario de nuevo aforo con la carta preseleccionada.
function irAforar(carta) {
  const aforoId = carta.aforos?.[0]?.id
  if (aforoId) {
    router.visit(route('aforos.edit', aforoId))
  } else {
    router.visit(route('aforos.create', { carta: carta.id }))
  }
}

function imprimirCarta(carta) {
  window.open(route('carta-porte.imprimir', { carta: carta.id }), '_blank')
}

function verAforo(carta) {
  const aforoId = carta.aforos?.[0]?.id
  if (aforoId) router.visit(route('aforos.show', aforoId))
}

const cartaFacturada = (c) => Boolean(c.facturas_exists)
const cartaAforada = (c) => Boolean(c.aforos_exists)

</script>

<template>
  <AppLayout :title="title">
    <div class="space-y-4">
      <!-- Barra de acciones y filtros -->
      <div class="flex flex-col lg:flex-row lg:items-center gap-3 justify-between rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3 shadow-sm">
        <div class="flex flex-wrap items-center gap-2">
          <Button label="Adicionar" icon="pi pi-plus" severity="success" @click="openEmision" />
          <span class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-700/60 text-xs font-semibold text-gray-600 dark:text-gray-300">
            <i class="pi pi-folder text-gray-400" />
            {{ cartas.total }} cartas
          </span>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <Select v-model="cliente" :options="opcionesClientes" optionLabel="nombre" optionValue="id" filter placeholder="Cliente" class="w-48" :showClear="true" />
          <Select v-model="chofer" :options="opcionesChoferes" optionLabel="label" optionValue="id" filter placeholder="Chofer" class="w-44" :showClear="true" />
          <Select v-model="equipo" :options="opcionesTractivos" optionLabel="codigo" optionValue="id" filter placeholder="Tractivo" class="w-40" :showClear="true" />
          <span class="relative">
            <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm" />
            <InputText v-model="search" placeholder="Buscar folio, cliente, chofer..." class="w-56 !pl-9" />
          </span>
        </div>
      </div>

      <!-- Grid de tarjetas -->
      <div v-if="cartas.data.length" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article
          v-for="(data, i) in cartas.data"
          :key="data.id"
          class="cp-card relative flex flex-col overflow-hidden rounded-2xl border bg-white dark:bg-gray-800 shadow-sm transition-shadow hover:shadow-lg dark:border-gray-700"
          :class="data.cancelada ? 'border-red-300 dark:border-red-800/60' : 'border-gray-200'"
          :style="{ animationDelay: `${Math.min(i, 10) * 45}ms` }"
        >
          <!-- Sello de cancelada -->
          <div v-if="data.cancelada" class="pointer-events-none absolute inset-0 z-10 flex items-center justify-center">
            <span class="rotate-[-14deg] border-[3px] border-red-500/70 text-red-500/80 dark:border-red-400/70 dark:text-red-300/80 rounded-lg px-4 py-1 text-xl font-black uppercase tracking-[0.22em]">Cancelada</span>
          </div>

          <!-- Cabecera: folio protagonista -->
          <header class="relative px-4 pt-3 pb-2.5 border-b border-gray-100 dark:border-gray-700/70" :class="data.cancelada ? 'bg-red-50/60 dark:bg-red-950/20' : 'bg-gradient-to-br from-blue-50/80 to-white dark:from-blue-950/20 dark:to-gray-800'">
            <div class="flex items-start gap-4">
              <div class="min-w-0 flex-1">
                <span class="block text-[10px] font-bold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">Carta de porte</span>
                <div
                  class="cp-folio mt-1 text-[24px] font-black leading-none tracking-tight"
                  :class="data.cancelada ? 'text-red-500 dark:text-red-400 line-through' : 'text-blue-800 dark:text-blue-300'"
                >{{ data.numero }}</div>
                <div class="mt-1.5 text-[11px] text-gray-400 dark:text-gray-500">
                  <i class="pi pi-calendar mr-1 text-[10px]" />{{ formatDate(data.fecha_emision) }}
                </div>
              </div>
              <div class="shrink-0 text-right">
                <span class="block text-[10px] font-bold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">Hoja de ruta</span>
                <div
                  class="cp-folio mt-1 text-[24px] font-black leading-none tracking-tight"
                  :class="data.cancelada ? 'text-red-500 dark:text-red-400 line-through' : 'text-blue-700 dark:text-blue-300'"
                >{{ data.hoja_ruta?.numero || '—' }}</div>
                <div class="mt-1.5 text-[11px]" :class="data.hoja_ruta?.fecha_cierre ? 'text-gray-400 dark:text-gray-500' : 'text-red-400 dark:text-red-400/80'">
                  <i class="pi pi-calendar-times mr-1 text-[10px]" />{{ fmtFcierre(data) }}
                </div>
              </div>
              <div class="flex items-center gap-1 pt-4 shrink-0">
                <i v-if="fechaRecep(data)" class="pi pi-star-fill" title="Recepcionada" :style="{ color: '#f5b301' }"></i>
                <i v-else class="pi pi-star" title="Sin recepción" style="color:#cbd5e1"></i>
                <i v-if="data.aforos_exists" class="pi pi-star-fill" title="Aforada" :style="{ color: '#f5b301' }"></i>
                <i v-else class="pi pi-star" title="Sin aforo" style="color:#cbd5e1"></i>
                <i v-if="data.facturas_exists" class="pi pi-star-fill" title="Facturada" :style="{ color: '#f5b301' }"></i>
                <i v-else class="pi pi-star" title="Sin factura" style="color:#cbd5e1"></i>
              </div>
            </div>
          </header>

          <!-- Cuerpo -->
          <div class="flex flex-1 flex-col gap-3 px-4 py-3">
            <div class="min-w-0">
              <div v-if="data.cancelada" class="text-xs italic text-gray-500 dark:text-gray-400">{{ data.notas || 'Sin notas de cancelación' }}</div>
              <div v-if="data.cancelada" class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[11px] text-gray-500 dark:text-gray-400">
                <i class="pi pi-user text-[10px]" />
                <span>Cancelada por: <strong>{{ data.user_cancelacion?.name || '—' }}</strong></span>
                <span v-if="data.fecha_cancelacion">· {{ formatDate(data.fecha_cancelacion) }}</span>
              </div>
              <template v-else>
                <div class="truncate text-[16px] font-black tracking-tight text-gray-900 dark:text-white">{{ data.cliente?.nombre || '—' }}</div>
                <div class="mt-0.5 h-1 w-10 rounded-full bg-gradient-to-r from-blue-500 to-blue-300 dark:from-blue-400 dark:to-blue-600" />
                <div class="mt-1.5 flex items-center gap-1.5 text-sm">
                  <span class="inline-flex min-w-0 items-center gap-1.5 rounded-lg bg-gray-50 dark:bg-gray-700/50 px-2 py-1 text-gray-700 dark:text-gray-300">
                    <i class="pi pi-map-marker text-xs" style="color:#2563eb" />
                    <span class="truncate">{{ data.lugar_origen?.nombre || '—' }}</span>
                  </span>
                  <i class="pi pi-arrow-right text-xs text-gray-400 shrink-0" />
                  <span class="inline-flex min-w-0 items-center gap-1.5 rounded-lg bg-gray-50 dark:bg-gray-700/50 px-2 py-1 text-gray-700 dark:text-gray-300">
                    <i class="pi pi-map-marker text-xs" style="color:#dc2626" />
                    <span class="truncate">{{ data.lugar_destino?.nombre || '—' }}</span>
                  </span>
                </div>
              </template>
            </div>

            <!-- Personal y equipo -->
            <template v-if="!data.cancelada">
              <div class="flex items-center gap-2 min-w-0">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-100 text-[11px] font-bold text-blue-700 dark:bg-blue-500/20 dark:text-blue-300">
                  {{ iniciales(choferNombre(data.chofer)) }}
                </span>
                <div class="min-w-0">
                  <div class="truncate text-sm font-semibold text-gray-800 dark:text-gray-100">{{ choferNombre(data.chofer) }}</div>
                  <div v-if="data.chofer2" class="truncate text-[11px] text-gray-500 dark:text-gray-400">2do: {{ choferNombre(data.chofer2) }}</div>
                </div>
              </div>

              <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-2 rounded-xl border border-blue-200 dark:border-blue-700/50 bg-blue-50 dark:bg-blue-950/30 px-3 py-1.5">
                  <i class="pi pi-truck text-xl" style="color:#2563eb" />
                  <span class="text-lg font-black tracking-tight text-blue-800 dark:text-blue-300">{{ data.tractivo?.codigo || '—' }}</span>
                </span>
                <span v-if="data.arrastre" class="inline-flex items-center gap-2 rounded-xl border border-violet-200 dark:border-violet-700/50 bg-violet-50 dark:bg-violet-950/30 px-3 py-1.5">
                  <i class="pi pi-box text-xl" style="color:#7c3aed" />
                  <span class="text-lg font-black tracking-tight text-violet-800 dark:text-violet-300">{{ data.arrastre.codigo }}</span>
                </span>
              </div>
            </template>
          </div>

          <!-- Pie: acciones -->
          <div class="mt-auto flex items-center justify-end gap-1 border-t border-gray-100 dark:border-gray-700/70 px-3 py-2 bg-gray-50/80 dark:bg-gray-700/30">
            <template v-if="cartaFacturada(data)">
              <Button icon="pi pi-eye" rounded text severity="info" title="Ver" @click="verAforo(data)" />
            </template>
            <template v-else-if="!data.cancelada">
              <Button
                icon="pi pi-file-edit"
                rounded
                :severity="cartaAforada(data) ? 'info' : 'success'"
                :title="cartaAforada(data) ? 'Editar aforo' : 'Aforar'"
                @click="irAforar(data)"
              />
              <Button v-if="!data.cancelada" icon="pi pi-pencil" rounded text severity="info" title="Editar" @click="openEdicion(data)" />
              <Button v-if="!data.cancelada && !fechaRecep(data)" icon="pi pi-star" rounded text severity="success" title="Recepcionar" @click="recepcionar(data)" />
              <Button v-if="!data.cancelada" icon="pi pi-ban" rounded text severity="warning" title="Cancelar" @click="cancelar(data)" />
            </template>
            <Button v-if="!data.cancelada" icon="pi pi-print" rounded text severity="success" title="Imprimir" @click="imprimirCarta(data)" />
            <Button v-if="!cartaFacturada(data)" icon="pi pi-trash" rounded text severity="danger" title="Eliminar" @click="eliminar(data)" />
          </div>
        </article>
      </div>

      <!-- Vacío -->
      <div v-else class="flex flex-col items-center justify-center gap-3 rounded-2xl border border-dashed border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 py-16 text-center">
        <i class="pi pi-inbox text-4xl text-gray-300 dark:text-gray-600" />
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No hay cartas de porte para los filtros seleccionados</p>
        <Button label="Emitir carta" icon="pi pi-plus" severity="success" @click="openEmision" />
      </div>

      <!-- Paginación -->
      <div v-if="cartas.last_page > 1" class="flex justify-center">
        <Paginator
          :rows="cartas.per_page"
          :total-records="cartas.total"
          :first="(cartas.current_page - 1) * cartas.per_page"
          :page-links-size="5"
          template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
          currentPageReportTemplate="Mostrando {first}–{last} de {totalRecords}"
          @page="onPage"
        />
      </div>
    </div>

    <!-- Cancelación -->
    <Dialog v-model:visible="showCancelar" :header="`Cancelar Carta de Porte ${cancelarCarta?.numero || ''}`" modal :style="{ width: '460px' }">
      <div class="space-y-3">
        <p class="text-sm text-gray-600 dark:text-gray-300">
          Va a cancelar la carta de porte <strong>{{ cancelarCarta?.numero }}</strong>. Esta acción no se puede deshacer.
        </p>
        <div>
          <label class="block mb-1 font-medium">Observación (motivo de la cancelación)</label>
          <Textarea v-model="cancelarNotas" rows="3" class="w-full" placeholder="Escriba el motivo de la cancelación..." />
        </div>
        <div class="flex justify-end gap-2 pt-1">
          <Button label="No, volver" severity="secondary" type="button" @click="showCancelar = false" />
          <Button label="Cancelar carta" icon="pi pi-ban" severity="danger" @click="confirmCancelar" />
        </div>
      </div>
    </Dialog>

    <!-- Emisión -->
    <Dialog v-model:visible="showEmision" :header="editandoId ? `Editar Carta de Porte ${form.numero}` : 'Nueva Carta de Porte'" modal :style="{ width: '880px' }">
      <form @submit.prevent="submitEmision" class="space-y-4">
        <fieldset class="border rounded p-3">
          <legend class="font-semibold px-2">DATOS DE LA EMISION</legend>
          <div class="grid grid-cols-2 lg:grid-cols-3 gap-3">
            <div>
              <label class="block mb-1 font-medium">Fecha</label>
              <input v-model="form.fecha_emision" type="date" class="w-full border rounded p-2" required @blur="validarFolio" :min="soloFecha(minFecha)" :max="soloFecha(maxFecha)" />
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
              <label class="block mb-1 font-medium">Solicitud</label>
              <Select v-model="form.id_solicitud" :options="solicitudesCat" optionLabel="label" optionValue="id" filter class="w-full" :showClear="true" @change="aplicarSolicitud" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Conduce</label>
              <InputText v-model="form.conduce" class="w-full" />
            </div>
            <!-- Equipo/choferes derivados de la HR (solo lectura) -->
            <div class="col-span-2 lg:col-span-3 rounded-lg bg-gray-50 dark:bg-gray-700/40 p-2 text-xs text-gray-600 dark:text-gray-300">
              <span class="font-semibold text-gray-500 dark:text-gray-400">Equipo (de la hoja de ruta):</span>
              <span v-if="hrSeleccionada" class="ml-2">
                <i class="pi pi-truck mr-1" />{{ hrSeleccionada.tractivo_codigo || '—' }}
                <span v-if="hrSeleccionada.arrastre_codigo" class="ml-2"><i class="pi pi-box mr-1" />{{ hrSeleccionada.arrastre_codigo }}</span>
                <span v-if="hrSeleccionada.chofer_nombre" class="ml-2"><i class="pi pi-user mr-1" />{{ hrSeleccionada.chofer_nombre }}</span>
                <span v-if="hrSeleccionada.chofer2_nombre" class="ml-2"><i class="pi pi-user mr-1" />{{ hrSeleccionada.chofer2_nombre }}</span>
              </span>
              <span v-else class="ml-2 text-gray-400">No hay hoja de ruta seleccionada</span>
            </div>
          </div>
        </fieldset>

        <fieldset class="border rounded p-3">
          <legend class="font-semibold px-2">DATOS GENERALES</legend>
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
            <div>
              <label class="block mb-1 font-medium">Cliente</label>
              <Select v-model="form.id_cliente" :options="clientesCat" optionLabel="nombre" optionValue="id" filter class="w-full" :showClear="true" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Origen</label>
              <Select v-model="form.id_lugar_origen" :options="lugaresCat" optionLabel="nombre" optionValue="id" filter class="w-full" :showClear="true" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Destino</label>
              <Select v-model="form.id_lugar_destino" :options="lugaresCat" optionLabel="nombre" optionValue="id" filter class="w-full" :showClear="true" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Producto</label>
              <Select v-model="form.id_producto" :options="productosCat" optionLabel="nombre" optionValue="id" filter class="w-full" :showClear="true" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Tipo de Carga</label>
              <Select v-model="form.id_tipo_carga" :options="tiposCargasCat" optionLabel="nombre" optionValue="id" filter class="w-full" :showClear="true" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Moneda</label>
              <Select v-model="form.id_moneda" :options="(props.catalogos?.monedas || [])" optionLabel="nombre" optionValue="id" class="w-full" />
            </div>
          </div>
        </fieldset>

        <fieldset class="border rounded p-3">
          <legend class="font-semibold px-2">DATOS DE LA TRANSPORTACION</legend>
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
            <div>
              <label class="block mb-1 font-medium">Peso 1 (tns)</label>
              <InputNumber v-model="form.peso1" :min="0" :max-fraction-digits="2" class="w-full" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Peso 2 (opcional)</label>
              <InputNumber v-model="form.peso2" :min="0" :max-fraction-digits="2" class="w-full" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Distancia (kms)</label>
              <InputNumber v-model="form.distancia" :min="0" :max-fraction-digits="2" class="w-full" />
            </div>
          </div>
          <div class="mt-3 flex items-center gap-4">
            <label class="flex items-center gap-2"><Checkbox v-model="form.imprimir" :binary="true" /> Imprimir notas en la CP</label>
          </div>
          <div v-if="form.imprimir" class="mt-3">
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

<style scoped>
.cp-card {
  animation: cp-rise 0.45s ease both;
}
@keyframes cp-rise {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
.cp-folio {
  font-variant-numeric: tabular-nums;
}
</style>
