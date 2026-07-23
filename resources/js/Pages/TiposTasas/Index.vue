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
import ToggleSwitch from 'primevue/toggleswitch'
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ tiposTasas: Object, filters: Object })
const toast = useToast()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)
const form = ref({ codigo: '', nombre: '', unidad: '', valor: 0, activo: true })
const title = 'Tipos de Tasas'

watch(search, () => {
    router.get(route('tipos-tasas.index'), { search: search.value }, { preserveState: true, replace: true })
})

function openCreate() {
    editing.value = null
    form.value = { codigo: '', nombre: '', unidad: '', valor: 0, activo: true }
    showForm.value = true
}

function openEdit(item) {
    editing.value = item
    form.value = { codigo: item.codigo, nombre: item.nombre, unidad: item.unidad || '', valor: item.valor || 0, activo: Boolean(item.activo) }
    showForm.value = true
}

function submit() {
    const url = editing.value ? route('tipos-tasas.update', editing.value.id) : route('tipos-tasas.store')
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

            <DataTable :value="tiposTasas.data" striped-rows paginator :rows="20" :total-records="tiposTasas.total">
                <Column field="codigo" header="Código" sortable />
                <Column field="nombre" header="Nombre" sortable />
                <Column field="unidad" header="Unidad" />
                <Column field="valor" header="Valor" sortable />
                <Column field="activo" header="Activo">
                    <template #body="{ data }">
                        <Tag :value="data.activo ? 'Sí' : 'No'" :severity="data.activo ? 'success' : 'danger'" />
                    </template>
                </Column>
                <Column header="Acciones" style="width: 120px">
                    <template #body="{ data }">
                        <div class="flex gap-1">
                            <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
                            <Button icon="pi pi-trash" rounded text severity="danger" @click="router.delete(route('tipos-tasas.destroy', data.id))" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="showForm" :header="editing ? 'Editar Tipo de Tasa' : 'Nuevo Tipo de Tasa'" modal style="width: 500px">
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
                    <label class="block mb-1 font-medium">Unidad</label>
                    <InputText v-model="form.unidad" class="w-full" />
                </div>
                <div>
                    <label class="block mb-1 font-medium">Valor</label>
                    <InputNumber v-model="form.valor" class="w-full" :min="0" />
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
