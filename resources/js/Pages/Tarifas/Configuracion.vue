<script setup>
import { ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import InputNumber from 'primevue/inputnumber'
import Button from 'primevue/button'
import Toolbar from 'primevue/toolbar'
import Panel from 'primevue/panel'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ config: Object })
const toast = useToast()
const saving = ref(false)

const form = ref({
    demora_1: props.config?.demora_1 ?? 0,
    demora_2: props.config?.demora_2 ?? 0,
    kms_vacio_1: props.config?.kms_vacio_1 ?? 0,
    kms_vacio_2: props.config?.kms_vacio_2 ?? 0,
    tarifa_horaria_1: props.config?.tarifa_horaria_1 ?? 0,
    tarifa_horaria_2: props.config?.tarifa_horaria_2 ?? 0,
    kms_adicionales_1: props.config?.kms_adicionales_1 ?? 0,
    kms_adicionales_2: props.config?.kms_adicionales_2 ?? 0,
    almacenaje: props.config?.almacenaje ?? 0,
    recargo_1: props.config?.recargo_1 ?? 0,
    recargo_2: props.config?.recargo_2 ?? 0,
    recargo_3_1: props.config?.recargo_3_1 ?? 0,
    recargo_3_2: props.config?.recargo_3_2 ?? 0,
    recargo_3_3: props.config?.recargo_3_3 ?? 0,
    recargo_4: props.config?.recargo_4 ?? 0,
    recargo_5: props.config?.recargo_5 ?? 0,
    hora_1: props.config?.hora_1 ?? 0,
    hora_2: props.config?.hora_2 ?? 0,
    hora_3: props.config?.hora_3 ?? 0,
    izaje_1: props.config?.izaje_1 ?? 0,
    izaje_2: props.config?.izaje_2 ?? 0,
    valor_izaje_mt: props.config?.valor_izaje_mt ?? 0,
    valor_izaje_me: props.config?.valor_izaje_me ?? 0,
    valor_almacenaje: props.config?.valor_almacenaje ?? 0,
    plazo_libre_exp: props.config?.plazo_libre_exp ?? 0,
})

function submit() {
    saving.value = true
    router.put(route('tarifas-config.update'), form.value, {
        onSuccess: () => {
            saving.value = false
            toast.add({ severity: 'success', summary: 'Guardado', detail: 'Configuración actualizada correctamente.', life: 3000 })
        },
        onError: (e) => {
            saving.value = false
            toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 })
        },
    })
}
</script>

<template>
    <AppLayout title="Configuración de Tarifas">
        <form @submit.prevent="submit">
            <Toolbar class="mb-4">
                <template #start>
                    <Button icon="pi pi-arrow-left" severity="secondary" text rounded class="mr-2"
                        @click="router.visit(route('tarifas.index'))" v-tooltip="'Volver a tarifas'" />
                    <Button label="Guardar configuración" type="submit" icon="pi pi-save" :loading="saving" />
                </template>
            </Toolbar>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <Panel header="Configuración de Carga" toggleable>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-1 font-medium">Demora 1</label>
                            <InputNumber v-model="form.demora_1" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-1 font-medium">Demora 2</label>
                            <InputNumber v-model="form.demora_2" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-1 font-medium">Kms Vacío 1</label>
                            <InputNumber v-model="form.kms_vacio_1" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-1 font-medium">Kms Vacío 2</label>
                            <InputNumber v-model="form.kms_vacio_2" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-1 font-medium">Tarifa Horaria 1</label>
                            <InputNumber v-model="form.tarifa_horaria_1" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-1 font-medium">Tarifa Horaria 2</label>
                            <InputNumber v-model="form.tarifa_horaria_2" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-1 font-medium">Kms Adicionales 1</label>
                            <InputNumber v-model="form.kms_adicionales_1" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-1 font-medium">Kms Adicionales 2</label>
                            <InputNumber v-model="form.kms_adicionales_2" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-1 font-medium">Almacenaje</label>
                            <InputNumber v-model="form.almacenaje" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-1 font-medium">Recargo 1</label>
                            <InputNumber v-model="form.recargo_1" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-1 font-medium">Recargo 2</label>
                            <InputNumber v-model="form.recargo_2" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-1 font-medium">Recargo 3.1</label>
                            <InputNumber v-model="form.recargo_3_1" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-1 font-medium">Recargo 3.2</label>
                            <InputNumber v-model="form.recargo_3_2" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-1 font-medium">Recargo 3.3</label>
                            <InputNumber v-model="form.recargo_3_3" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-1 font-medium">Recargo 4</label>
                            <InputNumber v-model="form.recargo_4" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-1 font-medium">Recargo 5</label>
                            <InputNumber v-model="form.recargo_5" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-1 font-medium">Hora 1</label>
                            <InputNumber v-model="form.hora_1" :minFractionDigits="0" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-1 font-medium">Hora 2</label>
                            <InputNumber v-model="form.hora_2" :minFractionDigits="0" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-1 font-medium">Hora 3</label>
                            <InputNumber v-model="form.hora_3" :minFractionDigits="0" class="w-full" />
                        </div>
                    </div>
                </Panel>

                <Panel header="Configuración de Contenedor" toggleable>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-1 font-medium">Demora 1</label>
                            <InputNumber v-model="form.demora_1" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-1 font-medium">Demora 2</label>
                            <InputNumber v-model="form.demora_2" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-1 font-medium">Kms Vacío 1</label>
                            <InputNumber v-model="form.kms_vacio_1" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-1 font-medium">Kms Vacío 2</label>
                            <InputNumber v-model="form.kms_vacio_2" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-1 font-medium">Tarifa Horaria 1</label>
                            <InputNumber v-model="form.tarifa_horaria_1" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-1 font-medium">Izaje 1</label>
                            <InputNumber v-model="form.izaje_1" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-1 font-medium">Izaje 2</label>
                            <InputNumber v-model="form.izaje_2" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-1 font-medium">Valor Izaje MT</label>
                            <InputNumber v-model="form.valor_izaje_mt" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-1 font-medium">Valor Izaje ME</label>
                            <InputNumber v-model="form.valor_izaje_me" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-1 font-medium">Valor Almacenaje</label>
                            <InputNumber v-model="form.valor_almacenaje" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-1 font-medium">Plazo Libre Exp</label>
                            <InputNumber v-model="form.plazo_libre_exp" :minFractionDigits="0" class="w-full" />
                        </div>
                    </div>
                </Panel>
            </div>
        </form>
    </AppLayout>
</template>
