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
        <div class="flex items-center gap-2">
          <span class="px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300">
            {{ diaLabel }}
          </span>
          <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">
            {{ mesLabel }}
          </span>
        </div>
      </div>

      <!-- KPIs Totales -->
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="kpi-card dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-start justify-between">
            <div class="min-w-0">
              <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Hojas de Ruta</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1.5">{{ formatNumber(totales.hr_del_mes) }}</p>
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ formatNumber(totales.hr_emitidas_dia) }} emitidas hoy · {{ formatNumber(totales.kms_hr, 0) }} km</p>
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
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ formatNumber(totales.cp_emitidas_dia) }} emitidas hoy</p>
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
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ formatNumber(totales.solicitudes_dia) }} nuevas · {{ formatNumber(totales.solicitudes_cumplidas_dia) }} cumplidas hoy</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-violet-500 flex items-center justify-center shrink-0 ml-3">
              <i class="pi pi-clipboard text-white text-lg" />
            </div>
          </div>
        </div>
        <div class="kpi-card dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-start justify-between">
            <div class="min-w-0">
              <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Flota Activa</p>
              <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1.5">{{ formatNumber(totales.flota_activa) }}</p>
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ formatNumber(totales.flota) }} en total · {{ formatNumber(totales.flota_taller) }} en taller</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-emerald-500 flex items-center justify-center shrink-0 ml-3">
              <i class="pi pi-car text-white text-lg" />
            </div>
          </div>
        </div>
      </div>

      <!-- ═══════════════════ PANEL 1: DOCUMENTOS DEL DÍA ═══════════════════ -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-amber-500 flex items-center justify-center">
            <i class="pi pi-file text-white text-sm" />
          </div>
          <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Documentos del día — {{ diaLabel }}</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">Cartas de porte y hojas de ruta emitidas, recepcionadas y cerradas hoy</p>
          </div>
        </div>

        <div class="p-5 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">
          <div>
            <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">CP emitidas · por cliente</h4>
            <div class="space-y-2">
              <div v-for="fila in documentosDia.cpEmitidasPorCliente" :key="'cpe-' + fila.cliente" class="flex items-center justify-between px-3 py-2 rounded-lg bg-gray-50 dark:bg-gray-700/60">
                <span class="text-sm text-gray-600 dark:text-gray-300 truncate">{{ fila.cliente }}</span>
                <span class="text-sm font-semibold font-mono text-gray-900 dark:text-gray-100 ml-2">{{ formatNumber(fila.cantidad) }}</span>
              </div>
              <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-blue-50 dark:bg-blue-900/30">
                <span class="text-sm font-semibold text-blue-700 dark:text-blue-300">Total</span>
                <span class="text-sm font-bold font-mono text-blue-700 dark:text-blue-300">{{ formatNumber(totalDocumentos.cpEmitidas) }}</span>
              </div>
            </div>
          </div>

          <div>
            <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">CP recepcionadas · por chofer</h4>
            <div class="space-y-2">
              <div v-for="fila in documentosDia.cpRecepcionadasPorChofer" :key="'cpr-' + fila.chofer" class="flex items-center justify-between px-3 py-2 rounded-lg bg-gray-50 dark:bg-gray-700/60">
                <span class="text-sm text-gray-600 dark:text-gray-300 truncate">{{ fila.chofer }}</span>
                <span class="text-sm font-semibold font-mono text-gray-900 dark:text-gray-100 ml-2">{{ formatNumber(fila.cantidad) }}</span>
              </div>
              <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-emerald-50 dark:bg-emerald-900/30">
                <span class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">Total</span>
                <span class="text-sm font-bold font-mono text-emerald-700 dark:text-emerald-300">{{ formatNumber(totalDocumentos.cpRecepcionadas) }}</span>
              </div>
            </div>
          </div>

          <div>
            <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">HR emitidas · por chofer</h4>
            <div class="space-y-2">
              <div v-for="fila in documentosDia.hrEmitidasPorChofer" :key="'hre-' + fila.chofer" class="flex items-center justify-between px-3 py-2 rounded-lg bg-gray-50 dark:bg-gray-700/60">
                <span class="text-sm text-gray-600 dark:text-gray-300 truncate">{{ fila.chofer }}</span>
                <span class="text-sm font-semibold font-mono text-gray-900 dark:text-gray-100 ml-2">{{ formatNumber(fila.cantidad) }}</span>
              </div>
              <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-amber-50 dark:bg-amber-900/30">
                <span class="text-sm font-semibold text-amber-700 dark:text-amber-300">Total</span>
                <span class="text-sm font-bold font-mono text-amber-700 dark:text-amber-300">{{ formatNumber(totalDocumentos.hrEmitidas) }}</span>
              </div>
            </div>
          </div>

          <div>
            <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">HR cerradas · por chofer</h4>
            <div class="space-y-2">
              <div v-for="fila in documentosDia.hrCerradasPorChofer" :key="'hrc-' + fila.chofer" class="flex items-center justify-between px-3 py-2 rounded-lg bg-gray-50 dark:bg-gray-700/60">
                <span class="text-sm text-gray-600 dark:text-gray-300 truncate">{{ fila.chofer }}</span>
                <span class="text-sm font-semibold font-mono text-gray-900 dark:text-gray-100 ml-2">{{ formatNumber(fila.cantidad) }}</span>
              </div>
              <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-gray-100 dark:bg-gray-700/80">
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">Total</span>
                <span class="text-sm font-bold font-mono text-gray-700 dark:text-gray-200">{{ formatNumber(totalDocumentos.hrCerradas) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══════════════════ PANEL 2: SOLICITUDES ═══════════════════ -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-violet-500 flex items-center justify-center">
            <i class="pi pi-clipboard text-white text-sm" />
          </div>
          <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Solicitudes — {{ diaLabel }}</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">Nuevas y cumplidas en el día</p>
          </div>
        </div>

        <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-5">
          <div class="space-y-3">
            <div class="flex items-center justify-between px-4 py-3 rounded-lg bg-violet-50 dark:bg-violet-900/30 border border-violet-100 dark:border-violet-900/50">
              <div>
                <p class="text-xs font-semibold text-violet-700 dark:text-violet-300 uppercase tracking-wider">Nuevas del día</p>
                <p class="text-xs text-violet-500 dark:text-violet-400 mt-0.5">Solicitudes registradas hoy</p>
              </div>
              <span class="text-2xl font-bold font-mono text-violet-700 dark:text-violet-300">{{ formatNumber(solicitudesDia.nuevas) }}</span>
            </div>
            <div class="flex items-center justify-between px-4 py-3 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-100 dark:border-emerald-900/50">
              <div>
                <p class="text-xs font-semibold text-emerald-700 dark:text-emerald-300 uppercase tracking-wider">Cumplidas del día</p>
                <p class="text-xs text-emerald-500 dark:text-emerald-400 mt-0.5">Estado ejecutada</p>
              </div>
              <span class="text-2xl font-bold font-mono text-emerald-700 dark:text-emerald-300">{{ formatNumber(solicitudesDia.cumplidas) }}</span>
            </div>
          </div>

          <div>
            <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Cumplidas · por cliente</h4>
            <div class="space-y-2">
              <div v-for="fila in solicitudesDia.cumplidasPorCliente" :key="'sol-' + fila.cliente" class="flex items-center justify-between px-3 py-2 rounded-lg bg-gray-50 dark:bg-gray-700/60">
                <span class="text-sm text-gray-600 dark:text-gray-300 truncate">{{ fila.cliente }}</span>
                <span class="text-sm font-semibold font-mono text-gray-900 dark:text-gray-100 ml-2">{{ formatNumber(fila.cantidad) }}</span>
              </div>
              <div v-if="!solicitudesDia.cumplidasPorCliente.length" class="text-center py-6">
                <i class="pi pi-inbox text-2xl text-gray-300 dark:text-gray-600 block mb-1" />
                <p class="text-sm text-gray-400 dark:text-gray-500">Sin solicitudes cumplidas hoy</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══════════════════ PANEL 3: TABLERO DE FLOTA ═══════════════════ -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-gray-500 flex items-center justify-center">
              <i class="pi pi-car text-white text-sm" />
            </div>
            <div>
              <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Tablero de Flota</h3>
              <p class="text-xs text-gray-500 dark:text-gray-400">Vehículos por tipo de equipo</p>
            </div>
          </div>
          <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
            <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500" /> Activo</span>
            <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-500" /> En taller (OT abierta)</span>
          </div>
        </div>

        <div class="p-5">
          <div v-if="flotaPorTipo.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-4">
            <div v-for="tipo in flotaPorTipo" :key="tipo.nombre" class="rounded-xl border-2 overflow-hidden bg-white dark:bg-gray-800 shadow-sm">
              <!-- Header tipo de equipo -->
              <div class="p-4 flex items-center gap-3 border-b border-gray-100 dark:border-gray-700"
                   :class="tipo.tallerCount > 0 ? 'bg-gradient-to-r from-amber-50 to-white dark:from-amber-900/20 dark:to-gray-800' : 'bg-gradient-to-r from-emerald-50 to-white dark:from-emerald-900/20 dark:to-gray-800'">
                <div class="w-12 h-12 rounded-full flex items-center justify-center shrink-0"
                     :class="tipo.tallerCount > 0 ? 'bg-amber-100 dark:bg-amber-900/40' : 'bg-emerald-100 dark:bg-emerald-900/40'">
                  <i :class="tipo.esArrastre ? 'pi pi-box' : 'pi pi-truck'" class="text-lg"
                     :style="{ color: tipo.tallerCount > 0 ? '#d97706' : '#059669' }" />
                </div>
                <div class="flex-1 min-w-0">
                  <h3 class="font-bold text-gray-900 dark:text-gray-100 truncate">{{ tipo.nombre }}</h3>
                  <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ tipo.activosCount }} activo{{ tipo.activosCount !== 1 ? 's' : '' }}
                    <span v-if="tipo.tallerCount > 0" class="text-amber-600 dark:text-amber-400"> · {{ tipo.tallerCount }} en taller</span>
                  </p>
                </div>
                <span class="text-2xl font-extrabold" :class="tipo.tallerCount > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400'">
                  {{ tipo.total }}
                </span>
              </div>
              <!-- Lista de vehículos -->
              <div class="divide-y divide-gray-100 dark:divide-gray-700 max-h-[400px] overflow-y-auto">
                <template v-if="tipo.activos.length">
                  <div v-for="v in tipo.activos" :key="v.id" class="px-4 py-2.5 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/80 transition">
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-mono font-bold text-gray-900 dark:text-gray-100">{{ v.codigo }}</p>
                      <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ v.marca }}</p>
                    </div>
                    <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0 ml-2" />
                  </div>
                </template>
                <template v-if="tipo.taller.length">
                  <div v-for="v in tipo.taller" :key="v.id" class="px-4 py-2.5 flex items-center justify-between bg-amber-50/50 dark:bg-amber-900/10 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition">
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-mono font-bold text-gray-900 dark:text-gray-100">{{ v.codigo }}</p>
                      <p class="text-xs text-amber-600 dark:text-amber-400 truncate">
                        <i class="pi pi-wrench mr-1" />OT {{ v.ot?.numero ?? '—' }} · {{ v.ot?.dias ?? '' }} días
                      </p>
                    </div>
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse shrink-0 ml-2" />
                  </div>
                </template>
                <div v-if="!tipo.activos.length && !tipo.taller.length" class="px-4 py-3 text-center text-xs text-gray-400 dark:text-gray-500">
                  Sin vehículos
                </div>
              </div>
            </div>
          </div>
          <p v-else class="text-xs text-gray-400 dark:text-gray-500 py-6 text-center">Sin vehículos en la flota</p>
        </div>
      </div>

      <!-- ═══════════════════ PANEL 4: ACTIVIDAD DIARIA ═══════════════════ -->
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
import { computed, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  title: { type: String, default: 'Dashboard Operativos' },
  fechaOperaciones: { type: String, default: '' },
  diaOperaciones: { type: String, default: '' },
  entidadNombre: { type: String, default: '' },
  entidad: { type: Object, default: null },

  totales: {
    type: Object,
    default: () => ({
      hr_del_mes: 0, cp_del_mes: 0, solicitudes_del_mes: 0,
      combustible_lts: 0, combustible_mon: 0, combustible_descargas: 0,
      combustible_lts_anterior: 0, kms_hr: 0,
      flota: 0, flota_taller: 0, flota_activa: 0,
      cp_emitidas_dia: 0, hr_emitidas_dia: 0,
      solicitudes_dia: 0, solicitudes_cumplidas_dia: 0,
    }),
  },
  hrPorEstado: { type: Array, default: () => [] },
  cpPorEstado: { type: Array, default: () => [] },
  solicitudesPorEstado: { type: Array, default: () => [] },
  flotaPorEstado: { type: Array, default: () => [] },
  serie: { type: Array, default: () => [] },
  documentosDia: {
    type: Object,
    default: () => ({
      dia: '', cpEmitidasPorCliente: [], cpRecepcionadasPorChofer: [],
      hrEmitidasPorChofer: [], hrCerradasPorChofer: [],
    }),
  },
  solicitudesDia: {
    type: Object,
    default: () => ({ dia: '', nuevas: 0, cumplidas: 0, cumplidasPorCliente: [] }),
  },
  flotaPorTipo: { type: Array, default: () => [] },
});

const mesLabel = computed(() => {
  if (!props.fechaOperaciones) return '';
  const d = new Date(props.fechaOperaciones + 'T00:00:00');
  return d.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' });
});

const diaLabel = computed(() => {
  const base = props.diaOperaciones || props.fechaOperaciones;
  if (!base) return '';
  const d = new Date(base + 'T00:00:00');
  return d.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
});

const totalDocumentos = computed(() => ({
  cpEmitidas: (props.documentosDia.cpEmitidasPorCliente || []).reduce((a, f) => a + (f.cantidad || 0), 0),
  cpRecepcionadas: (props.documentosDia.cpRecepcionadasPorChofer || []).reduce((a, f) => a + (f.cantidad || 0), 0),
  hrEmitidas: (props.documentosDia.hrEmitidasPorChofer || []).reduce((a, f) => a + (f.cantidad || 0), 0),
  hrCerradas: (props.documentosDia.hrCerradasPorChofer || []).reduce((a, f) => a + (f.cantidad || 0), 0),
}));

function formatNumber(valor, decimales = 0) {
  const num = Number(valor) || 0;
  return num.toLocaleString('es-CU', {
    minimumFractionDigits: decimales,
    maximumFractionDigits: decimales,
  });
}

function barraAltura(valor) {
  const max = Math.max(...props.serie.map((d) => d.hr), 1);
  const ratio = Math.min((Number(valor) || 0) / max, 1);
  return (8 + ratio * 96) + 'px';
}
</script>
