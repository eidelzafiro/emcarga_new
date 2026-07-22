<template>
  <AppLayout>
    <div class="space-y-6">
      <Card>
        <template #title>Bienvenido, {{ user?.name }}</template>
        <template #subtitle>Sistema de gestión EMCARGA — Panel de control</template>
      </Card>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
        <Card v-for="kpi in kpis" :key="kpi.label" class="!shadow-sm">
          <template #content>
            <div class="flex items-center justify-between">
              <div class="min-w-0">
                <p class="text-xs font-medium text-surface-500 uppercase tracking-wider truncate">{{ kpi.label }}</p>
                <p class="text-2xl font-bold text-surface-900 mt-1">{{ kpi.valor }}</p>
              </div>
              <div
                class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 ml-3"
                :class="kpi.color"
              >
                <i :class="kpi.icono" class="text-white text-lg" />
              </div>
            </div>
          </template>
        </Card>
      </div>

      <Card>
        <template #title>Actualización en vivo</template>
        <template #subtitle>Los KPIs se actualizan automáticamente vía WebSocket</template>
        <template #content>
          <div class="flex items-center gap-2 text-sm">
            <span class="w-2 h-2 rounded-full" :class="conectado ? 'bg-emerald-500' : 'bg-red-500'" />
            <span class="text-surface-500">{{ conectado ? 'Conectado' : 'Desconectado' }}</span>
            <span v-if="ultimaActualizacion" class="text-surface-400 ml-2">
              · Última actualización: {{ ultimaActualizacion }}
            </span>
          </div>
        </template>
      </Card>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const page = usePage();
const user = page.props.auth?.user;
const props = defineProps({
  kpis: {
    type: Array,
    default: () => [],
  },
});

const kpis = ref(props.kpis);
const conectado = ref(false);
const ultimaActualizacion = ref(null);

let echoChannel = null;

onMounted(() => {
  if (window.Echo) {
    echoChannel = window.Echo.channel('kpis')
      .listen('.KpisUpdated', (e) => {
        if (e.kpis) {
          kpis.value = e.kpis;
          ultimaActualizacion.value = new Date().toLocaleTimeString('es');
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
    window.Echo.leaveChannel('kpis');
  }
});
</script>
