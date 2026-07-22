<template>
  <AppLayout>
    <Card>
      <template #title>Gestión de perfiles</template>
      <template #content>
        <p class="text-sm text-surface-500 mb-4">
          Perfiles (roles) del sistema y los permisos asignados a cada uno.
        </p>

        <DataTable :value="perfiles" stripedRows size="small">
          <Column field="name" header="Perfil">
            <template #body="{ data }">
              <div class="flex items-center gap-2">
                <span class="font-medium">{{ data.name }}</span>
                <Tag v-if="data.name === 'ADMIN'" value="protegido" severity="secondary" size="small" />
              </div>
            </template>
          </Column>
          <Column header="Usuarios">
            <template #body="{ data }">
              <span class="text-sm">{{ data.users_count }}</span>
            </template>
          </Column>
          <Column header="Permisos">
            <template #body="{ data }">
              <div class="flex flex-wrap gap-1">
                <Tag
                  v-for="permiso in data.permissions.slice(0, 6)"
                  :key="permiso.id"
                  :value="permiso.name"
                  severity="info"
                  size="small"
                />
                <Tag v-if="data.permissions.length > 6" :value="'+' + (data.permissions.length - 6) + ' más'" severity="secondary" size="small" />
                <span v-if="data.permissions.length === 0" class="text-xs text-surface-400">Sin permisos</span>
              </div>
            </template>
          </Column>
          <Column header="Acciones" :exportable="false">
            <template #body="{ data }">
              <div class="flex gap-1">
                <Button
                  v-if="can('perfiles.editar')"
                  icon="pi pi-pencil"
                  severity="secondary"
                  text
                  rounded
                  size="small"
                  @click="abrirEditar(data)"
                  v-tooltip.left="'Editar'"
                />
                <Button
                  v-if="can('perfiles.editar') && data.name !== 'ADMIN'"
                  icon="pi pi-trash"
                  severity="danger"
                  text
                  rounded
                  size="small"
                  @click="abrirEliminar(data)"
                  v-tooltip.left="'Eliminar'"
                />
              </div>
            </template>
          </Column>
          <template #empty>
            <div class="text-center py-8 text-surface-400">
              <i class="pi pi-shield text-3xl mb-2 block" />
              No hay perfiles registrados.
            </div>
          </template>
        </DataTable>

        <div class="mt-4 flex justify-end">
          <Button
            v-if="can('perfiles.editar')"
            icon="pi pi-plus"
            label="Nuevo perfil"
            @click="abrirCrear"
          />
        </div>
      </template>
    </Card>

    <!-- Modal crear/editar -->
    <Dialog
      v-model:visible="modalForm"
      :header="editando ? 'Editar perfil' : 'Nuevo perfil'"
      :modal="true"
      class="w-full max-w-lg"
    >
      <form class="space-y-4" @submit.prevent="guardarForm">
        <div>
          <label class="block text-sm font-medium text-surface-700 mb-1">Nombre del perfil</label>
          <InputText
            v-model="form.nombre"
            class="w-full uppercase"
            :disabled="editando && seleccionado?.name === 'ADMIN'"
            :class="{ 'p-invalid': form.errors.nombre }"
          />
          <small v-if="form.errors.nombre" class="text-red-500">{{ form.errors.nombre }}</small>
        </div>

        <div>
          <label class="block text-sm font-medium text-surface-700 mb-2">Permisos asignados</label>
          <div class="max-h-80 overflow-y-auto border border-surface-200 rounded-lg divide-y divide-surface-100">
            <div v-for="(permisosGrupo, modulo) in permisosAgrupados" :key="modulo" class="p-3">
              <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-semibold text-surface-700 capitalize">{{ modulo }}</span>
                <Button
                  :label="grupoCompleto(permisosGrupo) ? 'Quitar todos' : 'Seleccionar todos'"
                  size="small"
                  severity="secondary"
                  text
                  @click="alternarGrupo(permisosGrupo)"
                />
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-1">
                <label
                  v-for="permiso in permisosGrupo"
                  :key="permiso"
                  class="flex items-center gap-2 text-sm text-surface-600 cursor-pointer p-1 rounded hover:bg-surface-50"
                >
                  <input
                    v-model="form.permisos"
                    type="checkbox"
                    :value="permiso"
                    class="rounded border-surface-300 text-emerald-600 focus:ring-emerald-500"
                  />
                  <span>{{ permiso.split('.')[1] }}</span>
                </label>
              </div>
            </div>
          </div>
          <small v-if="form.errors.permisos" class="text-red-500">{{ form.errors.permisos }}</small>
        </div>
      </form>
      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="cerrarModales" />
        <Button
          :label="form.processing ? 'Guardando…' : 'Guardar'"
          :loading="form.processing"
          @click="guardarForm"
        />
      </template>
    </Dialog>

    <!-- Modal eliminar -->
    <Dialog
      v-model:visible="modalEliminar"
      header="Eliminar perfil"
      :modal="true"
      class="w-full max-w-sm"
    >
      <p class="text-sm text-surface-600">
        ¿Está seguro de eliminar el perfil <strong>{{ seleccionado?.name }}</strong>?
        <span v-if="seleccionado?.users_count > 0" class="block mt-2 text-red-500">
          Tiene {{ seleccionado.users_count }} usuario(s) asignado(s); el sistema no lo permitirá.
        </span>
      </p>
      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="cerrarModales" />
        <Button
          label="Eliminar"
          severity="danger"
          :loading="formEliminar.processing"
          @click="eliminar"
        />
      </template>
    </Dialog>
  </AppLayout>
</template>

<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  perfiles: Array,
  permisos: Array,
});

const page = usePage();
const permissions = computed(() => page.props.auth?.permissions ?? []);
const can = (permiso) => permissions.value.includes(permiso);

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
