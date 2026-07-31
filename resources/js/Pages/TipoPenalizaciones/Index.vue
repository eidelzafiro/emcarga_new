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
import ToggleSwitch from 'primevue/toggleswitch'
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ tipos: Object, filters: Object, areas: Array, sistemasPago: Array })
const toast = useToast()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)
const form = ref({ codigo: '', nombre: '', activo: true, area_id: null, tipo_pago_adicional_id: null, porcentaje: null })
const title = 'Tipo de Penalizaciones'

function onPage(event) {
    router.get(route('tipos-penalizaciones.index'), { page: event.page + 1, search: search.value }, { preserveState: true, replace: true })
}

watch(search, () => {
    router.get(route('tipos-penalizaciones.index'), { search: search.value }, { preserveState: true, replace: true })
})

function openCreate() {
    editing.value = null
    form.value = { codigo: '', nombre: '', activo: true, area_id: null, tipo_pago_adicional_id: null, porcentaje: null }
    showForm.value = true
}

function openEdit(item) {
    editing.value = item
    form.value = { codigo: item.codigo, nombre: item.nombre, activo: Boolean(item.activo), area_id: item.area_id, tipo_pago_adicional_id: item.tipo_pago_adicional_id, porcentaje: item.porcentaje }
    showForm.value = true
}

function submit() {
    const url = editing.value ? route('tipos-penalizaciones.update', editing.value.id) : route('tipos-penalizaciones.store')
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
                    <InputText v-model="search" placeholder="Buscar..." />
                </template>
            </Toolbar>

            <DataTable :value="tipos.data" striped-rows paginator :rows="20" :total-records="tipos.total" :lazy="true" :first="(tipos.current_page - 1) * tipos.per_page" @page="onPage">
                <Column field="codigo" header="Código" sortable />
                <Column field="nombre" header="Nombre" sortable />
                <Column field="area" header="Área">
                    <template #body="{ data }">
                        {{ data.area?.nombre || '-' }}
                    </template>
                </Column>
                <Column field="tipo_pago_adicional" header="Sistema de Pago">
                    <template #body="{ data }">
                        {{ data.tipo_pago_adicional?.nombre || '-' }}
                    </template>
                </Column>
                <Column field="porcentaje" header="% Penalizar" sortable>
                    <template #body="{ data }">
                        {{ data.porcentaje != null ? data.porcentaje + '%' : '-' }}
                    </template>
                </Column>
                <Column field="activo" header="Activo">
                    <template #body="{ data }">
                        <Tag :value="data.activo ? 'Sí' : 'No'" :severity="data.activo ? 'success' : 'danger'" />
                    </template>
                </Column>
                <Column header="Acciones" style="width: 120px">
                    <template #body="{ data }">
                        <div class="flex gap-1">
                            <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
                            <Button icon="pi pi-trash" rounded text severity="danger" @click="router.delete(route('tipos-penalizaciones.destroy', data.id))" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="showForm" :header="editing ? 'Editar Tipo de Penalización' : 'Nuevo Tipo de Penalización'" modal style="width: 550px">
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block mb-1 font-medium">Código</label>
                    <InputText v-model="form.codigo" class="w-full" required />
                </div>
                <div>
                    <label class="block mb-1 font-medium">Nombre</label>
                    <InputText v-model="form.nombre" class="w-full" required />
                </div>
                <div>
                    <label class="block mb-1 font-medium">Área</label>
                    <Select v-model="form.area_id" :options="areas" option-value="id" option-label="nombre" placeholder="Seleccione un área" class="w-full" filter />
                </div>
                <div>
                    <label class="block mb-1 font-medium">Sistema de Pago</label>
                    <Select v-model="form.tipo_pago_adicional_id" :options="sistemasPago" option-value="id" option-label="nombre" placeholder="Seleccione un sistema de pago" class="w-full" filter />
                </div>
                <div>
                    <label class="block mb-1 font-medium">% a Penalizar</label>
                    <InputNumber v-model="form.porcentaje" :min="0" :max="100" :max-fraction-digits="2" class="w-full" placeholder="0.00">
                        <template #suffix>%</template>
                    </InputNumber>
                </div>
                <div class="flex items-center gap-2">
                    <ToggleSwitch v-model="form.activo" inputId="activo" />
                    <label for="activo" class="font-medium">Activo</label>
                </div>
                <div class="flex gap-2 justify-end">
                    <Button label="Cancelar" severity="secondary" @click="showForm = false" />
                    <Button label="Guardar" type="submit" icon="pi pi-save" />
                </div>
            </form>
        </Dialog>
    </AppLayout>
</template>
