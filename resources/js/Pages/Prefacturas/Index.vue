<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ prefacturas: Object, filters: Object, clientes: Array, aforos_pendientes: Array })
const toast = useToast()
const search = ref(props.filters?.search || '')
const estado = ref(props.filters?.estado || '')
const showForm = ref(false)
const form = ref({ numero: '', id_cliente: null, fecha: new Date(), flete_mt: 0, flete_mlc: 0, flete_demora: 0, otros_mt: 0, ingreso_mt: 0, notas: '' })

watch([search, estado], () => {
    router.get(route('prefacturas.index'), { search: search.value, estado: estado.value }, { preserveState: true, replace: true })
})

function openCreate() {
    form.value = { numero: '', id_cliente: null, fecha: new Date(), flete_mt: 0, flete_mlc: 0, flete_demora: 0, otros_mt: 0, ingreso_mt: 0, notas: '' }
    showForm.value = true
}

function submit() {
    router.post(route('prefacturas.store'), form.value, {
        onSuccess: () => { showForm.value = false; toast.add({ severity: 'success', summary: 'Prefactura creada', life: 3000 }) },
        onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
    })
}

function facturar(prefactura) {
    router.post(route('prefacturas.facturar', prefactura.id), {}, {
        onSuccess: () => toast.add({ severity: 'success', summary: 'Prefactura facturada', life: 3000 }),
        onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
    })
}
</script>

<template>
    <AppLayout :title="title">
        <div class="card">
            <Toolbar class="mb-4">
                <template #start>
                    <Button label="Nueva Prefactura" icon="pi pi-plus" severity="success" @click="router.get(route('prefacturas.create'))" />
                </template>
                <template #end>
                    <div class="flex gap-2">
                        <InputText v-model="search" placeholder="Buscar..." />
                        <Select v-model="estado" :options="['', 'pendiente', 'procesada', 'cancelada']" placeholder="Estado" class="w-40" />
                    </div>
                </template>
            </Toolbar>

            <DataTable :value="prefacturas.data" striped-rows paginator :rows="20" :total-records="prefacturas.total" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros">
                <Column field="numero" header="No. Prefactura" sortable />
                <Column field="cliente.nombre" header="Cliente" sortable />
                <Column field="fecha" header="Fecha" sortable />
                <Column field="ingreso_mt" header="Total MN">
                    <template #body="{ data }">${{ Number(data.ingreso_mt).toLocaleString() }}</template>
                </Column>
                <Column field="estado" header="Estado">
                    <template #body="{ data }">
                        <Tag :severity="data.estado === 'cancelada' ? 'danger' : data.estado === 'procesada' ? 'success' : 'info'">{{ data.estado }}</Tag>
                    </template>
                </Column>
                <Column header="Acciones" style="width: 200px">
                    <template #body="{ data }">
                        <div class="flex gap-1">
                            <Button icon="pi pi-file-pdf" rounded text severity="danger" @click="window.open(route('reportes.prefactura', data.id), '_blank')" v-tooltip.top="'PDF'" />
                            <Button icon="pi pi-arrow-right" rounded text severity="success" :disabled="data.estado !== 'pendiente'" @click="facturar(data)" v-tooltip.top="'Facturar'" />
                            <Button icon="pi pi-trash" rounded text severity="danger" @click="router.delete(route('prefacturas.destroy', data.id))" v-tooltip.top="'Eliminar'" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>
    </AppLayout>
</template>
