<template>
  <AppLayout :title="title">
    <div class="space-y-6">
      <!-- Encabezado -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Áreas</h1>
          <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Estructura organizativa — organigrama interactivo</p>
        </div>
        <div class="flex gap-2">
          <button @click="expandAll" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition">
            <i class="pi pi-plus mr-1"></i> Expandir
          </button>
          <button @click="collapseAll" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition">
            <i class="pi pi-minus mr-1"></i> Colapsar
          </button>
          <button @click="abrirCrear()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition">
            <i class="pi pi-plus mr-1"></i> Nueva Área Raíz
          </button>
        </div>
      </div>

      <!-- Árbol -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="p-5">
          <div v-if="areas.length" class="space-y-1">
            <AreaNodo
              v-for="area in areas"
              :key="area.id"
              :area="area"
              :level="0"
              :expanded="expandedAll"
              @editar="abrirEditar"
              @agregar-hijo="abrirCrear"
              @eliminar="abrirEliminar"
              @mover="moverArea"
            />
          </div>
          <div v-else class="text-center py-12">
            <i class="pi pi-sitemap text-4xl text-gray-300 dark:text-gray-600 block mb-3" />
            <p class="text-gray-500 dark:text-gray-400 mb-4">No hay áreas configuradas</p>
            <button @click="abrirCrear()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm">
              <i class="pi pi-plus mr-1"></i> Crear primera área
            </button>
          </div>
        </div>
      </div>

      <!-- Stats -->
      <div v-if="areas.length" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
          <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Áreas</p>
          <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ totalAreas }}</p>
        </div>
        <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
          <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Áreas Raíz</p>
          <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ areas.length }}</p>
        </div>
        <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
          <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Profundidad Máx.</p>
          <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ maxDepth }}</p>
        </div>
      </div>
    </div>

    <!-- Dialog Crear/Editar -->
    <Dialog v-model:visible="dlgVisible" :header="dlgEditando ? 'Editar Área' : 'Nueva Área'" modal :style="{ width: '420px' }" class="p-fluid">
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre *</label>
          <InputText v-model="form.nombre" class="w-full" placeholder="Nombre del área" autofocus />
          <small v-if="formErrors.nombre" class="text-red-500">{{ formErrors.nombre }}</small>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Área Superior</label>
          <Select
            v-model="form.id_area_padre"
            :options="opcionesPadre"
            optionLabel="label"
            optionValue="value"
            placeholder="(Raíz — sin padre)"
            class="w-full"
            showClear
          />
        </div>
      </div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" text @click="dlgVisible = false" />
        <Button :label="dlgEditando ? 'Guardar' : 'Crear'" @click="guardar" :loading="guardando" />
      </template>
    </Dialog>

    <!-- Dialog Eliminar -->
    <Dialog v-model:visible="dlgEliminarVisible" header="Eliminar Área" modal :style="{ width: '400px' }">
      <p class="text-sm text-gray-600 dark:text-gray-400">
        ¿Está seguro que desea eliminar <strong>{{ eliminarNombre }}</strong>?
      </p>
      <template #footer>
        <Button label="Cancelar" severity="secondary" text @click="dlgEliminarVisible = false" />
        <Button label="Eliminar" severity="danger" @click="confirmarEliminar" :loading="eliminando" />
      </template>
    </Dialog>

    <!-- Dialog Error -->
    <Dialog v-model:visible="dlgErrorVisible" header="Error" modal :style="{ width: '400px' }">
      <p class="text-sm text-red-600 dark:text-red-400">{{ dlgErrorMsg }}</p>
      <template #footer>
        <Button label="Aceptar" @click="dlgErrorVisible = false" />
      </template>
    </Dialog>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AreaNodo from '@/Components/AreaNodo.vue';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Button from 'primevue/button';

const props = defineProps({
  title: { type: String, default: 'Áreas' },
  areas: { type: Array, default: () => [] },
  todasAreas: { type: Array, default: () => [] },
  errors: { type: Object, default: () => ({}) },
});

const expandedAll = ref(true);
const dlgVisible = ref(false);
const dlgEditando = ref(false);
const dlgAreaId = ref(null);
const dlgPadreId = ref(null);
const dlgPadreNombre = ref('');
const dlgEliminarVisible = ref(false);
const dlgErrorVisible = ref(false);
const dlgErrorMsg = ref('');
const eliminarId = ref(null);
const eliminarNombre = ref('');
const guardando = ref(false);
const eliminando = ref(false);

const form = ref({ nombre: '' });
const formErrors = ref({});

const opcionesPadre = computed(() => {
  if (!props.todasAreas) return [];
  return props.todasAreas
    .filter(a => !dlgEditando.value || a.id !== dlgAreaId.value)
    .map(a => ({ label: a.nombre, value: a.id }));
});

function expandAll() { expandedAll.value = true; }
function collapseAll() { expandedAll.value = false; }

function abrirCrear(padreId = null, padreNombre = '') {
  dlgEditando.value = false;
  dlgAreaId.value = null;
  dlgPadreId.value = padreId;
  dlgPadreNombre.value = padreNombre;
  form.value = { nombre: '', id_area_padre: padreId || null };
  formErrors.value = {};
  dlgVisible.value = true;
}

function abrirEditar(area) {
  dlgEditando.value = true;
  dlgAreaId.value = area.id;
  dlgPadreId.value = area.id_area_padre;
  dlgPadreNombre.value = '';
  form.value = { nombre: area.nombre, id_area_padre: area.id_area_padre || null };
  formErrors.value = {};
  dlgVisible.value = true;
}

function abrirEliminar(area) {
  eliminarId.value = area.id;
  eliminarNombre.value = area.nombre;
  dlgEliminarVisible.value = true;
}

function guardar() {
  guardando.value = true;
  formErrors.value = {};

  const data = { nombre: form.value.nombre };
  if (form.value.id_area_padre) data.id_area_padre = form.value.id_area_padre;

  const url = dlgEditando.value
    ? route('areas.update', dlgAreaId.value)
    : route('areas.store');

  const opts = {
    preserveState: true,
    onSuccess: () => {
      dlgVisible.value = false;
      guardando.value = false;
    },
    onError: (errs) => {
      formErrors.value = errs;
      guardando.value = false;
    },
    onFinish: () => { guardando.value = false; },
  };

  if (dlgEditando.value) {
    router.put(url, data, opts);
  } else {
    router.post(url, data, opts);
  }
}

function confirmarEliminar() {
  eliminando.value = true;
  router.delete(route('areas.destroy', eliminarId.value), {
    preserveState: true,
    onSuccess: () => {
      dlgEliminarVisible.value = false;
      eliminando.value = false;
    },
    onError: (errs) => {
      dlgEliminarVisible.value = false;
      dlgErrorMsg.value = errs.error || 'No se pudo eliminar el área.';
      dlgErrorVisible.value = true;
      eliminando.value = false;
    },
  });
}

function moverArea({ area_id, nuevo_padre_id, area_nombre, nuevo_padre_nombre }) {
  router.put(route('areas.update', area_id), {
    nombre: area_nombre,
    id_area_padre: nuevo_padre_id,
  }, {
    preserveState: true,
    onSuccess: () => {},
    onError: (errs) => {
      dlgErrorMsg.value = errs.error || 'No se pudo mover el área.';
      dlgErrorVisible.value = true;
    },
  });
}

function countAreas(list) {
  let count = 0;
  for (const a of list) {
    count++;
    if (a.sub_areas && a.sub_areas.length) count += countAreas(a.sub_areas);
  }
  return count;
}

function calcMaxDepth(list, depth = 1) {
  let max = depth;
  for (const a of list) {
    if (a.sub_areas && a.sub_areas.length) {
      const sub = calcMaxDepth(a.sub_areas, depth + 1);
      if (sub > max) max = sub;
    }
  }
  return max;
}

const totalAreas = computed(() => countAreas(props.areas));
const maxDepth = computed(() => calcMaxDepth(props.areas));
</script>
