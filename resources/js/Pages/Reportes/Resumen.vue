<script setup>
import { ref, computed } from 'vue'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import DatePicker from 'primevue/datepicker'
import Select from 'primevue/select'
import Button from 'primevue/button'
import Card from 'primevue/card'

const props = defineProps({
  title: { type: String, default: 'Resumen de Ingresos e Indicadores' },
  mes: { type: Number, required: true },
  ano: { type: Number, required: true },
  dimensiones: { type: Array, default: () => [] },
})

const fechaMes = ref(new Date(props.ano, props.mes - 1, 1))
const dimension = ref('tractivo')
const tipo = ref('ingresos')

const mes = computed(() => fechaMes.value.getMonth() + 1)
const ano = computed(() => fechaMes.value.getFullYear())

const tipos = [
  { id: 'ingresos', nombre: 'Ingresos' },
  { id: 'indicadores', nombre: 'Indicadores de explotación' },
]

function generar(formato) {
  const params = {
    mes: mes.value,
    ano: ano.value,
    dimension: dimension.value,
    indicadores: tipo.value === 'indicadores' ? 1 : 0,
  }
  const url = route('reportes.resumen.generar', { formato }) + '?' + new URLSearchParams(params).toString()
  window.open(url, '_blank')
}
</script>

<template>
  <AppLayout :title="title">
    <div class="p-4">
      <h1 class="text-2xl font-bold mb-1">{{ title }}</h1>
      <p class="text-sm text-gray-500 mb-4">
        Selecciona el período y la variable de agrupación. Los totales de ingresos,
        toneladas y kilómetros coinciden en todas las variantes.
      </p>

      <Card class="max-w-2xl">
        <template #content>
          <div class="flex flex-col gap-4">
            <div class="flex flex-col gap-1">
              <label class="text-sm font-semibold">Mes de operaciones</label>
              <DatePicker
                v-model="fechaMes"
                view="month"
                date-format="mm/yy"
                input-id="resumen-mes"
              />
            </div>

            <div class="flex flex-col gap-1">
              <label class="text-sm font-semibold">Variable de agrupación</label>
              <Select
                v-model="dimension"
                :options="dimensiones"
                option-label="nombre"
                option-value="id"
                class="w-full"
              />
            </div>

            <div class="flex flex-col gap-1">
              <label class="text-sm font-semibold">Tipo de reporte</label>
              <Select
                v-model="tipo"
                :options="tipos"
                option-label="nombre"
                option-value="id"
                class="w-full"
              />
            </div>

            <div class="flex gap-2">
              <Button label="Generar PDF" icon="pi pi-file-pdf" @click="generar('pdf')" />
              <Button label="Generar Excel" icon="pi pi-file-excel" severity="success" @click="generar('excel')" />
            </div>
          </div>
        </template>
      </Card>
    </div>
  </AppLayout>
</template>
