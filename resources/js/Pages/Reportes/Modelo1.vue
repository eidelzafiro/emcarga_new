<script setup>
import { ref, computed } from 'vue'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import DatePicker from 'primevue/datepicker'
import Select from 'primevue/select'
import Button from 'primevue/button'
import Card from 'primevue/card'

const props = defineProps({
  title: { type: String, default: 'Modelo 1' },
  mes: { type: Number, required: true },
  ano: { type: Number, required: true },
  choferes: { type: Array, default: () => [] },
})

const fechaMes = ref(new Date(props.ano, props.mes - 1, 1))
const choferId = ref(null)

const mes = computed(() => fechaMes.value.getMonth() + 1)
const ano = computed(() => fechaMes.value.getFullYear())

function generar(formato) {
  const params = {
    mes: mes.value,
    ano: ano.value,
  }
  if (choferId.value) params.id_bolsa = choferId.value

  const nombreRuta = formato === 'pdf' ? 'reportes.modelo1' : 'reportes.modelo1-excel'
  const url = route(nombreRuta) + '?' + new URLSearchParams(params).toString()
  window.open(url, '_blank')
}
</script>

<template>
  <AppLayout :title="title">
    <div class="p-4">
      <h1 class="text-2xl font-bold mb-1">{{ title }}</h1>
      <p class="text-sm text-gray-500 mb-4">
        El corte se hace por la fecha de cierre de las hojas de ruta del mes seleccionado.
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
                input-id="modelo1-mes"
              />
            </div>

            <div class="flex flex-col gap-1">
              <label class="text-sm font-semibold">Chofer</label>
              <Select
                v-model="choferId"
                :options="choferes"
                option-label="nombre"
                option-value="id"
                placeholder="Todos los choferes"
                filter
                show-clear
                class="w-full"
              />
              <span class="text-xs text-gray-500">Opcional: deja vacío para todos los choferes del mes.</span>
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
