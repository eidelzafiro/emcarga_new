<template>
  <AppLayout :title="title">
    <div class="space-y-6">
      <DashHeader
        :mes-label="d.mesLabel"
        :entidad="d.entidadNombre"
        :conectado="conectado"
        :ultima="ultima"
        :mes-param="mesParam"
        @prev="cambiarMes(-1)"
        @next="cambiarMes(1)"
        @hoy="irMesActual"
      />

      <div v-if="d.sinEntidad" class="panel">
        <p class="text-sm text-gray-500 dark:text-gray-400">Seleccione una entidad activa para ver el dashboard.</p>
      </div>

      <!-- KPIs -->
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
        <div v-for="kpi in d.kpis" :key="kpi.label" class="kpi-card dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-start justify-between">
            <div class="min-w-0">
              <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ kpi.label }}</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1.5">{{ kpi.valor }}</p>
              <p v-if="kpi.subtexto" class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ kpi.subtexto }}</p>
            </div>
            <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 ml-3" :class="kpi.color">
              <i :class="kpi.icono" class="text-white text-lg" />
            </div>
          </div>
        </div>
      </div>

      <!-- Composición -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <ChartCard titulo="Flota por tipo de equipo" :datos="d.composicion.porTipo" @segmento="abrirDetalle('tipo', $event)" />
        <ChartCard titulo="Flota por marca" :datos="d.composicion.porMarca" @segmento="abrirDetalle('marca', $event)" />
        <ChartCard titulo="Flota por modelo" :datos="d.composicion.porModelo" @segmento="abrirDetalle('modelo', $event)" />
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- OT del mes por estado -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
          <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Órdenes de taller · {{ d.mesLabel }}</h3>
          <div class="space-y-3">
            <div v-for="(v, k) in d.otResumen" :key="k" class="flex items-center justify-between">
              <span class="text-xs text-gray-500 dark:text-gray-400 capitalize">{{ etiquetaEstado(k) }}</span>
              <span class="text-sm font-semibold text-gray-800 dark:text-gray-200 tabular-nums">{{ v }}</span>
            </div>
          </div>
        </div>

        <!-- Serie diaria OT -->
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
          <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Ingreso de OT por día</h3>
          <div class="relative" style="height: 260px">
            <canvas ref="serieCanvas" />
          </div>
        </div>
      </div>

      <!-- Drill-down -->
      <div v-if="detalle.mostrar" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
          <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
              Vehículos — {{ etiquetaDim(detalle.dimension) }}: {{ detalle.valor }}
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ detalle.total }} vehículo(s)</p>
          </div>
          <button class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" @click="detalle.mostrar = false">
            <i class="pi pi-times" />
          </button>
        </div>
        <div class="overflow-x-auto max-h-96">
          <table class="w-full text-sm">
            <thead>
              <tr>
                <th class="table-header dark:bg-gray-700 dark:text-gray-300">Código</th>
                <th class="table-header dark:bg-gray-700 dark:text-gray-300">Clase</th>
                <th class="table-header dark:bg-gray-700 dark:text-gray-300">Placa</th>
                <th class="table-header dark:bg-gray-700 dark:text-gray-300">Tipo</th>
                <th class="table-header dark:bg-gray-700 dark:text-gray-300">Marca</th>
                <th class="table-header dark:bg-gray-700 dark:text-gray-300">Modelo</th>
                <th class="table-header dark:bg-gray-700 dark:text-gray-300">En taller</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
              <tr v-for="v in detalle.vehiculos" :key="v.clase + v.id" class="hover:bg-gray-50 dark:hover:bg-gray-750">
                <td class="table-cell font-mono text-xs dark:text-gray-400">{{ v.codigo }}</td>
                <td class="table-cell dark:text-gray-300">{{ v.clase }}</td>
                <td class="table-cell dark:text-gray-300">{{ v.placa }}</td>
                <td class="table-cell dark:text-gray-300">{{ v.tipo }}</td>
                <td class="table-cell dark:text-gray-300">{{ v.marca }}</td>
                <td class="table-cell dark:text-gray-300">{{ v.modelo }}</td>
                <td class="table-cell">
                  <span :class="v.enTaller ? 'status-badge-pendiente' : 'status-badge-completado'">
                    {{ v.enTaller ? 'Sí' : 'No' }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
          <div v-if="!detalle.vehiculos.length" class="text-center py-8">
            <p class="text-sm text-gray-400 dark:text-gray-500">Sin vehículos para este filtro.</p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { Chart, registerables } from 'chart.js';
import AppLayout from '@/Layouts/AppLayout.vue';
import DashHeader from '@/Components/Tecnico/DashHeader.vue';
import ChartCard from '@/Components/Tecnico/ChartCard.vue';

Chart.register(...registerables);

const props = defineProps({
  title: { type: String, default: '' },
  mesLabel: String,
  entidadNombre: String,
  sinEntidad: Boolean,
  kpis: Array,
  composicion: Object,
  otResumen: Object,
  serieOt: Array,
  totalVehiculos: Number,
});

const d = reactive({
  mesLabel: props.mesLabel,
  entidadNombre: props.entidadNombre,
  sinEntidad: props.sinEntidad,
  kpis: props.kpis || [],
  composicion: props.composicion || { porTipo: [], porMarca: [], porModelo: [] },
  otResumen: props.otResumen || {},
  serieOt: props.serieOt || [],
  totalVehiculos: props.totalVehiculos || 0,
});

const mesParam = ref(null);
const conectado = ref(false);
const ultima = ref(null);
const detalle = reactive({ mostrar: false, dimension: '', valor: '', vehiculos: [], total: 0 });
const serieCanvas = ref(null);
let chartInstance = null;
let timer = null;

const PALETA = ['#2563eb', '#059669', '#d97706', '#7c3aed', '#0891b2', '#dc2626', '#db2777', '#65a30d', '#ea580c', '#4f46e5', '#0d9488', '#9333ea'];

const etiquetaEstado = (k) => ({ abierta: 'Abiertas', cerrada: 'Cerradas', cancelada: 'Canceladas', total: 'Total' }[k] || k);
const etiquetaDim = (dim) => ({ tipo: 'Tipo', marca: 'Marca', modelo: 'Modelo' }[dim] || dim);

const recargar = async () => {
  try {
    const qs = mesParam.value ? `?mes=${mesParam.value}` : '';
    const r = await fetch(`/api/tecnico/flota${qs}`, { headers: { Accept: 'application/json' } });
    const j = await r.json();
    Object.assign(d, j);
    conectado.value = true;
    ultima.value = new Date().toLocaleTimeString('es-ES');
    nextTick(renderSerie);
  } catch (e) {
    conectado.value = false;
  }
};

const abrirDetalle = async (dimension, valor) => {
  const qs = new URLSearchParams({ dimension, valor });
  if (mesParam.value) qs.set('mes', mesParam.value);
  const r = await fetch(`/api/tecnico/flota/detalle?${qs.toString()}`, { headers: { Accept: 'application/json' } });
  const j = await r.json();
  detalle.dimension = j.dimension;
  detalle.valor = j.valor;
  detalle.vehiculos = j.vehiculos;
  detalle.total = j.total;
  detalle.mostrar = true;
};

const cambiarMes = (delta) => {
  const base = mesParam.value ? new Date(mesParam.value + '-01') : new Date();
  base.setMonth(base.getMonth() + delta);
  mesParam.value = base.toISOString().slice(0, 7);
  recargar();
};
const irMesActual = () => { mesParam.value = null; recargar(); };

const renderSerie = () => {
  if (!serieCanvas.value) return;
  if (chartInstance) chartInstance.destroy();
  const labels = d.serieOt.map((p) => new Date(p.fecha + 'T00:00:00').toLocaleDateString('es-ES', { day: 'numeric', month: 'short' }));
  chartInstance = new Chart(serieCanvas.value, {
    type: 'line',
    data: {
      labels,
      datasets: [{
        label: 'OT ingresadas',
        data: d.serieOt.map((p) => p.ot),
        borderColor: '#2563eb',
        backgroundColor: 'rgba(37, 99, 235, 0.08)',
        fill: true, tension: 0.4, pointRadius: 2,
      }],
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false }, ticks: { font: { size: 11 }, color: '#9ca3af' } },
        y: { grid: { color: '#f3f4f6' }, ticks: { font: { size: 11 }, color: '#9ca3af', beginAtZero: true, precision: 0 } },
      },
    },
  });
};

watch(() => props, () => {}, { deep: true });

onMounted(() => {
  nextTick(renderSerie);
  timer = setInterval(recargar, 20000);
  if (window.Echo) {
    window.Echo.connector?.pusher?.connection?.bind?.('state_change', (s) => { conectado.value = s.current === 'connected'; });
  }
  window.addEventListener('resize', () => chartInstance && chartInstance.resize());
});

onUnmounted(() => {
  if (timer) clearInterval(timer);
  if (chartInstance) chartInstance.destroy();
});
</script>
