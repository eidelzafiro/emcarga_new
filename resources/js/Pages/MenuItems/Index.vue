<template>
  <AppLayout>
    <Card>
      <template #title>Gestión del menú</template>
      <template #content>
        <div class="flex flex-wrap items-center gap-3 mb-4">
          <IconField>
            <InputIcon>
              <i class="pi pi-search" />
            </InputIcon>
            <InputText v-model="busqueda" placeholder="Buscar ítem…" class="w-48" />
          </IconField>
          <div class="ml-auto flex gap-2">
            <Button
              v-if="can('menus.crear')"
              icon="pi pi-plus"
              label="Nuevo ítem"
              @click="abrirCrear"
            />
          </div>
        </div>

        <DataTable :value="itemsFiltrados" stripedRows size="small" sortField="orden" :sortOrder="1">
          <Column header="Ítem">
            <template #body="{ data }">
              <span
                class="font-medium"
                :style="{ paddingLeft: (data._depth || 0) * 24 + 'px' }"
              >
                <i v-if="data.icon" :class="data.icon + ' mr-2'" />
                {{ data.label }}
              </span>
            </template>
          </Column>
          <Column field="route" header="Ruta">
            <template #body="{ data }">
              <code v-if="data.route" class="text-xs bg-surface-100 dark:bg-surface-800 px-1.5 py-0.5 rounded">
                {{ data.route }}
              </code>
              <span v-else class="text-xs text-surface-400 italic">Agrupador</span>
            </template>
          </Column>
          <Column field="permission" header="Permiso">
            <template #body="{ data }">
              <Tag v-if="data.permission" :value="data.permission" severity="info" size="small" />
              <span v-else class="text-xs text-surface-400">—</span>
            </template>
          </Column>

          <Column field="orden" header="Orden" style="width:80px" />
          <Column header="Activo" style="width:80px">
            <template #body="{ data }">
              <i v-if="data.activo !== false" class="pi pi-check-circle text-green-500" />
              <i v-else class="pi pi-times-circle text-red-400" />
            </template>
          </Column>
          <Column header="Acciones" :exportable="false" style="width:100px">
            <template #body="{ data }">
              <div class="flex gap-1">
                <Button
                  v-if="can('menus.editar')"
                  icon="pi pi-pencil"
                  severity="secondary"
                  text
                  rounded
                  size="small"
                  @click="abrirEditar(data)"
                  v-tooltip.left="'Editar'"
                />
                <Button
                  v-if="can('menus.eliminar')"
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
              <i class="pi pi-bars text-3xl mb-2 block" />
              No hay ítems de menú registrados.
            </div>
          </template>
        </DataTable>
      </template>
    </Card>

    <!-- Modal crear/editar -->
    <Dialog
      v-model:visible="modalForm"
      :header="editando ? 'Editar ítem' : 'Nuevo ítem'"
      :modal="true"
      class="w-full max-w-lg"
    >
      <form class="space-y-4" @submit.prevent="guardarForm">
        <div>
          <label class="block text-sm font-medium text-surface-700 mb-1">Etiqueta</label>
          <InputText v-model="form.label" class="w-full" :class="{ 'p-invalid': form.errors.label }" />
          <small v-if="form.errors.label" class="text-red-500">{{ form.errors.label }}</small>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Icono (clase Prime)</label>
            <InputText v-model="form.icon" class="w-full" placeholder="pi pi-circle" />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Orden</label>
            <InputNumber v-model="form.orden" class="w-full" :min="0" />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-surface-700 mb-1">Ruta (nombre de ruta)</label>
          <InputText v-model="form.route" class="w-full" placeholder="ej: tractivos.index" />
          <small class="text-surface-400">Dejar vacío si es agrupador.</small>
        </div>

        <div>
          <label class="block text-sm font-medium text-surface-700 mb-1">Padre (agrupador)</label>
          <Select
            v-model="form.parent_id"
            :options="opcionesPadre"
            optionLabel="label"
            optionValue="id"
            placeholder="Sin padre (raíz)"
            class="w-full"
            :showClear="true"
            @change="alCambiarPadre($event.value)"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-surface-700 mb-1">Permiso requerido</label>
          <Select
            v-model="form.permission"
            :options="permisos"
            placeholder="Sin restricción"
            class="w-full"
            :showClear="true"
            filter
          />
          <small class="text-surface-400">Solo los usuarios con este permiso verán el ítem.</small>
        </div>

        <div v-if="editando" class="flex items-center gap-2">
          <ToggleSwitch v-model="form.activo" inputId="activo" />
          <label for="activo" class="text-sm text-surface-700">Ítem activo</label>
        </div>

        <div v-if="editando && form.permission" class="border-t pt-4 mt-4">
          <label class="block text-sm font-medium text-surface-700 mb-2">Visibilidad por perfil</label>
          <p class="text-xs text-surface-400 mb-3">Seleccione qué perfiles pueden ver este ítem.</p>
          <div class="flex flex-wrap gap-3">
            <div v-for="rol in rolesExcluyendoSuperadmin" :key="rol.id" class="flex items-center gap-2">
              <ToggleSwitch
                :modelValue="rol.tienePermiso"
                @update:modelValue="toggleRolEnForm(rol)"
                :inputId="'rol-' + rol.id"
                :disabled="!can('menus.editar')"
              />
              <label :for="'rol-' + rol.id" class="text-sm cursor-pointer">{{ rol.name }}</label>
            </div>
          </div>
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
      header="Eliminar ítem"
      :modal="true"
      class="w-full max-w-sm"
    >
      <p class="text-sm text-surface-600">
        ¿Está seguro de eliminar el ítem <strong>{{ seleccionado?.label }}</strong>?
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
import { useForm, usePage, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import { route } from 'ziggy-js';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import ToggleSwitch from 'primevue/toggleswitch';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Card from 'primevue/card';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
  items: Array,
  permisos: Array,
  roles: Array,
  parents: Array,
});

const page = usePage();
const toast = useToast();
const permissions = computed(() => page.props.auth?.permissions ?? []);
const can = (permiso) => permissions.value.includes(permiso);

const busqueda = ref('');

const rolesExcluyendoSuperadmin = computed(() =>
  props.roles
    .filter((r) => r.name !== 'SUPERADMIN')
    .map((r) => ({
      ...r,
      get tienePermiso() {
        return seleccionado.value?.roles?.includes(r.name) ?? false;
      },
    }))
);

function aplanar(nodos, depth = 0) {
  const result = [];
  for (const n of nodos) {
    result.push({ ...n, _depth: depth });
    if (n.children?.length) {
      result.push(...aplanar(n.children, depth + 1));
    }
  }
  return result;
}

const itemsFlat = computed(() => aplanar(props.items));

function coincideBusqueda(item) {
  if (!busqueda.value) return true;
  const q = busqueda.value.toLowerCase();
  return (
    (item.label || '').toLowerCase().includes(q) ||
    (item.route || '').toLowerCase().includes(q) ||
    (item.permission || '').toLowerCase().includes(q)
  );
}

const itemsFiltrados = computed(() => {
  let base = itemsFlat.value;
  if (busqueda.value) {
    base = base.filter((i) => coincideBusqueda(i));
  }
  return base;
});

function toggleRolEnForm(rol) {
  if (!seleccionado.value?.permission || !can('menus.editar')) return;
  router.visit(route('menu-items.toggle-visibility', [seleccionado.value.id, rol.id]), {
    method: 'post',
    preserveScroll: true,
    preserveState: false,
    onSuccess: () => {
      const tieneAhora = seleccionado.value.roles.includes(rol.name);
      toast.add({
        severity: 'success',
        summary: tieneAhora
          ? `Acceso quitado a ${rol.name}`
          : `Acceso concedido a ${rol.name}`,
        life: 3000,
      });
    },
  });
}

const opcionesPadre = computed(() => {
  const build = (nodos, depth = 0) => {
    const result = [];
    for (const n of nodos) {
      const disabled = editando.value && n.id === seleccionado.value?.id;
      result.push({
        id: n.id,
        label: '  '.repeat(depth) + n.label,
        disabled,
      });
      if (n.children?.length) {
        result.push(...build(n.children, depth + 1));
      }
    }
    return result;
  };
  return build(props.items);
});

function alCambiarPadre(id) {
  if (!id) return;
  const padre = itemsFlat.value.find((i) => i.id === id);
  if (padre) {
    form.orden = padre.orden;
  }
}

const modalForm = ref(false);
const modalEliminar = ref(false);
const editando = ref(false);
const seleccionado = ref(null);

const form = useForm({
  label: '',
  icon: '',
  route: '',
  permission: null,
  parent_id: null,
  orden: 0,
  activo: true,
});

const formEliminar = useForm({});

const abrirCrear = () => {
  editando.value = false;
  seleccionado.value = null;
  form.reset();
  form.clearErrors();
  modalForm.value = true;
};

const abrirEditar = (item) => {
  editando.value = true;
  seleccionado.value = item;
  form.label = item.label;
  form.icon = item.icon || '';
  form.route = item.route || '';
  form.permission = item.permission || null;
  form.parent_id = item.parent_id || null;
  form.orden = item.orden ?? 0;
  form.activo = item.activo !== false;
  form.clearErrors();
  modalForm.value = true;
};

const guardarForm = () => {
  if (editando.value) {
    form.put(route('menu-items.update', seleccionado.value.id), {
      onSuccess: () => cerrarModales(),
    });
  } else {
    form.post(route('menu-items.store'), {
      onSuccess: () => cerrarModales(),
    });
  }
};

const abrirEliminar = (item) => {
  seleccionado.value = item;
  modalEliminar.value = true;
};

const eliminar = () => {
  formEliminar.delete(route('menu-items.destroy', seleccionado.value.id), {
    onSuccess: () => cerrarModales(),
  });
};

const cerrarModales = () => {
  modalForm.value = false;
  modalEliminar.value = false;
  seleccionado.value = null;
};
</script>
