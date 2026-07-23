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

const props = defineProps({ cargas: Object, filters: Object, tarjetas: Array, tractivos: Array })
const toast = useToast()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)
const form = ref({ numero: '', id_tarjeta: null, id_tractivo: null, fecha_carga: null, cantidad_litros: null, precio_litro: null, total: null, tipo_combustible: null, lugar: '' })
const title = 'Cargas de Combustible'

const tiposCombustible = [
    { label: 'Diesel', value: 'diesel' },
    { label: 'Gasolina', value: 'gasolina' },
    { label: 'Gas', value: 'gas' },
]

watch(search, () => {
    router.get(route('combustible-cargas.index'), { search: search.value }, { preserveState: true, replace: true })
})

function openCreate() {
    editing.value = null
    form.value = { numero: '', id_tarjeta: null, id_tractivo: null, fecha_carga: null, cantidad_litros: null, precio_litro: null, total: null, tipo_combustible: null, lugar: '' }
    showForm.value = true
}

function openEdit(item) {
    editing.value = item
    form.value = {
        numero: item.numero,
        id_tarjeta: item.id_tarjeta,
        id_tractivo: item.id_tractivo,
        fecha_carga: item.fecha_carga ? new Date(item.fecha_carga) : null,
        cantidad_litros: item.cantidad_litros,
        precio_litro: item.precio_litro,
        total: item.total,
        tipo_combustible: item.tipo_combustible,
        lugar: item.lugar || '',
    }
    showForm.value = true
}

function submit() {
    const url = editing.value ? route('combustible-cargas.update', editing.value.id) : route('combustible-cargas.store')
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

            <DataTable :value="cargas.data" striped-rows paginator :rows="20" :total-records="cargas.total">
                <Column field="numero" header="Número" sortable />
                <Column field="tarjeta.numero" header="Tarjeta" />
                <Column field="tractivo.descripcion" header="Tractivo" />
                <Column field="fecha_carga" header="Fecha Carga" sortable />
                <Column field="cantidad_litros" header="Litros" />
                <Column field="precio_litro" header="Precio/Litro">
                    <template #body="{ data }">
                        {{ data.precio_litro?.toLocaleString('es-CU', { minimumFractionDigits: 2 }) }}
                    </template>
                </Column>
                <Column field="total" header="Total">
                    <template #body="{ data }">
                        {{ data.total?.toLocaleString('es-CU', { minimumFractionDigits: 2 }) }}
                    </template>
                </Column>
                <Column field="tipo_combustible" header="Tipo">
                    <template #body="{ data }">
                        <Tag :value="data.tipo_combustible" :severity="data.tipo_combustible === 'diesel' ? 'warn' : data.tipo_combustible === 'gasolina' ? 'info' : 'success'" />
                    </template>
                </Column>
                <Column field="lugar" header="Lugar" />
                <Column header="Acciones" style="width: 120px">
                    <template #body="{ data }">
                        <div class="flex gap-1">
                            <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
                            <Button icon="pi pi-trash" rounded text severity="danger" @click="router.delete(route('combustible-cargas.destroy', data.id))" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="showForm" :header="editing ? 'Editar Carga' : 'Nueva Carga'" modal style="width: 600px">
            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-1 font-medium">Número</label>
                        <InputText v-model="form.numero" class="w-full" required />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Tarjeta</label>
                        <Select v-model="form.id_tarjeta" :options="tarjetas" optionLabel="numero" optionValue="id" placeholder="Seleccione..." class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Tractivo</label>
                        <Select v-model="form.id_tractivo" :options="tractivos" optionLabel="descripcion" optionValue="id" placeholder="Seleccione..." class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Fecha Carga</label>
                        <DatePicker v-model="form.fecha_carga" dateFormat="dd/mm/yy" class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Cantidad (Litros)</label>
                        <InputNumber v-model="form.cantidad_litros" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Precio por Litro</label>
                        <InputNumber v-model="form.precio_litro" :minFractionDigits="2" :maxFractionDigits="4" class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Total</label>
                        <InputNumber v-model="form.total" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Tipo Combustible</label>
                        <Select v-model="form.tipo_combustible" :options="tiposCombustible" optionLabel="label" optionValue="value" placeholder="Seleccione..." class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Lugar</label>
                        <InputText v-model="form.lugar" class="w-full" />
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
