<script setup>
import DatePicker from 'primevue/datepicker'

/**
 * Envoltorio de DatePicker anclado al MES de la fecha de operaciones activa.
 *
 * El calendario NO permite navegar a otros meses/años: minDate/maxDate
 * deshabilitan los días fuera del rango y, además, al interceptar month-change y
 * year-change se fuerza el valor del modelo de vuelta al mes permitido, lo que
 * realinea el panel de PrimeVue con el mes de operaciones.
 *
 * Props:
 * - modelValue: fecha (Date o null).
 * - minDate / maxDate: límites del rango permitido.
 * - Resto de props/eventos se reenvían al DatePicker (attrs).
 */
const props = defineProps({
    modelValue: { type: [Date, String, null], default: null },
    minDate: { type: Date, default: null },
    maxDate: { type: Date, default: null },
})

const emit = defineEmits(['update:modelValue', 'date-select'])

function ancla() {
    const base = props.minDate || props.maxDate || new Date()
    return { anio: base.getFullYear(), mes: base.getMonth() }
}

function fueraDeRango(mes, anio) {
    const a = ancla()
    return mes !== a.mes || anio !== a.anio
}

function revertirAlMes() {
    const a = ancla()
    const base = props.modelValue ? new Date(props.modelValue) : new Date()
    const corregida = new Date(a.anio, a.mes, Math.min(base.getDate(), 28))
    emit('update:modelValue', corregida)
}

function onMonthChange(e) {
    if (e && fueraDeRango(e.month, e.year)) revertirAlMes()
}

function onYearChange(e) {
    if (e && fueraDeRango(e.month, e.year)) revertirAlMes()
}
</script>

<template>
    <DatePicker
        :model-value="modelValue"
        :min-date="minDate"
        :max-date="maxDate"
        :month-navigator="false"
        :year-navigator="false"
        @month-change="onMonthChange"
        @year-change="onYearChange"
        v-bind="$attrs"
        @update:model-value="(v) => emit('update:modelValue', v)"
        @date-select="(v) => emit('date-select', v)"
    />
</template>
