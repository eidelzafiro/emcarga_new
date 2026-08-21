<script setup>
import DatePicker from 'primevue/datepicker'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'

const props = defineProps({
  tipo: { type: String, required: true },
  opciones: { type: Array, default: () => [] },
  soloLectura: { type: Boolean, default: false },
})

const filtros = defineModel({ type: Object, default: () => ({}) })

function set(key, value) {
  filtros.value = { ...filtros.value, [key]: value }
}
</script>

<template>
  <div class="flex flex-wrap gap-2 items-end align-items-center" :class="soloLectura ? 'opacity-60' : ''">
    <template v-if="tipo === 'mes'">
      <DatePicker
        :model-value="filtros.mes"
        view="month"
        date-format="mm/yy"
        placeholder="Mes"
        :disabled="soloLectura"
        @update:model-value="set('mes', $event)"
      />
    </template>

    <template v-else-if="tipo === 'fecha'">
      <DatePicker
        :model-value="filtros.desde"
        placeholder="Desde"
        :disabled="soloLectura"
        @update:model-value="set('desde', $event)"
      />
      <DatePicker
        :model-value="filtros.hasta"
        placeholder="Hasta"
        :disabled="soloLectura"
        @update:model-value="set('hasta', $event)"
      />
    </template>

    <template v-else-if="tipo === 'consecutivo'">
      <InputNumber
        :model-value="filtros.consecutivo"
        placeholder="Consecutivo"
        :disabled="soloLectura"
        @update:model-value="set('consecutivo', $event)"
      />
    </template>

    <template v-else-if="tipo === 'tractivo'">
      <InputText
        :model-value="filtros.tractivo"
        placeholder="Tractivo / equipo"
        :disabled="soloLectura"
        @update:model-value="set('tractivo', $event)"
      />
    </template>

    <template v-else-if="tipo === 'cliente'">
      <InputText
        :model-value="filtros.cliente"
        placeholder="Cliente"
        :disabled="soloLectura"
        @update:model-value="set('cliente', $event)"
      />
    </template>

    <template v-else-if="tipo === 'tarjeta'">
      <InputText
        :model-value="filtros.tarjeta"
        placeholder="Tarjeta"
        :disabled="soloLectura"
        @update:model-value="set('tarjeta', $event)"
      />
    </template>

    <template v-else-if="tipo === 'agrupacion'">
      <Select
        :model-value="filtros.agrupacion"
        :options="opciones"
        option-label="label"
        option-value="value"
        placeholder="Agrupación"
        :disabled="soloLectura"
        @update:model-value="set('agrupacion', $event)"
      />
    </template>

    <template v-else>
      <span class="text-sm text-gray-500">Sin filtros</span>
    </template>
  </div>
</template>
