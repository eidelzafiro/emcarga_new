<template>
  <AppLayout :title="title">
    <div class="space-y-6">
      <!-- Encabezado -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Dashboard de Operativos</h1>
          <p class="text-gray-500 dark:text-gray-400 text-sm mt-1 flex items-center gap-2">
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300">
              Operativos
            </span>
            {{ entidadNombre }}
          </p>
        </div>
        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">
          {{ mesLabel }}
        </span>
      </div>

      <!-- KPIs Totales -->
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="kpi-card dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-start justify-between">
            <div class="min-w-0">
              <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Hojas de Ruta</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1.5">{{ formatNumber(totales.hr_del_mes) }}</p>
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ formatNumber(totales.kms_hr, 0) }} km recorridos</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-amber-500 flex items-center justify-center shrink-0 ml-3">
              <i class="pi pi-truck text-white text-lg" />
            </div>
          </div>
        </div>
        <div class="kpi-card dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-start justify-between">
            <div class="min-w-0">
              <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cartas de Porte</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1.5">{{ formatNumber(totales.cp_del_mes) }}</p>
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Emitidas en el mes</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-blue-500 flex items-center justify-center shrink-0 ml-3">
              <i class="pi pi-file text-white text-lg" />
            </div>
          </div>
        </div>
        <div class="kpi-card dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-start justify-between">
            <div class="min-w-0">
              <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Solicitudes</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1.5">{{ formatNumber(totales.solicitudes_del_mes) }}</p>
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Del mes de operaciones</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-violet-500 flex items-center justify-center shrink-0 ml-3">
              <i class="pi pi-clipboard text-white text-lg" />
            </div>
          </div>
        </div>
        <div class="kpi-card dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-start justify-between">
            <div class="min-w-0">
              <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Combustible</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1.5">{{ formatNumber(totales.combustible_lts, 0) }} <span class="text-sm font-normal text-gray-400">L</span></p>
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ formatNumber(totales.combustible_descargas) }} descargas · {{ formatNumber(totales.combustible_mon, 2) }} MN</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-emerald-500 flex items-center justify-center shrink-0 ml-3">
              <i class="pi pi-inbox text-white text-lg" />
            </div>
          </div>
        </div>
        <div class="kpi-card dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-start justify-between">
            <div class="min-w-0">
              <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Flota Total</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1.5">{{ formatNumber(totales.flota) }}</p>
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Tractivos en la entidad</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-gray-500 flex items-center justify-center shrink-0 ml-3">
              <i class="pi pi-car text-white text-lg" />
            </div>
          </div>
        </div>
        <div class="kpi-card dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-start justify-between">
            <div class="min-w-0">
              <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Flota Activa</p>
              <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1.5">{{ formatNumber(totales.flota_activa) }}</p>
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Estado ACTIVO</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-emerald-500 flex items-center justify-center shrink-0 ml-3">
              <i class="pi pi-check-circle text-white text-lg" />
            </div>
          </div>
        </div>
        <div class="kpi-card dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-start justify-between">
            <div class="min-w-0">
              <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">En Taller</p>
              <p class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1.5">{{ formatNumber(totales.flota_taller) }}</p>
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Estado EN TALLER</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-amber-500 flex items-center justify-center shrink-0 ml-3">
              <i class="pi pi-wrench text-white text-lg" />
            </div>
          </div>
        </div>
        <div class="kpi-card dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-start justify-between">
            <div class="min-w-0">
              <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Variación Combustible</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1.5">{{ variacionCombustible }}</p>
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">vs mes anterior</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-cyan-500 flex items-center justify-center shrink-0 ml-3">
              <i class="pi pi-chart-line text-white text-lg" />
            </div>
          </div>
        </div>
      </div>

      <!-- ═══════════════════ SECCIÓN 1: ESTADO DE OPERACIONES ═══════════════════ -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-amber-500 flex items-center justify-center">
            <i class="pi pi-clipboard text-white text-sm" />
          </div>
          <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Estado de Operaciones — {{ mesLabel }}</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">Hojas de ruta, cartas de porte y solicitudes por estado</p>
          </div>
        </div>

        <div class="p-5 grid grid-cols-1 md:grid-cols-3 gap-6">
          <div>
            <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Hojas de Ruta</h4>
            <div class="space-y-2">
              <div v-for="fila in hrPorEstado" :key="fila.estado" class="flex items-center justify-between px-3 py-2 rounded-lg bg-gray-50 dark:bg-gray-700/60">
                <span class="text-sm text-gray-600 dark:text-gray-300">{{ fila.etiqueta }}</span>
                <span class="text-sm font-semibold font-mono text-gray-900 dark:text-gray-100">{{ formatNumber(fila.total) }}</span>
              </div>
              <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-amber-50 dark:bg-amber-900/30">
                <span class="text-sm font-semibold text-amber-700 dark:text-amber-300">Total</span>
                <span class="text-sm font-bold font-mono text-amber-700 dark:text-amber-300">{{ formatNumber(totales.hr_del_mes) }}</span>
              </div>
            </div>
          </div>
          <div>
            <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Cartas de Porte</h4>
            <div class="space-y-2">
              <div v-for="fila in cpPorEstado" :key="fila.estado" class="flex items-center justify-between px-3 py-2 rounded-lg bg-gray-50 dark:bg-gray-700/60">
                <span class="text-sm text-gray-600 dark:text-gray-300">{{ fila.etiqueta }}</span>
                <span class="text-sm font-semibold font-mono text-gray-900 dark:text-gray-100">{{ formatNumber(fila.total) }}</span>
              </div>
              <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-blue-50 dark:bg-blue-900/30">
                <span class="text-sm font-semibold text-blue-700 dark:text-blue-300">Total</span>
                <span class="text-sm font-bold font-mono text-blue-700 dark:text-blue-300">{{ formatNumber(totales.cp_del_mes) }}</span>
              </div>
            </div>
          </div>
          <div>
            <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Solicitudes</h4>
            <div class="space-y-2">
              <div v-for="fila in solicitudesPorEstado" :key="fila.estado" class="flex items-center justify-between px-3 py-2 rounded-lg bg-gray-50 dark:bg-gray-700/60">
                <span class="text-sm text-gray-600 dark:text-gray-300">{{ fila.etiqueta }}</span>
                <span class="text-sm font-semibold font-mono text-gray-900 dark:text-gray-100">{{ formatNumber(fila.total) }}</span>
              </div>
              <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-violet-50 dark:bg-violet-900/30">
                <span class="text-sm font-semibold text-violet-700 dark:text-violet-300">Total</span>
                <span class="text-sm font-bold font-mono text-violet-700 dark:text-violet-300">{{ formatNumber(totales.solicitudes_del_mes) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══════════════════ SECCIÓN 2: FLOTA POR ESTADO ═══════════════════ -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-gray-500 flex items-center justify-center">
            <i class="pi pi-car text-white text-sm" />
          </div>
          <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Tablero de Flota</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">Tractivos por estado técnico</p>
          </div>
        </div>

        <div class="p-5">
          <div v-if="flotaPorEstado.length" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-4">
            <div
              v-for="fila in flotaPorEstado"
              :key="fila.estado"
              class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 flex items-center justify-between"
            >
              <div class="min-w-0">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide truncate">{{ fila.estado }}</p>
                <p class="text-xl font-bold font-mono text-gray-900 dark:text-gray-100 mt-1">{{ formatNumber(fila.total) }}</p>
              </div>
              <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 ml-3" :class="colorEstado(fila.estado)">
                <i class="pi pi-circle-fill text-white text-sm" />
              </div>
            </div>
          </div>
          <p v-else class="text-sm text-gray-500 dark:text-gray-400">No hay tractivos registrados en la entidad.</p>
        </div>
      </div>

      <!-- ═══════════════════ SECCIÓN 3: ACTIVIDAD DIARIA ═══════════════════ -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-blue-500 flex items-center justify-center">
            <i class="pi pi-chart-bar text-white text-sm" />
          </div>
          <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Actividad Diaria — Hojas de Ruta</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">HR emitidas por día del mes</p>
          </div>
        </div>

        <div class="p-5">
          <div class="flex items-end gap-1.5 h-32 overflow-x-auto pb-1">
            <div v-for="dia in serie" :key="dia.fecha" class="flex flex-col items-center justify-end min-w-6 flex-1" :title="dia.etiqueta + ': ' + dia.hr + ' HR'">
              <div
                class="w-full rounded-t bg-blue-500 hover:bg-blue-600 transition-colors"
                :style="{ height: barraAltura(dia.hr) }"
              ></div>
            </div>
          </div>
          <div class="flex justify-between text-[10px] text-gray-400 dark:text-gray-500 mt-1 overflow-x-auto">
            <span>{{ serie[0]?.etiqueta }}</span>
            <span>{{ serie[serie.length - 1]?.etiqueta }}</span>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  title: { type: String, default: 'Dashboard Operativos' },
  fechaOperaciones: { type: String, default: '' },
  entidadNombre: { type: String, default: '' },
  entidad: { type: Object, default: null },

  totales: {
    type: Object,
    default: () => ({
      hr_del_mes: 0, cp_del_mes: 0, solicitudes_del_mes: 0,
      combustible_lts: 0, combustible_mon: 0, combustible_descargas: 0,
      combustible_lts_anterior: 0, kms_hr: 0,
      flota: 0, flota_taller: 0, flota_activa: 0,
    }),
  },
  hrPorEstado: { type: Array, default: () => [] },
  cpPorEstado: { type: Array, default: () => [] },
  solicitudesPorEstado: { type: Array, default: () => [] },
  flotaPorEstado: { type: Array, default: () => [] },
  serie: { type: Array, default: () => [] },
});

const mesLabel = computed(() => {
  if (!props.fechaOperaciones) return '';
  const d = new Date(props.fechaOperaciones + 'T00:00:00');
  return d.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' });
});

const variacionCombustible = computed(() => {
  const actual = Number(props.totales.combustible_lts) || 0;
  const anterior = Number(props.totales.combustible_lts_anterior) || 0;
  if (!anterior) return '—';
  const pct = ((actual - anterior) / anterior) * 100;
  return (pct > 0 ? '+' : '') + pct.toFixed(1) + '%';
});

function formatNumber(valor, decimales = 0) {
  const num = Number(valor) || 0;
  return num.toLocaleString('es-CU', {
    minimumFractionDigits: decimales,
    maximumFractionDigits: decimales,
  });
}

function colorEstado(estado) {
  const e = String(estado).toLowerCase();
  if (e.includes('activo') || e.includes('buen')) return 'bg-emerald-500';
  if (e.includes('taller') || e.includes('trabajando') || e.includes('paraliz')) return 'bg-amber-500';
  if (e.includes('malo') || e.includes('baja') || e.includes('regrab')) return 'bg-red-500';
  if (e.includes('nuevo') || e.includes('reconstru')) return 'bg-cyan-500';
  return 'bg-gray-400';
}

function barraAltura(valor) {
  const max = Math.max(...props.serie.map((d) => d.hr), 1);
  const ratio = Math.min((Number(valor) || 0) / max, 1);
  return (8 + ratio * 96) + 'px';
}
</script>