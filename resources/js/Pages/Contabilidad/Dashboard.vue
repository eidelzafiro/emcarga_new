<template>
  <AppLayout :title="title">
    <div class="space-y-6">
      <!-- Encabezado -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Dashboard de Contabilidad</h1>
          <p class="text-gray-500 dark:text-gray-400 text-sm mt-1 flex items-center gap-2">
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300">
              Contabilidad
            </span>
            {{ mesLabel }}
          </p>
        </div>
        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300">
          {{ fechaOperaciones }}
        </span>
      </div>

      <!-- KPIs Totales -->
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div v-if="totales.combustible_cargado_mon" class="kpi-card dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-start justify-between">
            <div class="min-w-0">
              <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Combustible Cargado</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1.5">{{ formatNumber(totales.combustible_cargado_mon) }} <span class="text-sm font-normal text-gray-400">MN</span></p>
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ formatNumber(totales.combustible_cargado_lts) }} LTS</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-amber-500 flex items-center justify-center shrink-0 ml-3">
              <i class="pi pi-inbox text-white text-lg" />
            </div>
          </div>
        </div>
        <div v-if="totales.combustible_descargado_mon" class="kpi-card dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-start justify-between">
            <div class="min-w-0">
              <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Combustible Descargado</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1.5">{{ formatNumber(totales.combustible_descargado_mon) }} <span class="text-sm font-normal text-gray-400">MN</span></p>
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ formatNumber(totales.combustible_descargado_lts) }} LTS</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-orange-500 flex items-center justify-center shrink-0 ml-3">
              <i class="pi pi-arrow-down text-white text-lg" />
            </div>
          </div>
        </div>
        <div v-if="totales.ingresos_mt" class="kpi-card dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-start justify-between">
            <div class="min-w-0">
              <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ingresos Facturación</p>
              <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1.5">{{ formatNumber(totales.ingresos_mt) }} <span class="text-sm font-normal text-gray-400">MT</span></p>
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ totales.total_facturas }} facturas</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-emerald-500 flex items-center justify-center shrink-0 ml-3">
              <i class="pi pi-dollar text-white text-lg" />
            </div>
          </div>
        </div>
        <div v-if="totales.gasto_material_mn" class="kpi-card dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-start justify-between">
            <div class="min-w-0">
              <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Gasto Material</p>
              <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1.5">{{ formatNumber(totales.gasto_material_mn) }} <span class="text-sm font-normal text-gray-400">MN</span></p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-red-500 flex items-center justify-center shrink-0 ml-3">
              <i class="pi pi-wrench text-white text-lg" />
            </div>
          </div>
        </div>
        <div v-if="totales.otros_gastos_mn" class="kpi-card dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-start justify-between">
            <div class="min-w-0">
              <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Otros Gastos</p>
              <p class="text-2xl font-bold text-orange-600 dark:text-orange-400 mt-1.5">{{ formatNumber(totales.otros_gastos_mn) }} <span class="text-sm font-normal text-gray-400">MN</span></p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-orange-500 flex items-center justify-center shrink-0 ml-3">
              <i class="pi pi-list text-white text-lg" />
            </div>
          </div>
        </div>
      </div>

      <!-- ═══════════════════ SECCIÓN COMBUSTIBLE ═══════════════════ -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-amber-500 flex items-center justify-center">
            <i class="pi pi-inbox text-white text-sm" />
          </div>
          <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Combustible</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">Tarjetas, saldos y movimiento del mes</p>
          </div>
        </div>

        <div class="p-5 space-y-5">
          <!-- Tarjetas por tipo y estado -->
          <div v-if="tarjetasPorTipo.length">
            <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Tarjetas por tipo</h4>
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead>
                  <tr>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300">Tipo de combustible</th>
                    <th v-for="estado in estadosUnicos" :key="estado" class="table-header dark:bg-gray-700 dark:text-gray-300 text-center">{{ estado }}</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-center font-bold">Total</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                  <tr v-for="fila in tarjetasPorTipo" :key="fila.tipo_combustible" class="hover:bg-gray-50 dark:hover:bg-gray-750">
                    <td class="table-cell font-medium dark:text-gray-200">{{ fila.tipo_combustible }}</td>
                    <td v-for="estado in estadosUnicos" :key="estado" class="table-cell text-center dark:text-gray-400">
                      {{ getEstadoCantidad(fila, estado) || '—' }}
                    </td>
                    <td class="table-cell text-center font-semibold dark:text-gray-200">{{ fila.total }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Saldo por tipo de combustible (inicio + cargado - descargado = final) -->
          <div v-if="saldoPorTipo.length">
            <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Saldo por tipo de combustible</h4>
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead>
                  <tr>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300">Tipo</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300">Moneda</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-right">Saldo Inicio</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-right">Cargado</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-right">Descargado</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-right font-bold">Saldo Final</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                  <tr v-for="(fila, i) in saldoPorTipo" :key="i" class="hover:bg-gray-50 dark:hover:bg-gray-750">
                    <td class="table-cell font-medium dark:text-gray-200">{{ fila.tipo }}</td>
                    <td class="table-cell dark:text-gray-400">
                      <Tag :value="fila.moneda" :severity="fila.moneda === 'MN' ? 'success' : 'info'" />
                    </td>
                    <td class="table-cell text-right font-mono dark:text-gray-300">{{ formatNumber(fila.inicio_mon) }}</td>
                    <td class="table-cell text-right font-mono text-green-600 dark:text-green-400">+{{ formatNumber(fila.cargado_mon) }}</td>
                    <td class="table-cell text-right font-mono text-red-600 dark:text-red-400">-{{ formatNumber(fila.descargado_mon) }}</td>
                    <td class="table-cell text-right font-mono font-bold dark:text-gray-200">{{ formatNumber(fila.final_mon) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Saldos actuales en tarjetas -->
          <div v-if="combustibleActual.length">
            <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Saldos actuales en tarjetas</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
              <div v-for="(saldo, i) in combustibleActual" :key="i" class="flex items-center gap-3 px-4 py-3 rounded-lg bg-gray-50 dark:bg-gray-750 border border-gray-200 dark:border-gray-700">
                <div class="w-8 h-8 rounded-lg bg-amber-500 flex items-center justify-center shrink-0">
                  <i class="pi pi-wallet text-white text-xs" />
                </div>
                <div class="min-w-0 flex-1">
                  <p class="text-xs text-gray-500 dark:text-gray-400">{{ saldo.tipo_combustible }} · {{ saldo.moneda }}</p>
                  <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">{{ formatNumber(saldo.total_lts) }} LTS</p>
                  <p class="text-xs text-gray-400 dark:text-gray-500">{{ formatNumber(saldo.total_mon) }} {{ saldo.moneda }}</p>
                </div>
              </div>
            </div>
          </div>

          <div v-if="!tarjetasPorTipo.length && !saldoPorTipo.length && !combustibleActual.length" class="text-center py-8">
            <i class="pi pi-inbox text-3xl text-gray-300 dark:text-gray-600 block mb-2" />
            <p class="text-sm text-gray-400 dark:text-gray-500">No hay datos de combustible para este período</p>
          </div>
        </div>
      </div>

      <!-- ═══════════════════ SECCIÓN FACTURACIÓN ═══════════════════ -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-emerald-500 flex items-center justify-center">
            <i class="pi pi-dollar text-white text-sm" />
          </div>
          <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Facturación</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">Ingresos por concepto, estado y clientes</p>
          </div>
        </div>

        <div class="p-5 space-y-5">
          <!-- Ingresos por concepto -->
          <div v-if="ingresosPorConcepto.length">
            <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Ingresos por concepto</h4>
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead>
                  <tr>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300">Concepto</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-center">Facturas</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-right">Total MT</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                  <tr v-for="(fila, i) in ingresosPorConcepto" :key="i" class="hover:bg-gray-50 dark:hover:bg-gray-750">
                    <td class="table-cell font-medium dark:text-gray-200">{{ fila.concepto }}</td>
                    <td class="table-cell text-center dark:text-gray-400">{{ fila.cantidad }}</td>
                    <td class="table-cell text-right font-mono font-semibold text-emerald-600 dark:text-emerald-400">{{ formatNumber(fila.total_mt) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Top clientes -->
          <div v-if="facturacionPorCliente.length">
            <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Principales clientes</h4>
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead>
                  <tr>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300">#</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300">Cliente</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-center">Facturas</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-right">Total MT</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                  <tr v-for="(fila, i) in facturacionPorCliente" :key="i" class="hover:bg-gray-50 dark:hover:bg-gray-750">
                    <td class="table-cell text-gray-400 dark:text-gray-500 font-mono text-xs">{{ i + 1 }}</td>
                    <td class="table-cell font-medium dark:text-gray-200">{{ fila.cliente }}</td>
                    <td class="table-cell text-center dark:text-gray-400">{{ fila.cantidad_facturas }}</td>
                    <td class="table-cell text-right font-mono font-semibold text-emerald-600 dark:text-emerald-400">{{ formatNumber(fila.total_mt) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div v-if="!ingresosPorConcepto.length && !facturacionPorCliente.length" class="text-center py-8">
            <i class="pi pi-inbox text-3xl text-gray-300 dark:text-gray-600 block mb-2" />
            <p class="text-sm text-gray-400 dark:text-gray-500">No hay datos de facturación para este período</p>
          </div>
        </div>
      </div>

      <!-- ═══════════════════ SECCIÓN COSTOS ═══════════════════ -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-red-500 flex items-center justify-center">
            <i class="pi pi-chart-line text-white text-sm" />
          </div>
          <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Costos</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">Gasto material, otros gastos y amortización</p>
          </div>
        </div>

        <div class="p-5 space-y-5">
          <!-- Gasto material -->
          <div v-if="gastoMaterialPorConcepto.length">
            <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Gasto de material</h4>
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead>
                  <tr>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300">Concepto</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-right">Cantidad</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-right">Total MN</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                  <tr v-for="(fila, i) in gastoMaterialPorConcepto" :key="i" class="hover:bg-gray-50 dark:hover:bg-gray-750">
                    <td class="table-cell font-medium dark:text-gray-200">{{ fila.concepto }}</td>
                    <td class="table-cell text-right font-mono dark:text-gray-400">{{ formatNumber(fila.cantidad, 3) }}</td>
                    <td class="table-cell text-right font-mono font-semibold text-red-600 dark:text-red-400">{{ formatNumber(fila.total_mn) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Otros gastos -->
          <div v-if="otrosGastosPorConcepto.length">
            <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Otros gastos</h4>
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead>
                  <tr>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300">Concepto</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-center">Registros</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-right">Total MN</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-right">Total MLC</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                  <tr v-for="(fila, i) in otrosGastosPorConcepto" :key="i" class="hover:bg-gray-50 dark:hover:bg-gray-750">
                    <td class="table-cell font-medium dark:text-gray-200">{{ fila.concepto }}</td>
                    <td class="table-cell text-center dark:text-gray-400">{{ fila.cantidad }}</td>
                    <td class="table-cell text-right font-mono font-semibold text-red-600 dark:text-red-400">{{ formatNumber(fila.total_mn) }}</td>
                    <td class="table-cell text-right font-mono text-gray-500 dark:text-gray-400">{{ formatNumber(fila.total_mlc) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Amortización taller -->
          <div v-if="amortizacionTaller.length">
            <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Amortización taller</h4>
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead>
                  <tr>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300">Tractivo</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-center">Registros</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-right">Amortización MN</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-right">Chapa</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                  <tr v-for="(fila, i) in amortizacionTaller" :key="i" class="hover:bg-gray-50 dark:hover:bg-gray-750">
                    <td class="table-cell font-medium dark:text-gray-200">{{ fila.tractivo }}</td>
                    <td class="table-cell text-center dark:text-gray-400">{{ fila.registros }}</td>
                    <td class="table-cell text-right font-mono font-semibold text-red-600 dark:text-red-400">{{ formatNumber(fila.amortizacion_mn) }}</td>
                    <td class="table-cell text-right font-mono dark:text-gray-400">{{ formatNumber(fila.chapa) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <!-- Total amortización -->
            <div class="mt-3 flex justify-end">
              <div class="px-4 py-2 rounded-lg bg-gray-50 dark:bg-gray-750 border border-gray-200 dark:border-gray-700">
                <span class="text-xs text-gray-500 dark:text-gray-400">Total amortización: </span>
                <span class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ formatNumber(totales.amortizacion_mn) }} MN</span>
              </div>
            </div>
          </div>

          <div v-if="!gastoMaterialPorConcepto.length && !otrosGastosPorConcepto.length && !amortizacionTaller.length" class="text-center py-8">
            <i class="pi pi-inbox text-3xl text-gray-300 dark:text-gray-600 block mb-2" />
            <p class="text-sm text-gray-400 dark:text-gray-500">No hay datos de costos para este período</p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Tag from 'primevue/tag';

const props = defineProps({
  title: { type: String, default: 'Dashboard Contabilidad' },
  fechaOperaciones: { type: String, default: '' },
  tarjetasPorTipo: { type: Array, default: () => [] },
  combustibleActual: { type: Array, default: () => [] },
  saldoPorTipo: { type: Array, default: () => [] },
  combustibleCargado: { type: Array, default: () => [] },
  combustibleDescargado: { type: Array, default: () => [] },
  ingresosPorConcepto: { type: Array, default: () => [] },
  facturacionPorCliente: { type: Array, default: () => [] },
  gastoMaterialPorConcepto: { type: Array, default: () => [] },
  otrosGastosPorConcepto: { type: Array, default: () => [] },
  amortizacionTaller: { type: Array, default: () => [] },
  totales: { type: Object, default: () => ({}) },
});

const mesLabel = computed(() => {
  if (!props.fechaOperaciones) return '';
  const d = new Date(props.fechaOperaciones + 'T00:00:00');
  return d.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' });
});

const estadosUnicos = computed(() => {
  const estados = new Set();
  props.tarjetasPorTipo.forEach(fila => {
    fila.estados?.forEach(e => estados.add(e.estado));
  });
  return [...estados].sort();
});

function getEstadoCantidad(fila, estado) {
  const e = fila.estados?.find(s => s.estado === estado);
  return e ? e.cantidad : null;
}

function formatNumber(valor, decimales = 2) {
  const num = Number(valor) || 0;
  return num.toLocaleString('es-CU', {
    minimumFractionDigits: decimales,
    maximumFractionDigits: decimales,
  });
}
</script>
