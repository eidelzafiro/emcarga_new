<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Button from 'primevue/button'
import DatePickerMes from '@/Components/DatePickerMes.vue'
import Checkbox from 'primevue/checkbox'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import ToggleSwitch from 'primevue/toggleswitch'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ clientes: Array, tipos_ingreso: Array, siguiente_numero: Number, aforos_pendientes: Array, fechaOperaciones: String })
const toast = useToast()

// Rango del mes de la fecha de operaciones activa para los selectores de fecha.
const fechaOp = () => (props.fechaOperaciones ? new Date(props.fechaOperaciones.slice(0, 10)) : new Date())
const minFecha = new Date(fechaOp().getFullYear(), fechaOp().getMonth(), 1)
const maxFecha = new Date(fechaOp().getFullYear(), fechaOp().getMonth() + 1, 0)

const form = ref({
    numero: props.siguiente_numero ?? '',
    fecha_emision: new Date(),
    id_cliente: null,
    flete_mt: 0,
    flete_mlc: 0,
    flete_demora: 0,
    otros_mt: 0,
    ingreso_mt: 0,
    oventas: false,
    id_tipo_ingreso: null,
    notas: '',
    aforos_ids: [],
})

const selectedAforos = ref([])
const totalFlete = computed(() => form.value.flete_mt + form.value.flete_demora + form.value.otros_mt)

// Aforos pendientes filtrados por el cliente seleccionado.
const aforosPorCliente = computed(() => {
    if (!form.value.id_cliente) return props.aforos_pendientes || []
    return (props.aforos_pendientes || []).filter((a) =>
        a.carta_porte?.solicitud?.id_cliente === form.value.id_cliente ||
        a.carta_porte?.cliente?.id === form.value.id_cliente
    )
})

function onAforosSelect(aforos) {
    form.value.aforos_ids = aforos.map(a => a.id)
    form.value.flete_mt = aforos.reduce((s, a) => s + Number(a.flete_mt), 0)
    form.value.flete_mlc = aforos.reduce((s, a) => s + Number(a.flete_mlc), 0)
    form.value.flete_demora = aforos.reduce((s, a) => s + Number(a.flete_demora), 0)
    form.value.otros_mt = aforos.reduce((s, a) => s + Number(a.otros_mt), 0)
    form.value.ingreso_mt = aforos.reduce((s, a) => s + Number(a.ingreso_mt), 0)
}

function onClienteChange() {
    selectedAforos.value = []
    form.value.aforos_ids = []
    form.value.flete_mt = 0
    form.value.flete_mlc = 0
    form.value.flete_demora = 0
    form.value.otros_mt = 0
    form.value.ingreso_mt = 0
}

function submit() {
    router.post(route('facturas.store'), form.value, {
        onSuccess: () => toast.add({ severity: 'success', summary: 'Factura creada', life: 3000 }),
        onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
    })
}
</script>

<template>
    <AppLayout :title="title">
        <div class="card p-6">
            <h2 class="text-xl font-bold mb-6">Nueva Factura</h2>
            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block mb-1 font-medium">Número</label>
                        <InputNumber v-model="form.numero" class="w-full" :min="100001" />
                        <small class="text-surface-400">Auto-generado; puede editarlo si lo necesita.</small>
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Fecha Emisión</label>
                        <DatePickerMes v-model="form.fecha_emision" date-format="dd/mm/yy" class="w-full" :min-date="minFecha" :max-date="maxFecha" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Cliente</label>
                        <Select v-model="form.id_cliente" :options="clientes" option-value="id" option-label="nombre" placeholder="Seleccione cliente" class="w-full" @change="onClienteChange" />
                    </div>
                </div>

                <div class="flex items-center gap-2 mb-4">
                    <ToggleSwitch v-model="form.oventas" input-id="oventas" />
                    <label for="oventas">Otras Ventas (OV)</label>
                </div>

                <div v-if="form.oventas" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-1 font-medium">Tipo de Ingreso</label>
                        <Select v-model="form.id_tipo_ingreso" :options="tipos_ingreso" option-value="id" option-label="nombre" placeholder="Seleccione tipo" class="w-full" />
                    </div>
                </div>

                <div v-if="!form.oventas && aforosPorCliente.length" class="border rounded-lg p-4">
                    <h3 class="font-medium mb-2">Cartas Porte pendientes de facturar</h3>
                    <DataTable v-model:selection="selectedAforos" :value="aforosPorCliente" selection-mode="multiple" data-key="id" @update:selection="onAforosSelect" striped-rows>
                        <Column selection-mode="multiple" header-style="width: 3rem" />
                        <Column field="carta_porte.numero" header="CP" />
                        <Column field="fecha_parte" header="Fecha" />
                        <Column field="flete_mt" header="Flete MN">
                            <template #body="{ data }">${{ Number(data.flete_mt).toLocaleString() }}</template>
                        </Column>
                        <Column field="flete_demora" header="Demora">
                            <template #body="{ data }">${{ Number(data.flete_demora).toLocaleString() }}</template>
                        </Column>
                        <Column field="ingreso_mt" header="Total">
                            <template #body="{ data }">${{ Number(data.ingreso_mt).toLocaleString() }}</template>
                        </Column>
                    </DataTable>
                    <div class="text-xs text-surface-400 pt-1">Total: {{ aforosPorCliente.length }} registros</div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block mb-1 font-medium">Flete MN</label>
                        <InputNumber v-model="form.flete_mt" :min="0" class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Flete MLC</label>
                        <InputNumber v-model="form.flete_mlc" :min="0" class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Demora</label>
                        <InputNumber v-model="form.flete_demora" :min="0" class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Otros</label>
                        <InputNumber v-model="form.otros_mt" :min="0" class="w-full" />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-1 font-medium">Ingreso Total MN</label>
                        <InputNumber v-model="form.ingreso_mt" :min="0" class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Notas</label>
                        <Textarea v-model="form.notas" class="w-full" rows="3" />
                    </div>
                </div>

                <div class="flex gap-2 justify-end">
                    <Button label="Cancelar" severity="secondary" @click="router.get(route('facturas.index'))" />
                    <Button label="Crear Factura" type="submit" icon="pi pi-save" />
                </div>
            </form>
        </div>
    </AppLayout>
</template>
