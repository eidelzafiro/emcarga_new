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

      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4">
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

      <!-- Selector de componente -->
      <div class="flex items-center gap-2 flex-wrap">
        <span class="text-xs text-gray-500 dark:text-gray-400">Ver:</span>
        <button v-for="t in tabs" :key="t.id"
          @click="tabActivo = t.id"
          class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors"
          :class="tabActivo === t.id ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700'">
          {{ t.label }}
        </button>
      </div>

      <!-- Pronóstico de bajas -->
      <div v-show="tabActivo === 'general' || tabActivo === 'pronostico'" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Pronóstico de bajas de neumáticos (6 meses)</h3>
        <div class="relative" style="height: 260px">
          <canvas ref="pronosticoCanvas" />
        </div>
      </div>

      <!-- Totales por componente -->
      <div v-show="tabActivo === 'general'" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div v-for="c in resumenComponentes" :key="c.nombre" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center" :class="c.color">
              <i :class="c.icono" class="text-white text-lg" />
            </div>
            <div>
              <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ c.nombre }}</p>
              <p class="text-xs text-gray-400 dark:text-gray-500">Total: {{ c.total }} · Kms méd.: {{ c.kms }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Neumáticos por vencer -->
        <div v-show="tabActivo === 'general' || tabActivo === 'neumaticos'" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Neumáticos por vencer (30 días)</h3>
          </div>
          <div class="overflow-x-auto max-h-80">
            <table class="w-full text-sm">
              <thead>
                <tr>
                  <th class="table-header dark:bg-gray-700 dark:text-gray-300">Folio</th>
                  <th class="table-header dark:bg-gray-700 dark:text-gray-300">Medida</th>
                  <th class="table-header dark:bg-gray-700 dark:text-gray-300">Aviso</th>
                  <th class="table-header dark:bg-gray-700 dark:text-gray-300">Kms</th>
                  <th class="table-header dark:bg-gray-700 dark:text-gray-300">Estado</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <tr v-for="(n, i) in d.neumaticosPorVencer" :key="i" class="hover:bg-gray-50 dark:hover:bg-gray-750">
                  <td class="table-cell font-mono text-xs dark:text-gray-400">{{ n.folio }}</td>
                  <td class="table-cell dark:text-gray-300">{{ n.medida }}</td>
                  <td class="table-cell dark:text-gray-300">{{ n.planAviso }}</td>
                  <td class="table-cell tabular-nums dark:text-gray-300">{{ n.kms }}</td>
                  <td class="table-cell"><span :class="n.retirado ? 'status-badge-completado' : 'status-badge-pendiente'">{{ n.retirado ? 'Retirado' : 'Activo' }}</span></td>
                </tr>
              </tbody>
            </table>
            <div v-if="!d.neumaticosPorVencer.length" class="text-center py-8">
              <p class="text-sm text-gray-400 dark:text-gray-500">Sin neumáticos por vencer.</p>
            </div>
          </div>
        </div>

        <!-- Baterías -->
        <div v-show="tabActivo === 'general' || tabActivo === 'baterias'" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Baterías (últimas)</h3>
          </div>
          <div class="overflow-x-auto max-h-80">
            <table class="w-full text-sm">
              <thead>
                <tr>
                  <th class="table-header dark:bg-gray-700 dark:text-gray-300">Folio</th>
                  <th class="table-header dark:bg-gray-700 dark:text-gray-300">Voltaje</th>
                  <th class="table-header dark:bg-gray-700 dark:text-gray-300">Amperaje</th>
                  <th class="table-header dark:bg-gray-700 dark:text-gray-300">Instalación</th>
                  <th class="table-header dark:bg-gray-700 dark:text-gray-300">Estado</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <tr v-for="(b, i) in d.baterias" :key="i" class="hover:bg-gray-50 dark:hover:bg-gray-750">
                  <td class="table-cell font-mono text-xs dark:text-gray-400">{{ b.folio }}</td>
                  <td class="table-cell tabular-nums dark:text-gray-300">{{ b.voltaje }}</td>
                  <td class="table-cell tabular-nums dark:text-gray-300">{{ b.amperaje }}</td>
                  <td class="table-cell dark:text-gray-300">{{ b.instalacion }}</td>
                  <td class="table-cell"><span :class="b.baja ? 'status-badge-pendiente' : 'status-badge-completado'">{{ b.baja ? 'Con baja' : 'OK' }}</span></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { Chart, registerables } from 'chart.js';
import AppLayout from '@/Layouts/AppLayout.vue';
import DashHeader from '@/Components/Tecnico/DashHeader.vue';

Chart.register(...registerables);

const props = defineProps({
  title: String,
  mesLabel: String, entidadNombre: String, sinEntidad: Boolean,
  kpis: Array, pronostico: Array, neumaticosPorVencer: Array, baterias: Array, totales: Object,
});

const d = reactive({
  mesLabel: props.mesLabel, entidadNombre: props.entidadNombre, sinEntidad: props.sinEntidad,
  kpis: props.kpis || [], pronostico: props.pronostico || [],
  neumaticosPorVencer: props.neumaticosPorVencer || [], baterias: props.baterias || [],
  totales: props.totales || {},
});

const tabs = [
  { id: 'general', label: 'Visión general' },
  { id: 'neumaticos', label: 'Neumáticos' },
  { id: 'baterias', label: 'Baterías' },
  { id: 'pronostico', label: 'Pronóstico' },
];
const tabActivo = ref('general');
const mesParam = ref(null);
const conectado = ref(false);
const ultima = ref(null);
const pronosticoCanvas = ref(null);
let chart = null;
let timer = null;

const resumenComponentes = computed(() => [
  { nombre: 'Motores', total: d.totales.motores?.total ?? 0, kms: d.totales.motores?.kmsPromedio ?? 0, icono: 'pi pi-cog', color: 'bg-blue-500' },
  { nombre: 'Cajas', total: d.totales.cajas?.total ?? 0, kms: d.totales.cajas?.kmsPromedio ?? 0, icono: 'pi pi-sitemap', color: 'bg-emerald-500' },
  { nombre: 'Diferenciales', total: d.totales.diferenciales?.total ?? 0, kms: d.totales.diferenciales?.kmsPromedio ?? 0, icono: 'pi pi-share-alt', color: 'bg-cyan-500' },
]);

const recargar = async () => {
  try {
    const qs = mesParam.value ? `?mes=${mesParam.value}` : '';
    const r = await fetch(`/api/tecnico/componentes${qs}`, { headers: { Accept: 'application/json' } });
    Object.assign(d, await r.json());
    conectado.value = true;
    ultima.value = new Date().toLocaleTimeString('es-ES');
    nextTick(renderPronostico);
  } catch (e) { conectado.value = false; }
};

const cambiarMes = (delta) => {
  const base = mesParam.value ? new Date(mesParam.value + '-01') : new Date();
  base.setMonth(base.getMonth() + delta);
  mesParam.value = base.toISOString().slice(0, 7);
  recargar();
};
const irMesActual = () => { mesParam.value = null; recargar(); };

const renderPronostico = () => {
  if (!pronosticoCanvas.value) return;
  if (chart) chart.destroy();
  chart = new Chart(pronosticoCanvas.value, {
    type: 'bar',
    data: {
      labels: d.pronostico.map((p) => p.mes),
      datasets: [{
        label: 'Bajas previstas',
        data: d.pronostico.map((p) => p.cantidad),
        backgroundColor: '#7c3aed',
        borderRadius: 6,
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

onMounted(() => { nextTick(renderPronostico); timer = setInterval(recargar, 20000); });
onUnmounted(() => { timer && clearInterval(timer); chart && chart.destroy(); });
</script>
