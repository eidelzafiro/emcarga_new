<template>
  <div class="mb-2">
    <!-- Nodo vertical -->
    <div
      class="group rounded-xl border-2 transition-all duration-200 cursor-pointer"
      :class="[
        hasChildren
          ? 'border-blue-300 dark:border-blue-700 bg-gradient-to-b from-blue-50 to-white dark:from-blue-900/30 dark:to-gray-800 shadow-md'
          : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm',
        isDragOver ? 'ring-2 ring-blue-400 dark:ring-blue-500 scale-[1.02]' : '',
        isDragging ? 'opacity-50 border-dashed' : '',
      ]"
      draggable="true"
      @dragstart="onDragStart"
      @dragover.prevent="onDragOver"
      @dragleave="onDragLeave"
      @drop="onDrop"
      @dragend="onDragEnd"
    >
      <!-- Contenido principal -->
      <div class="p-4 flex flex-col items-center text-center">
        <!-- Imagen / Icono -->
        <div class="relative mb-3">
          <div
            class="w-16 h-16 rounded-full flex items-center justify-center overflow-hidden border-3"
            :class="imagenClasses"
          >
            <img v-if="area.imagen" :src="area.imagen" :alt="area.nombre" class="w-full h-full object-cover" />
            <i v-else :class="iconoClasses" class="text-2xl"></i>
          </div>
          <!-- Badge de orden -->
          <span class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-gray-200 dark:bg-gray-600 text-[10px] font-bold text-gray-600 dark:text-gray-300 flex items-center justify-center">
            {{ area.orden || 0 }}
          </span>
        </div>

        <!-- Nombre del área -->
        <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100 leading-tight mb-1">{{ area.nombre }}</h3>

        <!-- Conteo de trabajadores — grande y destacado -->
        <div v-if="trabajadoresCount > 0" class="mt-1 mb-2">
          <span class="text-3xl font-black font-mono leading-none"
                :class="trabajadoresColor">
            {{ trabajadoresCount }}
          </span>
          <p class="text-[10px] uppercase tracking-wider font-semibold mt-0.5"
             :class="trabajadoresTextColor">
            {{ trabajadoresCount === 1 ? 'trabajador' : 'trabajadores' }}
          </p>
        </div>
        <div v-else class="mt-1 mb-2">
          <span class="text-lg font-bold text-gray-300 dark:text-gray-600">0</span>
          <p class="text-[10px] uppercase tracking-wider text-gray-300 dark:text-gray-600">sin personal</p>
        </div>

        <!-- Acciones -->
        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
          <button @click.stop="$emit('agregar-hijo', area.id, area.nombre)"
                  class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/50 transition"
                  title="Agregar sub-área">
            <i class="pi pi-plus text-xs text-blue-600 dark:text-blue-400"></i>
          </button>
          <button @click.stop="$emit('editar', area)"
                  class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/50 transition"
                  title="Editar">
            <i class="pi pi-pencil text-xs text-amber-600 dark:text-amber-400"></i>
          </button>
          <button v-if="trabajadoresCount <= 1 && !hasChildren"
                  @click.stop="$emit('eliminar', area)"
                  class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-red-100 dark:hover:bg-red-900/50 transition"
                  title="Eliminar">
            <i class="pi pi-trash text-xs text-red-600 dark:text-red-400"></i>
          </button>
        </div>
      </div>

      <!-- Toggle expand/collapse para áreas con hijos -->
      <button v-if="hasChildren"
              @click="isExpanded = !isExpanded"
              class="w-full py-1.5 border-t border-gray-100 dark:border-gray-700 flex items-center justify-center gap-1 text-xs text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-750 transition">
        <i :class="isExpanded ? 'pi pi-chevron-up' : 'pi pi-chevron-down'"></i>
        <span>{{ area.sub_areas.length }} sub-áreas</span>
      </button>
    </div>

    <!-- Línea conectora vertical hacia hijos -->
    <div v-if="hasChildren && isExpanded" class="flex justify-center my-1">
      <div class="w-0.5 h-4 bg-blue-300 dark:bg-blue-600"></div>
    </div>

    <!-- Hijos en fila horizontal con línea conectora horizontal -->
    <div v-if="hasChildren && isExpanded" class="hijos-contenedor">
      <!-- Línea horizontal que conecta todos los hermanos -->
      <div v-if="area.sub_areas.length > 1" class="linea-horizontal-hermanos"></div>
      <div class="hijos-horizontales">
        <div v-for="(hijo, idx) in area.sub_areas" :key="hijo.id" class="hijo-wrapper">
          <!-- Línea vertical corta desde la horizontal hasta el nodo -->
          <div class="linea-vertical-hijo"></div>
          <AreaNodo
            :area="hijo"
            :level="level + 1"
            :expanded="expanded"
            :trabajadores-por-area="trabajadoresPorArea"
            @editar="$emit('editar', $event)"
            @agregar-hijo="$emit('agregar-hijo', $event)"
            @eliminar="$emit('eliminar', $event)"
            @mover="$emit('mover', $event)"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
  area: { type: Object, required: true },
  level: { type: Number, default: 0 },
  expanded: { type: Boolean, default: true },
  trabajadoresPorArea: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['editar', 'agregar-hijo', 'eliminar', 'mover']);

const isExpanded = ref(props.expanded);
const isDragOver = ref(false);
const isDragging = ref(false);

watch(() => props.expanded, (val) => { isExpanded.value = val; });

const hasChildren = computed(() => {
  return props.area.sub_areas && props.area.sub_areas.length > 0;
});

const trabajadoresCount = computed(() => {
  return props.trabajadoresPorArea[props.area.id] || 0;
});

// Color del conteo según cantidad
const trabajadoresColor = computed(() => {
  const n = trabajadoresCount.value;
  if (n >= 20) return 'text-emerald-600 dark:text-emerald-400';
  if (n >= 10) return 'text-blue-600 dark:text-blue-400';
  if (n >= 5) return 'text-amber-600 dark:text-amber-400';
  return 'text-gray-600 dark:text-gray-400';
});

const trabajadoresTextColor = computed(() => {
  const n = trabajadoresCount.value;
  if (n >= 20) return 'text-emerald-500 dark:text-emerald-500';
  if (n >= 10) return 'text-blue-500 dark:text-blue-500';
  if (n >= 5) return 'text-amber-500 dark:text-amber-500';
  return 'text-gray-400 dark:text-gray-500';
});

// Clases para imagen/icono según nivel
const imagenClasses = computed(() => {
  if (props.area.imagen) return 'border-blue-400 dark:border-blue-600';
  if (props.level === 0) return 'bg-blue-500 border-blue-400 dark:bg-blue-700 dark:border-blue-600';
  if (props.level === 1) return 'bg-emerald-500 border-emerald-400 dark:bg-emerald-700 dark:border-emerald-600';
  return 'bg-amber-500 border-amber-400 dark:bg-amber-700 dark:border-amber-600';
});

const iconoClasses = computed(() => {
  if (props.level === 0) return 'pi pi-building text-white';
  if (props.level === 1) return 'pi pi-sitemap text-white';
  return 'pi pi-folder text-white';
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
  isDragOver.value = true;
  e.dataTransfer.dropEffect = 'move';
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
.hijos-contenedor {
  position: relative;
  margin-top: 0;
}

/* Línea horizontal que conecta todos los hermanos */
.linea-horizontal-hermanos {
  position: absolute;
  top: 0;
  left: 50%;
  right: 50%;
  height: 2px;
  background: #93c5fd;
  z-index: 1;
}
@media (prefers-color-scheme: dark) {
  .linea-horizontal-hermanos {
    background: #1d4ed8;
  }
}

.hijos-horizontales {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  justify-content: center;
  gap: 0.5rem;
  position: relative;
  padding-top: 0.25rem;
}

.hijo-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex: 0 1 auto;
  min-width: 140px;
  max-width: 180px;
}

/* Escalar nodos hijos moderadamente */
.hijo-wrapper :deep(.group) {
  transform: scale(0.85);
  transform-origin: top center;
}

/* Si hay más de 5 hijos, escalar un poco más */
.hijos-horizontales:has(.hijo-wrapper:nth-child(n+6)) .hijo-wrapper :deep(.group) {
  transform: scale(0.75);
}

/* Si hay más de 8 hijos, escalar aún más */
.hijos-horizontales:has(.hijo-wrapper:nth-child(n+9)) .hijo-wrapper :deep(.group) {
  transform: scale(0.65);
}

/* Línea vertical corta desde la horizontal hasta cada hijo */
.linea-vertical-hijo {
  width: 2px;
  height: 12px;
  background: #93c5fd;
}
@media (prefers-color-scheme: dark) {
  .linea-vertical-hijo {
    background: #1d4ed8;
  }
}
</style>
