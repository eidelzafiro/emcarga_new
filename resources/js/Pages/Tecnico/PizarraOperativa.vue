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

      <!-- Tablero de Flota (toda la flota, color por estado) -->
      <div>
        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Tablero de Flota</h2>
        <div>
          <template v-if="gruposFlota.length">
            <section v-for="g in gruposFlota" :key="g.nombre" class="mb-5">
              <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100 mb-2">
                {{ g.nombre }} <span class="text-gray-400 font-normal">({{ g.vehiculos.length }})</span>
              </h3>
              <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-3">
                <div
                  v-for="v in g.vehiculos"
                  :key="v.id"
                  class="rounded-xl border-2 overflow-hidden bg-white dark:bg-gray-800 shadow-sm flex flex-col min-h-[170px]"
                  :class="bordeColor(v)"
                  :title="v.ot ? `OT ${v.ot.numero} · ${v.ot.taller ?? '—'} · ${v.ot.motivo ?? '—'} · ${v.ot.dias} días en taller` : null"
                >
                  <img v-if="v.imagen" :src="v.imagen" alt="" class="h-24 w-full object-cover bg-gray-100 dark:bg-gray-700" />
                  <div v-else class="h-24 w-full bg-gray-100 dark:bg-gray-700" />
                  <div class="p-3 flex-1 flex flex-col">
                    <div class="flex items-center justify-between gap-2">
                      <span class="font-mono text-2xl font-extrabold text-gray-900 dark:text-gray-100 leading-none">{{ v.codigo }}</span>
                      <span v-if="v.baja" class="text-[10px] uppercase font-bold px-1.5 py-0.5 rounded bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">Baja</span>
                      <span v-else-if="v.enTaller" class="text-[10px] uppercase font-bold px-1.5 py-0.5 rounded bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">En taller</span>
                    </div>

                    <div v-if="v.ot" class="mt-2 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800 px-2 py-1.5 text-xs space-y-0.5">
                      <p class="text-amber-700 dark:text-amber-300 font-semibold">OT {{ v.ot.numero }}</p>
                      <p class="text-gray-600 dark:text-gray-300 truncate">{{ v.ot.taller ?? '—' }}{{ (v.ot.taller && v.ot.motivo) ? ' · ' : '' }}{{ v.ot.motivo ?? '' }}</p>
                      <p class="text-gray-400 dark:text-gray-500">{{ v.ot.dias }} días en taller</p>
                    </div>

                  </div>
                </div>
              </div>
            </section>
          </template>
          <p v-else class="text-xs text-gray-400 dark:text-gray-500 py-6 text-center">Sin vehículos en la flota</p>
        </div>
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
                  <span :class="estadoOtClase(ot.estado)" class="text-xs px-2 py-0.5 rounded-full">{{ ot.estado }}</span>
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
                <td class="py-1.5 pr-2 text-right text-gray-700 dark:text-gray-300">{{ v.dias }}</td>
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

const gruposFlota = computed(() => {
  const cols = d.columnas || [];
  const todos = (Array.isArray(cols) ? cols : Object.values(cols)).flatMap((c) => c.vehiculos || []);
  const out = [];
  for (const esArr of [false, true]) {
    const lista = todos.filter((v) => !!v.esArrastre === esArr);
    if (!lista.length) continue;
    lista.sort((a, b) => String(a.codigo).localeCompare(String(b.codigo)));
    out.push({ nombre: esArr ? 'Arrastres' : 'Tractivos', vehiculos: lista });
  }
  return out;
});

const bordeColor = (v) => {
  if (v.baja) return 'border-red-400 dark:border-red-600';
  if (v.enTaller) return 'border-amber-400 dark:border-amber-600';
  if (v.estado === 14) return 'border-emerald-400 dark:border-emerald-600';
  return 'border-gray-300 dark:border-gray-600';
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
