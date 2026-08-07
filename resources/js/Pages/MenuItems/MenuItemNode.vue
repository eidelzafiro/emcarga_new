<template>
  <div>
    <div
      class="flex items-center gap-2 px-3 py-2 border-b border-surface-100 dark:border-surface-800 hover:bg-surface-50 dark:hover:bg-surface-800/50"
      :style="{ paddingLeft: (profundidad || 0) * 28 + 12 + 'px' }"
    >
      <span
        class="drag-handle cursor-grab text-surface-400 hover:text-surface-600 dark:hover:text-surface-200 select-none"
        title="Arrastrar para mover"
      >
        <i class="pi pi-bars" />
      </span>

      <span class="flex-1 min-w-0 flex items-center gap-2">
        <i v-if="node.icon" :class="node.icon" class="text-surface-400 text-sm" />
        <span class="font-medium truncate">{{ node.label }}</span>
        <i
          v-if="tieneHijos"
          class="pi pi-folder text-xs text-amber-400"
          title="Agrupador"
        />
      </span>

      <Tag :value="node.orden" severity="secondary" style="min-width: 40px; justify-content: center" />

      <div class="flex items-center gap-2">
        <span class="text-xs text-surface-400">{{ node.activo !== false ? 'Activo' : 'Inactivo' }}</span>
      </div>

      <div class="flex-1 flex min-w-0 items-stretch">
        <template v-if="node.permission">
          <span
            v-for="rol in roles"
            :key="rol.id"
            class="flex-1 flex items-center justify-center cursor-pointer select-none"
            :class="tienePermiso(rol.name) ? 'text-blue-600 dark:text-blue-400' : 'text-surface-300 hover:text-surface-500'"
            :title="`${tienePermiso(rol.name) ? 'Ocultar' : 'Mostrar'} para ${rol.name}`"
            @click="toggleRol(rol)"
          >
            <i :class="tienePermiso(rol.name) ? 'pi pi-check-circle' : 'pi pi-circle'" class="text-sm" />
          </span>
        </template>
        <span v-else class="flex-1 flex items-center justify-center text-xs text-surface-400 italic" title="Sin permiso asociado; aplica a todo perfil.">—</span>
      </div>

      <div class="flex gap-1 ml-2">
        <Button
          v-if="can('menus.editar')"
          icon="pi pi-pencil"
          severity="secondary"
          text
          rounded
          size="small"
          @click="$emit('editar', node)"
          v-tooltip.left="'Editar'"
        />
        <Button
          v-if="can('menus.eliminar')"
          icon="pi pi-trash"
          severity="danger"
          text
          rounded
          size="small"
          @click="$emit('eliminar', node)"
          v-tooltip.left="'Eliminar'"
        />
      </div>
    </div>

    <div v-if="tieneHijos">
      <draggable
        v-model="hijos"
        item-key="id"
        :group="grupo"
        :handle="'.drag-handle'"
        :animation="150"
        class="select-none"
      >
        <template #item="{ element }">
          <MenuItemNode
            :node="element"
            :roles="roles"
            :padre="node"
            :profundidad="profundidad + 1"
            :grupo="grupo"
            @editar="$emit('editar', $event)"
            @eliminar="$emit('eliminar', $event)"
          />
        </template>
      </draggable>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import draggable from 'vuedraggable';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import { router, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

const props = defineProps({
  node: { type: Object, required: true },
  roles: { type: Array, default: () => [] },
  profundidad: { type: Number, default: 0 },
  grupo: { type: String, default: 'menu' },
});

defineEmits(['editar', 'eliminar']);

const page = usePage();
const permissions = computed(() => page.props.auth?.permissions ?? []);
const can = (permiso) => permissions.value.includes(permiso);

const hijos = computed({
  get: () => props.node.children ?? [],
  set: (val) => { props.node.children = val; },
});

const tieneHijos = computed(() => (props.node.children ?? []).length > 0);

function tienePermiso(nombre) {
  return (props.node.roles ?? []).includes(nombre);
}

function toggleRol(rol) {
  if (!props.node.permission) return;
  router.visit(route('menu-items.toggle-visibility', [props.node.id, rol.id]), {
    method: 'post',
    preserveScroll: true,
    preserveState: false,
    only: ['items', 'flash'],
  });
}
</script>