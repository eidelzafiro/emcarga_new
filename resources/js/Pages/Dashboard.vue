<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Panel de control</h1>
          <p class="text-gray-500 text-sm mt-1">
            Bienvenido, {{ user?.name }} — {{ new Date().toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
          </p>
        </div>
        <div class="flex items-center gap-2">
          <span class="flex items-center gap-1.5 text-xs text-gray-500">
            <span class="w-2 h-2 rounded-full" :class="conectado ? 'bg-emerald-500' : 'bg-red-500'" />
            {{ conectado ? 'En vivo' : 'Desconectado' }}
          </span>
          <span v-if="ultimaActualizacion" class="text-xs text-gray-400">· {{ ultimaActualizacion }}</span>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div v-for="kpi in kpis" :key="kpi.label" class="kpi-card">
          <div class="flex items-start justify-between">
            <div class="min-w-0">
              <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ kpi.label }}</p>
              <p class="text-2xl font-bold text-gray-900 mt-1.5">{{ kpi.valor }}</p>
              <p v-if="kpi.subtexto" class="text-xs text-gray-400 mt-1">{{ kpi.subtexto }}</p>
            </div>
            <div
              class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 ml-3"
              :class="kpi.color"
            >
              <i :class="kpi.icono" class="text-white text-lg" />
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-900">Resumen de actividad</h3>
            <div class="flex items-center gap-2">
              <button
                v-for="r in periodos"
                :key="r.value"
                @click="periodoActivo = r.value"
                class="px-2.5 py-1 text-xs font-medium rounded-md transition-colors"
                :class="periodoActivo === r.value ? 'bg-blue-50 text-blue-700' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'"
              >
                {{ r.label }}
              </button>
            </div>
          </div>
          <div class="relative" style="height: 280px">
            <canvas ref="chartCanvas" />
          </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
          <h3 class="text-sm font-semibold text-gray-900 mb-4">Actividad reciente</h3>
          <div class="space-y-4">
            <div v-for="(act, i) in actividadReciente" :key="i" class="flex items-start gap-3">
              <div
                class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 mt-0.5"
                :class="act.color"
              >
                <i :class="act.icono" class="text-white text-xs" />
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">{{ act.titulo }}</p>
                <p class="text-xs text-gray-500 truncate">{{ act.descripcion }}</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ act.hace }}</p>
              </div>
            </div>
            <div v-if="actividadReciente.length === 0" class="text-center py-6">
              <i class="pi pi-inbox text-2xl text-gray-300 block mb-2" />
              <p class="text-sm text-gray-400">Sin actividad reciente</p>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
          <div>
            <h3 class="text-sm font-semibold text-gray-900">Últimos movimientos</h3>
            <p class="text-xs text-gray-500 mt-0.5">Listado de las últimas operaciones registradas</p>
          </div>
          <button class="text-sm font-medium text-blue-600 hover:text-blue-700 transition-colors">
            Ver todos
            <i class="pi pi-arrow-right ml-1 text-xs" />
          </button>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr>
                <th class="table-header">ID</th>
                <th class="table-header">Tipo</th>
                <th class="table-header">Descripción</th>
                <th class="table-header">Monto</th>
                <th class="table-header">Estado</th>
                <th class="table-header">Fecha</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="row in movimientos" :key="row.id" class="hover:bg-gray-50 transition-colors">
                <td class="table-cell font-mono text-xs text-gray-400">#{{ row.id }}</td>
                <td class="table-cell">
                  <span class="inline-flex items-center gap-1.5">
                    <i :class="row.icono" :style="{ color: row.color }" class="text-sm" />
                    {{ row.tipo }}
                  </span>
                </td>
                <td class="table-cell font-medium">{{ row.descripcion }}</td>
                <td class="table-cell font-medium" :class="row.monto >= 0 ? 'text-emerald-600' : 'text-red-600'">
                  {{ row.monto >= 0 ? '+' : '' }}${{ Math.abs(row.monto).toLocaleString() }}
                </td>
                <td class="table-cell">
                  <span :class="row.claseBadge">{{ row.estado }}</span>
                </td>
                <td class="table-cell text-gray-400">{{ row.fecha }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-if="movimientos.length === 0" class="text-center py-12">
          <i class="pi pi-inbox text-3xl text-gray-300 block mb-2" />
          <p class="text-sm text-gray-400">No hay movimientos registrados</p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Chart, registerables } from 'chart.js';
import AppLayout from '@/Layouts/AppLayout.vue';

Chart.register(...registerables);

const page = usePage();
const user = page.props.auth?.user;
const props = defineProps({
  kpis: { type: Array, default: () => [] },
  movimientos: { type: Array, default: () => [] },
  actividadReciente: { type: Array, default: () => [] },
});

const kpis = ref(props.kpis);
const periodos = [
  { label: '7 días', value: '7d' },
  { label: '30 días', value: '30d' },
  { label: '90 días', value: '90d' },
];
const periodoActivo = ref('30d');
const chartCanvas = ref(null);
const conectado = ref(false);
const ultimaActualizacion = ref(null);
let chartInstance = null;

const generarDatosGrafico = (periodo) => {
  const puntos = periodo === '7d' ? 7 : periodo === '30d' ? 30 : 90;
  const etiquetas = [];
  const ingresos = [];
  const egresos = [];
  const ahora = new Date();

  for (let i = puntos - 1; i >= 0; i--) {
    const d = new Date(ahora);
    d.setDate(d.getDate() - i);
    etiquetas.push(d.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' }));
    ingresos.push(Math.round(Math.random() * 8000 + 2000));
    egresos.push(Math.round(Math.random() * 5000 + 1000));
  }

  return { etiquetas, ingresos, egresos };
};

const renderChart = () => {
  if (!chartCanvas.value) return;
  if (chartInstance) chartInstance.destroy();

  const datos = generarDatosGrafico(periodoActivo.value);

  chartInstance = new Chart(chartCanvas.value, {
    type: 'line',
    data: {
      labels: datos.etiquetas,
      datasets: [
        {
          label: 'Ingresos',
          data: datos.ingresos,
          borderColor: '#059669',
          backgroundColor: 'rgba(5, 150, 105, 0.08)',
          fill: true,
          tension: 0.4,
          pointRadius: 3,
          pointBackgroundColor: '#059669',
          pointBorderColor: '#fff',
          pointBorderWidth: 2,
        },
        {
          label: 'Egresos',
          data: datos.egresos,
          borderColor: '#d97706',
          backgroundColor: 'rgba(217, 119, 6, 0.08)',
          fill: true,
          tension: 0.4,
          pointRadius: 3,
          pointBackgroundColor: '#d97706',
          pointBorderColor: '#fff',
          pointBorderWidth: 2,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: true,
          position: 'top',
          align: 'end',
          labels: {
            boxWidth: 8,
            boxHeight: 8,
            usePointStyle: true,
            padding: 16,
            font: { size: 12 },
          },
        },
        tooltip: {
          backgroundColor: '#1f2937',
          titleFont: { size: 12 },
          bodyFont: { size: 12 },
          padding: 12,
          cornerRadius: 8,
          displayColors: true,
        },
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: { font: { size: 11 }, color: '#9ca3af' },
        },
        y: {
          grid: { color: '#f3f4f6' },
          ticks: {
            font: { size: 11 },
            color: '#9ca3af',
            callback: (v) => '$' + v.toLocaleString(),
          },
        },
      },
      interaction: {
        intersect: false,
        mode: 'index',
      },
    },
  });
};

watch(periodoActivo, () => nextTick(renderChart));

let echoChannel = null;

onMounted(() => {
  nextTick(renderChart);

  if (window.Echo) {
    echoChannel = window.Echo.channel('kpis')
      .listen('.KpisUpdated', (e) => {
        if (e.kpis) {
          kpis.value = e.kpis;
          ultimaActualizacion.value = 'Actualizado: ' + new Date().toLocaleTimeString('es-ES');
        }
      });

    window.Echo.connector.pusher.connection.bind('state_change', (states) => {
      conectado.value = states.current === 'connected';
    });
    conectado.value = window.Echo.connector.pusher.connection.state === 'connected';
  }

  window.addEventListener('resize', () => { if (chartInstance) chartInstance.resize(); });
});

onUnmounted(() => {
  if (chartInstance) chartInstance.destroy();
  if (echoChannel) window.Echo.leaveChannel('kpis');
  window.removeEventListener('resize', () => {});
});
</script>
