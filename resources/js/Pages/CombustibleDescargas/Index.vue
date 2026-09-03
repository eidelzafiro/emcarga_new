<script setup>
import { ref, watch, computed } from 'vue'
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
import { useConfirm } from 'primevue/useconfirm'

const props = defineProps({ descargas: Object, filters: Object, filtros: Object, fechaOperaciones: String })
const toast = useToast()
const confirm = useConfirm()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)
const form = ref(emptyForm())
const title = 'Descarga Combustible'

function emptyForm() {
    return {
        id_tarjeta: null,
        fdescarga: new Date(props.fechaOperaciones || new Date()),
        folio: '',
        saldo_mon: null,
        id_hoja_ruta: null,
        id_tractivo: null,
        id_empleado: null,
        hora_descarga: '',
        id_servicentro: null,
        f_chip: null,
        kms: null,
    }
}

const tarjetaSeleccionada = computed(() => {
    if (!form.value.id_tarjeta) return null
    return props.filtros.tarjetas.find(t => t.id === form.value.id_tarjeta) || null
})

const saldoActualMn = computed(() => {
    return tarjetaSeleccionada.value?.saldo_actual || 0
})

const precio = computed(() => {
    return tarjetaSeleccionada.value?.tipoCombustible?.preciomn || 0
})

const combustibleNombre = computed(() => {
    return tarjetaSeleccionada.value?.tipoCombustible?.nombre || ''
})

const monedaCodigo = computed(() => {
    return tarjetaSeleccionada.value?.moneda?.codigo || ''
})

const utilizedLts = computed(() => {
    if (!form.value.saldo_mon || !precio.value) return 0
    return Math.round((form.value.saldo_mon / precio.value) * 1000) / 1000
})

const saldoFinalMn = computed(() => {
    if (!form.value.saldo_mon) return saldoActualMn.value
    return Math.round((saldoActualMn.value - form.value.saldo_mon) * 1000) / 1000
})

const saldoActualLts = computed(() => {
    return tarjetaSeleccionada.value?.saldoactuallts || 0
})

const saldoFinalLts = computed(() => {
    return Math.round((saldoActualLts.value - utilizedLts.value) * 1000) / 1000
})

const hrSeleccionada = computed(() => {
    if (!form.value.id_hoja_ruta) return null
    return props.filtros.hojasRuta.find(h => h.id === form.value.id_hoja_ruta) || null
})

watch(search, () => {
    router.get(route('combustible-descargas.index'), { search: search.value }, { preserveState: true, replace: true })
})

function onTarjetaSelect() {
    // Auto-focus after selecting tarjeta
}

function onHrSelect() {
    const hr = hrSeleccionada.value
    if (hr) {
        form.value.id_tractivo = hr.id_tractivo || null
        form.value.id_empleado = hr.id_chofer || null
    }
}

function onSaldoMonBlur() {
    // Calculations happen via computed properties
}

function openCreate() {
    editing.value = null
    form.value = emptyForm()
    showForm.value = true
}

function openEdit(item) {
    editing.value = item
    form.value = {
        id_tarjeta: item.id_tarjeta,
        fdescarga: new Date(item.fdescarga),
        folio: item.folio,
        saldo_mon: Number(item.saldo_mon),
        id_hoja_ruta: item.id_hoja_ruta,
        id_tractivo: item.id_tractivo,
        id_empleado: item.id_empleado,
        hora_descarga: item.hora_descarga || '',
        id_servicentro: item.id_servicentro,
        f_chip: item.f_chip ? new Date(item.f_chip) : null,
        kms: item.kms,
    }
    showForm.value = true
}

function submit() {
    const url = editing.value ? route('combustible-descargas.update', editing.value.id) : route('combustible-descargas.store')
    const method = editing.value ? 'put' : 'post'
    router[method](url, form.value, {
        onSuccess: () => { showForm.value = false; toast.add({ severity: 'success', summary: editing.value ? 'Actualizado' : 'Creado', life: 3000 }) },
        onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
    })
}

function confirmDelete(item) {
    confirm.require({
        message: `¿Eliminar la descarga ${item.folio}?`,
        header: 'Confirmar eliminación',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Sí, eliminar',
        rejectLabel: 'Cancelar',
        accept: () => router.delete(route('combustible-descargas.destroy', item.id), {
            onSuccess: () => toast.add({ severity: 'success', summary: 'Eliminada', life: 3000 }),
        }),
    })
}

const fmt = (n) => n?.toLocaleString('es-CU', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

function fmtFecha(fecha) {
    if (!fecha) return ''
    const d = new Date(fecha)
    return d.toLocaleDateString('es-CU', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function empleadoLabel(e) {
    return e ? `${e.nombre} ${e.apellidos}` : ''
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
                    <div class="flex gap-2 items-center">
                        <Select v-model="filters.id_tarjeta" :options="filtros.tarjetas" optionLabel="numero" optionValue="id" placeholder="Tarjeta" class="w-44" @change="router.get(route('combustible-descargas.index'), { id_tarjeta: filters.id_tarjeta }, { preserveState: true, replace: true })" />
                        <Select v-model="filters.id_servicentro" :options="filtros.servicentros" optionLabel="nombre" optionValue="id" placeholder="Servicentro" class="w-56" @change="router.get(route('combustible-descargas.index'), { id_servicentro: filters.id_servicentro }, { preserveState: true, replace: true })" />
                        <InputText v-model="search" placeholder="Buscar folio/tarjeta/HR..." />
                    </div>
                </template>
            </Toolbar>

            <DataTable :value="descargas.data" striped-rows paginator :rows="20" :total-records="descargas.total" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros">
                <Column field="fdescarga" header="Fecha" sortable>
                    <template #body="{ data }">{{ fmtFecha(data.fdescarga) }}</template>
                </Column>
                <Column field="tarjeta.numero" header="Tarjeta" />
                <Column field="folio" header="Folio" sortable />
                <Column field="hojaRuta.numero" header="HR" />
                <Column field="tractivo.codigo" header="Tractivo" />
                <Column header="Empleado">
                    <template #body="{ data }">{{ empleadoLabel(data.empleado) }}</template>
                </Column>
                <Column field="saldo_mon" header="Importe">
                    <template #body="{ data }">{{ fmt(data.saldo_mon) }}</template>
                </Column>
                <Column field="saldo_lts" header="Litros">
                    <template #body="{ data }">{{ fmt(data.saldo_lts) }}</template>
                </Column>
                <Column field="servicentro.nombre" header="Servicentro" />
                <Column field="hora_descarga" header="Hora" />
                <Column field="kms" header="Kms">
                    <template #body="{ data }">{{ fmt(data.kms) }}</template>
                </Column>
                <Column header="Acciones" style="width: 120px">
                    <template #body="{ data }">
                        <div class="flex gap-1">
                            <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
                            <Button icon="pi pi-trash" rounded text severity="danger" @click="confirmDelete(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="showForm" :header="editing ? 'Editar Descarga' : 'Nueva Descarga'" modal style="width: 750px">
            <form @submit.prevent="submit" class="space-y-4">
                <fieldset class="border rounded p-3">
                    <legend class="text-sm font-bold px-1">DATOS DE LA TARJETA</legend>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block mb-1 font-medium">Tarjeta *</label>
                            <Select v-model="form.id_tarjeta" :options="filtros.tarjetas" optionLabel="numero" optionValue="id" placeholder="Seleccione..." class="w-full" required @change="onTarjetaSelect" />
                        </div>
                        <div>
                            <label class="block mb-1 font-medium">Fecha *</label>
                            <DatePicker v-model="form.fdescarga" dateFormat="dd/mm/yy" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-1 font-medium">Fecha Chip *</label>
                            <DatePicker v-model="form.f_chip" dateFormat="dd/mm/yy" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-1 font-medium">Servicentro *</label>
                            <Select v-model="form.id_servicentro" :options="filtros.servicentros" optionLabel="nombre" optionValue="id" placeholder="Seleccione..." class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-1 font-medium">Hora *</label>
                            <InputText v-model="form.hora_descarga" placeholder="HH:MM" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-1 font-medium">Folio *</label>
                            <InputText v-model="form.folio" class="w-full" required />
                        </div>
                        <div>
                            <label class="block mb-1 font-medium">Hoja de Ruta *</label>
                            <Select v-model="form.id_hoja_ruta" :options="filtros.hojasRuta" optionLabel="numero" optionValue="id" placeholder="Seleccione..." class="w-full" required @change="onHrSelect" />
                        </div>
                        <div>
                            <label class="block mb-1 font-medium">Tractivo</label>
                            <Select v-model="form.id_tractivo" :options="filtros.tractivos" optionLabel="codigo" optionValue="id" placeholder="Seleccione..." class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-1 font-medium">Empleado</label>
                            <Select v-model="form.id_empleado" :options="filtros.empleados" :optionLabel="(e) => `${e.nombre} ${e.apellidos}`" optionValue="id" placeholder="Seleccione..." class="w-full" filter />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4 mt-3">
                        <div class="flex gap-2 items-center">
                            <span class="text-sm font-medium">Combustible:</span>
                            <Tag :value="combustibleNombre" severity="danger" v-if="combustibleNombre" />
                        </div>
                        <div class="flex gap-2 items-center">
                            <span class="text-sm font-medium">Moneda:</span>
                            <Tag :value="monedaCodigo" severity="danger" v-if="monedaCodigo" />
                        </div>
                    </div>
                </fieldset>

                <fieldset class="border rounded p-3">
                    <legend class="text-sm font-bold px-1">DATOS DE LA DESCARGA</legend>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block mb-1 font-medium">Saldo Actual $</label>
                            <InputNumber :modelValue="saldoActualMn" :disabled="true" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-1 font-medium">Utilizado $ *</label>
                            <InputNumber v-model="form.saldo_mon" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" required @blur="onSaldoMonBlur" />
                        </div>
                        <div>
                            <label class="block mb-1 font-medium">Saldo Final $</label>
                            <InputNumber :modelValue="saldoFinalMn" :disabled="true" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-1 font-medium">Saldo Actual Lts</label>
                            <InputNumber :modelValue="saldoActualLts" :disabled="true" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-1 font-medium">Utilizado Lts</label>
                            <InputNumber :modelValue="utilizedLts" :disabled="true" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-1 font-medium">Saldo Final Lts</label>
                            <InputNumber :modelValue="saldoFinalLts" :disabled="true" class="w-full" />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-4 mt-3">
                        <div>
                            <label class="block mb-1 font-medium">Kms *</label>
                            <InputNumber v-model="form.kms" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" required />
                        </div>
                    </div>
                </fieldset>

                <div class="flex gap-2 justify-end">
                    <Button label="Cancelar" severity="secondary" @click="showForm = false" />
                    <Button label="Guardar y continuar" icon="pi pi-save" severity="success" @click.prevent="submit" />
                    <Button label="Guardar" icon="pi pi-save" type="submit" />
                </div>
            </form>
        </Dialog>
    </AppLayout>
</template>
