<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import Toolbar from 'primevue/toolbar'

const props = defineProps({ aforos: Object, filters: Object })

const search = ref(props.filters?.search || '')
const estado = ref(props.filters?.estado || '')
const anio = ref(props.filters?.anio || '')
const mes = ref(props.filters?.mes || '')

const meses = [
    { label: 'Enero', value: 1 }, { label: 'Febrero', value: 2 }, { label: 'Marzo', value: 3 },
    { label: 'Abril', value: 4 }, { label: 'Mayo', value: 5 }, { label: 'Junio', value: 6 },
    { label: 'Julio', value: 7 }, { label: 'Agosto', value: 8 }, { label: 'Septiembre', value: 9 },
    { label: 'Octubre', value: 10 }, { label: 'Noviembre', value: 11 }, { label: 'Diciembre', value: 12 },
]

function estadoDe(aforo) {
    if (aforo.id_factura) return { label: 'Facturado', severity: 'success' }
    if (aforo.id_prefactura) return { label: 'Prefacturado', severity: 'warn' }
    return { label: 'Pendiente', severity: 'info' }
}

watch([search, estado, anio, mes], () => {
    router.get(route('aforos.index'), { search: search.value, estado: estado.value, anio: anio.value, mes: mes.value }, { preserveState: true, replace: true })
})
</script>

<template>
    <AppLayout :title="title">
        <div class="card">
            <Toolbar class="mb-4">
                <template #start>
                    <h2 class="text-xl font-bold">Aforos</h2>
                </template>
                <template #end>
                    <div class="flex gap-2">
                        <Button label="Nuevo Aforo" icon="pi pi-plus" @click="router.get(route('aforos.create'))" />
                        <InputText v-model="search" placeholder="Buscar CP, cliente o tractivo..." />
                        <Select v-model="estado" :options="['', 'pendiente', 'prefacturado', 'facturado']" placeholder="Estado" class="w-44" />
                        <Select v-model="anio" :options="['', '2024', '2025', '2026']" placeholder="Año" class="w-28" />
                        <Select v-model="mes" :options="meses" option-label="label" option-value="value" placeholder="Mes" class="w-36" />
                    </div>
                </template>
            </Toolbar>

            <DataTable :value="aforos.data" :loading="false" striped-rows paginator :rows="20" :total-records="aforos.total" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros">
                <Column field="carta_porte.numero" header="CP" sortable />
                <Column field="fecha_parte" header="Fecha" sortable>
                    <template #body="{ data }">{{ data.fecha_parte }}</template>
                </Column>
                <Column field="carta_porte.cliente.nombre" header="Cliente" sortable />
                <Column field="carta_porte.tractivo.codigo" header="Tractivo" sortable />
                <Column field="flete_mt" header="Flete MN">
                    <template #body="{ data }">${{ Number(data.flete_mt).toLocaleString() }}</template>
                </Column>
                <Column field="ingreso_mt" header="Total">
                    <template #body="{ data }">${{ Number(data.ingreso_mt).toLocaleString() }}</template>
                </Column>
                <Column header="Estado">
                    <template #body="{ data }">
                        <Tag :severity="estadoDe(data).severity">{{ estadoDe(data).label }}</Tag>
                    </template>
                </Column>
                <Column header="Acciones" style="width: 120px">
                    <template #body="{ data }">
                        <div class="flex gap-1">
                            <Button icon="pi pi-eye" rounded text severity="info" @click="router.get(route('aforos.show', data.id))" v-tooltip.top="'Ver'" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>
    </AppLayout>
</template>
