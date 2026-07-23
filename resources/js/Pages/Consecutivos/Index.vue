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
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ items: Object, filters: Object })
const toast = useToast()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)
const form = ref({ codigo: '', descripcion: '', ultimo: 0, formato: '' })

watch(search, () => {
    router.get(route('consecutivos.index'), { search: search.value }, { preserveState: true, replace: true })
})

function openCreate() {
    editing.value = null
    form.value = { codigo: '', descripcion: '', ultimo: 0, formato: '' }
    showForm.value = true
}

function openEdit(item) {
    editing.value = item
    form.value = { codigo: item.codigo, descripcion: item.descripcion, ultimo: item.ultimo, formato: item.formato ?? '' }
    showForm.value = true
}

function submit() {
    const url = editing.value ? route('consecutivos.update', editing.value.id) : route('consecutivos.store')
    const method = editing.value ? 'put' : 'post'
    router[method](url, form.value, {
        onSuccess: () => { showForm.value = false; toast.add({ severity: 'success', summary: editing.value ? 'Actualizado' : 'Creado', life: 3000 }) },
        onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
    })
}
</script>

<template>
    <AppLayout title="Consecutivos">
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
                <Column field="descripcion" header="Descripción" sortable />
                <Column field="ultimo" header="Último Valor" sortable />
                <Column field="formato" header="Formato" />
                <Column header="Acciones" :style="{ width: '120px' }">
                    <template #body="{ data }">
                        <div class="flex gap-1">
                            <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
                            <Button icon="pi pi-trash" rounded text severity="danger"
                                @click="router.delete(route('consecutivos.destroy', data.id))" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="showForm" :header="editing ? 'Editar Consecutivo' : 'Nuevo Consecutivo'" modal :style="{ width: '550px' }">
            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-1 font-medium">Código</label>
                        <InputText v-model="form.codigo" class="w-full" required />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Descripción</label>
                        <InputText v-model="form.descripcion" class="w-full" required />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Último Valor</label>
                        <InputNumber v-model="form.ultimo" class="w-full" :min="0" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Formato</label>
                        <InputText v-model="form.formato" class="w-full" />
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
