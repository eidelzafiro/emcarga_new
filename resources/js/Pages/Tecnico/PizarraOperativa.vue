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
        <p class="text-sm text-gray-500 dark:text-gray-400">Seleccione una entidad activa para ver el tablero.</p>
      </div>

      <!-- KPIs de Flota Técnica (cabecera) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-6 gap-4">
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

      <!-- Composición de flota -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div v-for="bloque in bloquesDesglose" :key="bloque.titulo" class="panel overflow-x-auto">
          <h3 class="panel-title">{{ bloque.titulo }}</h3>
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                <th class="py-1.5 pr-2">{{ bloque.campo }}</th>
                <th class="py-1.5 pr-2 text-right">Activos</th>
                <th class="py-1.5 pr-2 text-right">Taller</th>
                <th class="py-1.5 pr-2 text-right">Baja</th>
                <th class="py-1.5 text-right">Total</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in bloque.filas" :key="r.etiqueta" class="border-b border-gray-100 dark:border-gray-800">
                <td class="py-1.5 pr-2 text-gray-900 dark:text-gray-100">{{ r.etiqueta }}</td>
                <td class="py-1.5 pr-2 text-right text-emerald-600 dark:text-emerald-400">{{ r.activos }}</td>
                <td class="py-1.5 pr-2 text-right text-amber-600 dark:text-amber-400">{{ r.taller }}</td>
                <td class="py-1.5 pr-2 text-right text-red-600 dark:text-red-400">{{ r.baja }}</td>
                <td class="py-1.5 text-right font-semibold text-gray-900 dark:text-gray-100">{{ r.total }}</td>
              </tr>
              <tr class="font-bold bg-gray-50 dark:bg-gray-800/60">
                <td class="py-1.5 pr-2 text-gray-900 dark:text-gray-100">TOTAL</td>
                <td class="py-1.5 pr-2 text-right text-emerald-600 dark:text-emerald-400">{{ bloque.total.activos }}</td>
                <td class="py-1.5 pr-2 text-right text-amber-600 dark:text-amber-400">{{ bloque.total.taller }}</td>
                <td class="py-1.5 pr-2 text-right text-red-600 dark:text-red-400">{{ bloque.total.baja }}</td>
                <td class="py-1.5 text-right text-gray-900 dark:text-gray-100">{{ bloque.total.total }}</td>
              </tr>
              <tr v-if="!bloque.filas.length"><td colspan="5" class="py-3 text-center text-gray-400 dark:text-gray-500">Sin datos</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tablero de Flota por Tipo de Equipo -->
      <div>
        <div class="flex items-center justify-between mb-3">
          <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Tablero de Flota</h2>
          <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
            <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500" /> Activo</span>
            <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-500" /> En taller (OT abierta)</span>
          </div>
        </div>
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

      <!-- Taller: Órdenes del mes + Vehículos en Taller -->
      <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
        <div class="panel overflow-x-auto">
          <h3 class="panel-title">Órdenes de Taller — {{ d.mesLabel }}</h3>
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                <th class="py-2 pr-2">OT</th><th class="py-2 pr-2">Vehículo</th><th class="py-2 pr-2">Taller</th>
                <th class="py-2 pr-2">Motivo</th><th class="py-2 pr-2">Estado</th><th class="py-2">Ingreso</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="ot in d.otsMes" :key="ot.numero" class="border-b border-gray-100 dark:border-gray-800">
                <td class="py-1.5 pr-2 font-mono text-gray-900 dark:text-gray-100">{{ ot.numero }}</td>
                <td class="py-1.5 pr-2 font-mono text-gray-700 dark:text-gray-300">{{ ot.vehiculo }}</td>
                <td class="py-1.5 pr-2 text-gray-700 dark:text-gray-300">{{ ot.taller }}</td>
                <td class="py-1.5 pr-2 text-gray-700 dark:text-gray-300">{{ ot.motivo }}</td>
                <td class="py-1.5 pr-2">
                  <span :class="estadoOtClase(ot.estado)" class="text-xs px-2 py-0.5 rounded-full capitalize">{{ ot.estado }}</span>
                </td>
                <td class="py-1.5 text-gray-700 dark:text-gray-300">{{ ot.fecha }}</td>
              </tr>
              <tr v-if="!d.otsMes.length"><td colspan="6" class="py-4 text-center text-gray-400 dark:text-gray-500">Sin órdenes en el mes</td></tr>
            </tbody>
          </table>
        </div>

        <div class="panel overflow-x-auto">
          <h3 class="panel-title">Vehículos en Taller ({{ d.taller.vehiculosTaller.length }})</h3>
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                <th class="py-2 pr-2">Código</th><th class="py-2 pr-2">Clase</th><th class="py-2 pr-2">OT</th>
                <th class="py-2 pr-2">Motivo</th><th class="py-2 pr-2">Días</th><th class="py-2">Estado</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="v in d.taller.vehiculosTaller" :key="v.codigo" class="border-b border-gray-100 dark:border-gray-800">
                <td class="py-1.5 pr-2 font-mono text-gray-900 dark:text-gray-100">{{ v.codigo }}</td>
                <td class="py-1.5 pr-2 text-gray-700 dark:text-gray-300">{{ v.clase }}</td>
                <td class="py-1.5 pr-2 font-mono text-gray-700 dark:text-gray-300">{{ v.ot }}</td>
                <td class="py-1.5 pr-2 text-gray-700 dark:text-gray-300">{{ v.motivo }}</td>
                <td class="py-1.5 pr-2 text-right">
                  <span :class="diasClase(v.dias)" class="font-mono text-xs px-1.5 py-0.5 rounded">{{ v.dias ?? '—' }}</span>
                </td>
                <td class="py-1.5">
                  <span :class="v.estado === 'Paralizado' ? 'status-badge-cancelado' : 'status-badge-proceso'" class="text-xs px-2 py-0.5 rounded-full">{{ v.estado }}</span>
                </td>
              </tr>
              <tr v-if="!d.taller.vehiculosTaller.length"><td colspan="6" class="py-4 text-center text-gray-400 dark:text-gray-500">Sin vehículos en taller</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Salud de Componentes -->
      <div>
        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Salud de Componentes</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4 mb-4">
          <div v-for="kpi in d.componentes.kpis" :key="kpi.label" class="kpi-card dark:bg-gray-800 dark:border-gray-700">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ kpi.label }}</p>
                <p class="text-xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ kpi.valor }}</p>
              </div>
              <div class="w-9 h-9 rounded-lg flex items-center justify-center" :class="kpi.color">
                <i :class="kpi.icono" class="text-white text-base" />
              </div>
            </div>
            <p v-if="kpi.subtexto" class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ kpi.subtexto }}</p>
          </div>
        </div>

        <!-- Ver componente: tabs según selección -->
        <div class="panel">
          <div class="flex flex-wrap gap-1 mb-3">
            <button
              v-for="t in compTabs"
              :key="t.key"
              type="button"
              class="px-3 py-1.5 text-sm rounded-md border transition"
              :class="compSeleccionado === t.key
                ? 'bg-blue-600 text-white border-blue-600 dark:bg-blue-500 dark:border-blue-500'
                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700'"
              @click="compSeleccionado = t.key"
            >
              {{ t.label }}
            </button>
          </div>

          <!-- Motores / Cajas / Diferenciales -->
          <table v-if="esListaComponente" class="w-full text-sm">
            <thead>
              <tr class="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                <th class="py-2 pr-2">Folio</th><th class="py-2 pr-2">Marca</th><th class="py-2 pr-2">Modelo</th>
                <th class="py-2 pr-2">Serie</th><th class="py-2 pr-2 text-right">Kms</th><th class="py-2 pr-2">Instalación</th><th class="py-2">Estado</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="x in compFilas" :key="x.folio" class="border-b border-gray-100 dark:border-gray-800">
                <td class="py-1.5 pr-2 font-mono text-gray-900 dark:text-gray-100">{{ x.folio }}</td>
                <td class="py-1.5 pr-2 text-gray-700 dark:text-gray-300">{{ x.marca }}</td>
                <td class="py-1.5 pr-2 text-gray-700 dark:text-gray-300">{{ x.modelo }}</td>
                <td class="py-1.5 pr-2 text-gray-700 dark:text-gray-300">{{ x.serie }}</td>
                <td class="py-1.5 pr-2 text-right text-gray-700 dark:text-gray-300">{{ x.kms }}</td>
                <td class="py-1.5 pr-2 text-gray-700 dark:text-gray-300">{{ x.instalacion }}</td>
                <td class="py-1.5">
                  <span :class="x.baja ? 'status-badge-cancelado' : 'status-badge-activo'" class="text-xs px-2 py-0.5 rounded-full">{{ x.baja ? 'Baja' : 'Activo' }}</span>
                </td>
              </tr>
              <tr v-if="!compFilas.length"><td colspan="7" class="py-4 text-center text-gray-400 dark:text-gray-500">Sin registros</td></tr>
            </tbody>
          </table>

          <!-- Neumáticos por vencer -->
          <table v-else-if="compSeleccionado === 'neumaticos'" class="w-full text-sm">
            <thead>
              <tr class="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                <th class="py-2 pr-2">Folio</th><th class="py-2 pr-2">Medida</th><th class="py-2 pr-2">Plan aviso</th><th class="py-2 pr-2">Kms</th><th class="py-2">Estado</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="n in d.componentes.neumaticosPorVencer" :key="n.folio" class="border-b border-gray-100 dark:border-gray-800">
                <td class="py-1.5 pr-2 font-mono text-gray-900 dark:text-gray-100">{{ n.folio }}</td>
                <td class="py-1.5 pr-2 text-gray-700 dark:text-gray-300">{{ n.medida }}</td>
                <td class="py-1.5 pr-2 text-gray-700 dark:text-gray-300">{{ n.planAviso }}</td>
                <td class="py-1.5 pr-2 text-gray-700 dark:text-gray-300">{{ n.kms }}</td>
                <td class="py-1.5">
                  <span :class="n.retirado ? 'status-badge-cancelado' : 'status-badge-proceso'" class="text-xs px-2 py-0.5 rounded-full">{{ n.retirado ? 'Retirado' : 'Vigente' }}</span>
                </td>
              </tr>
              <tr v-if="!d.componentes.neumaticosPorVencer.length"><td colspan="5" class="py-4 text-center text-gray-400 dark:text-gray-500">Sin neumáticos por vencer</td></tr>
            </tbody>
          </table>

          <!-- Baterías -->
          <table v-else class="w-full text-sm">
            <thead>
              <tr class="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                <th class="py-2 pr-2">Folio</th><th class="py-2 pr-2">V</th><th class="py-2 pr-2">A</th><th class="py-2 pr-2">Instalación</th><th class="py-2">Estado</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="b in d.componentes.baterias" :key="b.folio" class="border-b border-gray-100 dark:border-gray-800">
                <td class="py-1.5 pr-2 font-mono text-gray-900 dark:text-gray-100">{{ b.folio }}</td>
                <td class="py-1.5 pr-2 text-gray-700 dark:text-gray-300">{{ b.voltaje }}</td>
                <td class="py-1.5 pr-2 text-gray-700 dark:text-gray-300">{{ b.amperaje }}</td>
                <td class="py-1.5 pr-2 text-gray-700 dark:text-gray-300">{{ b.instalacion }}</td>
                <td class="py-1.5">
                  <span :class="b.baja ? 'status-badge-cancelado' : 'status-badge-activo'" class="text-xs px-2 py-0.5 rounded-full">{{ b.baja ? 'Baja' : 'Activa' }}</span>
                </td>
              </tr>
              <tr v-if="!d.componentes.baterias.length"><td colspan="5" class="py-4 text-center text-gray-400 dark:text-gray-500">Sin baterías</td></tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/Layouts/AppLayout.vue';
import DashHeader from '@/Components/Tecnico/DashHeader.vue';

const props = defineProps({
  title: { type: String, default: '' },
  mesLabel: String,
  entidadNombre: String,
  sinEntidad: Boolean,
  kpis: Array,
  desglose: Object,
  columnas: Array,
  otsMes: Array,
  taller: Object,
  componentes: Object,
  estados: Array,
  conflictos: Array,
});

const d = reactive({
  mesLabel: props.mesLabel,
  entidadNombre: props.entidadNombre,
  sinEntidad: props.sinEntidad,
  kpis: props.kpis || [],
  desglose: props.desglose || { tipo: [], marcaModelo: [] },
  columnas: props.columnas || [],
  otsMes: props.otsMes || [],
  taller: props.taller || { kpis: [], vehiculosTaller: [] },
  componentes: props.componentes || { kpis: [], neumaticosPorVencer: [], baterias: [], motores: [], cajas: [], diferenciales: [] },
  estados: props.estados || [],
  conflictos: props.conflictos || [],
});

const mesParam = ref(null);
const conectado = ref(false);
const ultima = ref(null);
let timer = null;

const suma = (filas, campo) => filas.reduce((a, r) => a + (r[campo] || 0), 0);
const bloquesDesglose = computed(() => {
  const tipos = d.desglose.tipo || [];
  const marcaModelo = d.desglose.marcaModelo || [];
  const mk = (titulo, campo, filas) => ({
    titulo,
    campo,
    filas,
    total: {
      activos: suma(filas, 'activos'),
      taller: suma(filas, 'taller'),
      baja: suma(filas, 'baja'),
      total: suma(filas, 'total'),
    },
  });
  return [
    mk('Composición por Tipo de Equipo', 'Tipo', tipos),
    mk('Composición por Marca-Modelo', 'Marca-Modelo', marcaModelo),
  ];
});

const flotaPorTipo = computed(() => {
  const cols = d.columnas || [];
  const todos = (Array.isArray(cols) ? cols : Object.values(cols)).flatMap((c) => c.vehiculos || []);
  const sinBaja = todos.filter((v) => !v.baja);
  const map = new Map();
  for (const v of sinBaja) {
    const nombre = v.tipoVehiculo || v.tipo || 'Sin tipo';
    if (!map.has(nombre)) map.set(nombre, { nombre, esArrastre: !!v.esArrastre, activos: [], taller: [] });
    const grupo = map.get(nombre);
    if (v.enTaller) grupo.taller.push(v);
    else grupo.activos.push(v);
  }
  const arr = [...map.values()];
  arr.sort((a, b) => b.activos.length + b.taller.length - (a.activos.length + a.taller.length));
  return arr.map((g) => ({
    ...g,
    activosCount: g.activos.length,
    tallerCount: g.taller.length,
    total: g.activos.length + g.taller.length,
  }));
});

const diasClase = (dias) => {
  if (dias == null) return '';
  if (dias >= 14) return 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300 font-bold';
  if (dias >= 7) return 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300 font-semibold';
  if (dias >= 3) return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300';
  return 'text-gray-700 dark:text-gray-300';
};

const estadoOtClase = (s) => s === 'abierta'
  ? 'status-badge-proceso'
  : (s === 'cerrada' ? 'status-badge-activo' : 'status-badge-cancelado');

// --- Salud de Componentes: "Ver" por selección ---
const compTabs = [
  { key: 'motores', label: 'Motores' },
  { key: 'cajas', label: 'Cajas' },
  { key: 'diferenciales', label: 'Diferenciales' },
  { key: 'neumaticos', label: 'Neumáticos' },
  { key: 'baterias', label: 'Baterías' },
];
const compSeleccionado = ref('motores');
const esListaComponente = computed(() => ['motores', 'cajas', 'diferenciales'].includes(compSeleccionado.value));
const compFilas = computed(() => ({
  motores: d.componentes.motores || [],
  cajas: d.componentes.cajas || [],
  diferenciales: d.componentes.diferenciales || [],
}[compSeleccionado.value] || []));

const recargar = async () => {
  try {
    const qs = mesParam.value ? `?mes=${mesParam.value}` : '';
    const r = await fetch(`/api/tecnico/pizarra-operativa${qs}`, { headers: { Accept: 'application/json' } });
    const j = await r.json();
    Object.assign(d, j);
    conectado.value = true;
    ultima.value = new Date().toLocaleTimeString('es-ES');
    await nextTick();
  } catch (e) {
    conectado.value = false;
  }
};

const cambiarMes = (delta) => {
  const base = mesParam.value ? new Date(mesParam.value + '-01') : new Date();
  base.setMonth(base.getMonth() + delta);
  mesParam.value = base.toISOString().slice(0, 7);
  recargar();
};
const irMesActual = () => { mesParam.value = null; recargar(); };

onMounted(() => { timer = setInterval(recargar, 20000); });
onUnmounted(() => { if (timer) clearInterval(timer); });
</script>
