<template>
  <AppLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Gestión de usuarios
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
          <!-- Barra superior: búsqueda + nuevo -->
          <div class="p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-gray-200">
            <input
              v-model="search"
              type="text"
              placeholder="Buscar por nombre o usuario…"
              class="w-full sm:w-80 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              @input="buscar"
            />
            <button
              v-if="can('usuarios.crear')"
              class="inline-flex justify-center px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition"
              @click="abrirCrear"
            >
              Nuevo usuario
            </button>
          </div>

          <!-- Tabla -->
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Perfil</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Último acceso</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="usuario in usuarios.data" :key="usuario.id">
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ usuario.name }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ usuario.username }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ usuario.roles.map(r => r.name).join(', ') || '—' }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span v-if="usuario.bloqueado || usuario.intentos_fallidos >= 5" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                      Bloqueado
                    </span>
                    <span v-else-if="usuario.password_temporal" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                      Contraseña temporal
                    </span>
                    <span v-else class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                      Activo
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ formatoFecha(usuario.ultimo_login) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                    <button v-if="can('usuarios.editar')" class="text-indigo-600 hover:text-indigo-900" @click="abrirEditar(usuario)">
                      Editar
                    </button>
                    <button
                      v-if="can('usuarios.desbloquear') && (usuario.bloqueado || usuario.intentos_fallidos >= 5)"
                      class="text-yellow-600 hover:text-yellow-900"
                      @click="desbloquear(usuario)"
                    >
                      Desbloquear
                    </button>
                    <button v-if="can('usuarios.restablecer')" class="text-gray-600 hover:text-gray-900" @click="abrirRestablecer(usuario)">
                      Restablecer
                    </button>
                    <button v-if="can('usuarios.eliminar')" class="text-red-600 hover:text-red-900" @click="abrirEliminar(usuario)">
                      Eliminar
                    </button>
                  </td>
                </tr>
                <tr v-if="usuarios.data.length === 0">
                  <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                    No se encontraron usuarios.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Paginación -->
          <div v-if="usuarios.links.length > 3" class="p-4 border-t border-gray-200 flex flex-wrap gap-1">
            <Link
              v-for="(link, i) in usuarios.links"
              :key="i"
              :href="link.url || '#'"
              class="px-3 py-1 rounded text-sm"
              :class="link.active ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100'"
              v-html="link.label"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Modal crear/editar -->
    <Modal v-if="modalForm" @close="cerrarModales">
      <template #header>{{ editando ? 'Editar usuario' : 'Nuevo usuario' }}</template>
      <template #body>
        <form class="space-y-4" @submit.prevent="guardarForm">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre completo</label>
            <input v-model="form.name" type="text" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" />
            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Usuario</label>
            <input v-model="form.username" type="text" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" />
            <p v-if="form.errors.username" class="mt-1 text-sm text-red-600">{{ form.errors.username }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Correo (opcional)</label>
            <input v-model="form.email" type="email" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" />
            <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
          </div>
          <div v-if="!editando">
            <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña temporal</label>
            <input v-model="form.password" type="text" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" />
            <p class="mt-1 text-xs text-gray-500">El usuario deberá cambiarla en su primer acceso.</p>
            <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Perfil</label>
            <select v-model="form.role" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
              <option value="" disabled>Seleccione un perfil</option>
              <option v-for="rol in roles" :key="rol" :value="rol">{{ rol }}</option>
            </select>
            <p v-if="form.errors.role" class="mt-1 text-sm text-red-600">{{ form.errors.role }}</p>
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

    <!-- Modal restablecer contraseña -->
    <Modal v-if="modalReset" @close="cerrarModales">
      <template #header>Restablecer contraseña</template>
      <template #body>
        <p class="text-sm text-gray-600 mb-4">
          Indique la contraseña temporal para <strong>{{ seleccionado?.username }}</strong>.
          Deberá cambiarla en su próximo acceso. También se desbloqueará la cuenta.
        </p>
        <input v-model="formReset.password" type="text" placeholder="Contraseña temporal" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" />
        <p v-if="formReset.errors.password" class="mt-1 text-sm text-red-600">{{ formReset.errors.password }}</p>
      </template>
      <template #footer>
        <button
          class="w-full inline-flex justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto disabled:opacity-50"
          :disabled="formReset.processing"
          @click="restablecer"
        >
          Restablecer
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
      <template #header>Eliminar usuario</template>
      <template #body>
        <p class="text-sm text-gray-600">
          ¿Está seguro de eliminar al usuario <strong>{{ seleccionado?.username }}</strong> ({{ seleccionado?.name }})?
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
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
  usuarios: Object,
  roles: Array,
  filters: Object,
});

const page = usePage();
const permissions = computed(() => page.props.auth?.permissions ?? []);
const can = (permiso) => permissions.value.includes(permiso);

// Búsqueda
const search = ref(props.filters?.search ?? '');
let timer = null;
const buscar = () => {
  clearTimeout(timer);
  timer = setTimeout(() => {
    router.get(route('usuarios.index'), { search: search.value }, { preserveState: true, replace: true });
  }, 300);
};

// Estado de modales
const modalForm = ref(false);
const modalReset = ref(false);
const modalEliminar = ref(false);
const editando = ref(false);
const seleccionado = ref(null);

const form = useForm({
  name: '',
  username: '',
  email: '',
  password: '',
  role: '',
  idunidad: null,
  idgrupo: null,
});

const formReset = useForm({ password: '' });
const formEliminar = useForm({});

const abrirCrear = () => {
  editando.value = false;
  seleccionado.value = null;
  form.reset();
  form.clearErrors();
  modalForm.value = true;
};

const abrirEditar = (usuario) => {
  editando.value = true;
  seleccionado.value = usuario;
  form.name = usuario.name;
  form.username = usuario.username;
  form.email = usuario.email ?? '';
  form.password = '';
  form.role = usuario.roles[0]?.name ?? '';
  form.idunidad = usuario.idunidad;
  form.idgrupo = usuario.idgrupo;
  form.clearErrors();
  modalForm.value = true;
};

const guardarForm = () => {
  if (editando.value) {
    form.put(route('usuarios.update', seleccionado.value.id), {
      onSuccess: () => cerrarModales(),
    });
  } else {
    form.post(route('usuarios.store'), {
      onSuccess: () => cerrarModales(),
    });
  }
};

const abrirRestablecer = (usuario) => {
  seleccionado.value = usuario;
  formReset.reset();
  formReset.clearErrors();
  modalReset.value = true;
};

const restablecer = () => {
  formReset.post(route('usuarios.restablecer', seleccionado.value.id), {
    onSuccess: () => cerrarModales(),
  });
};

const abrirEliminar = (usuario) => {
  seleccionado.value = usuario;
  modalEliminar.value = true;
};

const eliminar = () => {
  formEliminar.delete(route('usuarios.destroy', seleccionado.value.id), {
    onSuccess: () => cerrarModales(),
  });
};

const desbloquear = (usuario) => {
  router.post(route('usuarios.desbloquear', usuario.id));
};

const cerrarModales = () => {
  modalForm.value = false;
  modalReset.value = false;
  modalEliminar.value = false;
  seleccionado.value = null;
};

const formatoFecha = (fecha) => {
  if (!fecha) return 'Nunca';
  return new Date(fecha).toLocaleString('es');
};
</script>
