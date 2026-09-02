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
import Textarea from 'primevue/textarea'
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ otros_gastos: Object, filters: Object, tractivos: Array, tipos_concepto: Array })
const toast = useToast()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)
const form = ref({ id_tractivo: null, id_tipo_concepto: null, fecha: null, monto_mn: null, monto_mlc: null, descripcion: '' })
const title = 'Otros Gastos'

const tiposConceptoLocal = ref([...props.tipos_concepto])
const showNewTipoConcepto = ref(false)
const nuevoTipoConcepto = ref('')
const savingTipoConcepto = ref(false)

watch(search, () => {
    router.get(route('otros-gastos.index'), { search: search.value }, { preserveState: true, replace: true })
})

function fmtFecha(fecha) {
    if (!fecha) return ''
    const d = new Date(fecha)
    return d.toLocaleDateString('es-CU', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function openCreate() {
    editing.value = null
    form.value = { id_tractivo: null, id_tipo_concepto: null, fecha: null, monto_mn: null, monto_mlc: null, descripcion: '' }
    showForm.value = true
}

function openEdit(item) {
    editing.value = item
    form.value = {
        id_tractivo: item.id_tractivo,
        id_tipo_concepto: item.id_tipo_concepto,
        fecha: item.fecha ? new Date(item.fecha) : null,
        monto_mn: item.monto_mn,
        monto_mlc: item.monto_mlc,
        descripcion: item.descripcion || '',
    }
    showForm.value = true
}

function submit(continueAfter = false) {
    if (!form.value.id_tractivo) { toast.add({ severity: 'warn', summary: 'Seleccione un tractivo', life: 3000 }); return }
    if (!form.value.id_tipo_concepto) { toast.add({ severity: 'warn', summary: 'Seleccione un tipo de concepto', life: 3000 }); return }
    if (!form.value.fecha) { toast.add({ severity: 'warn', summary: 'Seleccione una fecha', life: 3000 }); return }

    const payload = {
        id_tractivo: form.value.id_tractivo,
        id_tipo_concepto: form.value.id_tipo_concepto,
        fecha: form.value.fecha instanceof Date ? form.value.fecha.toISOString().split('T')[0] : form.value.fecha,
        monto_mn: form.value.monto_mn ?? 0,
        monto_mlc: form.value.monto_mlc ?? 0,
        descripcion: form.value.descripcion || '',
    }

    const url = editing.value ? route('otros-gastos.update', editing.value.id) : route('otros-gastos.store')
    const method = editing.value ? 'put' : 'post'
    router[method](url, payload, {
        onSuccess: () => {
            if (continueAfter) {
                form.value = { id_tractivo: form.value.id_tractivo, id_tipo_concepto: null, fecha: null, monto_mn: null, monto_mlc: null, descripcion: '' }
                editing.value = null
                toast.add({ severity: 'success', summary: 'Guardado', life: 2000 })
            } else {
                showForm.value = false
                toast.add({ severity: 'success', summary: editing.value ? 'Actualizado' : 'Creado', life: 3000 })
            }
        },
        onError: (e) => {
            const msgs = []
            for (const [field, errors] of Object.entries(e)) { msgs.push(errors.join(', ')) }
            toast.add({ severity: 'error', summary: 'Error', detail: msgs.length ? msgs.join(' | ') : 'Error de validación', life: 5000 })
        },
    })
}

function abrirCrearTipoConcepto() {
    nuevoTipoConcepto.value = ''
    showNewTipoConcepto.value = true
}

async function guardarTipoConcepto() {
    if (!nuevoTipoConcepto.value.trim()) return
    savingTipoConcepto.value = true
    try {
        const res = await fetch(route('otros-gastos.store-tipo-concepto'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] || ''),
            },
            body: JSON.stringify({ nombre: nuevoTipoConcepto.value }),
        })
        const data = await res.json()
        if (res.ok && data.id) {
            tiposConceptoLocal.value.push({ id: data.id, nombre: data.nombre })
            form.value.id_tipo_concepto = data.id
            showNewTipoConcepto.value = false
            toast.add({ severity: 'success', summary: 'Tipo de concepto creado', life: 3000 })
        } else {
            toast.add({ severity: 'error', summary: 'Error', detail: data.message || 'Error al crear', life: 5000 })
        }
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error de red', life: 5000 })
    } finally {
        savingTipoConcepto.value = false
    }
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

            <DataTable :value="otros_gastos.data" striped-rows paginator :rows="20" :total-records="otros_gastos.total" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros">
                <Column field="numero" header="Folio" sortable style="width: 140px" />
                <Column field="tipo_concepto.nombre" header="Concepto" sortable />
                <Column field="tractivo.codigo" header="Tractivo" />
                <Column field="fecha" header="Fecha" sortable>
                    <template #body="{ data }">{{ fmtFecha(data.fecha) }}</template>
                </Column>
                <Column field="monto_mn" header="Monto MN">
                    <template #body="{ data }">
                        {{ data.monto_mn?.toLocaleString('es-CU', { minimumFractionDigits: 2 }) }}
                    </template>
                </Column>
                <Column field="monto_mlc" header="Monto MLC">
                    <template #body="{ data }">
                        {{ data.monto_mlc?.toLocaleString('es-CU', { minimumFractionDigits: 2 }) }}
                    </template>
                </Column>
                <Column field="descripcion" header="Descripcion" show-overflow-tooltip />
                <Column header="Acciones" style="width: 120px">
                    <template #body="{ data }">
                        <div class="flex gap-1">
                            <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
                            <Button icon="pi pi-trash" rounded text severity="danger" @click="router.delete(route('otros-gastos.destroy', data.id))" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="showForm" :header="editing ? 'Editar Otro Gasto' : 'Nuevo Otro Gasto'" modal style="width: 600px">
            <form @submit.prevent="submit(false)" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-1 font-medium">Tractivo *</label>
                        <Select v-model="form.id_tractivo" :options="tractivos" optionLabel="codigo" optionValue="id" placeholder="Seleccione..." class="w-full" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <label class="font-medium">Tipo Concepto *</label>
                            <Button icon="pi pi-plus" rounded text severity="success" size="small" @click="abrirCrearTipoConcepto" title="Crear nuevo" />
                        </div>
                        <Select v-model="form.id_tipo_concepto" :options="tiposConceptoLocal" optionLabel="nombre" optionValue="id" placeholder="Seleccione..." class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Fecha *</label>
                        <DatePicker v-model="form.fecha" dateFormat="dd/mm/yy" class="w-full" />
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block mb-1 font-medium">Monto MN *</label>
                            <InputNumber v-model="form.monto_mn" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-1 font-medium">Monto MLC *</label>
                            <InputNumber v-model="form.monto_mlc" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
                        </div>
                    </div>
                    <div class="col-span-2">
                        <label class="block mb-1 font-medium">Descripcion</label>
                        <Textarea v-model="form.descripcion" class="w-full" rows="3" />
                    </div>
                </div>
                <div class="flex gap-2 justify-end">
                    <Button label="Cancelar" severity="secondary" @click="showForm = false" />
                    <Button label="Guardar y continuar" severity="warn" icon="pi pi-arrow-right" @click.prevent="submit(true)" />
                    <Button label="Guardar" type="submit" icon="pi pi-save" />
                </div>
            </form>
        </Dialog>

        <Dialog v-model:visible="showNewTipoConcepto" header="Nuevo Tipo de Concepto" modal style="width: 400px">
            <form @submit.prevent="guardarTipoConcepto" class="space-y-4">
                <div>
                    <label class="block mb-1 font-medium">Nombre *</label>
                    <InputText v-model="nuevoTipoConcepto" class="w-full" autofocus />
                </div>
                <div class="flex gap-2 justify-end">
                    <Button label="Cancelar" severity="secondary" @click="showNewTipoConcepto = false" />
                    <Button label="Crear" type="submit" icon="pi pi-save" :loading="savingTipoConcepto" />
                </div>
            </form>
        </Dialog>
    </AppLayout>
</template>
