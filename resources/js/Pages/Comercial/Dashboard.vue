<template>
  <AppLayout :title="title">
    <div class="space-y-6">
      <!-- Encabezado -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Dashboard de Comercial</h1>
          <p class="text-gray-500 dark:text-gray-400 text-sm mt-1 flex items-center gap-2">
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-cyan-100 text-cyan-800 dark:bg-cyan-900/50 dark:text-cyan-300">
              Comercial
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
              <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ingresos Facturados</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1.5">{{ formatNumber(totales.ingresos_facturados, 2) }} <span class="text-sm font-normal text-gray-400">MN</span></p>
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ formatNumber(totales.facturas_del_mes) }} facturas</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-cyan-500 flex items-center justify-center shrink-0 ml-3">
              <i class="pi pi-dollar text-white text-lg" />
            </div>
          </div>
        </div>
        <div class="kpi-card dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-start justify-between">
            <div class="min-w-0">
              <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ingresos Aforados</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1.5">{{ formatNumber(totales.ingresos_aforados, 2) }} <span class="text-sm font-normal text-gray-400">MN</span></p>
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ formatNumber(totales.aforos_del_mes) }} aforos</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-emerald-500 flex items-center justify-center shrink-0 ml-3">
              <i class="pi pi-receipt text-white text-lg" />
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
              <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Clientes Activos</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1.5">{{ formatNumber(totales.clientes_activos) }}</p>
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Con cartas de porte en el mes</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-violet-500 flex items-center justify-center shrink-0 ml-3">
              <i class="pi pi-building text-white text-lg" />
            </div>
          </div>
        </div>
      </div>

      <!-- ═══════════════════ SECCIÓN 1: CICLO DE OPERACIONES ═══════════════════ -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-blue-500 flex items-center justify-center">
            <i class="pi pi-sync text-white text-sm" />
          </div>
          <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Ciclo de Operaciones — {{ mesLabel }}</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">Aforos, cartas de porte y solicitudes por estado</p>
          </div>
        </div>

        <div class="p-5 grid grid-cols-1 md:grid-cols-3 gap-6">
          <div>
            <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Aforos</h4>
            <div class="space-y-2">
              <div v-for="fila in aforosPorEstado" :key="fila.estado" class="flex items-center justify-between px-3 py-2 rounded-lg bg-gray-50 dark:bg-gray-700/60">
                <span class="text-sm text-gray-600 dark:text-gray-300">
                  <i class="mr-1.5" :class="'pi pi-circle-fill ' + fila.color"></i>{{ fila.etiqueta }}
                </span>
                <span class="text-sm font-semibold font-mono text-gray-900 dark:text-gray-100">{{ formatNumber(fila.total) }}</span>
              </div>
              <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-cyan-50 dark:bg-cyan-900/30">
                <span class="text-sm font-semibold text-cyan-700 dark:text-cyan-300">Total</span>
                <span class="text-sm font-bold font-mono text-cyan-700 dark:text-cyan-300">{{ formatNumber(totales.aforos_del_mes) }}</span>
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
                <span class="text-sm font-bold font-mono text-violet-700 dark:text-violet-300">{{ formatNumber(totalSolicitudes) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══════════════════ SECCIÓN 2: FACTURACIÓN POR CONCEPTO ═══════════════════ -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-cyan-500 flex items-center justify-center">
            <i class="pi pi-chart-pie text-white text-sm" />
          </div>
          <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Facturación por Concepto</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">Ingresos del mes agrupados por concepto</p>
          </div>
        </div>

        <div class="p-5">
          <div v-if="facturacionPorConcepto.length" class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr>
                  <th class="table-header dark:bg-gray-700 dark:text-gray-300">Concepto</th>
                  <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-right">Facturas</th>
                  <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-right">Total MN</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <tr v-for="fila in facturacionPorConcepto" :key="fila.concepto" class="hover:bg-gray-50 dark:hover:bg-gray-700/80">
                  <td class="table-cell font-medium dark:text-gray-200">{{ fila.concepto }}</td>
                  <td class="table-cell text-right dark:text-gray-400">{{ formatNumber(fila.cantidad) }}</td>
                  <td class="table-cell text-right font-mono font-semibold text-emerald-600 dark:text-emerald-400">{{ formatNumber(fila.total_mt, 2) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <p v-else class="text-sm text-gray-500 dark:text-gray-400">No hay facturas en el mes de operaciones.</p>
        </div>
      </div>

      <!-- ═══════════════════ SECCIÓN 3: FACTURACIÓN POR CLIENTE ═══════════════════ -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-emerald-500 flex items-center justify-center">
            <i class="pi pi-building text-white text-sm" />
          </div>
          <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Facturación por Cliente</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">Top 10 clientes del mes</p>
          </div>
        </div>

        <div class="p-5">
          <div v-if="facturacionPorCliente.length" class="space-y-3">
            <div v-for="(fila, i) in facturacionPorCliente" :key="fila.cliente" class="flex items-center gap-3">
              <span class="w-6 text-sm font-semibold text-gray-400 dark:text-gray-500 text-right shrink-0">{{ i + 1 }}</span>
              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between mb-1">
                  <span class="text-sm font-medium text-gray-700 dark:text-gray-200 truncate">{{ fila.cliente }}</span>
                  <span class="text-sm font-mono font-semibold text-gray-900 dark:text-gray-100 shrink-0 ml-3">{{ formatNumber(fila.total_mt, 2) }} <span class="text-xs text-gray-400 font-normal">MN</span></span>
                </div>
                <div class="h-2 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                  <div class="h-full rounded-full bg-emerald-500" :style="{ width: fila.porcentaje + '%' }"></div>
                </div>
                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">{{ formatNumber(fila.cantidad) }} facturas · {{ fila.porcentaje }}% del total</p>
              </div>
            </div>
          </div>
          <p v-else class="text-sm text-gray-500 dark:text-gray-400">No hay facturas en el mes de operaciones.</p>
        </div>
      </div>

      <!-- ═══════════════════ SECCIÓN 4: INGRESOS DIARIOS ═══════════════════ -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-emerald-500 flex items-center justify-center">
            <i class="pi pi-chart-bar text-white text-sm" />
          </div>
          <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Ingresos Diarios — Aforos</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">Ingresos aforados por día del mes</p>
          </div>
        </div>

        <div class="p-5">
          <div class="flex items-end gap-1.5 h-32 overflow-x-auto pb-1">
            <div v-for="dia in serie" :key="dia.fecha" class="flex flex-col items-center justify-end min-w-6 flex-1" :title="dia.etiqueta + ': ' + formatNumber(dia.ingreso_mt, 2) + ' MN'">
              <div
                class="w-full rounded-t bg-emerald-500 hover:bg-emerald-600 transition-colors"
                :style="{ height: barraAltura(dia.ingreso_mt) }"
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
  title: { type: String, default: 'Dashboard Comercial' },
  fechaOperaciones: { type: String, default: '' },
  entidadNombre: { type: String, default: '' },
  entidad: { type: Object, default: null },

  totales: {
    type: Object,
    default: () => ({
      facturas_del_mes: 0,
      ingresos_facturados: 0,
      aforos_del_mes: 0,
      ingresos_aforados: 0,
      cp_del_mes: 0,
      clientes_activos: 0,
    }),
  },
  facturacionPorConcepto: { type: Array, default: () => [] },
  facturacionPorCliente: { type: Array, default: () => [] },
  aforosPorEstado: { type: Array, default: () => [] },
  solicitudesPorEstado: { type: Array, default: () => [] },
  cpPorEstado: { type: Array, default: () => [] },
  serie: { type: Array, default: () => [] },
});

const mesLabel = computed(() => {
  if (!props.fechaOperaciones) return '';
  const d = new Date(props.fechaOperaciones + 'T00:00:00');
  return d.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' });
});

const totalSolicitudes = computed(() =>
  props.solicitudesPorEstado.reduce((acc, fila) => acc + (Number(fila.total) || 0), 0)
);

function formatNumber(valor, decimales = 0) {
  const num = Number(valor) || 0;
  return num.toLocaleString('es-CU', {
    minimumFractionDigits: decimales,
    maximumFractionDigits: decimales,
  });
}

function barraAltura(valor) {
  const max = Math.max(...props.serie.map((d) => Number(d.ingreso_mt) || 0), 1);
  const ratio = Math.min((Number(valor) || 0) / max, 1);
  return (8 + ratio * 96) + 'px';
}
</script>