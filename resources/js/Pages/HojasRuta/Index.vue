<script setup>
import { ref, watch, computed, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import Checkbox from 'primevue/checkbox'
import Dialog from 'primevue/dialog'
import Paginator from 'primevue/paginator'

import Textarea from 'primevue/textarea'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { formatDate } from '@/Utils/date'

const props = defineProps({ hojas: Object, catalogos: Object, filters: Object, filtros: Object, fechaOperaciones: String })
const toast = useToast()
const confirmDialog = useConfirm()
const title = 'Hoja de Ruta'

// Rango del mes de la fecha de operaciones activa para los selectores de fecha.
const fechaOp = () => (props.fechaOperaciones ? new Date(props.fechaOperaciones.slice(0, 10)) : new Date())
const minFecha = soloFecha(new Date(fechaOp().getFullYear(), fechaOp().getMonth(), 1))
const maxFecha = soloFecha(new Date(fechaOp().getFullYear(), fechaOp().getMonth() + 1, 0))
const search = ref(props.filters?.search || '')
const estado = ref(props.filters?.estado || 'todas')
const equipo = ref(props.filters?.equipo || null)
const chofer = ref(props.filters?.chofer || null)
const grupo = ref(props.filters?.grupo || null)

// Opciones de filtros: solo lo que tiene hojas de ruta este mes, con combinaciones reales
const filtrosTractivos = computed(() => props.filtros?.tractivos || [])
const filtrosGrupos = computed(() => props.filtros?.grupos || [])
const filtrosChoferes = computed(() => (props.filtros?.choferes || []).map(c => ({ id: c.id, label: `${c.nombre} ${c.apellidos || ''}`.trim() })))
const combinacionesHr = computed(() => props.filtros?.combinaciones || [])

// Filtros dependientes: cada selector restringe a las combinaciones reales del mes.
function idsRelacionadosHr(extraer, usarTractivo = true, usarChofer = true, usarGrupo = true) {
  const set = new Set()
  for (const r of combinacionesHr.value) {
    if (usarTractivo && equipo.value && r.tractivo !== equipo.value) continue
    if (usarChofer && chofer.value && r.chofer !== chofer.value && r.chofer2 !== chofer.value) continue
    if (usarGrupo && grupo.value && r.grupo !== grupo.value) continue
    const id = extraer(r)
    if (id != null) set.add(id)
  }
  return set
}

const opcionesTractivosHr = computed(() => {
  if (!chofer.value && !grupo.value) return filtrosTractivos.value
  const ids = idsRelacionadosHr(r => r.tractivo, false, true, true)
  return filtrosTractivos.value.filter(t => ids.has(t.id))
})
const opcionesGruposHr = computed(() => {
  if (!equipo.value && !chofer.value) return filtrosGrupos.value
  const ids = idsRelacionadosHr(r => r.grupo, true, true, false)
  return filtrosGrupos.value.filter(g => ids.has(g.id))
})
const opcionesChoferesHr = computed(() => {
  if (!equipo.value && !grupo.value) return filtrosChoferes.value
  const ids = idsRelacionadosHr(r => r.chofer, true, false, true)
  const ids2 = idsRelacionadosHr(r => r.chofer2, true, false, true)
  const unidos = new Set([...ids, ...ids2])
  return filtrosChoferes.value.filter(c => unidos.has(c.id))
})

watch(equipo, () => {
  if (chofer.value && !opcionesChoferesHr.value.some(c => c.id === chofer.value)) chofer.value = null
  if (grupo.value && !opcionesGruposHr.value.some(g => g.id === grupo.value)) grupo.value = null
})
watch(chofer, () => {
  if (equipo.value && !opcionesTractivosHr.value.some(t => t.id === equipo.value)) equipo.value = null
  if (grupo.value && !opcionesGruposHr.value.some(g => g.id === grupo.value)) grupo.value = null
})
watch(grupo, () => {
  if (equipo.value && !opcionesTractivosHr.value.some(t => t.id === equipo.value)) equipo.value = null
  if (chofer.value && !opcionesChoferesHr.value.some(c => c.id === chofer.value)) chofer.value = null
})

const showApertura = ref(false)
const showCierre = ref(false)
const showEdicion = ref(false)
const creandoHr = ref(null)
const editandoId = ref(null)
const modoAperturaEdicion = ref(false)

const choferes = (props.catalogos?.choferes || [])
  .filter(c => c.id)
  .map(c => ({ id: c.id, label: `${c.nombre} ${c.apellidos || ''}`.trim(), ci: c.ci, cat: c.categorias_licencia || '' }))
const choferOptions = choferes
// Choferes ya asignados en la fila en edición/cierre: se agregan a las opciones
// aunque no cumplan los filtros del catálogo (entidad activa + licencia válida)
// para que el selector siempre muestre el valor actual.
const choferesExtra = ref([])
const choferOptionsCompleto = computed(() => {
  const mapa = new Map()
  for (const c of choferes) mapa.set(c.id, c)
  for (const c of choferesExtra.value) mapa.set(c.id, c)
  return [...mapa.values()]
})

function choferesDeFila(row) {
  const extra = []
  const empujar = (id, rel) => {
    if (id && rel) extra.push({ id, label: `${rel.nombre || ''} ${rel.apellidos || ''}`.trim(), ci: rel.ci, cat: rel.categorias_licencia || '' })
  }
  empujar(row?.id_chofer, row?.chofer)
  empujar(row?.id_chofer2, row?.chofer2)
  return extra
}

const hojasAnteriores = computed(() => (props.catalogos?.hojasAnteriores || []).map(h => ({ ...h, label: `${h.numero} - (${h.tractivo_codigo || '?'})` })))
const tractivosCat = computed(() => props.catalogos?.tractivos || [])
const arrastresCat = computed(() => props.catalogos?.arrastres || [])
const lugaresCat = computed(() => props.catalogos?.lugares || [])

const apertura = ref({ id_hr_anterior: null, numero: '', fecha_emision: '', hora_emision: '', id_tractivo: null, id_arrastre: null, id_chofer: null, id_chofer2: null, id_parqueo: null, id_grupo: null, kms_disponible: null, kms_disponibles_adicionales: null })
const folioInput = ref(null)

const infoTractivo = computed(() => tractivosCat.value.find(t => t.id === apertura.value.id_tractivo) || null)
const infoArrastre = computed(() => arrastresCat.value.find(a => a.id === apertura.value.id_arrastre) || null)
const infoChofer = computed(() => apertura.value.id_chofer ? choferOptionsCompleto.value.find(c => c.id === apertura.value.id_chofer) || null : null)
const infoChofer2 = computed(() => apertura.value.id_chofer2 ? choferOptionsCompleto.value.find(c => c.id === apertura.value.id_chofer2) || null : null)

function aplicarHrAnterior(hr) {
  if (!hr) return
  apertura.value = {
    id_hr_anterior: hr.id,
    numero: apertura.value.numero || '',
    fecha_emision: soloFecha(hr.fecha_cierre) || apertura.value.fecha_emision,
    hora_emision: hr.hora_cierre || apertura.value.hora_emision,
    id_tractivo: hr.id_tractivo,
    id_arrastre: hr.id_arrastre,
    id_chofer: hr.id_chofer,
    id_chofer2: hr.id_chofer2,
    id_parqueo: hr.id_parqueo,
    id_grupo: hr.id_grupo,
    kms_disponible: hr.kms_disponible ?? tractivosCat.value.find(t => t.id === hr.id_tractivo)?.kms_disp ?? null,
    kms_disponibles_adicionales: arrastresCat.value.find(a => a.id === hr.id_arrastre)?.kms_disp ?? null,
  }
  nextTick(() => {
    const el = folioInput.value?.$el ?? folioInput.value
    el?.focus?.()
  })
}

function limpiarHrAnterior() {
  apertura.value = { id_hr_anterior: null, numero: apertura.value.numero || '', fecha_emision: '', hora_emision: '', id_tractivo: null, id_arrastre: null, id_chofer: null, id_chofer2: null, id_parqueo: null, id_grupo: null, kms_disponible: null, kms_disponibles_adicionales: null }
}

const cierre = ref({ fecha_cierre: '', hora_cierre: '', kms_totales: null, combustible_habilitado: 0, combustible_consumido: 0, combustible_tecnico: 0, dias_trabajados: '', crear_siguiente: true, numero_nueva: '', fecha_emision: '', hora_emision: '', kms_disponible: null, kms_disponibles_adicionales: null, id_arrastre: null, id_chofer: null, id_parqueo: null })

const edicion = ref({})

watch(apertura.value ? [() => apertura.value.id_tractivo, () => apertura.value.id_arrastre] : [], ([tid, taid]) => {
  if (apertura.value.id_hr_anterior) return
  if (tid) {
    const t = tractivosCat.value.find(x => x.id === tid)
    if (t && apertura.value.kms_disponible === null) apertura.value.kms_disponible = t.kms_disp
    if (t) apertura.value.id_grupo = t.id_grupo ?? null
  }
  if (taid) {
    const a = arrastresCat.value.find(x => x.id === taid)
    if (a && apertura.value.kms_disponibles_adicionales === null) apertura.value.kms_disponibles_adicionales = a.kms_disp
  }
})

watch([search, estado, equipo, chofer, grupo], () => {
  router.get(route('hojas-ruta.index'), { search: search.value, estado: estado.value, equipo: equipo.value, chofer: chofer.value, grupo: grupo.value }, { preserveState: true, replace: true })
})

function onPage(event) {
  router.get(route('hojas-ruta.index'), { page: event.page + 1, search: search.value, estado: estado.value, equipo: equipo.value, chofer: chofer.value, grupo: grupo.value }, { preserveState: true, replace: true })
}

function nowDate() { return new Date().toISOString().slice(0, 10) }
function nowTime() { return new Date().toTimeString().slice(0, 5) }
function soloFecha(valor) {
  if (!valor) return ''
  return String(valor).slice(0, 10)
}
function soloHora(valor) {
  if (!valor) return ''
  return String(valor).slice(0, 5)
}

function openApertura() {
  modoAperturaEdicion.value = false
  apertura.value = { id_hr_anterior: null, numero: '', fecha_emision: nowDate(), hora_emision: nowTime(), id_tractivo: null, id_arrastre: null, id_chofer: null, id_chofer2: null, id_parqueo: props.catalogos?.parqueo_default ?? null, id_grupo: null, kms_disponible: null, kms_disponibles_adicionales: null }
  showApertura.value = true
}

function submitApertura() {
  const onSuccess = () => {
    showApertura.value = false
    toast.add({ severity: 'success', summary: modoAperturaEdicion.value ? 'Actualizada' : 'Creada', life: 3000 })
  }
  const onError = (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 })

  if (modoAperturaEdicion.value) {
    router.put(route('hojas-ruta.update', editandoId.value), { ...apertura.value, operacion: 'datos-apertura' }, { onSuccess, onError })
    return
  }

  router.post(route('hojas-ruta.store'), apertura.value, { onSuccess, onError })
}

function openCierre(row) {
  creandoHr.value = row
  choferesExtra.value = choferesDeFila(row)
  cierre.value = { fecha_cierre: nowDate(), hora_cierre: nowTime(), kms_totales: null, combustible_habilitado: 0, combustible_consumido: 0, combustible_tecnico: 0, dias_trabajados: '', crear_siguiente: true, numero_nueva: '', fecha_emision: row.fecha_cierre || nowDate(), hora_emision: nowTime(), kms_disponible: row.kms_disponible, kms_disponibles_adicionales: row.kms_disponibles_adicionales, id_arrastre: row.id_arrastre ?? null, id_chofer: row.id_chofer ?? null, id_parqueo: row.id_parqueo ?? null }
  showCierre.value = true
}

const indiceTractivo = computed(() => Number(creandoHr.value?.tractivo?.indice_consumo) || 0)

watch(() => cierre.value.kms_totales, (kms) => {
  const indice = Number(creandoHr.value?.tractivo?.indice_consumo) || 0
  if (indice > 0 && kms !== null && kms !== undefined && kms !== '') {
    cierre.value.combustible_consumido = Math.round((Number(kms) / indice) * 100) / 100
  }
})

function submitCierre() {
  const operacion = cierre.value.crear_siguiente ? 'cierre-con-siguiente' : 'cierre'
  const datos = { operacion, ...cierre.value }
  if (cierre.value.crear_siguiente) {
    datos.fecha_emision = cierre.value.fecha_cierre
    datos.hora_emision = cierre.value.hora_cierre
  }
  router.put(route('hojas-ruta.update', creandoHr.value.id), datos, {
    onSuccess: () => { showCierre.value = false; toast.add({ severity: 'success', summary: 'Cerrada', life: 3000 }) },
    onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
  })
}

function openEdicion(row) {
  choferesExtra.value = choferesDeFila(row)
  // HR sin cerrar → reutiliza el formulario de apertura en modo edición (no crea de nuevo)
  if (!row.fecha_cierre) {
    editandoId.value = row.id
    modoAperturaEdicion.value = true
    apertura.value = {
      id_hr_anterior: row.id_hr_anterior ?? null,
      numero: row.numero,
      fecha_emision: soloFecha(row.fecha_emision),
      hora_emision: soloHora(row.hora_emision),
      id_tractivo: row.id_tractivo ?? null,
      id_arrastre: row.id_arrastre ?? null,
      id_chofer: row.id_chofer ?? null,
      id_chofer2: row.id_chofer2 ?? null,
      id_parqueo: row.id_parqueo ?? null,
      id_grupo: row.id_grupo ?? null,
      kms_disponible: row.kms_disponible,
      kms_disponibles_adicionales: row.kms_disponibles_adicionales,
    }
    showApertura.value = true
    return
  }

  editandoId.value = row.id
  edicion.value = {
    numero: row.numero, fecha_emision: soloFecha(row.fecha_emision), hora_emision: soloHora(row.hora_emision),
    fecha_cierre: soloFecha(row.fecha_cierre), hora_cierre: soloHora(row.hora_cierre),
    id_tractivo: row.id_tractivo, id_arrastre: row.id_arrastre, id_chofer: row.id_chofer, id_chofer2: row.id_chofer2,
    id_parqueo: row.id_parqueo, id_grupo: row.id_grupo,
    kms_disponible: row.kms_disponible, kms_disponibles_adicionales: row.kms_disponibles_adicionales,
    kms_totales: row.kms_totales, combustible_habilitado: row.combustible_habilitado, combustible_consumido: row.combustible_consumido, combustible_tecnico: row.combustible_tecnico,
    tiempo_mov: row.tiempo_mov, tiempo_espera: row.tiempo_espera, tiempo_carga: row.tiempo_carga,
    tiempo_taller: row.tiempo_taller, tiempo_inactivo: row.tiempo_inactivo, tiempo_otras_actividades: row.tiempo_otras_actividades, tiempo_total: row.tiempo_total,
    notas: row.notas, analisis: row.analisis, dias_trabajados: row.dias_trabajados,
  }
  showEdicion.value = true
}

function submitEdicion() {
  router.put(route('hojas-ruta.update', editandoId.value), edicion.value, {
    onSuccess: () => { showEdicion.value = false; toast.add({ severity: 'success', summary: 'Actualizada', life: 3000 }) },
    onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
  })
}

function cancelar(row) {
  confirmDialog.require({
    message: `¿Cancelar la Hoja de Ruta ${row.numero}?`,
    header: 'Cancelar Hoja de Ruta',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Cancelar',
    rejectLabel: 'Volver',
    acceptClass: 'p-button-danger',
    accept: () => {
      router.post(route('hojas-ruta.destroy', row.id), { operacion: 'cancelar', _method: 'delete' }, {
        onSuccess: () => toast.add({ severity: 'success', summary: 'Cancelada', life: 3000 }),
        onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
      })
    },
  })
}

function eliminar(row) {
  confirmDialog.require({
    message: `¿Eliminar la Hoja de Ruta ${row.numero}?`,
    header: 'Eliminar Hoja de Ruta',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Eliminar',
    rejectLabel: 'Volver',
    acceptClass: 'p-button-danger',
    accept: () => {
      router.delete(route('hojas-ruta.destroy', row.id), {
        onSuccess: () => toast.add({ severity: 'success', summary: 'Eliminada', life: 3000 }),
        onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
      })
    },
  })
}

function imprimir(row) {
  window.open(route('hojas-ruta.imprimir', { hoja: row.id }), '_blank')
}

function choferNombre(c) { return c ? `${c.nombre} ${c.apellidos || ''}`.trim() : '—' }
function tractivoCodigo(t) { return t ? t.codigo : '—' }
function marcaModelo(v) {
  if (!v) return '—'
  const partes = [v.marca, v.modelo].filter(Boolean)
  return partes.length ? partes.join(' ') : '—'
}
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
  const n = Number(v)
  return Number.isFinite(n) ? n.toLocaleString('es-CU', { maximumFractionDigits: 2 }) : '—'
}
function estadoHR(d) {
  if (d.cancelada) return { label: 'Cancelada', cls: 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300' }
  if (!d.fecha_cierre) return { label: 'Abierta', cls: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' }
  return { label: 'Cerrada', cls: 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300' }
}
const diffComb = (h) => ((Number(h.combustible_consumido) || 0) - (Number(h.combustible_habilitado) || 0))

function tieneValor(v) {
  return v !== null && v !== undefined && v !== ''
}

// Tarjetas KMS/combustible solo cuando hay valores: kms_totales, consumo,
// habilitado y diferencia (esta última requiere consumo y habilitado).
function tilesHR(h) {
  const tiles = []
  if (tieneValor(h.kms_totales)) tiles.push({ label: 'KMS totales', valor: fmtNum(h.kms_totales), cls: 'text-gray-800 dark:text-gray-100' })
  if (tieneValor(h.combustible_consumido)) tiles.push({ label: 'Consumo', valor: fmtNum(h.combustible_consumido), cls: 'text-gray-800 dark:text-gray-100' })
  if (tieneValor(h.combustible_habilitado)) tiles.push({ label: 'Habilitado', valor: fmtNum(h.combustible_habilitado), cls: 'text-gray-800 dark:text-gray-100' })
  if (tieneValor(h.combustible_consumido) && tieneValor(h.combustible_habilitado)) {
    const d = diffComb(h)
    tiles.push({ label: 'Dif', valor: fmtNum(d), cls: d < 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-800 dark:text-gray-100' })
  }
  return tiles
}

function editarCarta(cp) {
  router.get(route('carta-porte.index', { editar: cp.id }))
}

const CAMPOS_TIEMPO = ['tiempo_mov', 'tiempo_espera', 'tiempo_carga', 'tiempo_taller', 'tiempo_inactivo', 'tiempo_otras_actividades']

function fmtTiempo(v) {
  if (v === null || v === undefined || v === '') return '—'
  return Number(v).toLocaleString('es-CU', { minimumFractionDigits: 0, maximumFractionDigits: 2 })
}

function numTiempo(v) {
  const n = Number(v)
  return Number.isFinite(n) ? n : 0
}

function calcularTiempoTotal(fechaEmision, horaEmision, fechaCierre, horaCierre) {
  if (!fechaEmision || !fechaCierre) return null
  const finicio = new Date(`${fechaEmision}T${horaEmision || '00:00'}:00`)
  const ffin = new Date(`${fechaCierre}T${horaCierre || '00:00'}:00`)
  const dif = Math.floor((ffin - finicio) / 1000)
  if (!Number.isFinite(dif) || dif < 0) return null
  const hor = Math.floor(dif / 3600)
  const minutos = Math.round((dif / 60) - (hor * 60))
  const fminutos = Math.round((minutos / 60) * 100) / 100
  return Math.round((hor + fminutos) * 100) / 100
}

const sumaTiempos = computed(() => CAMPOS_TIEMPO.reduce((acc, k) => acc + numTiempo(edicion.value[k]), 0))
const totalEmisionCierre = computed(() => calcularTiempoTotal(edicion.value.fecha_emision, edicion.value.hora_emision, edicion.value.fecha_cierre, edicion.value.hora_cierre))
const diferenciaTiempos = computed(() => {
  const total = numTiempo(edicion.value.tiempo_total)
  return Math.round((total - sumaTiempos.value) * 100) / 100
})

watch(() => [edicion.value.fecha_emision, edicion.value.hora_emision, edicion.value.fecha_cierre, edicion.value.hora_cierre], () => {
  const total = calcularTiempoTotal(edicion.value.fecha_emision, edicion.value.hora_emision, edicion.value.fecha_cierre, edicion.value.hora_cierre)
  if (total !== null) edicion.value.tiempo_total = total
})
</script>

<template>
  <AppLayout :title="title">
    <div class="space-y-4">
      <!-- Barra de acciones y filtros -->
      <div class="flex flex-col lg:flex-row lg:items-center gap-3 justify-between rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3 shadow-sm">
        <div class="flex flex-wrap items-center gap-2">
          <Button label="Nueva Hoja" icon="pi pi-plus" severity="success" @click="openApertura" />
          <span class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-700/60 text-xs font-semibold text-gray-600 dark:text-gray-300">
            <i class="pi pi-folder text-gray-400" />
            {{ hojas.total }} hojas
          </span>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <Select v-model="equipo" :options="opcionesTractivosHr" optionLabel="codigo" optionValue="id" filter placeholder="Equipo" class="w-40" :showClear="true" />
          <Select v-model="grupo" :options="opcionesGruposHr" optionLabel="nombre" optionValue="id" filter placeholder="Grupo" class="w-36" :showClear="true" />
          <Select v-model="chofer" :options="opcionesChoferesHr" optionLabel="label" optionValue="id" filter placeholder="Chofer" class="w-44" :showClear="true" />
          <Select v-model="estado" :options="[{ label: 'Todas', value: 'todas' }, { label: 'Abiertas', value: 'abiertas' }, { label: 'Cerradas', value: 'cerradas' }, { label: 'Canceladas', value: 'canceladas' }]" optionLabel="label" optionValue="value" class="w-36" />
          <span class="relative">
            <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm" />
            <InputText v-model="search" placeholder="Buscar número, chofer..." class="w-52 !pl-9" />
          </span>
        </div>
      </div>

      <!-- Grid de tarjetas -->
      <div v-if="hojas.data.length" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article
          v-for="(h, i) in hojas.data"
          :key="h.id"
          class="hr-card relative flex flex-col overflow-hidden rounded-2xl border bg-white dark:bg-gray-800 shadow-sm transition-shadow hover:shadow-lg dark:border-gray-700"
          :class="h.cancelada ? 'border-red-300 dark:border-red-800/60' : h.fecha_cierre ? 'border-blue-300 dark:border-blue-700/60' : 'border-emerald-200 dark:border-emerald-800/40'"
          :style="{ animationDelay: `${Math.min(i, 10) * 45}ms` }"
        >
          <!-- Sello cancelada -->
          <div v-if="h.cancelada" class="pointer-events-none absolute inset-0 z-10 flex items-center justify-center">
            <span class="rotate-[-14deg] border-[3px] border-red-500/70 text-red-500/80 dark:border-red-400/70 dark:text-red-300/80 rounded-lg px-4 py-1 text-xl font-black uppercase tracking-[0.22em]">Cancelada</span>
          </div>

          <!-- Cabecera: folio protagonista -->
          <header class="relative px-4 pt-3 pb-2.5 border-b border-gray-100 dark:border-gray-700/70" :class="h.cancelada ? 'bg-red-50/60 dark:bg-red-950/20' : h.fecha_cierre ? 'bg-gradient-to-br from-blue-50/80 to-white dark:from-blue-950/30 dark:to-gray-800' : 'bg-gradient-to-br from-emerald-50/80 to-white dark:from-emerald-950/20 dark:to-gray-800'">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <span class="block text-[10px] font-bold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">Hoja de ruta</span>
                <div class="hr-folio mt-1 text-[24px] font-black leading-none tracking-tight" :class="h.cancelada ? 'text-red-500 dark:text-red-400 line-through' : h.fecha_cierre ? 'text-blue-700 dark:text-blue-300' : 'text-emerald-700 dark:text-emerald-300'">
                  {{ h.numero }}
                </div>
                <div class="mt-1.5 flex items-center gap-1.5 text-[11px] text-gray-400 dark:text-gray-500">
                  <i class="pi pi-calendar mr-0.5 text-[10px]" />{{ formatDate(h.fecha_emision) }} {{ h.hora_emision ? `· ${soloHora(h.hora_emision)}` : '' }}
                  <i class="pi pi-arrow-right mx-1 text-[9px]" />
                  <i class="pi pi-calendar-times mr-0.5 text-[10px]" />{{ h.fecha_cierre ? `${formatDate(h.fecha_cierre)} ${h.hora_cierre ? `· ${soloHora(h.hora_cierre)}` : ''}` : 'abierta' }}
                  <i v-if="tieneValor(h.tiempo_total)" class="pi pi-clock ml-1 text-[10px]" />
                  <span v-if="tieneValor(h.tiempo_total)" class="font-semibold">{{ fmtTiempo(h.tiempo_total) }} h</span>
                </div>
              </div>
              <span class="shrink-0 rounded-lg px-2 py-1 text-[11px] font-bold" :class="estadoHR(h).cls">{{ estadoHR(h).label }}</span>
            </div>
          </header>

          <!-- Cuerpo -->
          <div class="flex flex-1 flex-col gap-3 px-4 py-3">
            <div class="flex items-center gap-2 min-w-0">
              <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-[11px] font-bold text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300">
                {{ iniciales(choferNombre(h.chofer)) }}
              </span>
              <div class="min-w-0">
                <div class="truncate text-sm font-semibold text-gray-800 dark:text-gray-100">{{ choferNombre(h.chofer) }}</div>
                <div v-if="h.chofer2" class="truncate text-[11px] text-gray-500 dark:text-gray-400">2do: {{ choferNombre(h.chofer2) }}</div>
              </div>
              <span v-if="Number(h.cartas_porte_count) > 0" class="ml-auto shrink-0 rounded-lg bg-emerald-50 dark:bg-emerald-500/10 px-2 py-0.5 text-[11px] font-bold text-emerald-700 dark:text-emerald-300">
                {{ h.cartas_porte_count }} CP
              </span>
            </div>

            <!-- Folios de cartas de porte asociadas (clic para editar la carta) -->
            <div v-if="h.cartas_porte && h.cartas_porte.length" class="flex flex-wrap items-center gap-1.5">
              <span class="text-[10px] font-bold uppercase tracking-wide text-gray-400 dark:text-gray-500">CP:</span>
              <button
                v-for="cp in h.cartas_porte"
                :key="cp.id"
                type="button"
                class="inline-flex items-center gap-1 rounded-lg border border-blue-200 dark:border-blue-700/50 bg-blue-50 dark:bg-blue-950/30 px-2 py-0.5 text-[11px] font-bold text-blue-700 dark:text-blue-300 transition-colors hover:bg-blue-100 dark:hover:bg-blue-900/40"
                :title="`Editar carta de porte ${cp.numero}`"
                @click="editarCarta(cp)"
              >
                <i class="pi pi-file-edit text-[10px]" />{{ cp.numero }}
              </button>
            </div>

            <div class="flex flex-wrap items-center gap-2">
              <span class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 dark:border-emerald-700/50 bg-emerald-50 dark:bg-emerald-950/30 px-3 py-1.5">
                <i class="pi pi-truck text-xl" style="color:#059669" />
                <span class="text-lg font-black tracking-tight text-emerald-800 dark:text-emerald-300">{{ tractivoCodigo(h.tractivo) }}</span>
              </span>
              <span v-if="h.arrastre" class="inline-flex items-center gap-2 rounded-xl border border-violet-200 dark:border-violet-700/50 bg-violet-50 dark:bg-violet-950/30 px-3 py-1.5">
                <i class="pi pi-box text-xl" style="color:#7c3aed" />
                <span class="text-lg font-black tracking-tight text-violet-800 dark:text-violet-300">{{ h.arrastre.codigo }}</span>
              </span>
            </div>

            <div v-if="tilesHR(h).length" class="grid grid-cols-2 gap-2 text-center">
              <div v-for="t in tilesHR(h)" :key="t.label" class="rounded-xl border border-gray-100 dark:border-gray-700/60 bg-gray-50 dark:bg-gray-700/30 px-2 py-1.5">
                <div class="text-[10px] font-bold uppercase tracking-wide text-gray-400 dark:text-gray-500">{{ t.label }}</div>
                <div class="text-sm font-black" :class="t.cls">{{ t.valor }}</div>
              </div>
            </div>
          </div>

          <!-- Pie: entidad/parqueo y acciones -->
          <footer class="mt-auto border-t border-gray-100 dark:border-gray-700/70 bg-gray-50/80 dark:bg-gray-700/30 px-3 py-2">
            <div class="flex items-center justify-between text-[11px] text-gray-500 dark:text-gray-400">
              <span>{{ h.entidad?.nombre || '—' }}</span>
              <span>{{ h.parqueo?.nombre || '—' }}</span>
            </div>
            <div class="mt-1.5 flex items-center justify-end gap-1">
              <Button v-if="!h.cancelada" icon="pi pi-print" rounded text severity="success" title="Imprimir" @click="imprimir(h)" />
              <Button v-if="!h.cancelada && !h.fecha_cierre" icon="pi pi-check" rounded text severity="success" title="Cerrar" @click="openCierre(h)" />
              <Button v-if="!h.cancelada" icon="pi pi-pencil" rounded text severity="info" title="Editar" @click="openEdicion(h)" />
              <Button v-if="!h.cancelada" icon="pi pi-ban" rounded text severity="warning" title="Cancelar" @click="cancelar(h)" />
              <Button icon="pi pi-trash" rounded text severity="danger" title="Eliminar" @click="eliminar(h)" />
            </div>
          </footer>
        </article>
      </div>

      <!-- Vacío -->
      <div v-else class="flex flex-col items-center justify-center gap-3 rounded-2xl border border-dashed border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 py-16 text-center">
        <i class="pi pi-inbox text-4xl text-gray-300 dark:text-gray-600" />
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No hay hojas de ruta para los filtros seleccionados</p>
        <Button label="Nueva Hoja" icon="pi pi-plus" severity="success" @click="openApertura" />
      </div>

      <!-- Paginación -->
      <div v-if="hojas.last_page > 1" class="flex justify-center">
        <Paginator
          :rows="hojas.per_page"
          :total-records="hojas.total"
          :first="(hojas.current_page - 1) * hojas.per_page"
          :page-links-size="5"
          template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
          currentPageReportTemplate="Total: {totalRecords} registros"
          @page="onPage"
        />
      </div>
    </div>

    <!-- Apertura -->
    <Dialog v-model:visible="showApertura" :header="modoAperturaEdicion ? 'Editar Hoja de Ruta' : 'Apertura de Hoja de Ruta'" modal :style="{ width: '820px' }">
      <form @submit.prevent="submitApertura" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block mb-1 font-medium">HR Anterior</label>
            <div class="flex gap-2">
              <Select v-model="apertura.id_hr_anterior" :options="hojasAnteriores" optionLabel="label" optionValue="id" filter :disabled="modoAperturaEdicion" class="w-full" @change="aplicarHrAnterior($event.value ? hojasAnteriores.find(h => h.id === $event.value) : null)" />
              <Button type="button" icon="pi pi-times" severity="secondary" outlined title="Limpiar" :disabled="modoAperturaEdicion" @click="limpiarHrAnterior" />
            </div>
          </div>
          <div>
            <label class="block mb-1 font-medium">Folio</label>
            <InputText ref="folioInput" v-model="apertura.numero" class="w-full" required readonly />
          </div>
          <div>
            <label class="block mb-1 font-medium">Fecha emisión</label>
            <input v-model="apertura.fecha_emision" type="date" class="w-full border rounded p-2" required :min="minFecha" :max="maxFecha" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Hora emisión</label>
            <input v-model="apertura.hora_emision" type="time" class="w-full border rounded p-2" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Grupo</label>
            <Select v-model="apertura.id_grupo" :options="catalogos.grupos" optionLabel="nombre" optionValue="id" filter class="w-full" :showClear="true" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Parqueo</label>
            <Select v-model="apertura.id_parqueo" :options="lugaresCat" optionLabel="nombre" optionValue="id" filter class="w-full" :showClear="true" />
          </div>
        </div>

        <!-- Equipo (tractivo) -->
        <div class="border rounded-lg p-3 bg-surface-50">
          <div class="flex items-center justify-between mb-2">
            <span class="font-semibold">Equipo (tractivo)</span>
          </div>
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 items-end">
            <div>
              <label class="block mb-1 font-medium">Código</label>
              <Select v-model="apertura.id_tractivo" :options="tractivosCat" optionLabel="codigo" optionValue="id" filter placeholder="Seleccione el tractivo" class="w-full" required />
            </div>
            <div>
              <label class="block mb-1 font-medium">Marca-Modelo</label>
              <InputText :model-value="marcaModelo(infoTractivo)" readonly class="w-full bg-surface-100" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Chapa</label>
              <InputText :model-value="infoTractivo?.placa || '—'" readonly class="w-full bg-surface-100" />
            </div>
            <div>
              <label class="block mb-1 font-medium">KMS disponibles</label>
              <InputNumber v-model="apertura.kms_disponible" :min="0" :max-fraction-digits="2" class="w-full" />
            </div>
          </div>
        </div>

        <!-- Arrastre -->
        <div class="border rounded-lg p-3 bg-surface-50">
          <div class="flex items-center justify-between mb-2">
            <span class="font-semibold">Arrastre</span>
          </div>
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 items-end">
            <div>
              <label class="block mb-1 font-medium">Código</label>
              <Select v-model="apertura.id_arrastre" :options="arrastresCat" optionLabel="codigo" optionValue="id" filter placeholder="Seleccione el arrastre" class="w-full" :showClear="true" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Marca-Modelo</label>
              <InputText :value="marcaModelo(infoArrastre)" readonly class="w-full bg-surface-100" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Chapa</label>
              <InputText :value="infoArrastre?.placa || '—'" readonly class="w-full bg-surface-100" />
            </div>
            <div>
              <label class="block mb-1 font-medium">KMS disponibles</label>
              <InputNumber v-model="apertura.kms_disponibles_adicionales" :min="0" :max-fraction-digits="2" class="w-full" />
            </div>
          </div>
        </div>

        <!-- Chóferes -->
        <div class="grid grid-cols-2 gap-4">
          <div class="border rounded-lg p-4 bg-surface-50">
            <span class="font-semibold block mb-2">Chofer</span>
            <div class="space-y-2">
              <Select v-model="apertura.id_chofer" :options="choferOptionsCompleto" optionLabel="label" optionValue="id" filter placeholder="Seleccione el chofer" class="w-full" required />
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <label class="block mb-1 font-medium">CI</label>
                  <InputText :model-value="infoChofer?.ci || '—'" readonly class="w-full bg-surface-100" />
                </div>
                <div>
                  <label class="block mb-1 font-medium">Licencia</label>
                  <InputText :model-value="infoChofer?.cat || '—'" readonly class="w-full bg-surface-100" />
                </div>
              </div>
            </div>
          </div>
          <div class="border rounded-lg p-4 bg-surface-50">
            <span class="font-semibold block mb-2">2do Chofer</span>
            <div class="space-y-2">
              <Select v-model="apertura.id_chofer2" :options="choferOptionsCompleto" optionLabel="label" optionValue="id" filter placeholder="Seleccione el chofer" class="w-full" :showClear="true" />
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <label class="block mb-1 font-medium">CI</label>
                  <InputText :model-value="infoChofer2?.ci || '—'" readonly class="w-full bg-surface-100" />
                </div>
                <div>
                  <label class="block mb-1 font-medium">Licencia</label>
                  <InputText :model-value="infoChofer2?.cat || '—'" readonly class="w-full bg-surface-100" />
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="flex gap-2 justify-end">
          <Button label="Cancelar" severity="secondary" @click="showApertura = false" />
          <Button label="Guardar" type="submit" icon="pi pi-save" />
        </div>
      </form>
    </Dialog>

    <!-- Cierre -->
    <Dialog v-model:visible="showCierre" header="Cierre de Hoja de Ruta" modal style="width: 760px">
      <form @submit.prevent="submitCierre" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block mb-1 font-medium">Fecha cierre</label>
            <input v-model="cierre.fecha_cierre" type="date" class="w-full border rounded p-2" required :min="minFecha" :max="maxFecha" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Hora cierre</label>
            <input v-model="cierre.hora_cierre" type="time" class="w-full border rounded p-2" />
          </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block mb-1 font-medium">KMS totales</label>
            <InputNumber v-model="cierre.kms_totales" :min="0" :max-fraction-digits="2" class="w-full" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Días trabajados</label>
            <InputText v-model="cierre.dias_trabajados" class="w-full" />
          </div>
        </div>
        <div class="grid grid-cols-3 gap-4">
          <div>
            <label class="block mb-1 font-medium">Combustible habilitado</label>
            <InputNumber v-model="cierre.combustible_habilitado" :min="0" :max-fraction-digits="2" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Consumido</label>
            <InputNumber v-model="cierre.combustible_consumido" :min="0" :max-fraction-digits="2" class="w-full" />
            <small class="text-surface-400">Índice del tractivo: {{ indiceTractivo }}</small>
          </div>
          <div>
            <label class="block mb-1 font-medium">Técnico</label>
            <InputNumber v-model="cierre.combustible_tecnico" :min="0" :max-fraction-digits="2" class="w-full" />
          </div>
        </div>
        <div class="flex items-center gap-2">
          <Checkbox v-model="cierre.crear_siguiente" :binary="true" inputId="crear_siguiente" />
          <label for="crear_siguiente" class="font-medium">Crear siguiente Hoja</label>
        </div>
        <template v-if="cierre.crear_siguiente">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block mb-1 font-medium">Nº siguiente</label>
              <InputText v-model="cierre.numero_nueva" class="w-full" required />
            </div>
            <div>
              <label class="block mb-1 font-medium">KMS disponibles</label>
              <InputNumber v-model="cierre.kms_disponible" :min="0" :max-fraction-digits="2" class="w-full" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Arrastre</label>
              <Select v-model="cierre.id_arrastre" :options="arrastresCat" optionLabel="codigo" optionValue="id" filter placeholder="Seleccione el arrastre" class="w-full" :showClear="true" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Chofer</label>
              <Select v-model="cierre.id_chofer" :options="choferOptionsCompleto" optionLabel="label" optionValue="id" filter placeholder="Seleccione el chofer" class="w-full" :showClear="true" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Parqueo</label>
              <Select v-model="cierre.id_parqueo" :options="lugaresCat" optionLabel="nombre" optionValue="id" filter placeholder="Seleccione el parqueo" class="w-full" :showClear="true" />
            </div>
          </div>
        </template>
        <div class="flex gap-2 justify-end">
          <Button label="Cancelar" severity="secondary" @click="showCierre = false" />
          <Button label="Cerrar" type="submit" icon="pi pi-check" />
        </div>
      </form>
    </Dialog>

    <!-- Edición -->
    <Dialog v-model:visible="showEdicion" header="Editar Hoja de Ruta" modal style="width: 700px">
      <form @submit.prevent="submitEdicion" class="grid grid-cols-2 gap-4">
        <div>
          <label class="block mb-1 font-medium">Número</label>
          <InputText v-model="edicion.numero" class="w-full" required readonly />
        </div>
        <div class="col-span-2 grid grid-cols-4 gap-3 border rounded-lg p-3 bg-surface-50">
          <div>
            <label class="block mb-1 font-medium">Fecha emisión</label>
            <input v-model="edicion.fecha_emision" type="date" class="w-full border rounded p-2" required :min="minFecha" :max="maxFecha" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Hora emisión</label>
            <input v-model="edicion.hora_emision" type="time" class="w-full border rounded p-2" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Fecha cierre</label>
            <input v-model="edicion.fecha_cierre" type="date" class="w-full border rounded p-2" :min="minFecha" :max="maxFecha" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Hora cierre</label>
            <input v-model="edicion.hora_cierre" type="time" class="w-full border rounded p-2" />
          </div>
        </div>
        <div>
          <label class="block mb-1 font-medium">Tractivo</label>
          <Select v-model="edicion.id_tractivo" :options="catalogos.tractivos" optionLabel="codigo" optionValue="id" filter class="w-full" required />
        </div>
        <div>
          <label class="block mb-1 font-medium">Arrastre</label>
          <Select v-model="edicion.id_arrastre" :options="catalogos.arrastres" optionLabel="codigo" optionValue="id" filter class="w-full" :showClear="true" />
        </div>
        <div>
          <label class="block mb-1 font-medium">Chofer</label>
          <Select v-model="edicion.id_chofer" :options="choferOptionsCompleto" optionLabel="label" optionValue="id" filter class="w-full" required />
        </div>
        <div>
          <label class="block mb-1 font-medium">Chofer 2</label>
          <Select v-model="edicion.id_chofer2" :options="choferOptionsCompleto" optionLabel="label" optionValue="id" filter class="w-full" :showClear="true" />
        </div>
        <div>
          <label class="block mb-1 font-medium">Parqueo</label>
          <Select v-model="edicion.id_parqueo" :options="catalogos.lugares" optionLabel="nombre" optionValue="id" filter class="w-full" :showClear="true" />
        </div>
        <div>
          <label class="block mb-1 font-medium">Grupo</label>
          <Select v-model="edicion.id_grupo" :options="catalogos.grupos" optionLabel="nombre" optionValue="id" filter class="w-full" :showClear="true" />
        </div>
        <div>
          <label class="block mb-1 font-medium">KMS disponibles</label>
          <InputNumber v-model="edicion.kms_disponible" :min="0" :max-fraction-digits="2" class="w-full" />
        </div>
        <div>
          <label class="block mb-1 font-medium">KMS adicionales</label>
          <InputNumber v-model="edicion.kms_disponibles_adicionales" :min="0" :max-fraction-digits="2" class="w-full" />
        </div>
        <div>
          <label class="block mb-1 font-medium">KMS totales</label>
          <InputNumber v-model="edicion.kms_totales" :min="0" :max-fraction-digits="2" class="w-full" />
        </div>
        <div>
          <label class="block mb-1 font-medium">Combustible habilitado</label>
          <InputNumber v-model="edicion.combustible_habilitado" :min="0" :max-fraction-digits="2" class="w-full" />
        </div>
        <div>
          <label class="block mb-1 font-medium">Combustible consumido</label>
          <InputNumber v-model="edicion.combustible_consumido" :min="0" :max-fraction-digits="2" class="w-full" />
        </div>
        <div>
          <label class="block mb-1 font-medium">Combustible técnico</label>
          <InputNumber v-model="edicion.combustible_tecnico" :min="0" :max-fraction-digits="2" class="w-full" />
        </div>
        <div>
          <label class="block mb-1 font-medium">Días trabajados</label>
          <InputText v-model="edicion.dias_trabajados" class="w-full" />
        </div>

        <div class="col-span-2 border rounded-lg p-3 bg-surface-50">
          <div class="flex items-center justify-between mb-2">
            <span class="font-semibold">Tiempos (horas decimales)</span>
            <div class="flex gap-4 text-sm">
              <span>Total emisión→cierre: <b>{{ fmtTiempo(totalEmisionCierre) }}</b></span>
              <span :class="{ 'text-red-600 font-semibold': diferenciaTiempos < 0, 'text-green-600': diferenciaTiempos > 0 }">Diferencia: {{ fmtTiempo(diferenciaTiempos) }}</span>
            </div>
          </div>
          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="block mb-1 font-medium">Movimiento</label>
              <InputNumber v-model="edicion.tiempo_mov" :min="0" :max-fraction-digits="2" class="w-full" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Espera</label>
              <InputNumber v-model="edicion.tiempo_espera" :min="0" :max-fraction-digits="2" class="w-full" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Cargas</label>
              <InputNumber v-model="edicion.tiempo_carga" :min="0" :max-fraction-digits="2" class="w-full" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Taller</label>
              <InputNumber v-model="edicion.tiempo_taller" :min="0" :max-fraction-digits="2" class="w-full" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Inactivo</label>
              <InputNumber v-model="edicion.tiempo_inactivo" :min="0" :max-fraction-digits="2" class="w-full" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Otras actividades</label>
              <InputNumber v-model="edicion.tiempo_otras_actividades" :min="0" :max-fraction-digits="2" class="w-full" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Total (auto)</label>
              <InputNumber v-model="edicion.tiempo_total" :min="0" :max-fraction-digits="2" class="w-full" />
            </div>
            <div class="flex items-end">
              <span class="text-sm"><b>Suma parcial:</b> {{ fmtTiempo(sumaTiempos) }}</span>
            </div>
          </div>
        </div>
        <div class="col-span-2">
          <label class="block mb-1 font-medium">Notas</label>
          <Textarea v-model="edicion.notas" rows="2" class="w-full" />
        </div>
        <div class="col-span-2">
          <label class="block mb-1 font-medium">Análisis</label>
          <Textarea v-model="edicion.analisis" rows="2" class="w-full" />
        </div>
        <div class="flex gap-2 justify-end col-span-2 mt-2">
          <Button label="Cancelar" severity="secondary" @click="showEdicion = false" />
          <Button label="Guardar" type="submit" icon="pi pi-save" />
        </div>
      </form>
    </Dialog>
  </AppLayout>
</template>

<style scoped>
.hr-card {
  animation: hr-rise 0.45s ease both;
}
@keyframes hr-rise {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
.hr-folio {
  font-variant-numeric: tabular-nums;
}
</style>