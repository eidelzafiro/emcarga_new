<template>
  <AppLayout title="Flota de Vehículos">
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          Flota de Vehículos
        </h2>
        <button
          @click="showModal = true"
          class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium"
        >
          Nuevo Vehículo
        </button>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Filtros -->
        <div class="mb-6">
          <input
            v-model="search"
            type="text"
            placeholder="Buscar por descripción o placa..."
            class="w-full md:w-1/3 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
            @input="debouncedSearch"
          />
        </div>

        <!-- Tabla -->
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Descripción
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Placa
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Marca
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Estado
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Acciones
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="tractivo in tractivos.data" :key="tractivo.id">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ tractivo.descripcion }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ tractivo.placa }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ tractivo.marca }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                    :class="tractivo.estado === 'activo' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                  >
                    {{ tractivo.estado }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                  <button
                    @click="edit(tractivo)"
                    class="text-indigo-600 hover:text-indigo-900 mr-3"
                  >
                    Editar
                  </button>
                  <button
                    @click="confirmDelete(tractivo)"
                    class="text-red-600 hover:text-red-900"
                  >
                    Eliminar
                  </button>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Paginación -->
          <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
            <div class="flex-1 flex justify-between sm:hidden">
              <Link
                v-if="tractivos.prev_page_url"
                :href="tractivos.prev_page_url"
                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
              >
                Anterior
              </Link>
              <Link
                v-if="tractivos.next_page_url"
                :href="tractivos.next_page_url"
                class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
              >
                Siguiente
              </Link>
            </div>
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
              <div>
                <p class="text-sm text-gray-700">
                  Mostrando {{ tractivos.from }} a {{ tractivos.to }} de {{ tractivos.total }} resultados
                </p>
              </div>
              <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                  <Link
                    v-for="link in tractivos.links"
                    :key="link.url"
                    :href="link.url || '#'"
                    class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
                    :class="link.active ? 'bg-indigo-50 border-indigo-500 text-indigo-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'"
                  >
                    <span v-html="link.label"></span>
                  </Link>
                </nav>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <Modal v-if="showModal" @close="showModal = false">
      <template #header>
        <h3>{{ editing ? 'Editar Vehículo' : 'Nuevo Vehículo' }}</h3>
      </template>
      <template #body>
        <form @submit.prevent="submit">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Descripción *</label>
              <input v-model="form.descripcion" type="text" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Placa *</label>
              <input v-model="form.placa" type="text" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" />
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Marca</label>
                <input v-model="form.marca" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Modelo</label>
                <input v-model="form.modelo" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" />
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Año</label>
              <input v-model="form.anno" type="number" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" />
            </div>
          </div>
          <div class="mt-6 flex justify-end space-x-3">
            <button type="button" @click="showModal = false" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
              Cancelar
            </button>
            <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
              {{ editing ? 'Actualizar' : 'Crear' }}
            </button>
          </div>
        </form>
      </template>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import { debounce } from 'lodash';

const props = defineProps({
  tractivos: Object,
  filters: Object,
});

const search = ref(props.filters?.search || '');
const showModal = ref(false);
const editing = ref(false);
const form = reactive({
  id: null,
  descripcion: '',
  placa: '',
  marca: '',
  modelo: '',
  anno: null,
});

const debouncedSearch = debounce(() => {
  router.get(route('tractivos.index'), { search: search.value }, {
    preserveState: true,
    replace: true,
  });
}, 300);

const edit = (tractivo) => {
  editing.value = true;
  Object.assign(form, tractivo);
  showModal.value = true;
};

const submit = () => {
  if (editing.value) {
    router.put(route('tractivos.update', form.id), form, {
      onSuccess: () => {
        showModal.value = false;
        editing.value = false;
        resetForm();
      },
    });
  } else {
    router.post(route('tractivos.store'), form, {
      onSuccess: () => {
        showModal.value = false;
        resetForm();
      },
    });
  }
};

const confirmDelete = (tractivo) => {
  if (confirm('¿Está seguro de eliminar este vehículo?')) {
    router.delete(route('tractivos.destroy', tractivo.id));
  }
};

const resetForm = () => {
  form.id = null;
  form.descripcion = '';
  form.placa = '';
  form.marca = '';
  form.modelo = '';
  form.anno = null;
};
</script>
