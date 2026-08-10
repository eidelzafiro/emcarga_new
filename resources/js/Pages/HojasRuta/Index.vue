<script setup>
import { ref, watch, computed, nextTick } from 'vue'
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
import { useToast } from 'primevue/usetoast'

const props = defineProps({ hojas: Object, catalogos: Object, filters: Object })
const toast = useToast()
const title = 'Hoja de Ruta'
const search = ref(props.filters?.search || '')
const estado = ref(props.filters?.estado || 'todas')
const equipo = ref(props.filters?.equipo || null)
const chofer = ref(props.filters?.chofer || null)
const grupo = ref(props.filters?.grupo || null)

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

const hojasAnteriores = computed(() => (props.catalogos?.hojasAnteriores || []).map(h => ({ ...h, label: `${h.numero} - (${h.tractivo_codigo || '?'})` })))
const tractivosCat = computed(() => props.catalogos?.tractivos || [])
const arrastresCat = computed(() => props.catalogos?.arrastres || [])
const lugaresCat = computed(() => props.catalogos?.lugares || [])

const apertura = ref({ id_hr_anterior: null, numero: '', fecha_emision: '', hora_emision: '', id_tractivo: null, id_arrastre: null, id_chofer: null, id_chofer2: null, id_parqueo: null, id_grupo: null, kms_disponible: null, kms_disponibles_adicionales: null })
const folioInput = ref(null)

const infoTractivo = computed(() => tractivosCat.value.find(t => t.id === apertura.value.id_tractivo) || null)
const infoArrastre = computed(() => arrastresCat.value.find(a => a.id === apertura.value.id_arrastre) || null)
const infoChofer = computed(() => apertura.value.id_chofer ? choferes.find(c => c.id === apertura.value.id_chofer) || null : null)
const infoChofer2 = computed(() => apertura.value.id_chofer2 ? choferes.find(c => c.id === apertura.value.id_chofer2) || null : null)

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
  if (!confirm(`¿Cancelar la Hoja de Ruta ${row.numero}?`)) return
  router.post(route('hojas-ruta.destroy', row.id), { operacion: 'cancelar', _method: 'delete' }, {
    onSuccess: () => toast.add({ severity: 'success', summary: 'Cancelada', life: 3000 }),
    onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
  })
}

function eliminar(row) {
  if (!confirm(`¿Eliminar la Hoja de Ruta ${row.numero}?`)) return
  router.delete(route('hojas-ruta.destroy', row.id), {
    onSuccess: () => toast.add({ severity: 'success', summary: 'Eliminada', life: 3000 }),
    onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
  })
}

function choferNombre(c) { return c ? `${c.nombre} ${c.apellidos || ''}`.trim() : '—' }
function tractivoCodigo(t) { return t ? t.codigo : '—' }
function marcaModelo(v) {
  if (!v) return '—'
  const partes = [v.marca, v.modelo].filter(Boolean)
  return partes.length ? partes.join(' ') : '—'
}
function filaClase(data) {
  if (data.estado === 'cancelada' || data.cancelada) return 'fila-cancelada'
  if (Number(data.cartas_porte_count) > 0) return 'fila-con-cartas'
  return undefined
}
function fmtDiaHora(fecha, hora) {
  if (!fecha) return '—'
  const d = new Date(fecha)
  if (isNaN(d.getTime())) return '—'
  const dia = `${String(d.getDate()).padStart(2, '0')}-${hora || '00:00'}`
  return dia
}
function fmtGrid(v) {
  if (v === null || v === undefined || v === '') return ''
  const n = Number(v)
  if (!Number.isFinite(n) || n <= 0) return ''
  return n.toLocaleString('es-CU', { minimumFractionDigits: 0, maximumFractionDigits: 2 })
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
    <div class="card">
      <Toolbar class="mb-4">
        <template #start>
          <Button label="Nueva Hoja" icon="pi pi-plus" severity="success" @click="openApertura" />
        </template>
        <template #end>
          <div class="flex gap-2 items-center">
            <Select v-model="equipo" :options="tractivosCat" optionLabel="codigo" optionValue="id" filter placeholder="Equipo" class="w-44" :showClear="true" />
            <Select v-model="grupo" :options="catalogos.grupos" optionLabel="nombre" optionValue="id" filter placeholder="Grupo" class="w-40" :showClear="true" />
            <Select v-model="chofer" :options="choferOptions" optionLabel="label" optionValue="id" filter placeholder="Chofer" class="w-48" :showClear="true" />
            <Select v-model="estado" :options="[{ label: 'Todas', value: 'todas' }, { label: 'Abiertas', value: 'abiertas' }, { label: 'Cerradas', value: 'cerradas' }, { label: 'Canceladas', value: 'canceladas' }]" optionLabel="label" optionValue="value" class="w-40" />
            <InputText v-model="search" placeholder="Buscar número, chofer..." class="w-52" />
          </div>
        </template>
      </Toolbar>

      <DataTable :value="hojas.data" stripedRows paginator :rows="20" :total-records="hojas.total" :lazy="true" :first="(hojas.current_page - 1) * hojas.per_page" @page="onPage" paginatorTemplate="FirstPageLink PrevPageLink NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros" size="small" :scrollable="true" scrollHeight="flex" :row-class="filaClase">
        <Column field="numero" header="Código" sortable :style="{ minWidth: '90px' }">
          <template #body="{ data }">
            <span :class="{ 'line-through': data.estado === 'cancelada' }">{{ data.numero }}<template v-if="Number(data.cartas_porte_count) > 0"> ({{ data.cartas_porte_count }})</template></span>
          </template>
        </Column>
        <Column header="Chofer" :style="{ minWidth: '280px' }"><template #body="{ data }">{{ choferNombre(data.chofer) }}</template></Column>
        <Column header="Equipos" :style="{ minWidth: '100px' }"><template #body="{ data }"><div>{{ tractivoCodigo(data.tractivo) }}</div><div class="text-surface-400">{{ data.arrastre?.codigo || '—' }}</div></template></Column>
        <Column header="Fecha emisión" :style="{ minWidth: '120px', whiteSpace: 'nowrap' }"><template #body="{ data }">{{ fmtDiaHora(data.fecha_emision, data.hora_emision) }}</template></Column>
        <Column header="Fecha Cierre" :style="{ minWidth: '120px', whiteSpace: 'nowrap' }"><template #body="{ data }">{{ fmtDiaHora(data.fecha_cierre, data.hora_cierre) }}</template></Column>
        <Column header="Kms" class="text-right"><template #body="{ data }">{{ fmtGrid(data.kms_totales) }}</template></Column>
        <Column header="Consumo" class="text-right"><template #body="{ data }">{{ fmtGrid(data.combustible_consumido) }}</template></Column>
        <Column header="Habilitado" class="text-right"><template #body="{ data }">{{ fmtGrid(data.combustible_habilitado) }}</template></Column>
        <Column header="Dif" class="text-right"><template #body="{ data }">{{ fmtGrid((Number(data.combustible_consumido) || 0) - (Number(data.combustible_habilitado) || 0)) }}</template></Column>
        <Column header="Tiempo" class="text-right"><template #body="{ data }">{{ fmtGrid(data.tiempo_total) }}</template></Column>
        <Column header="Acciones" :style="{ minWidth: '220px', whiteSpace: 'nowrap' }">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button v-if="data.estado === 'abierta' && !data.fecha_cierre" icon="pi pi-check" rounded text severity="success" title="Cerrar" @click="openCierre(data)" />
              <Button v-if="!data.cancelada" icon="pi pi-pencil" rounded text severity="info" title="Editar" @click="openEdicion(data)" />
              <Button v-if="!data.cancelada" icon="pi pi-ban" rounded text severity="warning" title="Cancelar" @click="cancelar(data)" />
              <Button icon="pi pi-trash" rounded text severity="danger" title="Eliminar" @click="eliminar(data)" />
            </div>
          </template>
        </Column>
      </DataTable>
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
            <input v-model="apertura.fecha_emision" type="date" class="w-full border rounded p-2" required />
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
              <Select v-model="apertura.id_tractivo" :options="tractivosCat" optionLabel="codigo" optionValue="id" filter placeholder="Seleccione el tractivo" class="w-full" :showClear="true" />
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
              <Select v-model="apertura.id_chofer" :options="choferOptions" optionLabel="label" optionValue="id" filter placeholder="Seleccione el chofer" class="w-full" :showClear="true" />
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
              <Select v-model="apertura.id_chofer2" :options="choferOptions" optionLabel="label" optionValue="id" filter placeholder="Seleccione el chofer" class="w-full" :showClear="true" />
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
            <input v-model="cierre.fecha_cierre" type="date" class="w-full border rounded p-2" required />
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
              <Select v-model="cierre.id_chofer" :options="choferOptions" optionLabel="label" optionValue="id" filter placeholder="Seleccione el chofer" class="w-full" :showClear="true" />
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
            <input v-model="edicion.fecha_emision" type="date" class="w-full border rounded p-2" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Hora emisión</label>
            <input v-model="edicion.hora_emision" type="time" class="w-full border rounded p-2" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Fecha cierre</label>
            <input v-model="edicion.fecha_cierre" type="date" class="w-full border rounded p-2" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Hora cierre</label>
            <input v-model="edicion.hora_cierre" type="time" class="w-full border rounded p-2" />
          </div>
        </div>
        <div>
          <label class="block mb-1 font-medium">Tractivo</label>
          <Select v-model="edicion.id_tractivo" :options="catalogos.tractivos" optionLabel="codigo" optionValue="id" filter class="w-full" :showClear="true" />
        </div>
        <div>
          <label class="block mb-1 font-medium">Arrastre</label>
          <Select v-model="edicion.id_arrastre" :options="catalogos.arrastres" optionLabel="codigo" optionValue="id" filter class="w-full" :showClear="true" />
        </div>
        <div>
          <label class="block mb-1 font-medium">Chofer</label>
          <Select v-model="edicion.id_chofer" :options="choferes" optionLabel="label" optionValue="id" filter class="w-full" :showClear="true" />
        </div>
        <div>
          <label class="block mb-1 font-medium">Chofer 2</label>
          <Select v-model="edicion.id_chofer2" :options="choferes" optionLabel="label" optionValue="id" filter class="w-full" :showClear="true" />
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
.p-datatable .p-datatable-tbody > tr > td {
  white-space: normal;
}
:deep(.fila-cancelada) {
  color: #dc2626 !important;
}
:deep(.fila-cancelada td) {
  color: #dc2626 !important;
  background-color: #fee2e2 !important;
}
:deep(.fila-con-cartas) td {
  color: #166534 !important;
  background-color: #dcfce7 !important;
}
</style>