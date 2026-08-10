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
            <InputText v-model="busqueda" placeholder="Buscar ítem…" class="w-48" @input="hidratarVista" />
          </IconField>
          <Select
            v-model="filtroPerfil"
            :options="rolesExpon"
            optionLabel="name"
            optionValue="name"
            placeholder="Filtrar por perfil"
            clearable
            showClear
            class="w-48"
            @update:modelValue="hidratarVista"
          />
          <div class="ml-auto flex gap-2">
            <Button
              v-if="tieneCambios"
              icon="pi pi-sort-alt"
              label="Guardar orden"
              :loading="guardando"
              :disabled="filtroActivo"
              @click="guardarOrden"
            />
            <Button
              v-if="can('menus.crear')"
              icon="pi pi-plus"
              label="Nuevo ítem"
              @click="abrirCrear"
            />
          </div>
        </div>

        <div v-if="puedeMover" class="mb-3 text-xs text-surface-400 dark:text-surface-400">
          <i class="pi pi-info-circle mr-1" />
          Arrastra el icono <i class="pi pi-bars mx-1" /> para reordenar o cambiar el agrupador.
          Para meter un ítem dentro de un agrupador suéltalo <strong>sobre el nombre</strong> del
          agrupador (o entre sus ítems). Al pulsar «Guardar orden» se respeta exactamente el orden
          dejado: los ítems se renumeran (1..n) dentro de cada agrupador sin reordenar alfabéticamente.
        </div>

        <div class="border border-surface-200 dark:border-surface-800 rounded-lg overflow-hidden">
          <div class="flex items-center gap-2 px-3 py-2 sticky top-0 z-10 bg-surface-50 dark:bg-surface-800/60 border-b border-surface-100 dark:border-surface-800 text-xs font-semibold text-surface-500">
            <span style="width: 28px" />
            <span class="flex-1">Ítem</span>
            <span style="min-width: 44px; text-align: center">Orden</span>
            <span style="min-width: 90px; text-align: center">Estado</span>
            <span class="flex-1 flex min-w-0">
              <span
                v-for="rol in rolesExpon"
                :key="rol.id"
                class="flex-1 text-center font-bold uppercase truncate px-1"
                :title="rol.name"
              >
                {{ abreviatura(rol.name) }}
              </span>
            </span>
            <span style="width: 72px; text-align: right">Acciones</span>
          </div>

          <div style="max-height: 62vh; overflow-y: auto;">
            <!-- Modo árbol arrastrable (sin filtros) -->
            <draggable
              v-if="!filtroActivo && itemsFiltrados.length"
              v-model="itemsFiltrados"
              item-key="id"
              group="menu"
              :animation="150"
              :force-fallback="true"
              :fallback-on-body="false"
              :ghost-class="'drag-ghost'"
              :fallback-class="'drag-fallback'"
              data-drag-root
              class="select-none"
              @end="emitirDrop"
              @move="onMoveTree"
            >
              <template #item="{ element }">
                <MenuItemNode
                  :node="element"
                  :roles="rolesExpon"
                  @editar="abrirEditar"
                  @eliminar="abrirEliminar"
                />
              </template>
            </draggable>

            <!-- Mod árbol inerte (solo lectura) con filtro activo -->
            <template v-else-if="filtroActivo">
              <MenuItemNode
                v-for="el in itemsVisibles"
                :key="el.id"
                :node="el"
                :roles="rolesExpon"
                :grupo="'sin-arrastre'"
                @editar="abrirEditar"
                @eliminar="abrirEliminar"
              />
              <div v-if="!itemsVisibles.length" class="text-center py-8 text-surface-400">
                <i class="pi pi-search text-2xl mb-2 block" />
                Sin resultados para el filtro actual.
              </div>
            </template>

            <div v-else class="text-center py-8 text-surface-400">
              <i class="pi pi-bars text-3xl mb-2 block" />
              No hay ítems de menú registrados.
            </div>
          </div>
        </div>
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
            <label class="block text-sm font-medium text-surface-700 mb-1">Ruta (nombre de ruta)</label>
            <InputText v-model="form.route" class="w-full" placeholder="ej: tractivos.index" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
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
              @update:modelValue="alCambiarPadre"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Orden</label>
            <InputNumber v-model="form.orden" class="w-full" :min="0" />
          </div>
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
            <div v-for="rol in rolesExpon" :key="rol.id" class="flex items-center gap-2">
              <ToggleSwitch
                :modelValue="seleccionado && seleccionado.roles.includes(rol.name)"
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
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import ToggleSwitch from 'primevue/toggleswitch';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import draggable from 'vuedraggable';
import { route } from 'ziggy-js';
import AppLayout from '@/Layouts/AppLayout.vue';
import MenuItemNode from './MenuItemNode.vue';

const props = defineProps({
  items: Array,
  permisos: Array,
  roles: Array,
  parents: Array,
});

const page = usePage();
const permissions = computed(() => page.props.auth?.permissions ?? []);
const can = (permiso) => permissions.value.includes(permiso);
const puedeMover = computed(() => can('menus.editar'));

const rolesExpon = computed(() =>
  (props.roles ?? []).filter((r) => r.name !== 'SUPERADMIN')
);

function abreviatura(nombre) {
  return (nombre || '').slice(0, 3).toUpperCase();
}

// itemsFiltrados es el árbol editable (draggable raíz vive aquí).
const itemsFiltrados = ref([]);
const snapshot = ref('');
const guardando = ref(false);
const tieneCambios = ref(false);

const busqueda = ref('');
const filtroPerfil = ref(null);

const filtroActivo = computed(() => busqueda.value.trim() !== '' || filtroPerfil.value != null);

function clonar(objs) {
  return JSON.parse(JSON.stringify(objs ?? []));
}

function hidratar() {
  itemsFiltrados.value = clonar(props.items);
  snapshot.value = JSON.stringify(itemsFiltrados.value);
  tieneCambios.value = false;
}

watch(
  () => props.items,
  () => { if (!filtroActivo.value) hidratar(); },
  { deep: true, immediate: true }
);
watch(itemsFiltrados, () => {
  tieneCambios.value = JSON.stringify(itemsFiltrados.value) !== snapshot.value;
}, { deep: true });

function buscarNodo(nodos, id) {
  for (const n of nodos) {
    if (n.id === id) return n;
    if (n.children?.length) {
      const enHijos = buscarNodo(n.children, id);
      if (enHijos) return enHijos;
    }
  }
  return null;
}

// Cada draggable propaga su fin de arrastre como evento propio, porque el
// evento `end` de vuedraggable solo se emite en el draggable ORIGEN y la
// corrección de anidación necesita acceso al árbol global.
function emitirDrop(evt) {
  const to = evt?.to;
  const raiz = !!(to && to.hasAttribute && to.hasAttribute('data-drag-root'));
  document.dispatchEvent(new CustomEvent('menu-drop', {
    detail: {
      raiz,
      newIndex: evt.newIndex,
      destino: document.__menuLastTarget ?? null,
    },
  }));
  document.__menuLastTarget = null;
}

// Durante el arrastre, anota la última fila de nodo bajo el cursor (sus
// posiciones son estables todavía, a diferencia del instante final).
function onMoveTree(evt) {
  const related = evt?.related;
  const fila = related?.querySelector?.('.drag-node') || related?.closest?.('.drag-node');
  if (fila) document.__menuLastTarget = Number(fila.getAttribute('data-menu-id'));
}

// Si el ítem quedó en la raíz pero se soltó sobre la fila de un agrupador, lo
// re-anida como último hijo de dicho agrupador.
function onMenuDrop(e) {
  const d = e.detail || {};
  if (!d.raiz || d.newIndex == null) return;

  const destinoId = d.destino;
  if (!destinoId) return;

  const destino = buscarNodo(itemsFiltrados.value, destinoId);
  const item = itemsFiltrados.value[d.newIndex];
  if (!destino || !item || destino.id === item.id) return;

  itemsFiltrados.value.splice(d.newIndex, 1);
  destino.children = [...(destino.children ?? []), item];
}

onMounted(() => document.addEventListener('menu-drop', onMenuDrop));
onBeforeUnmount(() => document.removeEventListener('menu-drop', onMenuDrop));

function aplanar(nodos, depth = 0, out = []) {
  for (const n of nodos) {
    out.push({ ...n, _depth: depth });
    if (n.children?.length) aplanar(n.children, depth + 1, out);
  }
  return out;
}

function coincide(n) {
  const q = busqueda.value.trim().toLowerCase();
  const hit = !q ||
    (n.label || '').toLowerCase().includes(q) ||
    (n.route || '').toLowerCase().includes(q) ||
    (n.permission || '').toLowerCase().includes(q);
  if (!hit) return false;
  if (filtroPerfil.value) {
    if (!n.permission) return true;
    if (!n.roles?.length) return false;
    return n.roles.includes(filtroPerfil.value);
  }
  return true;
}

const itemsVisibles = computed(() => {
  const encontrar = (nodos) => nodos
    .map((n) => ({ ...n, children: encontrar(n.children ?? []) }))
    .filter((n) => coincide(n) || (n.children ?? []).length > 0);
  return encontrar(itemsFiltrados.value);
});

function hidratarVista() {
  // sólo usado por eventos de filtro: garantiza copia viva acorde a props
  if (!filtroActivo.value && !tieneCambios.value) {
    itemsFiltrados.value = clonar(props.items);
    snapshot.value = JSON.stringify(itemsFiltrados.value);
  }
}

function guardarOrden() {
  guardando.value = true;
  router.post(route('menu-items.reordenar'), { tree: itemsFiltrados.value }, {
    preserveScroll: true,
    only: ['items', 'flash'],
    onSuccess: () => { hidratar(); },
    onFinish: () => { guardando.value = false; },
  });
}

// ---------- Modal crear/editar ----------
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

const itemsFlat = computed(() => aplanar(itemsFiltrados.value));

function opcionesPadreRec(nodos, depth = 0, out = []) {
  for (const n of nodos) {
    const disabled = editando.value && n.id === seleccionado.value?.id;
    out.push({ id: n.id, label: '  '.repeat(depth) + n.label, disabled });
    if (n.children?.length) opcionesPadreRec(n.children, depth + 1, out);
  }
  return out;
}

const opcionesPadre = computed(() => opcionesPadreRec(itemsFiltrados.value));

function alCambiarPadre(id) {
  if (!id) return;
  const padre = itemsFlat.value.find((i) => i.id === id);
  if (padre) form.orden = padre.orden;
}

function abrirCrear() {
  editando.value = false;
  seleccionado.value = null;
  form.reset();
  form.clearErrors();
  modalForm.value = true;
}

function abrirEditar(item) {
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
}

function guardarForm() {
  if (editando.value) {
    form.put(route('menu-items.update', seleccionado.value.id), {
      onSuccess: () => cerrarModales(),
    });
  } else {
    form.post(route('menu-items.store'), {
      onSuccess: () => cerrarModales(),
    });
  }
}

function abrirEliminar(item) {
  seleccionado.value = item;
  modalEliminar.value = true;
}

function eliminar() {
  formEliminar.delete(route('menu-items.destroy', seleccionado.value.id), {
    onSuccess: () => cerrarModales(),
  });
}

function cerrarModales() {
  modalForm.value = false;
  modalEliminar.value = false;
  seleccionado.value = null;
}

function toggleRolEnForm(rol) {
  if (!seleccionado.value?.permission || !can('menus.editar')) return;
  router.visit(route('menu-items.toggle-visibility', [seleccionado.value.id, rol.id]), {
    method: 'post',
    preserveScroll: true,
    preserveState: false,
    only: ['items', 'flash'],
    onSuccess: () => cerrarModales(),
  });
}
</script>