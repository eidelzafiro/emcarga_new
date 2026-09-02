<script setup>
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from 'primevue/button'
import Toast from 'primevue/toast'

const props = defineProps({
  cierres: Array,
  tarjetasActivas: Number,
})

const page = usePage()
const mes = ref('')
const procesando = ref(false)

const flash = computed(() => page.props.flash ?? {})

function ejecutarCierre() {
  if (!mes.value) return
  procesando.value = true
  router.post(route('cierre-tarjetas.store'), { mes: mes.value }, {
    onFinish: () => { procesando.value = false },
  })
}

function formatFecha(fecha) {
  if (!fecha) return '—'
  const d = new Date(fecha + 'T00:00:00')
  return d.toLocaleDateString('es-CU', { year: 'numeric', month: 'long' })
}

function formatNumber(n) {
  return n != null ? Number(n).toLocaleString('es-CU', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00'
}

// Default mes to previous month
const now = new Date()
const prev = new Date(now.getFullYear(), now.getMonth() - 1, 1)
mes.value = prev.toISOString().slice(0, 7)
</script>

<template>
  <AppLayout title="Cierre de Mes - Tarjetas">
    <div class="space-y-6">
      <!-- Encabezado -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Cierre de Mes — Tarjetas de Combustible</h1>
          <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">
            Realiza el cierre mensual de saldos para todas las tarjetas activas
          </p>
        </div>
        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">
          {{ tarjetasActivas }} tarjetas activas
        </span>
      </div>

      <!-- Formulario de cierre -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Ejecutar Cierre</h2>
        <div class="flex items-end gap-4">
          <div class="flex flex-col gap-1">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Mes a cerrar</label>
            <input
              v-model="mes"
              type="month"
              class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
          <Button
            label="Cerrar Mes"
            icon="pi pi-lock"
            :loading="procesando"
            :disabled="!mes || procesando"
            @click="ejecutarCierre"
            severity="warning"
          />
        </div>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-3">
          El cierre calcula saldos iniciales, cargas, descargas y saldo final por tarjeta. Si ya existe un cierre para el mes seleccionado, se omitirá.
        </p>
      </div>

      <!-- Historial de cierres -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Historial de Cierres</h2>
        </div>
        <div v-if="cierres.length === 0" class="px-6 py-8 text-center text-gray-400 dark:text-gray-500">
          No hay cierres registrados.
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-gray-200 dark:border-gray-700">
                <th class="px-6 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Mes</th>
                <th class="px-6 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Tarjetas Cerradas</th>
                <th class="px-6 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Total MN</th>
                <th class="px-6 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Total LTS</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="c in cierres"
                :key="c.ftrabajo"
                class="border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/30"
              >
                <td class="px-6 py-3 text-gray-900 dark:text-gray-100 font-medium">{{ formatFecha(c.ftrabajo) }}</td>
                <td class="px-6 py-3 text-right text-gray-700 dark:text-gray-300">{{ c.tarjetas_cerradas }}</td>
                <td class="px-6 py-3 text-right text-gray-700 dark:text-gray-300">{{ formatNumber(c.total_mn) }}</td>
                <td class="px-6 py-3 text-right text-gray-700 dark:text-gray-300">{{ formatNumber(c.total_lts) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
