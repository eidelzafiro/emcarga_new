<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import ReporteFiltro from '@/Components/ReporteFiltro.vue'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import Card from 'primevue/card'

const props = defineProps({
  grupos: { type: Object, required: true },
  resumen: { type: Object, default: () => ({}) },
})

const estado = ref({})

function generar(id) {
  const filtros = estado.value[id]?.filtros || {}
  router.post(
    route('reportes.generar', id),
    { filtros },
    {
      preserveScroll: true,
      onSuccess: (page) => {
        estado.value[id] = {
          filtros,
          mensaje: page.props.flash?.success || page.props.flash?.mensaje || 'Generado.',
          ok: true,
        }
      },
      onError: (errors) => {
        estado.value[id] = {
          filtros,
          mensaje: errors.mensaje || 'Reporte aún no migrado a Zafiro (Fase A pendiente).',
          ok: false,
        }
      },
    },
  )
}
</script>

<template>
  <AppLayout title="Reportes">
    <div class="p-4">
      <h1 class="text-2xl font-bold mb-1">Catálogo de Reportes</h1>
      <p class="text-sm text-gray-500 mb-4">
        {{ Object.keys(resumen).length }} agrupaciones · {{ Object.values(resumen).reduce((a, b) => a + b, 0) }} reportes usados
      </p>

      <div class="flex flex-col gap-4">
        <Card v-for="(reportes, tipo) in grupos" :key="tipo">
          <template #title>
            <div class="flex justify-between items-center">
              <span>{{ tipo }}</span>
              <Tag :value="reportes.length + ' reportes'" />
            </div>
          </template>
          <template #content>
            <div class="flex flex-col gap-3">
              <div
                v-for="rep in reportes"
                :key="rep.id"
                class="border rounded p-3 flex flex-col gap-2"
              >
                <div class="flex flex-wrap items-center gap-2">
                  <span class="font-semibold">{{ rep.nombre }}</span>
                  <Tag
                    v-for="p in rep.perfiles"
                    :key="p"
                    :value="p"
                    severity="info"
                  />
                  <Tag :value="'filtro: ' + rep.filtro" severity="secondary" />
                </div>

                <ReporteFiltro
                  v-model="estado[rep.id].filtros"
                  :tipo="rep.filtro"
                />

                <div class="flex items-center gap-2">
                  <Button
                    label="Generar"
                    icon="pi pi-file-pdf"
                    size="small"
                    @click="generar(rep.id)"
                  />
                  <span
                    v-if="estado[rep.id]?.mensaje"
                    class="text-sm"
                    :class="estado[rep.id]?.ok ? 'text-green-600' : 'text-orange-600'"
                  >
                    {{ estado[rep.id].mensaje }}
                  </span>
                </div>
              </div>
            </div>
          </template>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
