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
import InputNumber from 'primevue/inputnumber'
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ items: Object, tiposCarga: Array, filters: Object })
const toast = useToast()
const searchTipo = ref(props.filters?.id_tipo_carga || null)
const searchVersion = ref(props.filters?.version || null)
const showForm = ref(false)
const editing = ref(null)
const form = ref({ id_tipo_carga: null, kms: null, tarifa_mt: null, version: 'normal' })
const title = 'Tarifas'

const versiones = [
    { label: 'Normal', value: 'normal' },
    { label: '46', value: '46' },
]

watch([searchTipo, searchVersion], () => {
    router.get(route('tarifas.index'), {
        id_tipo_carga: searchTipo.value || undefined,
        version: searchVersion.value || undefined,
    }, { preserveState: true, replace: true })
})

function openCreate() {
    editing.value = null
    form.value = { id_tipo_carga: null, kms: null, tarifa_mt: null, version: 'normal' }
    showForm.value = true
}

function openEdit(item) {
    editing.value = item
    form.value = {
        id_tipo_carga: item.id_tipo_carga,
        kms: item.kms,
        tarifa_mt: item.tarifa_mt,
        version: item.version,
    }
    showForm.value = true
}

function submit() {
    const url = editing.value ? route('tarifas.update', editing.value.id) : route('tarifas.store')
    const method = editing.value ? 'put' : 'post'
    router[method](url, form.value, {
        onSuccess: () => { showForm.value = false; toast.add({ severity: 'success', summary: editing.value ? 'Actualizada' : 'Creada', life: 3000 }) },
        onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
    })
}
</script>

<template>
    <AppLayout :title="title">
        <div class="card">
            <Toolbar class="mb-4">
                <template #start>
                    <Button label="Nuevo" icon="pi pi-plus" severity="success" @click="openCreate" />
                </template>
                <template #end>
                    <div class="flex gap-2">
                        <Select v-model="searchTipo" :options="tiposCarga" optionLabel="nombre" optionValue="id" placeholder="Tipo carga" class="w-48" />
                        <Select v-model="searchVersion" :options="versiones" optionLabel="label" optionValue="value" placeholder="Versión" class="w-40" />
                    </div>
                </template>
            </Toolbar>

            <DataTable :value="items.data" striped-rows paginator :rows="20" :total-records="items.total" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros">
                <Column field="tipo_carga.nombre" header="Tipo Carga" sortable />
                <Column field="kms" header="Kms" sortable />
                <Column field="tarifa_mt" header="Tarifa MN">
                    <template #body="{ data }">
                        {{ data.tarifa_mt?.toLocaleString('es-CU', { minimumFractionDigits: 2 }) }}
                    </template>
                </Column>
                <Column field="version" header="Versión" />
                <Column header="Acciones" style="width: 120px">
                    <template #body="{ data }">
                        <div class="flex gap-1">
                            <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
                            <Button icon="pi pi-trash" rounded text severity="danger" @click="router.delete(route('tarifas.destroy', data.id))" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="showForm" :header="editing ? 'Editar Tarifa' : 'Nueva Tarifa'" modal style="width: 600px">
            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-1 font-medium">Tipo de Carga</label>
                        <Select v-model="form.id_tipo_carga" :options="tiposCarga" optionLabel="nombre" optionValue="id" placeholder="Seleccione..." class="w-full" required />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Versión</label>
                        <Select v-model="form.version" :options="versiones" optionLabel="label" optionValue="value" placeholder="Seleccione..." class="w-full" required />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Kms</label>
                        <InputNumber v-model="form.kms" :minFractionDigits="2" class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Tarifa MN</label>
                        <InputNumber v-model="form.tarifa_mt" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
                    </div>
                </div>
                <div class="flex gap-2 justify-end">
                    <Button label="Cancelar" severity="secondary" @click="showForm = false" />
                    <Button label="Guardar" type="submit" icon="pi pi-save" />
                </div>
            </form>
        </Dialog>
    </AppLayout>
</template>
