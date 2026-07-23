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
import Tag from 'primevue/tag'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ inventario: Object, filters: Object })
const toast = useToast()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)
const form = ref({ codigo: '', nombre: '', descripcion: '', categoria: '', unidad_medida: null, cantidad_actual: null, costo_unitario: null, costo_total: null, ubicacion: '' })
const title = 'Inventario'

const unidadesMedida = [
    { label: 'Unidad', value: 'unidad' },
    { label: 'Litro', value: 'litro' },
    { label: 'Kilogramo', value: 'kg' },
    { label: 'Metro', value: 'metro' },
    { label: 'Metro cuadrado', value: 'm2' },
    { label: 'Metro cúbico', value: 'm3' },
    { label: 'Caja', value: 'caja' },
    { label: 'Paquete', value: 'paquete' },
    { label: 'Rollo', value: 'rollo' },
    { label: 'Galón', value: 'galon' },
]

watch(search, () => {
    router.get(route('inventario.index'), { search: search.value }, { preserveState: true, replace: true })
})

function openCreate() {
    editing.value = null
    form.value = { codigo: '', nombre: '', descripcion: '', categoria: '', unidad_medida: null, cantidad_actual: null, costo_unitario: null, costo_total: null, ubicacion: '' }
    showForm.value = true
}

function openEdit(item) {
    editing.value = item
    form.value = {
        codigo: item.codigo,
        nombre: item.nombre,
        descripcion: item.descripcion || '',
        categoria: item.categoria || '',
        unidad_medida: item.unidad_medida,
        cantidad_actual: item.cantidad_actual,
        costo_unitario: item.costo_unitario,
        costo_total: item.costo_total,
        ubicacion: item.ubicacion || '',
    }
    showForm.value = true
}

function submit() {
    const url = editing.value ? route('inventario.update', editing.value.id) : route('inventario.store')
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

            <DataTable :value="inventario.data" striped-rows paginator :rows="20" :total-records="inventario.total">
                <Column field="codigo" header="Código" sortable />
                <Column field="nombre" header="Nombre" sortable />
                <Column field="categoria" header="Categoría" />
                <Column field="unidad_medida" header="U/M" />
                <Column field="cantidad_actual" header="Cant. Actual" />
                <Column field="costo_unitario" header="Costo Unitario">
                    <template #body="{ data }">
                        {{ data.costo_unitario?.toLocaleString('es-CU', { minimumFractionDigits: 2 }) }}
                    </template>
                </Column>
                <Column field="costo_total" header="Costo Total">
                    <template #body="{ data }">
                        {{ data.costo_total?.toLocaleString('es-CU', { minimumFractionDigits: 2 }) }}
                    </template>
                </Column>
                <Column field="ubicacion" header="Ubicación" />
                <Column header="Acciones" style="width: 120px">
                    <template #body="{ data }">
                        <div class="flex gap-1">
                            <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
                            <Button icon="pi pi-trash" rounded text severity="danger" @click="router.delete(route('inventario.destroy', data.id))" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="showForm" :header="editing ? 'Editar Producto' : 'Nuevo Producto'" modal style="width: 600px">
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
                    <div class="col-span-2">
                        <label class="block mb-1 font-medium">Descripción</label>
                        <Textarea v-model="form.descripcion" class="w-full" rows="3" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Categoría</label>
                        <InputText v-model="form.categoria" class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Unidad de Medida</label>
                        <Select v-model="form.unidad_medida" :options="unidadesMedida" optionLabel="label" optionValue="value" placeholder="Seleccione..." class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Cantidad Actual</label>
                        <InputNumber v-model="form.cantidad_actual" :minFractionDigits="2" class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Costo Unitario</label>
                        <InputNumber v-model="form.costo_unitario" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Costo Total</label>
                        <InputNumber v-model="form.costo_total" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Ubicación</label>
                        <InputText v-model="form.ubicacion" class="w-full" />
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
