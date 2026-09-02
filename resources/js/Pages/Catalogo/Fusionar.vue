<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from 'primevue/button'
import Select from 'primevue/select'
import Card from 'primevue/card'

const props = defineProps({
  title: String,
  tipos: Array,
  opciones: Array,
  tipoActual: String,
})

const tipo = ref(props.tipoActual || 'tipos_equipos')
const items = ref(props.opciones || [])
const origen = ref(null)
const destino = ref(null)
const error = ref('')

async function cargarOpciones() {
  origen.value = null
  destino.value = null
  try {
    const r = await fetch(route('fusionar-catalogos.opciones', { tipo: tipo.value }), { headers: { Accept: 'application/json' } })
    const j = await r.json()
    items.value = j.opciones || []
  } catch (e) {
    items.value = []
  }
}

watch(tipo, cargarOpciones)

function submit() {
  error.value = ''
  if (!origen.value || !destino.value) {
    error.value = 'Seleccione el origen y el destino.'
    return
  }
  if (origen.value === destino.value) {
    error.value = 'El origen y el destino deben ser distintos.'
    return
  }
  router.post(route('fusionar-catalogos.store'), {
    tipo: tipo.value,
    origen_id: origen.value,
    destino_id: destino.value,
  }, {
    onSuccess: () => {
      origen.value = null
      destino.value = null
      cargarOpciones()
    },
    onError: (e) => { error.value = Object.values(e).flat().join(' ') || 'Error al fusionar' },
  })
}
</script>

<template>
  <AppLayout :title="title ?? 'Fusionar Catálogos'">
    <div class="max-w-2xl">
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
        <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-1">Fusionar catálogos</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
          Fusiona un ítem dentro de otro. Las fichas de vehículos (y demás registros que lo referencian) se actualizan automáticamente al destino.
        </p>

        <div v-if="error" class="mb-4 p-3 rounded bg-red-50 text-red-700 border border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-800 text-sm">
          {{ error }}
        </div>

        <div class="space-y-4">
          <div class="flex flex-col gap-1">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de catálogo</label>
            <Select v-model="tipo" :options="tipos" optionLabel="label" optionValue="value" class="w-full" />
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="flex flex-col gap-1">
              <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Ítem origen (se elimina)</label>
              <Select v-model="origen" :options="items" optionLabel="nombre" optionValue="id" class="w-full" filter showClear placeholder="Seleccione el origen" />
            </div>
            <div class="flex flex-col gap-1">
              <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Ítem destino (se conserva)</label>
              <Select v-model="destino" :options="items" optionLabel="nombre" optionValue="id" class="w-full" filter showClear placeholder="Seleccione el destino" />
            </div>
          </div>

          <Button label="Fusionar" icon="pi pi-objects-column" @click="submit" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>
