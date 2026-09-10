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

      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
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

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Vehículos en taller -->
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Vehículos en taller ahora</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Estado taller / paralizado · orden de taller abierta</p>
          </div>
          <div class="overflow-x-auto max-h-96">
            <table class="w-full text-sm">
              <thead>
                <tr>
                  <th class="table-header dark:bg-gray-700 dark:text-gray-300">Código</th>
                  <th class="table-header dark:bg-gray-700 dark:text-gray-300">Clase</th>
                  <th class="table-header dark:bg-gray-700 dark:text-gray-300">OT</th>
                  <th class="table-header dark:bg-gray-700 dark:text-gray-300">Motivo</th>
                  <th class="table-header dark:bg-gray-700 dark:text-gray-300">Días</th>
                  <th class="table-header dark:bg-gray-700 dark:text-gray-300">Estado</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <tr v-for="(v, i) in d.vehiculosTaller" :key="i" class="hover:bg-gray-50 dark:hover:bg-gray-700/80">
                  <td class="table-cell font-mono text-xs dark:text-gray-400">{{ v.codigo }}</td>
                  <td class="table-cell dark:text-gray-300">{{ v.clase }}</td>
                  <td class="table-cell dark:text-gray-300">{{ v.ot }}</td>
                  <td class="table-cell dark:text-gray-300">{{ v.motivo }}</td>
                  <td class="table-cell tabular-nums dark:text-gray-300">{{ v.dias ?? '—' }}</td>
                  <td class="table-cell">
                    <span :class="v.estado === 'Paralizado' ? 'status-badge-pendiente' : 'status-badge-proceso'">{{ v.estado }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
            <div v-if="!d.vehiculosTaller.length" class="text-center py-10">
              <i class="pi pi-check-circle text-3xl text-emerald-300 dark:text-emerald-700 block mb-2" />
              <p class="text-sm text-gray-400 dark:text-gray-500">No hay vehículos en taller.</p>
            </div>
          </div>
        </div>

        <!-- Alertas -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
          <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Alertas técnicas</h3>
          <div class="space-y-3">
            <div v-for="(a, i) in d.alertas" :key="i" class="flex items-start gap-3 p-3 rounded-lg border"
              :class="claseAlerta(a.tipo)">
              <i :class="iconoAlerta(a.tipo)" class="text-lg mt-0.5" />
              <p class="text-xs text-gray-700 dark:text-gray-300">{{ a.texto }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <ChartCard titulo="OT por tipo de mantenimiento" :datos="d.porTipoMtto" />
        <ChartCard titulo="OT por taller" :datos="d.porTaller" />
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted, nextTick } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DashHeader from '@/Components/Tecnico/DashHeader.vue';
import ChartCard from '@/Components/Tecnico/ChartCard.vue';

const props = defineProps({
  title: String,
  mesLabel: String, entidadNombre: String, sinEntidad: Boolean,
  kpis: Array, vehiculosTaller: Array, otResumen: Object,
  porTipoMtto: Array, porTaller: Array, alertas: Array, vencidas: Number,
});

const d = reactive({
  mesLabel: props.mesLabel, entidadNombre: props.entidadNombre, sinEntidad: props.sinEntidad,
  kpis: props.kpis || [], vehiculosTaller: props.vehiculosTaller || [],
  otResumen: props.otResumen || {}, porTipoMtto: props.porTipoMtto || [],
  porTaller: props.porTaller || [], alertas: props.alertas || [], vencidas: props.vencidas || 0,
});

const mesParam = ref(null);
const conectado = ref(false);
const ultima = ref(null);
let timer = null;

const recargar = async () => {
  try {
    const qs = mesParam.value ? `?mes=${mesParam.value}` : '';
    const r = await fetch(`/api/tecnico/taller${qs}`, { headers: { Accept: 'application/json' } });
    Object.assign(d, await r.json());
    conectado.value = true;
    ultima.value = new Date().toLocaleTimeString('es-ES');
  } catch (e) { conectado.value = false; }
};

const cambiarMes = (delta) => {
  const base = mesParam.value ? new Date(mesParam.value + '-01') : new Date();
  base.setMonth(base.getMonth() + delta);
  mesParam.value = base.toISOString().slice(0, 7);
  recargar();
};
const irMesActual = () => { mesParam.value = null; recargar(); };

const claseAlerta = (t) => ({
  warning: 'bg-amber-50 border-amber-200 dark:bg-amber-900/20 dark:border-amber-800',
  danger: 'bg-red-50 border-red-200 dark:bg-red-900/20 dark:border-red-800',
  ok: 'bg-emerald-50 border-emerald-200 dark:bg-emerald-900/20 dark:border-emerald-800',
}[t] || 'bg-gray-50 border-gray-200');
const iconoAlerta = (t) => ({
  warning: 'pi pi-exclamation-triangle text-amber-500',
  danger: 'pi pi-times-circle text-red-500',
  ok: 'pi pi-check-circle text-emerald-500',
}[t] || 'pi pi-info-circle');

onMounted(() => { timer = setInterval(recargar, 20000); });
onUnmounted(() => timer && clearInterval(timer));
</script>
