<template>
  <AppLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Gestión de perfiles
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Mensajes flash -->
        <div v-if="$page.props.flash.success" class="mb-4 rounded-lg bg-green-50 border border-green-200 p-4 text-sm text-green-800">
          {{ $page.props.flash.success }}
        </div>
        <div v-if="$page.props.flash.error" class="mb-4 rounded-lg bg-red-50 border border-red-200 p-4 text-sm text-red-800">
          {{ $page.props.flash.error }}
        </div>

        <div class="bg-white shadow rounded-lg">
          <div class="p-4 flex items-center justify-between border-b border-gray-200">
            <p class="text-sm text-gray-600">
              Perfiles (roles) del sistema y los permisos asignados a cada uno.
            </p>
            <button
              v-if="can('perfiles.editar')"
              class="inline-flex justify-center px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition"
              @click="abrirCrear"
            >
              Nuevo perfil
            </button>
          </div>

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Perfil</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuarios</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Permisos</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="perfil in perfiles" :key="perfil.id">
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    {{ perfil.name }}
                    <span v-if="perfil.name === 'ADMIN'" class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-600">
                      protegido
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ perfil.users_count }}
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-500">
                    <div class="flex flex-wrap gap-1">
                      <span
                        v-for="permiso in perfil.permissions.slice(0, 6)"
                        :key="permiso.id"
                        class="px-2 py-0.5 inline-flex text-xs rounded-full bg-indigo-50 text-indigo-700"
                      >
                        {{ permiso.name }}
                      </span>
                      <span v-if="perfil.permissions.length > 6" class="px-2 py-0.5 text-xs text-gray-400">
                        +{{ perfil.permissions.length - 6 }} más
                      </span>
                      <span v-if="perfil.permissions.length === 0" class="text-xs text-gray-400">Sin permisos</span>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                    <button v-if="can('perfiles.editar')" class="text-indigo-600 hover:text-indigo-900" @click="abrirEditar(perfil)">
                      Editar
                    </button>
                    <button
                      v-if="can('perfiles.editar') && perfil.name !== 'ADMIN'"
                      class="text-red-600 hover:text-red-900"
                      @click="abrirEliminar(perfil)"
                    >
                      Eliminar
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal crear/editar -->
    <Modal v-if="modalForm" @close="cerrarModales">
      <template #header>{{ editando ? 'Editar perfil' : 'Nuevo perfil' }}</template>
      <template #body>
        <form class="space-y-4" @submit.prevent="guardarForm">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del perfil</label>
            <input
              v-model="form.nombre"
              type="text"
              class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 uppercase"
              :disabled="editando && seleccionado?.name === 'ADMIN'"
            />
            <p v-if="form.errors.nombre" class="mt-1 text-sm text-red-600">{{ form.errors.nombre }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Permisos asignados</label>
            <div class="max-h-72 overflow-y-auto border border-gray-200 rounded-lg divide-y divide-gray-100">
              <div v-for="(permisosGrupo, modulo) in permisosAgrupados" :key="modulo" class="p-3">
                <label class="flex items-center justify-between cursor-pointer">
                  <span class="text-sm font-semibold text-gray-700 capitalize">{{ modulo }}</span>
                  <input
                    type="checkbox"
                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                    :checked="grupoCompleto(permisosGrupo)"
                    @change="alternarGrupo(permisosGrupo)"
                  />
                </label>
                <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-1">
                  <label
                    v-for="permiso in permisosGrupo"
                    :key="permiso"
                    class="flex items-center space-x-2 text-sm text-gray-600 cursor-pointer"
                  >
                    <input
                      v-model="form.permisos"
                      type="checkbox"
                      :value="permiso"
                      class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                    />
                    <span>{{ permiso.split('.')[1] }}</span>
                  </label>
                </div>
              </div>
            </div>
            <p v-if="form.errors.permisos" class="mt-1 text-sm text-red-600">{{ form.errors.permisos }}</p>
          </div>
        </form>
      </template>
      <template #footer>
        <button
          class="w-full inline-flex justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto disabled:opacity-50"
          :disabled="form.processing"
          @click="guardarForm"
        >
          {{ form.processing ? 'Guardando…' : 'Guardar' }}
        </button>
        <button
          class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto"
          @click="cerrarModales"
        >
          Cancelar
        </button>
      </template>
    </Modal>

    <!-- Modal eliminar -->
    <Modal v-if="modalEliminar" @close="cerrarModales">
      <template #header>Eliminar perfil</template>
      <template #body>
        <p class="text-sm text-gray-600">
          ¿Está seguro de eliminar el perfil <strong>{{ seleccionado?.name }}</strong>?
          <span v-if="seleccionado?.users_count > 0" class="block mt-2 text-red-600">
            Tiene {{ seleccionado.users_count }} usuario(s) asignado(s); el sistema no lo permitirá.
          </span>
        </p>
      </template>
      <template #footer>
        <button
          class="w-full inline-flex justify-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 sm:ml-3 sm:w-auto disabled:opacity-50"
          :disabled="formEliminar.processing"
          @click="eliminar"
        >
          Eliminar
        </button>
        <button
          class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto"
          @click="cerrarModales"
        >
          Cancelar
        </button>
      </template>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
  perfiles: Array,
  permisos: Array,
});

const page = usePage();
const permissions = computed(() => page.props.auth?.permissions ?? []);
const can = (permiso) => permissions.value.includes(permiso);

// Agrupa los permisos por módulo (prefijo antes del punto)
const permisosAgrupados = computed(() => {
  const grupos = {};
  props.permisos.forEach((permiso) => {
    const modulo = permiso.split('.')[0];
    (grupos[modulo] ??= []).push(permiso);
  });
  return grupos;
});

const modalForm = ref(false);
const modalEliminar = ref(false);
const editando = ref(false);
const seleccionado = ref(null);

const form = useForm({
  nombre: '',
  permisos: [],
});

const formEliminar = useForm({});

const abrirCrear = () => {
  editando.value = false;
  seleccionado.value = null;
  form.reset();
  form.clearErrors();
  modalForm.value = true;
};

const abrirEditar = (perfil) => {
  editando.value = true;
  seleccionado.value = perfil;
  form.nombre = perfil.name;
  form.permisos = perfil.permissions.map((p) => p.name);
  form.clearErrors();
  modalForm.value = true;
};

const guardarForm = () => {
  if (editando.value) {
    form.put(route('perfiles.update', seleccionado.value.id), {
      onSuccess: () => cerrarModales(),
    });
  } else {
    form.post(route('perfiles.store'), {
      onSuccess: () => cerrarModales(),
    });
  }
};

const grupoCompleto = (permisosGrupo) =>
  permisosGrupo.every((p) => form.permisos.includes(p));

const alternarGrupo = (permisosGrupo) => {
  if (grupoCompleto(permisosGrupo)) {
    form.permisos = form.permisos.filter((p) => !permisosGrupo.includes(p));
  } else {
    form.permisos = [...new Set([...form.permisos, ...permisosGrupo])];
  }
};

const abrirEliminar = (perfil) => {
  seleccionado.value = perfil;
  modalEliminar.value = true;
};

const eliminar = () => {
  formEliminar.delete(route('perfiles.destroy', seleccionado.value.id), {
    onSuccess: () => cerrarModales(),
  });
};

const cerrarModales = () => {
  modalForm.value = false;
  modalEliminar.value = false;
  seleccionado.value = null;
};
</script>
