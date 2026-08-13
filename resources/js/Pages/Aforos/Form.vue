<script setup>
import { ref, reactive, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import Button from 'primevue/button'
import DatePicker from 'primevue/datepicker'
import Textarea from 'primevue/textarea'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Accordion from 'primevue/accordion'
import AccordionPanel from 'primevue/accordionpanel'
import AccordionHeader from 'primevue/accordionheader'
import AccordionContent from 'primevue/accordioncontent'
import { useToast } from 'primevue/usetoast'

const props = defineProps({
    tiposCarga: Array,
    clientes: Array,
    tractivos: Array,
    lugares: Array,
    productos: Array,
    siguiente_cp: Number,
})

const toast = useToast()
const calcular = ref(false)

const form = reactive({
    numero: props.siguiente_cp ?? '',
    fecha_parte: new Date(),
    id_cliente: null,
    id_tractivo: null,
    id_arrastre: null,
    id_chofer: null,
    id_chofer2: null,
    id_lugar_origen: null,
    id_lugar_destino: null,
    id_producto: null,
    id_tipo_carga: null,
    id_moneda: 1,
    distancia: 0,
    toneladas: 0,
    descuento: 0,
    flete_mt: 0,
    flete_mlc: 0,
    flete_demora: 0,
    otros_mt: 0,
    ingreso_mt: 0,
    notas: '',
})

// Hasta 5 líneas de tarifa (paridad con el formulario legacy: posiciones 1-5)
const lineas = reactive(
    Array.from({ length: 5 }, () => ({
        id_tipo_carga: null,
        distancia: 0,
        peso: 0,
        descuento: 0,
        tarifa_mt: 0,
        flete_mt: 0,
        flete_mlc: 0,
        calculando: false,
    }))
)

const fleteTotal = computed(() => lineas.reduce((s, l) => s + Number(l.flete_mt || 0), 0))
const demoraTotal = computed(() => Number(form.flete_demora || 0))
const otrosTotal = computed(() => Number(form.otros_mt || 0))
const ingresoTotal = computed(() => fleteTotal.value + demoraTotal.value + otrosTotal.value)

function tractivoSeleccionado() {
    return props.tractivos.find((t) => t.id === form.id_tractivo)
}

function onTractivo() {
    const t = tractivoSeleccionado()
    if (t?.capacidad_toneladas) {
        form.toneladas = Number(t.capacidad_toneladas)
    }
}

async function cotizarLinea(linea) {
    if (!linea.id_tipo_carga || !linea.distancia || !linea.peso) return

    linea.calculando = true
    try {
        const resp = await fetch(route('aforos.cotizar'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({
                moneda: form.id_moneda,
                tipocarga: linea.id_tipo_carga,
                distancia: linea.distancia,
                peso: linea.peso,
                descuento: linea.descuento,
                mlc: 0,
            }),
        })
        const data = await resp.json()
        if (!resp.ok) {
            toast.add({ severity: 'error', summary: 'Error', detail: Object.values(data.errors || {}).join(', '), life: 5000 })
            return
        }
        linea.tarifa_mt = Number(data.tarmt || 0)
        linea.flete_mt = Number(data.fletemt || 0)
        linea.flete_mlc = Number(data.fletemlc || 0)
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error de red', detail: e.message, life: 5000 })
    } finally {
        linea.calculando = false
    }
}

function calcularTodas() {
    calcular.value = true
    lineas.forEach((l) => cotizarLinea(l))
    toast.add({ severity: 'info', summary: 'Calculando tarifas...', life: 2000 })
}

function submit() {
    form.flete_mt = fleteTotal.value
    form.ingreso_mt = ingresoTotal.value
    router.post(route('aforos.store'), { ...form }, {
        onSuccess: () => toast.add({ severity: 'success', summary: 'Aforo creado', life: 3000 }),
        onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
    })
}
</script>

<template>
    <AppLayout :title="title">
        <div class="card p-6">
            <h2 class="text-xl font-bold mb-6">Nuevo Aforo</h2>
            <form @submit.prevent="submit" class="space-y-6">
                <Accordion :value="['gral']" multiple>
                    <AccordionPanel value="gral">
                        <AccordionHeader>Datos Generales</AccordionHeader>
                        <AccordionContent>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block mb-1 font-medium">Nro. Carta Porte</label>
                                <InputText v-model="form.numero" class="w-full" />
                            </div>
                            <div>
                                <label class="block mb-1 font-medium">Fecha de Parte</label>
                                <DatePicker v-model="form.fecha_parte" date-format="yy-mm-dd" class="w-full" />
                            </div>
                            <div>
                                <label class="block mb-1 font-medium">Moneda</label>
                                <Select v-model="form.id_moneda" :options="[{ id: 1, nombre: 'MN' }, { id: 2, nombre: 'CL' }]" option-value="id" option-label="nombre" class="w-full" />
                            </div>
                            <div>
                                <label class="block mb-1 font-medium">Cliente</label>
                                <Select v-model="form.id_cliente" :options="clientes" option-value="id" option-label="nombre" placeholder="Seleccione cliente" class="w-full" />
                            </div>
                            <div>
                                <label class="block mb-1 font-medium">Tractivo</label>
                                <Select v-model="form.id_tractivo" :options="tractivos" option-value="id" option-label="codigo" placeholder="Seleccione tractivo" class="w-full" @change="onTractivo" />
                            </div>
                            <div>
                                <label class="block mb-1 font-medium">Capacidad (t)</label>
                                <InputNumber v-model="form.toneladas" :min="0" class="w-full" />
                            </div>
                            <div>
                                <label class="block mb-1 font-medium">Origen</label>
                                <Select v-model="form.id_lugar_origen" :options="lugares" option-value="id" option-label="nombre" filter placeholder="Seleccione origen" class="w-full" />
                            </div>
                            <div>
                                <label class="block mb-1 font-medium">Destino</label>
                                <Select v-model="form.id_lugar_destino" :options="lugares" option-value="id" option-label="nombre" filter placeholder="Seleccione destino" class="w-full" />
                            </div>
                            <div>
                                <label class="block mb-1 font-medium">Producto</label>
                                <Select v-model="form.id_producto" :options="productos" option-value="id" option-label="nombre" filter placeholder="Seleccione producto" class="w-full" />
                            </div>
                            <div>
                                <label class="block mb-1 font-medium">Tipo de Carga</label>
                                <Select v-model="form.id_tipo_carga" :options="tiposCarga" option-value="id" option-label="nombre" filter placeholder="Seleccione tipo" class="w-full" />
                            </div>
                            <div>
                                <label class="block mb-1 font-medium">Distancia (km)</label>
                                <InputNumber v-model="form.distancia" :min="0" class="w-full" />
                            </div>
                            <div>
                                <label class="block mb-1 font-medium">Descuento (%)</label>
                                <InputNumber v-model="form.descuento" :min="0" :max="100" class="w-full" />
                            </div>
                        </div>
                        </AccordionContent>
                    </AccordionPanel>

                    <AccordionPanel value="lineas">
                        <AccordionHeader>Líneas de Tarifa</AccordionHeader>
                        <AccordionContent>
                        <div class="flex justify-end mb-3">
                            <Button label="Calcular todas" icon="pi pi-calculator" @click="calcularTodas" :loading="calcular" />
                        </div>
                        <DataTable :value="lineas" striped-rows>
                            <Column field="id_tipo_carga" header="Tipo Carga">
                                <template #body="{ data }">
                                    <Select v-model="data.id_tipo_carga" :options="tiposCarga" option-value="id" option-label="nombre" filter class="w-full" />
                                </template>
                            </Column>
                            <Column field="distancia" header="Distancia">
                                <template #body="{ data }">
                                    <InputNumber v-model="data.distancia" :min="0" class="w-full" @blur="cotizarLinea(data)" />
                                </template>
                            </Column>
                            <Column field="peso" header="Peso (t)">
                                <template #body="{ data }">
                                    <InputNumber v-model="data.peso" :min="0" class="w-full" @blur="cotizarLinea(data)" />
                                </template>
                            </Column>
                            <Column field="descuento" header="Desc. %">
                                <template #body="{ data }">
                                    <InputNumber v-model="data.descuento" :min="0" :max="100" class="w-full" @blur="cotizarLinea(data)" />
                                </template>
                            </Column>
                            <Column field="tarifa_mt" header="Tarifa">
                                <template #body="{ data }">${{ Number(data.tarifa_mt).toLocaleString() }}</template>
                            </Column>
                            <Column field="flete_mt" header="Flete MN">
                                <template #body="{ data }">${{ Number(data.flete_mt).toLocaleString() }}</template>
                            </Column>
                            <Column header="">
                                <template #body="{ data }">
                                    <Button icon="pi pi-calculator" text rounded severity="info" @click="cotizarLinea(data)" :loading="data.calculando" v-tooltip.top="'Calcular'" />
                                </template>
                            </Column>
                        </DataTable>
                        </AccordionContent>
                    </AccordionPanel>
                </Accordion>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block mb-1 font-medium">Flete MN</label>
                        <InputNumber v-model="form.flete_mt" :min="0" class="w-full" readonly />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Demora</label>
                        <InputNumber v-model="form.flete_demora" :min="0" class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Otros</label>
                        <InputNumber v-model="form.otros_mt" :min="0" class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Ingreso Total</label>
                        <InputNumber v-model="form.ingreso_mt" :min="0" class="w-full" readonly />
                    </div>
                </div>

                <div>
                    <label class="block mb-1 font-medium">Notas</label>
                    <Textarea v-model="form.notas" class="w-full" rows="3" />
                </div>

                <div class="flex gap-2 justify-end">
                    <Button label="Cancelar" severity="secondary" @click="router.get(route('aforos.index'))" />
                    <Button label="Guardar Aforo" type="submit" icon="pi pi-save" />
                </div>
            </form>
        </div>
    </AppLayout>
</template>
