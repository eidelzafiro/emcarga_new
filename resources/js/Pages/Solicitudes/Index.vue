<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import DatePicker from 'primevue/datepicker'
import Select from 'primevue/select'
import Checkbox from 'primevue/checkbox'
import Dialog from 'primevue/dialog'
import Paginator from 'primevue/paginator'
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

function aplicarHojaCarta(event) {
  const hr = hojasCarta().find(h => h.id === event)
  if (!hr) return
  carta.value.id_chofer = hr.id_chofer || null
  carta.value.id_chofer2 = hr.id_chofer2 || null
  carta.value.id_tractivo = hr.id_tractivo || null
  carta.value.id_arrastre = hr.id_arrastre || null
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
const fmtNum = (v) => {
  if (v === null || v === undefined || v === '') return '—'
  const n = Number(v)
  return Number.isFinite(n) && n > 0 ? n.toLocaleString('es-CU', { maximumFractionDigits: 2 }) : '—'
}
const soloFecha = (v) => (v ? String(v).slice(0, 10) : '')
const pct = (ejec, total) => {
  if (!total) return 0
  return Math.min(100, Math.round((Number(ejec) / Number(total)) * 100))
}
const estadoBadge = (s) => ({
  pendiente: { label: 'Pendiente', cls: 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300' },
  en_proceso: { label: 'En proceso', cls: 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300' },
  realizada: { label: 'Realizada', cls: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' },
})[s] || { label: s, cls: 'bg-gray-100 text-gray-600 dark:bg-gray-500/15 dark:text-gray-300' }
</script>

<template>
  <AppLayout :title="title">
    <div class="space-y-4">
      <!-- Barra de acciones y filtros -->
      <div class="flex flex-col sm:flex-row sm:items-center gap-3 justify-between rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3 shadow-sm">
        <div class="flex flex-wrap items-center gap-2">
          <Button label="Nueva" icon="pi pi-plus" severity="success" @click="openCreate" />
          <span class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-700/60 text-xs font-semibold text-gray-600 dark:text-gray-300">
            <i class="pi pi-folder text-gray-400" />
            {{ solicitudes.total }} solicitudes
          </span>
        </div>
        <span class="relative">
          <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm" />
          <InputText v-model="search" placeholder="Buscar por N° o cliente..." class="w-56 !pl-9" />
        </span>
      </div>

      <!-- Grid de tarjetas -->
      <div v-if="solicitudes.data.length" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
        <article
          v-for="(s, i) in solicitudes.data"
          :key="s.id"
          class="sol-card relative flex flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white dark:bg-gray-800 shadow-sm transition-shadow hover:shadow-lg dark:border-gray-700"
          :style="{ animationDelay: `${Math.min(i, 10) * 45}ms` }"
        >
          <!-- Cabecera: folio protagonista -->
          <header class="relative border-b border-gray-100 bg-gradient-to-br from-amber-50/80 to-white px-4 pt-3 pb-2.5 dark:border-gray-700/70 dark:from-amber-950/20 dark:to-gray-800">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <span class="block text-[10px] font-bold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">Solicitud</span>
                <div class="sol-folio mt-1 text-[24px] font-black leading-none tracking-tight text-amber-700 dark:text-amber-300">{{ s.numero }}</div>
                <div class="mt-1.5 text-[11px] text-gray-400 dark:text-gray-500">
                  <i class="pi pi-calendar mr-1 text-[10px]" />Plan: {{ soloFecha(s.fecha_planificada) || soloFecha(s.fecha_solicitud) }}
                </div>
              </div>
              <span class="shrink-0 rounded-lg px-2 py-1 text-[11px] font-bold" :class="estadoBadge(cumplimiento(s)).cls">{{ estadoBadge(cumplimiento(s)).label }}</span>
            </div>
          </header>

          <!-- Cuerpo -->
          <div class="flex flex-1 flex-col gap-3 px-4 py-3">
            <div>
              <div class="truncate text-[16px] font-black tracking-tight text-gray-900 dark:text-white">{{ s.cliente?.nombre || '—' }}</div>
              <div class="mt-0.5 h-1 w-10 rounded-full bg-gradient-to-r from-amber-500 to-amber-300 dark:from-amber-400 dark:to-amber-600" />
            </div>

            <div class="flex items-center gap-1.5 text-sm">
              <span class="inline-flex min-w-0 items-center gap-1.5 rounded-lg bg-gray-50 dark:bg-gray-700/50 px-2 py-1 text-gray-700 dark:text-gray-300">
                <i class="pi pi-map-marker text-xs" style="color:#2563eb" />
                <span class="truncate">{{ s.lugar_origen?.nombre || '—' }}</span>
              </span>
              <i class="pi pi-arrow-right text-xs text-gray-400 shrink-0" />
              <span class="inline-flex min-w-0 items-center gap-1.5 rounded-lg bg-gray-50 dark:bg-gray-700/50 px-2 py-1 text-gray-700 dark:text-gray-300">
                <i class="pi pi-map-marker text-xs" style="color:#dc2626" />
                <span class="truncate">{{ s.lugar_destino?.nombre || '—' }}</span>
              </span>
            </div>

            <div class="grid grid-cols-2 gap-2">
              <div class="rounded-xl border border-gray-100 dark:border-gray-700/60 bg-gray-50 dark:bg-gray-700/30 px-2 py-1.5">
                <div class="text-[10px] font-bold uppercase tracking-wide text-gray-400 dark:text-gray-500">Carga 1</div>
                <div class="truncate text-sm font-bold text-gray-800 dark:text-gray-100">{{ s.producto?.nombre || '—' }}</div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">{{ s.tipo_carga?.nombre || '—' }} · {{ fmtNum(s.peso1) }} t</div>
              </div>
              <div class="rounded-xl border border-gray-100 dark:border-gray-700/60 bg-gray-50 dark:bg-gray-700/30 px-2 py-1.5">
                <div class="text-[10px] font-bold uppercase tracking-wide text-gray-400 dark:text-gray-500">Carga 2</div>
                <div class="truncate text-sm font-bold text-gray-800 dark:text-gray-100">{{ s.producto2?.nombre || '—' }}</div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">{{ s.tipo_carga2?.nombre || '—' }} · {{ fmtNum(s.peso2) }} t</div>
              </div>
            </div>

            <div>
              <div class="mb-1 flex items-center justify-between text-[11px] font-semibold text-gray-500 dark:text-gray-400">
                <span>Toneladas</span>
                <span>{{ fmtNum(s.toneladas_ejecutadas) }} / {{ fmtNum(s.toneladas_total ?? s.peso1) }}</span>
              </div>
              <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700/60">
                <div class="h-full rounded-full bg-gradient-to-r from-amber-500 to-emerald-500" :style="{ width: `${pct(s.toneladas_ejecutadas, s.toneladas_total ?? s.peso1)}%` }" />
              </div>
            </div>

            <!-- Folios de cartas de porte asignadas -->
            <div v-if="s.cartas_porte && s.cartas_porte.length" class="flex flex-wrap items-center gap-1.5">
              <span class="text-[10px] font-bold uppercase tracking-wide text-gray-400 dark:text-gray-500">CP:</span>
              <span
                v-for="cp in s.cartas_porte"
                :key="cp.id"
                class="inline-flex items-center gap-1 rounded-lg border border-blue-200 dark:border-blue-700/50 bg-blue-50 dark:bg-blue-950/30 px-2 py-0.5 text-[11px] font-bold text-blue-700 dark:text-blue-300"
              >
                <i class="pi pi-file text-[10px]" />{{ cp.numero }}
              </span>
            </div>
          </div>

          <!-- Pie: resumen y acciones -->
          <footer class="mt-auto border-t border-gray-100 dark:border-gray-700/70 bg-gray-50/80 dark:bg-gray-700/30 px-3 py-2">
            <div class="flex items-center justify-between text-[11px] text-gray-500 dark:text-gray-400">
              <span>{{ fmtNum(s.toneladas_pendientes) }} t pendientes</span>
              <span>{{ s.cartas_porte?.length ? `${s.cartas_porte.length} CP` : 'sin cartas' }}</span>
            </div>
            <div class="mt-1.5 flex items-center justify-end gap-1">
              <Button icon="pi pi-copy" rounded text severity="secondary" title="Duplicar solicitud" @click="duplicar(s)" />
              <Button icon="pi pi-truck" rounded text severity="info" title="Registrar carta de porte" :disabled="cumplimiento(s) === 'realizada'" @click="abrirCarta(s)" />
              <Button icon="pi pi-pencil" rounded text severity="info" title="Editar" @click="openEdit(s)" />
              <Button icon="pi pi-trash" rounded text severity="danger" title="Eliminar" @click="eliminar(s)" />
            </div>
          </footer>
        </article>
      </div>

      <!-- Vacío -->
      <div v-else class="flex flex-col items-center justify-center gap-3 rounded-2xl border border-dashed border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 py-16 text-center">
        <i class="pi pi-inbox text-4xl text-gray-300 dark:text-gray-600" />
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No hay solicitudes para los filtros seleccionados</p>
        <Button label="Nueva solicitud" icon="pi pi-plus" severity="success" @click="openCreate" />
      </div>

      <!-- Paginación -->
      <div v-if="solicitudes.last_page > 1" class="flex justify-center">
        <Paginator
          :rows="solicitudes.per_page"
          :total-records="solicitudes.total"
          :first="(solicitudes.current_page - 1) * solicitudes.per_page"
          :page-links-size="5"
          template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
          currentPageReportTemplate="Total: {totalRecords} registros"
          @page="onPage"
        />
      </div>
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
            </div>
            <div class="mt-3 grid grid-cols-1 lg:grid-cols-2 gap-3">
              <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-3">
                <div class="mb-2 text-[11px] font-bold uppercase tracking-wide text-gray-400 dark:text-gray-500">Carga 1</div>
                <div class="space-y-3">
                  <div>
                    <label class="block mb-1 font-medium">Tipo de carga</label>
                    <Select v-model="carta.id_tipo_carga" :options="tiposCargas" optionLabel="nombre" optionValue="id" filter class="w-full" :showClear="true" />
                  </div>
                  <div>
                    <label class="block mb-1 font-medium">Producto</label>
                    <Select v-model="carta.id_producto" :options="productos" optionLabel="nombre" optionValue="id" filter class="w-full" :showClear="true" />
                  </div>
                  <div>
                    <label class="block mb-1 font-medium">Peso (tns)</label>
                    <InputText v-model="carta.peso1" type="number" step="0.01" min="0" class="w-full" />
                  </div>
                </div>
              </div>
              <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-3">
                <div class="mb-2 text-[11px] font-bold uppercase tracking-wide text-gray-400 dark:text-gray-500">Carga 2 <span class="font-normal normal-case">(opcional)</span></div>
                <div class="space-y-3">
                  <div>
                    <label class="block mb-1 font-medium">Tipo de carga</label>
                    <Select v-model="carta.id_tipo_carga2" :options="tiposCargas" optionLabel="nombre" optionValue="id" filter class="w-full" :showClear="true" />
                  </div>
                  <div>
                    <label class="block mb-1 font-medium">Producto</label>
                    <Select v-model="carta.id_producto2" :options="productos" optionLabel="nombre" optionValue="id" filter class="w-full" :showClear="true" />
                  </div>
                  <div>
                    <label class="block mb-1 font-medium">Peso (tns)</label>
                    <InputText v-model="carta.peso2" type="number" step="0.01" min="0" class="w-full" />
                  </div>
                </div>
              </div>
            </div>
            <div class="mt-3 flex items-center gap-3">
              <label class="flex items-center gap-2"><Checkbox v-model="carta.imprimir" :binary="true" /> Imprimir notas en la CP</label>
            </div>
            <div v-if="carta.imprimir" class="mt-3">
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
<style scoped>
.sol-card {
  animation: sol-rise 0.45s ease both;
}
@keyframes sol-rise {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
.sol-folio {
  font-variant-numeric: tabular-nums;
}
</style>
