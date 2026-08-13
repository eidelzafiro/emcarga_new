<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Button from 'primevue/button'
import DatePicker from 'primevue/datepicker'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ clientes: Array, siguiente_numero: String, aforos_pendientes: Array })
const toast = useToast()

const form = ref({
    numero: props.siguiente_numero ?? '',
    id_cliente: null,
    fecha: new Date(),
    flete_mt: 0,
    flete_mlc: 0,
    flete_demora: 0,
    otros_mt: 0,
    ingreso_mt: 0,
    notas: '',
    aforos_ids: [],
})

const selectedAforos = ref([])
const totalFlete = computed(() => form.value.flete_mt + form.value.flete_demora + form.value.otros_mt)

function onAforosSelect(aforos) {
    form.value.aforos_ids = aforos.map(a => a.id)
    form.value.flete_mt = aforos.reduce((s, a) => s + Number(a.flete_mt), 0)
    form.value.flete_mlc = aforos.reduce((s, a) => s + Number(a.flete_mlc), 0)
    form.value.flete_demora = aforos.reduce((s, a) => s + Number(a.flete_demora), 0)
    form.value.otros_mt = aforos.reduce((s, a) => s + Number(a.otros_mt), 0)
    form.value.ingreso_mt = aforos.reduce((s, a) => s + Number(a.ingreso_mt), 0)
}

function submit() {
    router.post(route('prefacturas.store'), form.value, {
        onSuccess: () => toast.add({ severity: 'success', summary: 'Prefactura creada', life: 3000 }),
        onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
    })
}
</script>

<template>
    <AppLayout :title="title">
        <div class="card p-6">
            <h2 class="text-xl font-bold mb-6">Nueva Prefactura</h2>
            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block mb-1 font-medium">Número</label>
                        <InputText v-model="form.numero" class="w-full" />
                        <small class="text-surface-400">Auto-generado; puede editarlo si lo necesita.</small>
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Fecha</label>
                        <DatePicker v-model="form.fecha" date-format="yy-mm-dd" class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Cliente</label>
                        <Select v-model="form.id_cliente" :options="clientes" option-value="id" option-label="nombre" placeholder="Seleccione cliente" class="w-full" />
                    </div>
                </div>

                <div v-if="aforos_pendientes.length" class="border rounded-lg p-4">
                    <h3 class="font-medium mb-2">Cartas Porte pendientes de prefacturar</h3>
                    <DataTable v-model:selection="selectedAforos" :value="aforos_pendientes" selection-mode="multiple" data-key="id" @update:selection="onAforosSelect" striped-rows>
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
                    <div class="text-xs text-surface-400 pt-1">Total: {{ aforos_pendientes.length }} registros</div>
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
                    <Button label="Cancelar" severity="secondary" @click="router.get(route('prefacturas.index'))" />
                    <Button label="Crear Prefactura" type="submit" icon="pi pi-save" />
                </div>
            </form>
        </div>
    </AppLayout>
</template>
