<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import InputNumber from 'primevue/inputnumber'
import DatePicker from 'primevue/datepicker'
import ToggleSwitch from 'primevue/toggleswitch'
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import { useConfirm } from 'primevue/useconfirm'
import { formatDate } from '@/Utils/date'

const props = defineProps({ tarjetas: Object, filters: Object, filtros: Object, tiposCombustibles: Array, monedas: Array })
const confirmDialog = useConfirm()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)

const estados = ['activa', 'inactiva', 'cancelada']

const baseForm = () => ({
  numero: '',
  idmonedas: null,
  idtipocombustibles: null,
  idempleado: null,
  idtractivos: null,
  saldo_actual: null,
  fcompra: null,
  fvence: null,
  inactiva: false,
  estado: 'activa',
})

const form = ref(baseForm())

const conSaldos = ref(props.filters?.con_saldos === '1' || props.filters?.con_saldos === true)
const sinSaldos = ref(props.filters?.sin_saldos === '1' || props.filters?.sin_saldos === true)
const bajas = ref(props.filters?.bajas === '1' || props.filters?.bajas === true)

const applyFilters = () => {
  const params = { search: search.value }
  if (props.filters?.id_tipo_combustible) params.id_tipo_combustible = props.filters.id_tipo_combustible
  if (props.filters?.estado) params.estado = props.filters.estado
  if (conSaldos.value) params.con_saldos = '1'
  if (sinSaldos.value) params.sin_saldos = '1'
  if (bajas.value) params.bajas = '1'
  router.get(route('tarjetas.index'), params, { preserveState: true, replace: true })
}

const buscar = () => {
  clearTimeout(window.__tarjetasSearchTimer)
  window.__tarjetasSearchTimer = setTimeout(applyFilters, 300)
}

const onFilterChange = () => {
  applyFilters()
}

const grupos = computed(() => {
  const map = {}
  for (const t of props.tarjetas) {
    const key = t.tipoCombustible?.nombre || 'Sin tipo'
    if (!map[key]) {
      map[key] = { nombre: key, items: [] }
    }
    map[key].items.push(t)
  }
  return Object.values(map).sort((a, b) => a.nombre.localeCompare(b.nombre))
})

function openCreate() {
  editing.value = null
  form.value = baseForm()
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  form.value = {
    numero: item.numero ?? '',
    idmonedas: item.idmonedas,
    idtipocombustibles: item.idtipocombustibles,
    idempleado: item.idempleado,
    idtractivos: item.idtractivos,
    saldo_actual: Number(item.saldo_actual),
    fcompra: item.fcompra ? new Date(item.fcompra) : null,
    fvence: item.fvence ? new Date(item.fvence) : null,
    inactiva: Boolean(item.inactiva),
    estado: item.estado || 'activa',
  }
  showForm.value = true
}

function submit() {
  const payload = { ...form.value }
  const url = editing.value ? route('tarjetas.update', { tarjeta: editing.value.id }) : route('tarjetas.store')
  router[editing.value ? 'put' : 'post'](url, payload, {
    onSuccess: () => { showForm.value = false },
  })
}

function destroy(item) {
  confirmDialog.require({
    message: `¿Eliminar la tarjeta ${item.numero}?`,
    header: 'Eliminar Tarjeta',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Eliminar',
    rejectLabel: 'Volver',
    acceptClass: 'p-button-danger',
    accept: () => router.delete(route('tarjetas.destroy', { tarjeta: item.id })),
  })
}

const fmt = (n) => n != null ? Number(n).toLocaleString('es-CU', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00'

const estadoSeverity = (e) => ({ activa: 'success', inactiva: 'warn', cancelada: 'danger' }[e] || 'secondary')

function iniciales(nombre) {
  return String(nombre || '')
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map(w => w[0])
    .join('')
    .toUpperCase() || '?'
}

function isVencida(t) {
  if (!t.fvence) return false
  return new Date(t.fvence) < new Date()
}

function isBaja(t) {
  return t.estado === 'inactiva' || t.estado === 'cancelada'
}
</script>

<template>
  <AppLayout>
    <Toolbar class="mb-4">
      <template #start>
        <h2 class="text-xl font-bold m-0">Tarjetas de Combustible</h2>
        <Tag :value="`${props.tarjetas.length} tarjeta(s)`" class="ml-3" />
      </template>
      <template #end>
        <div class="flex flex-wrap items-center gap-2">
          <Select
            v-model="filters.id_tipo_combustible"
            :options="[{ id: null, nombre: 'Todos' }, ...tiposCombustibles]"
            optionLabel="nombre" optionValue="id"
            placeholder="Tipo combustible" showClear class="w-44"
            @change="onFilterChange"
          />
          <Select
            v-model="filters.estado"
            :options="[{ value: null, label: 'Todos' }, ...estados.map(e => ({ value: e, label: e }))]"
            optionLabel="label" optionValue="value"
            placeholder="Estado" showClear class="w-40"
            @change="onFilterChange"
          />
          <div class="flex items-center gap-1 border border-gray-300 dark:border-gray-600 rounded px-2 py-1">
            <ToggleSwitch v-model="conSaldos" inputId="conSaldos" @change="sinSaldos = false; onFilterChange()" class="scale-75" />
            <label for="conSaldos" class="text-xs whitespace-nowrap cursor-pointer">Con saldos</label>
          </div>
          <div class="flex items-center gap-1 border border-gray-300 dark:border-gray-600 rounded px-2 py-1">
            <ToggleSwitch v-model="sinSaldos" inputId="sinSaldos" @change="conSaldos = false; onFilterChange()" class="scale-75" />
            <label for="sinSaldos" class="text-xs whitespace-nowrap cursor-pointer">Sin saldos</label>
          </div>
          <div class="flex items-center gap-1 border border-gray-300 dark:border-gray-600 rounded px-2 py-1">
            <ToggleSwitch v-model="bajas" inputId="bajas" @change="onFilterChange()" class="scale-75" />
            <label for="bajas" class="text-xs whitespace-nowrap cursor-pointer">Bajas</label>
          </div>
          <InputText v-model="search" placeholder="Buscar número/empleado..." class="w-56" @input="buscar" />
          <Button icon="pi pi-plus" label="Nueva" @click="openCreate" />
        </div>
      </template>
    </Toolbar>

    <div v-if="grupos.length === 0" class="text-center py-16 text-gray-400">
      <i class="pi pi-wallet text-5xl mb-4 block" />
      <p class="text-lg">No se encontraron tarjetas.</p>
    </div>

    <div v-for="grupo in grupos" :key="grupo.nombre" class="mb-8">
      <div class="flex items-center gap-3 mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">
        <i class="pi pi-directions text-2xl text-primary" />
        <h2 class="text-lg font-semibold">{{ grupo.nombre }}</h2>
        <Tag :value="`${grupo.items.length} tarjeta(s)`" severity="info" />
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <article
          v-for="t in grupo.items"
          :key="t.id"
          class="cp-card relative flex flex-col overflow-hidden rounded-2xl border bg-white dark:bg-gray-800 shadow-sm transition-shadow hover:shadow-lg"
          :class="isBaja(t) ? 'border-red-300 dark:border-red-800/60 opacity-80' : isVencida(t) ? 'border-amber-300 dark:border-amber-700' : 'border-gray-200 dark:border-gray-700'"
        >
          <!-- Sello de estado -->
          <div v-if="isBaja(t)" class="pointer-events-none absolute inset-0 z-10 flex items-center justify-center">
            <span class="rotate-[-14deg] border-[3px] border-red-500/70 text-red-500/80 dark:border-red-400/70 dark:text-red-300/80 rounded-lg px-4 py-1 text-xl font-black uppercase tracking-[0.22em]">
              {{ t.estado === 'cancelada' ? 'CANCELADA' : 'BAJA' }}
            </span>
          </div>
          <div v-else-if="isVencida(t)" class="pointer-events-none absolute inset-0 z-10 flex items-center justify-center">
            <span class="rotate-[-14deg] border-[3px] border-amber-500/70 text-amber-500/80 dark:border-amber-400/70 dark:text-amber-300/80 rounded-lg px-4 py-1 text-xl font-black uppercase tracking-[0.22em]">
              VENCIDA
            </span>
          </div>

          <!-- Cabecera -->
          <header class="relative px-4 pt-3 pb-2.5 border-b border-gray-100 dark:border-gray-700/70"
            :class="isBaja(t) ? 'bg-red-50/60 dark:bg-red-950/20' : isVencida(t) ? 'bg-amber-50/60 dark:bg-amber-950/20' : 'bg-gradient-to-br from-amber-50/80 to-white dark:from-amber-950/20 dark:to-gray-800'">
            <div class="flex items-start justify-between">
              <div class="min-w-0 flex-1">
                <span class="block text-[10px] font-bold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">Número</span>
                <div class="cp-folio mt-1 text-[22px] font-black leading-none tracking-tight"
                  :class="isBaja(t) ? 'text-red-500 dark:text-red-400 line-through' : 'text-amber-800 dark:text-amber-300'">
                  {{ t.numero }}
                </div>
              </div>
              <Tag :value="t.estado" :severity="estadoSeverity(t.estado)" />
            </div>
          </header>

          <!-- Cuerpo -->
          <div class="flex flex-1 flex-col gap-2.5 px-4 py-3">
            <div v-if="t.empleado" class="flex items-center gap-2 min-w-0">
              <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-amber-100 text-[11px] font-bold text-amber-700 dark:bg-amber-500/20 dark:text-amber-300">
                {{ iniciales(`${t.empleado?.nombre} ${t.empleado?.apellidos || ''}`) }}
              </span>
              <div class="min-w-0">
                <div class="truncate text-sm font-semibold text-gray-800 dark:text-gray-100">{{ t.empleado?.nombre }} {{ t.empleado?.apellidos }}</div>
              </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
              <span v-if="t.tractivo" class="inline-flex items-center gap-1.5 rounded-xl border border-amber-200 dark:border-amber-700/50 bg-amber-50 dark:bg-amber-950/30 px-3 py-1.5">
                <i class="pi pi-truck text-sm" style="color:#d97706" />
                <span class="text-sm font-bold tracking-tight text-amber-800 dark:text-amber-300">{{ t.tractivo.codigo }}</span>
              </span>
              <span v-if="t.entidad" class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-750 px-3 py-1.5">
                <i class="pi pi-building text-sm text-gray-400" />
                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ t.entidad.abreviatura }}</span>
              </span>
            </div>

            <div class="text-sm space-y-1.5 flex-1">
              <div class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                <i class="pi pi-wallet text-xs text-gray-400" />
                <span class="font-semibold">{{ fmt(t.saldo_actual) }} {{ t.moneda?.codigo || '' }}</span>
              </div>
              <div class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                <i class="pi pi-inbox text-xs text-gray-400" />
                <span>{{ fmt(t.saldoactuallts) }} LTS</span>
              </div>
              <div class="flex items-center gap-2 text-xs" :class="t.fcompra ? 'text-gray-500 dark:text-gray-400' : 'text-gray-400 dark:text-gray-500'">
                <i class="pi pi-calendar text-[10px]" />
                <span>Compra: {{ formatDate(t.fcompra) || '—' }}</span>
              </div>
              <div class="flex items-center gap-2 text-xs" :class="isVencida(t) ? 'text-red-600 font-bold' : 'text-gray-500 dark:text-gray-400'">
                <i class="pi pi-clock text-[10px]" />
                <span>Vence: {{ formatDate(t.fvence) || '—' }}</span>
                <Tag v-if="isVencida(t)" value="VENCIDA" severity="danger" class="text-xs" />
              </div>
              <div v-if="t.fcierre" class="flex items-center gap-2 text-xs text-gray-400 dark:text-gray-500">
                <i class="pi pi-calendar text-[10px]" />
                <span>Cierre: {{ formatDate(t.fcierre) }}</span>
              </div>
            </div>
          </div>

          <!-- Pie: acciones -->
          <div class="mt-auto flex items-center justify-end gap-1 border-t border-gray-100 dark:border-gray-700/70 px-3 py-2 bg-gray-50/80 dark:bg-gray-700/30">
            <Button icon="pi pi-pencil" rounded text severity="info" size="small" @click="openEdit(t)" v-tooltip.top="'Editar'" />
            <Button icon="pi pi-trash" rounded text severity="danger" size="small" @click="destroy(t)" v-tooltip.top="'Eliminar'" />
          </div>
        </article>
      </div>
    </div>

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar Tarjeta' : 'Nueva Tarjeta'" modal style="width: 580px">
      <form @submit.prevent="submit" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block mb-1 font-medium">Número</label>
            <InputText v-model="form.numero" class="w-full" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Tipo de Combustible</label>
            <Select v-model="form.idtipocombustibles" :options="tiposCombustibles" optionLabel="nombre" optionValue="id" placeholder="Seleccione..." class="w-full" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Moneda</label>
            <Select v-model="form.idmonedas" :options="monedas" optionLabel="codigo" optionValue="id" placeholder="Seleccione..." class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Empleado</label>
            <Select v-model="form.idempleado" :options="filtros.empleados" optionLabel="nombre" optionValue="id" placeholder="Seleccione..." class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Tractivo</label>
            <Select v-model="form.idtractivos" :options="filtros.tractivos" optionLabel="codigo" optionValue="id" placeholder="Seleccione..." class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Saldo Actual (Dinero)</label>
            <InputNumber v-model="form.saldo_actual" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Fecha Compra</label>
            <DatePicker v-model="form.fcompra" dateFormat="dd/mm/yy" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Fecha Vencimiento</label>
            <DatePicker v-model="form.fvence" dateFormat="dd/mm/yy" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Estado</label>
            <Select v-model="form.estado" :options="estados.map(e => ({ label: e, value: e }))" optionLabel="label" optionValue="value" class="w-full" />
          </div>
          <div class="flex items-center gap-2">
            <ToggleSwitch v-model="form.inactiva" />
            <label class="font-medium">Inactiva</label>
          </div>
        </div>
        <div class="flex gap-2 justify-end">
          <Button label="Cancelar" severity="secondary" @click="showForm = false" />
          <Button label="Guardar" type="submit" icon="pi pi-save" />
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
