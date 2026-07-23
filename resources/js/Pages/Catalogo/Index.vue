<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import ToggleSwitch from 'primevue/toggleswitch'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ items: Object, filters: Object, catalogConfig: Object })
const toast = useToast()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)
const form = ref({ codigo: '', nombre: '', activo: true })

watch(search, () => {
    router.get(route(`${props.catalogConfig.route}.index`), { search: search.value }, { preserveState: true, replace: true })
})

function openCreate() {
    editing.value = null
    form.value = { codigo: '', nombre: '', activo: true }
    if (props.catalogConfig?.extra) {
        Object.entries(props.catalogConfig.extra).forEach(([k]) => { form.value[k] = '' })
    }
    showForm.value = true
}

function openEdit(item) {
    editing.value = item
    form.value = { codigo: item.codigo, nombre: item.nombre, activo: Boolean(item.activo) }
    if (props.catalogConfig?.extra) {
        Object.entries(props.catalogConfig.extra).forEach(([k]) => { form.value[k] = item[k] ?? '' })
    }
    showForm.value = true
}

function submit() {
    const rt = props.catalogConfig.route
    const url = editing.value ? route(`${rt}.update`, editing.value.id) : route(`${rt}.store`)
    const method = editing.value ? 'put' : 'post'
    router[method](url, form.value, {
        onSuccess: () => { showForm.value = false; toast.add({ severity: 'success', summary: editing.value ? 'Actualizado' : 'Creado', life: 3000 }) },
        onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
    })
}
</script>

<template>
    <AppLayout :title="catalogConfig?.title || 'Catálogo'">
        <div class="card">
            <Toolbar class="mb-4">
                <template #start>
                    <Button label="Nuevo" icon="pi pi-plus" severity="success" @click="openCreate" />
                </template>
                <template #end>
                    <InputText v-model="search" placeholder="Buscar..." />
                </template>
            </Toolbar>

            <DataTable :value="items.data" striped-rows paginator :rows="20" :total-records="items.total">
                <Column field="codigo" header="Código" sortable />
                <Column field="nombre" header="Nombre" sortable />
                <Column v-for="(label, key) in (catalogConfig?.extra || {})" :key="key" :field="key" :header="label" />
                <Column field="activo" header="Activo" :style="{ width: '100px' }">
                    <template #body="{ data }">
                        <i v-if="data.activo !== undefined" :class="data.activo ? 'pi pi-check text-green-600' : 'pi pi-times text-red-500'" />
                    </template>
                </Column>
                <Column header="Acciones" :style="{ width: '120px' }">
                    <template #body="{ data }">
                        <div class="flex gap-1">
                            <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
                            <Button icon="pi pi-trash" rounded text severity="danger"
                                @click="router.delete(route(`${catalogConfig.route}.destroy`, data.id))" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="showForm" :header="editing ? `Editar ${catalogConfig?.title}` : `Nuevo ${catalogConfig?.title}`" modal :style="{ width: '550px' }">
            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-1 font-medium">Código</label>
                        <InputText v-model="form.codigo" class="w-full" required />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Nombre</label>
                        <InputText v-model="form.nombre" class="w-full" required />
                    </div>
                    <template v-for="(label, key) in (catalogConfig?.extra || {})" :key="key">
                        <div>
                            <label class="block mb-1 font-medium">{{ label }}</label>
                            <InputText v-model="form[key]" class="w-full" />
                        </div>
                    </template>
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
