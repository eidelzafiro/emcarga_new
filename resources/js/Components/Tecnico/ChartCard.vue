<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">{{ titulo }}</h3>
    <p class="text-xs text-gray-400 dark:text-gray-500 mb-2">Clic en un segmento para ver detalle</p>
    <div class="relative" style="height: 230px">
      <canvas ref="canvas" />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

const props = defineProps({
  titulo: String,
  datos: { type: Array, default: () => [] },
});
const emit = defineEmits(['segmento']);

const PALETA = ['#2563eb', '#059669', '#d97706', '#7c3aed', '#0891b2', '#dc2626', '#db2777', '#65a30d', '#ea580c', '#4f46e5', '#0d9488', '#9333ea'];

const canvas = ref(null);
let chart = null;

const render = () => {
  if (!canvas.value) return;
  if (chart) chart.destroy();
  if (!props.datos.length) return;
  chart = new Chart(canvas.value, {
    type: 'doughnut',
    data: {
      labels: props.datos.map((d) => d.etiqueta),
      datasets: [{
        data: props.datos.map((d) => d.valor),
        backgroundColor: props.datos.map((_, i) => PALETA[i % PALETA.length]),
        borderWidth: 2,
        borderColor: '#fff',
      }],
    },
    options: {
      responsive: true, maintainAspectRatio: false, cutout: '62%',
      onClick: (evt, els) => {
        if (els.length) emit('segmento', props.datos[els[0].index].etiqueta);
      },
      plugins: {
        legend: { position: 'bottom', labels: { boxWidth: 8, boxHeight: 8, usePointStyle: true, font: { size: 11 }, color: '#9ca3af' } },
        tooltip: { callbacks: { label: (c) => ` ${c.label}: ${c.raw}` } },
      },
    },
  });
};

onMounted(() => nextTick(render));
watch(() => props.datos, () => nextTick(render), { deep: true });
onUnmounted(() => chart && chart.destroy());
</script>
