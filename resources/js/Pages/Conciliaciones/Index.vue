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
import DatePicker from 'primevue/datepicker'
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ conciliaciones: Object, filters: Object, facturas: Array })
const toast = useToast()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)
const form = ref({ numero: '', id_factura: null, fecha_conciliacion: null, monto: null, tipo: null, observaciones: '', estado: null })
const title = 'Conciliaciones'

const tipos = [
    { label: 'Bancaria', value: 'bancaria' },
    { label: 'Interna', value: 'interna' },
    { label: 'Cliente', value: 'cliente' },
]

const estados = [
    { label: 'Pendiente', value: 'pendiente' },
    { label: 'Aprobada', value: 'aprobada' },
    { label: 'Rechazada', value: 'rechazada' },
]

watch(search, () => {
    router.get(route('conciliaciones.index'), { search: search.value }, { preserveState: true, replace: true })
})

function openCreate() {
    editing.value = null
    form.value = { numero: '', id_factura: null, fecha_conciliacion: null, monto: null, tipo: null, observaciones: '', estado: null }
    showForm.value = true
}

function openEdit(item) {
    editing.value = item
    form.value = {
        numero: item.numero,
        id_factura: item.id_factura,
        fecha_conciliacion: item.fecha_conciliacion ? new Date(item.fecha_conciliacion) : null,
        monto: item.monto,
        tipo: item.tipo,
        observaciones: item.observaciones || '',
        estado: item.estado,
    }
    showForm.value = true
}

function submit() {
    const url = editing.value ? route('conciliaciones.update', editing.value.id) : route('conciliaciones.store')
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

            <DataTable :value="conciliaciones.data" striped-rows paginator :rows="20" :total-records="conciliaciones.total">
                <Column field="numero" header="Número" sortable />
                <Column field="factura.numero" header="Factura" />
                <Column field="fecha_conciliacion" header="Fecha Conciliación" sortable />
                <Column field="monto" header="Monto">
                    <template #body="{ data }">
                        {{ data.monto?.toLocaleString('es-CU', { minimumFractionDigits: 2 }) }}
                    </template>
                </Column>
                <Column field="tipo" header="Tipo">
                    <template #body="{ data }">
                        <Tag :value="data.tipo" :severity="data.tipo === 'bancaria' ? 'info' : data.tipo === 'interna' ? 'warn' : 'success'" />
                    </template>
                </Column>
                <Column field="observaciones" header="Observaciones" />
                <Column field="estado" header="Estado">
                    <template #body="{ data }">
                        <Tag :value="data.estado" :severity="data.estado === 'aprobada' ? 'success' : data.estado === 'rechazada' ? 'danger' : 'warn'" />
                    </template>
                </Column>
                <Column header="Acciones" style="width: 120px">
                    <template #body="{ data }">
                        <div class="flex gap-1">
                            <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
                            <Button icon="pi pi-trash" rounded text severity="danger" @click="router.delete(route('conciliaciones.destroy', data.id))" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="showForm" :header="editing ? 'Editar Conciliación' : 'Nueva Conciliación'" modal style="width: 600px">
            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-1 font-medium">Número</label>
                        <InputText v-model="form.numero" class="w-full" required />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Factura</label>
                        <Select v-model="form.id_factura" :options="facturas" optionLabel="numero" optionValue="id" placeholder="Seleccione..." class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Fecha Conciliación</label>
                        <DatePicker v-model="form.fecha_conciliacion" dateFormat="dd/mm/yy" class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Monto</label>
                        <InputNumber v-model="form.monto" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Tipo</label>
                        <Select v-model="form.tipo" :options="tipos" optionLabel="label" optionValue="value" placeholder="Seleccione..." class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Estado</label>
                        <Select v-model="form.estado" :options="estados" optionLabel="label" optionValue="value" placeholder="Seleccione..." class="w-full" />
                    </div>
                    <div class="col-span-2">
                        <label class="block mb-1 font-medium">Observaciones</label>
                        <Textarea v-model="form.observaciones" class="w-full" rows="3" />
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
