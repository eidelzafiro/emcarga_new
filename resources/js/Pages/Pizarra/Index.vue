<template>
  <AppLayout>
    <Card>
      <template #title>Pizarra de Vehículos</template>
      <template #subtitle>Estado de la flota en tiempo real</template>
      <template #content>
        <!-- Barra de estado -->
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
          <div class="flex items-center gap-4 text-sm">
            <span class="flex items-center gap-1.5">
              <span class="w-2.5 h-2.5 rounded-full bg-emerald-500" />
              Disponibles
            </span>
            <span class="flex items-center gap-1.5">
              <span class="w-2.5 h-2.5 rounded-full bg-blue-500" />
              En ruta
            </span>
            <span class="flex items-center gap-1.5">
              <span class="w-2.5 h-2.5 rounded-full bg-amber-500" />
              En taller
            </span>
            <span class="flex items-center gap-1.5">
              <span class="w-2.5 h-2.5 rounded-full bg-red-500" />
              Averiado
            </span>
          </div>
          <div class="flex items-center gap-2 text-sm text-surface-500">
            <span class="w-2 h-2 rounded-full" :class="conectado ? 'bg-emerald-500' : 'bg-red-500'" />
            {{ conectado ? 'Tiempo real activo' : 'Desconectado' }}
          </div>
        </div>

        <!-- Grid de tarjetas -->
        <div v-if="registros.length === 0" class="text-center py-12 text-surface-400">
          <i class="pi pi-truck text-4xl mb-3 block" />
          <p>No hay vehículos en la pizarra.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
          <div
            v-for="r in registros"
            :key="r.id"
            class="rounded-lg border bg-white shadow-sm hover:shadow-md transition-shadow"
            :class="bordeEstado(r.estado)"
          >
            <div class="p-4">
              <div class="flex items-start justify-between mb-3">
                <div class="min-w-0">
                  <h3 class="font-semibold text-surface-900 truncate">{{ r.vehiculo }}</h3>
                  <p class="text-xs text-surface-500">{{ r.placa }}</p>
                </div>
                <Tag :value="r.estado" :severity="severidadEstado(r.estado)" />
              </div>

              <div class="space-y-2 text-sm">
                <div v-if="r.conductor !== '—'" class="flex items-center gap-2 text-surface-600">
                  <i class="pi pi-user text-surface-400 text-xs" />
                  <span class="truncate">{{ r.conductor }}</span>
                </div>
                <div v-if="r.origen" class="flex items-center gap-2 text-surface-600">
                  <i class="pi pi-map-marker text-surface-400 text-xs" />
                  <span class="truncate">{{ r.origen }} → {{ r.destino || '—' }}</span>
                </div>
                <div v-if="r.ubicacion" class="flex items-center gap-2 text-surface-600">
                  <i class="pi pi-globe text-surface-400 text-xs" />
                  <span class="truncate">{{ r.ubicacion }}</span>
                </div>
                <div v-if="r.salida" class="flex items-center gap-2 text-surface-500">
                  <i class="pi pi-clock text-surface-400 text-xs" />
                  <span>Salida: {{ r.salida }}</span>
                </div>
                <div v-if="r.tonelaje" class="flex items-center gap-2 text-surface-600">
                  <i class="pi pi-box text-surface-400 text-xs" />
                  <span>{{ r.tonelaje }} t — {{ r.carga || '—' }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>
    </Card>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  registros: {
    type: Array,
    default: () => [],
  },
});

const registros = ref(props.registros);
const conectado = ref(false);
let echoChannel = null;

onMounted(() => {
  if (window.Echo) {
    echoChannel = window.Echo.channel('pizarra')
      .listen('.PizarraUpdated', (e) => {
        if (e.registros) {
          registros.value = e.registros;
        }
      });

    window.Echo.connector.pusher.connection.bind('state_change', (states) => {
      conectado.value = states.current === 'connected';
    });
    conectado.value = window.Echo.connector.pusher.connection.state === 'connected';
  }
});

onUnmounted(() => {
  if (echoChannel) {
    window.Echo.leaveChannel('pizarra');
  }
});

const bordeEstado = (estado) => {
  return {
    disponible: 'border-l-4 border-l-emerald-500',
    'en ruta': 'border-l-4 border-l-blue-500',
    'en taller': 'border-l-4 border-l-amber-500',
    averiado: 'border-l-4 border-l-red-500',
  }[estado] || 'border-l-4 border-l-surface-300';
};

const severidadEstado = (estado) => {
  return {
    disponible: 'success',
    'en ruta': 'info',
    'en taller': 'warn',
    averiado: 'danger',
  }[estado] || 'secondary';
};
</script>
