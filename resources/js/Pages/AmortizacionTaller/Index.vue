<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ amortizaciones: Object, tractivos: Array, filters: Object })
const toast = useToast()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)

function baseForm() {
    return {
        id_tractivo: null,
        amortizacion_mn: null,
        chapa: null,
        observaciones: '',
    }
}
const form = ref(baseForm())

watch(search, () => reload())

function reload() {
    router.get(route('amortizacion-taller.index'), { search: search.value }, { preserveState: true, replace: true })
}

function onPage(event) {
    router.get(route('amortizacion-taller.index'), { page: event.page + 1, search: search.value }, { preserveState: true, replace: true })
}

function openCreate() {
    editing.value = null
    form.value = baseForm()
    showForm.value = true
}

function openEdit(item) {
    editing.value = item
    form.value = {
        id_tractivo: item.id_tractivo,
        amortizacion_mn: item.amortizacion_mn,
        chapa: item.chapa,
        observaciones: item.observaciones || '',
    }
    showForm.value = true
}

function submit() {
    const url = editing.value ? route('amortizacion-taller.update', editing.value.id) : route('amortizacion-taller.store')
    const method = editing.value ? 'put' : 'post'
    router[method](url, form.value, {
        onSuccess: () => { showForm.value = false; toast.add({ severity: 'success', summary: editing.value ? 'Actualizado' : 'Creado', life: 3000 }) },
        onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
    })
}
</script>

<template>
    <AppLayout :title="'Amortización Taller'">
        <div class="card">
            <Toolbar class="mb-4">
                <template #start>
                    <div class="flex items-center gap-2">
                        <Button label="Nuevo" icon="pi pi-plus" severity="success" @click="openCreate" />
                        <IconField>
                            <InputIcon class="pi pi-search" />
                            <InputText v-model="search" placeholder="Buscar..." @keyup.enter="reload" />
                        </IconField>
                    </div>
                </template>
            </Toolbar>

            <DataTable :value="amortizaciones.data" striped-rows paginator :rows="20" :total-records="amortizaciones.total" :lazy="true" :first="(amortizaciones.current_page - 1) * amortizaciones.per_page" @page="onPage" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords}">
                <Column header="Tractivo">
                    <template #body="{ data }">{{ data.tractivo?.codigo }}</template>
                </Column>
                <Column field="amortizacion_mn" header="Amortización MN" style="width: 130px">
                    <template #body="{ data }">{{ Number(data.amortizacion_mn).toFixed(2) }}</template>
                </Column>
                <Column field="chapa" header="Chapa" style="width: 100px">
                    <template #body="{ data }">{{ Number(data.chapa).toFixed(2) }}</template>
                </Column>
                <Column header="Entidad">
                    <template #body="{ data }">{{ data.entidad?.nombre }}</template>
                </Column>
                <Column field="observaciones" header="Observaciones" show-overflow-tooltip />
                <Column header="Acciones" style="width: 100px">
                    <template #body="{ data }">
                        <div class="flex gap-1">
                            <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
                            <Button icon="pi pi-trash" rounded text severity="danger" @click="router.delete(route('amortizacion-taller.destroy', data.id))" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="showForm" :header="editing ? 'Editar Amortización' : 'Nueva Amortización'" modal style="width: 500px">
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block mb-1 font-medium">Tractivo</label>
                    <Select v-model="form.id_tractivo" :options="tractivos" optionLabel="codigo" optionValue="id" filter placeholder="Seleccione..." class="w-full" :showClear="true" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-1 font-medium">Amortización MN *</label>
                        <InputNumber v-model="form.amortizacion_mn" :max-fraction-digits="2" class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Chapa *</label>
                        <InputNumber v-model="form.chapa" :max-fraction-digits="2" class="w-full" />
                    </div>
                </div>
                <div>
                    <label class="block mb-1 font-medium">Observaciones</label>
                    <Textarea v-model="form.observaciones" class="w-full" rows="2" />
                </div>
                <div class="flex gap-2 justify-end">
                    <Button label="Cancelar" severity="secondary" @click="showForm = false" />
                    <Button label="Guardar" type="submit" icon="pi pi-save" />
                </div>
            </form>
        </Dialog>
    </AppLayout>
</template>
