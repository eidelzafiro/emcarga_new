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

const props = defineProps({ items: Object, tractivos: Array, filters: Object })
const toast = useToast()
const searchMes = ref(props.filters?.mes || null)
const searchAno = ref(props.filters?.ano || null)
const showForm = ref(false)
const editing = ref(null)
const form = ref({ mes: null, ano: null, id_tractivo: null })
const title = 'Pizarra de Tractivos'

watch([searchMes, searchAno], () => {
    router.get(route('pizarra-tractivos.index'), {
        mes: searchMes.value || undefined,
        ano: searchAno.value || undefined,
    }, { preserveState: true, replace: true })
})

function openCreate() {
    editing.value = null
    form.value = { mes: null, ano: null, id_tractivo: null }
    showForm.value = true
}

function openEdit(item) {
    editing.value = item
    form.value = {
        mes: item.mes,
        ano: item.ano,
        id_tractivo: item.id_tractivo,
    }
    showForm.value = true
}

function submit() {
    const url = editing.value ? route('pizarra-tractivos.update', editing.value.id) : route('pizarra-tractivos.store')
    const method = editing.value ? 'put' : 'post'
    router[method](url, form.value, {
        onSuccess: () => { showForm.value = false; toast.add({ severity: 'success', summary: editing.value ? 'Actualizado' : 'Creado', life: 3000 }) },
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
                        <InputNumber v-model="searchMes" placeholder="Mes" :min="1" :max="12" class="w-24" />
                        <InputNumber v-model="searchAno" placeholder="Año" :min="2000" class="w-28" />
                    </div>
                </template>
            </Toolbar>

            <DataTable :value="items.data" striped-rows paginator :rows="20" :total-records="items.total" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros">
                <Column field="tractivo.codigo" header="Tractivo" sortable />
                <Column field="mes" header="Mes" sortable />
                <Column field="ano" header="Año" sortable />
                <Column header="Acciones" style="width: 120px">
                    <template #body="{ data }">
                        <div class="flex gap-1">
                            <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
                            <Button icon="pi pi-trash" rounded text severity="danger" @click="router.delete(route('pizarra-tractivos.destroy', data.id))" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="showForm" :header="editing ? 'Editar Registro' : 'Nuevo Registro'" modal style="width: 600px">
            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-1 font-medium">Tractivo</label>
                        <Select v-model="form.id_tractivo" :options="tractivos" optionLabel="codigo" optionValue="id" placeholder="Seleccione..." class="w-full" required />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Mes</label>
                        <InputNumber v-model="form.mes" :min="1" :max="12" class="w-full" required />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Año</label>
                        <InputNumber v-model="form.ano" :min="2000" class="w-full" required />
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
