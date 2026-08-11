<template>
    <AppLayout :title="'Dashboard — ' + rolLabel">
      <div class="space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Panel de control</h1>
            <p class="text-gray-500 dark:text-gray-400 text-sm mt-1 flex items-center gap-2">
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium"
                :class="rolBadgeClass">
                {{ rolLabel }}
              </span>
              Bienvenido, {{ user?.name }} — {{ new Date().toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
            </p>
          </div>
          <div class="flex items-center gap-2">
            <span class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
              <span class="w-2 h-2 rounded-full" :class="conectado ? 'bg-emerald-500' : 'bg-red-500'" />
              {{ conectado ? 'En vivo' : 'Desconectado' }}
            </span>
            <span v-if="ultimaActualizacion" class="text-xs text-gray-400 dark:text-gray-500">· {{ ultimaActualizacion }}</span>
          </div>
        </div>

        <div v-if="kpis && kpis.length" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
          <div v-for="kpi in kpis" :key="kpi.label" class="kpi-card dark:bg-gray-800 dark:border-gray-700">
            <div class="flex items-start justify-between">
              <div class="min-w-0">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ kpi.label }}</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1.5">{{ kpi.valor }}</p>
                <p v-if="kpi.subtexto" class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ kpi.subtexto }}</p>
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
          <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Resumen de actividad</h3>
              <div class="flex items-center gap-2">
                <button
                  v-for="r in periodos"
                  :key="r.value"
                  @click="periodoActivo = r.value"
                  class="px-2.5 py-1 text-xs font-medium rounded-md transition-colors"
                  :class="periodoActivo === r.value ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'"
                >
                  {{ r.label }}
                </button>
              </div>
            </div>
            <div class="relative" style="height: 280px">
              <canvas ref="chartCanvas" />
            </div>
          </div>

          <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Actividad reciente</h3>
            <div class="space-y-4">
              <div v-for="(act, i) in actividadReciente" :key="i" class="flex items-start gap-3">
                <div
                  class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 mt-0.5"
                  :class="act.color"
                >
                  <i :class="act.icono" class="text-white text-xs" />
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ act.titulo }}</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ act.descripcion }}</p>
                  <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ act.hace }}</p>
                </div>
              </div>
              <div v-if="actividadReciente && actividadReciente.length === 0" class="text-center py-6">
                <i class="pi pi-inbox text-2xl text-gray-300 dark:text-gray-600 block mb-2" />
                <p class="text-sm text-gray-400 dark:text-gray-500">Sin actividad reciente</p>
              </div>
            </div>
          </div>
        </div>

        <div v-if="secciones && secciones.length > 0" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
          <div v-for="(sec, i) in secciones" :key="i"
            class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm hover:shadow-md transition-shadow cursor-pointer">
            <div class="flex items-start gap-3">
              <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0" :class="sec.color">
                <i :class="sec.icono" class="text-white text-lg" />
              </div>
              <div class="min-w-0">
                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ sec.titulo }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ sec.descripcion }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <div>
              <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Últimos movimientos</h3>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Listado de las últimas operaciones registradas</p>
            </div>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr>
                  <th class="table-header dark:bg-gray-700 dark:text-gray-300">ID</th>
                  <th class="table-header dark:bg-gray-700 dark:text-gray-300">Tipo</th>
                  <th class="table-header dark:bg-gray-700 dark:text-gray-300">Descripción</th>
                  <th class="table-header dark:bg-gray-700 dark:text-gray-300">Estado</th>
                  <th class="table-header dark:bg-gray-700 dark:text-gray-300">Fecha</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <tr v-for="row in movimientos" :key="row.id" class="hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors">
                  <td class="table-cell dark:text-gray-400 font-mono text-xs text-gray-400">#{{ row.id }}</td>
                  <td class="table-cell dark:text-gray-300">
                    <span class="inline-flex items-center gap-1.5">
                      <i :class="row.icono" :style="{ color: row.color }" class="text-sm" />
                      {{ row.tipo }}
                    </span>
                  </td>
                  <td class="table-cell font-medium dark:text-gray-200">{{ row.descripcion }}</td>
                  <td class="table-cell">
                    <span :class="row.claseBadge">{{ row.estado }}</span>
                  </td>
                  <td class="table-cell text-gray-400 dark:text-gray-500">{{ row.fecha }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-if="movimientos && movimientos.length === 0" class="text-center py-12">
            <i class="pi pi-inbox text-3xl text-gray-300 dark:text-gray-600 block mb-2" />
            <p class="text-sm text-gray-400 dark:text-gray-500">No hay movimientos registrados</p>
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

const props = defineProps({
  kpis: { type: Array, default: () => [] },
  rol: { type: String, default: 'default' },
  movimientos: { type: Array, default: () => [] },
  actividadReciente: { type: Array, default: () => [] },
  secciones: { type: Array, default: () => [] },
  serieActividad: { type: Array, default: () => [] },
});

const user = usePage().props.auth?.user || { name: '' };
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

const labelPorRol = {
  SUPERADMIN: 'Super Administrador',
  TECNICA: 'Técnica',
  COMERCIAL: 'Comercial',
  CONTABILIDAD: 'Contabilidad',
  RECHUM: 'Recursos Humanos',
  OPERATIVOS: 'Operativos',
  CONFIGURACIONES: 'Configuraciones',
};

const badgePorRol = {
  SUPERADMIN: 'bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300',
  TECNICA: 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300',
  COMERCIAL: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300',
  CONTABILIDAD: 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300',
  RECHUM: 'bg-pink-100 text-pink-800 dark:bg-pink-900/50 dark:text-pink-300',
  OPERATIVOS: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/50 dark:text-cyan-300',
  CONFIGURACIONES: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-300',
};

const rolLabel = computed(() => labelPorRol[props.rol] || 'General');
const rolBadgeClass = computed(() => badgePorRol[props.rol] || 'bg-gray-100 text-gray-800');

const generarDatosGrafico = (periodo) => {
  const puntos = periodo === '7d' ? 7 : periodo === '30d' ? 30 : 90;
  const serie = props.serieActividad || [];
  const etiquetas = [];
  const hojas = [];
  const cartas = [];
  const solicitudes = [];
  const inicio = Math.max(0, serie.length - puntos);

  for (let i = inicio; i < serie.length; i++) {
    const d = serie[i];
    const fecha = new Date(String(d.fecha).slice(0, 10) + 'T00:00:00');
    etiquetas.push(fecha.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' }));
    hojas.push(Number(d.hojas) || 0);
    cartas.push(Number(d.cartas) || 0);
    solicitudes.push(Number(d.solicitudes) || 0);
  }

  return { etiquetas, hojas, cartas, solicitudes };
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
          label: 'Hojas de ruta',
          data: datos.hojas,
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
          label: 'Cartas de porte',
          data: datos.cartas,
          borderColor: '#2563eb',
          backgroundColor: 'rgba(37, 99, 235, 0.08)',
          fill: true,
          tension: 0.4,
          pointRadius: 3,
          pointBackgroundColor: '#2563eb',
          pointBorderColor: '#fff',
          pointBorderWidth: 2,
        },
        {
          label: 'Solicitudes',
          data: datos.solicitudes,
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
            beginAtZero: true,
            precision: 0,
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
