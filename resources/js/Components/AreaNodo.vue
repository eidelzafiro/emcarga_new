<template>
  <div :style="{ marginLeft: level > 0 ? '1.5rem' : '0' }" class="mb-1">
    <!-- Nodo -->
    <div
      class="group rounded-lg border transition-all duration-200"
      :class="[
        hasChildren
          ? 'border-blue-200 dark:border-blue-800 bg-blue-50/50 dark:bg-blue-900/20'
          : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800',
        isDragOver ? 'ring-2 ring-blue-400 dark:ring-blue-500 bg-blue-100 dark:bg-blue-800/40' : '',
        isDragging ? 'opacity-50 border-dashed' : '',
      ]"
      draggable="true"
      @dragstart="onDragStart"
      @dragover.prevent="onDragOver"
      @dragleave="onDragLeave"
      @drop="onDrop"
      @dragend="onDragEnd"
    >
      <div class="px-3 py-2.5 flex items-center gap-2.5">
        <!-- Toggle expand/collapse -->
        <button
          v-if="hasChildren"
          @click="isExpanded = !isExpanded"
          class="w-5 h-5 flex items-center justify-center rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition shrink-0"
        >
          <i :class="isExpanded ? 'pi pi-chevron-down' : 'pi pi-chevron-right'" class="text-xs text-gray-500 dark:text-gray-400"></i>
        </button>
        <div v-else class="w-5 shrink-0"></div>

        <!-- Icono de arrastre -->
        <div class="w-4 h-4 flex items-center justify-center shrink-0 cursor-grab active:cursor-grabbing" title="Arrastrar para reorganizar">
          <i class="pi pi-bars text-xs text-gray-300 dark:text-gray-600 group-hover:text-gray-500 dark:group-hover:text-gray-400 transition"></i>
        </div>

        <!-- Icono -->
        <div
          class="w-7 h-7 rounded flex items-center justify-center shrink-0"
          :class="level === 0 ? 'bg-blue-500' : level === 1 ? 'bg-emerald-500' : 'bg-amber-500'"
        >
          <i :class="[level === 0 ? 'pi pi-building' : level === 1 ? 'pi pi-sitemap' : 'pi pi-folder']" class="text-white text-xs"></i>
        </div>

        <!-- Nombre -->
        <div class="flex-1 min-w-0">
          <span class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate block">{{ area.nombre }}</span>
        </div>

        <!-- Acciones (siempre visibles en hover) -->
        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
          <button @click="$emit('agregar-hijo', area.id, area.nombre)" class="w-6 h-6 flex items-center justify-center rounded hover:bg-blue-100 dark:hover:bg-blue-900/50 transition" title="Agregar sub-área">
            <i class="pi pi-plus text-xs text-blue-600 dark:text-blue-400"></i>
          </button>
          <button @click="$emit('editar', area)" class="w-6 h-6 flex items-center justify-center rounded hover:bg-amber-100 dark:hover:bg-amber-900/50 transition" title="Editar">
            <i class="pi pi-pencil text-xs text-amber-600 dark:text-amber-400"></i>
          </button>
          <button @click="$emit('eliminar', area)" class="w-6 h-6 flex items-center justify-center rounded hover:bg-red-100 dark:hover:bg-red-900/50 transition" title="Eliminar">
            <i class="pi pi-trash text-xs text-red-600 dark:text-red-400"></i>
          </button>
        </div>

        <!-- Badge -->
        <span v-if="hasChildren" class="text-xs text-gray-400 dark:text-gray-500 shrink-0">
          {{ area.sub_areas.length }}
        </span>
      </div>
    </div>

    <!-- Hijos -->
    <div v-if="hasChildren && isExpanded" class="hijos">
      <AreaNodo
        v-for="hijo in area.sub_areas"
        :key="hijo.id"
        :area="hijo"
        :level="level + 1"
        :expanded="expanded"
        @editar="$emit('editar', $event)"
        @agregar-hijo="$emit('agregar-hijo', $event)"
        @eliminar="$emit('eliminar', $event)"
        @mover="$emit('mover', $event)"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
  area: { type: Object, required: true },
  level: { type: Number, default: 0 },
  expanded: { type: Boolean, default: true },
});

const emit = defineEmits(['editar', 'agregar-hijo', 'eliminar', 'mover']);

const isExpanded = ref(props.expanded);
const isDragOver = ref(false);
const isDragging = ref(false);

watch(() => props.expanded, (val) => { isExpanded.value = val; });

const hasChildren = computed(() => {
  return props.area.sub_areas && props.area.sub_areas.length > 0;
});

function onDragStart(e) {
  isDragging.value = true;
  e.dataTransfer.effectAllowed = 'move';
  e.dataTransfer.setData('text/plain', JSON.stringify({
    id: props.area.id,
    nombre: props.area.nombre,
    parent_id: props.area.id_area_padre,
  }));
}

function onDragOver(e) {
  e.preventDefault();
  const data = e.dataTransfer.types.includes('text/plain');
  if (data) {
    isDragOver.value = true;
    e.dataTransfer.dropEffect = 'move';
  }
}

function onDragLeave() {
  isDragOver.value = false;
}

function onDrop(e) {
  e.preventDefault();
  isDragOver.value = false;
  isDragging.value = false;

  try {
    const dragged = JSON.parse(e.dataTransfer.getData('text/plain'));
    if (dragged.id === props.area.id) return;

    emit('mover', {
      area_id: dragged.id,
      nuevo_padre_id: props.area.id,
      area_nombre: dragged.nombre,
      nuevo_padre_nombre: props.area.nombre,
    });
  } catch (err) {
    console.error('Error parseando drag data:', err);
  }
}

function onDragEnd() {
  isDragging.value = false;
  isDragOver.value = false;
}
</script>

<style scoped>
.hijos {
  border-left: 2px dashed #d1d5db;
  margin-left: 0.75rem;
  padding-left: 0.25rem;
  margin-top: 0.25rem;
}
@media (prefers-color-scheme: dark) {
  .hijos {
    border-left-color: #374151;
  }
}
</style>
