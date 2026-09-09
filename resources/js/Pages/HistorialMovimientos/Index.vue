<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Toolbar from 'primevue/toolbar'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Checkbox from 'primevue/checkbox'
import Tag from 'primevue/tag'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'

const props = defineProps({ historial: Object, filters: Object, trabajadores: Array, plazas: Array })
const toast = useToast()
const confirm = useConfirm()
const search = ref(props.filters?.search || '')
const filtroTipo = ref(props.filters?.tipo || null)
const soloVigentes = ref(props.filters?.solo_vigentes === '1')
const title = 'Historial de Movimientos'

const tipoOpciones = [
  { label: 'Altas', value: 'ALTAS' },
  { label: 'Traslados', value: 'TRASLADOS' },
  { label: 'Bajas', value: 'BAJAS' },
  { label: 'Movimientos', value: 'MOVIMIENTOS' },
]

watch([search, filtroTipo, soloVigentes], () => {
  router.get(route('historial-movimientos.index'), {
    search: search.value,
    tipo: filtroTipo.value,
    solo_vigentes: soloVigentes.value ? '1' : '',
  }, { preserveState: true, replace: true })
})

// ── Alta ──
const showAlta = ref(false)
const formAlta = ref({ id_bolsa: null, nronomina: null, id_plantilla: null, cubreplaza: false })

function abrirAlta() {
  formAlta.value = { id_bolsa: null, nronomina: null, id_plantilla: null, cubreplaza: false }
  showAlta.value = true
}

function submitAlta() {
  router.post(route('historial-movimientos.alta'), formAlta.value, {
    onSuccess: () => { showAlta.value = false; toast.add({ severity: 'success', summary: 'Alta registrada', life: 3000 }) },
    onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
  })
}

// ── Traslado ──
const showTraslado = ref(false)
const formTraslado = ref({ id_movimiento: null, id_plantilla: null, nronomina: null, cubreplaza: false })

function abrirTraslado(mov) {
  formTraslado.value = { id_movimiento: mov.id, id_plantilla: mov.plaza, nronomina: mov.nronomina, cubreplaza: mov.cubreplaza }
  showTraslado.value = true
}

function submitTraslado() {
  router.post(route('historial-movimientos.traslado'), formTraslado.value, {
    onSuccess: () => { showTraslado.value = false; toast.add({ severity: 'success', summary: 'Traslado registrado', life: 3000 }) },
    onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
  })
}

// ── Baja ──
function darBaja(mov) {
  confirm.require({
    message: `¿Dar de baja a ${mov.trabajador}? Se liberará la plaza y se marcará la fecha de baja (${mov.fecha_baja || 'hoy'}).`,
    header: 'Confirmar baja',
    icon: 'pi pi-exclamation-triangle',
    accept: () => {
      router.post(route('historial-movimientos.baja'), { id_movimiento: mov.id }, {
        onSuccess: () => toast.add({ severity: 'success', summary: 'Baja registrada', life: 3000 }),
        onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
      })
    },
  })
}
</script>

<template>
    <AppLayout :title="title">
        <div class="card">
            <Toolbar class="mb-4">
                <template #start>
                    <Button label="Alta" icon="pi pi-user-plus" severity="success" @click="abrirAlta" />
                </template>
                <template #end>
                    <div class="flex gap-2 items-center flex-wrap">
                        <div class="flex items-center gap-2">
                            <Checkbox v-model="soloVigentes" :binary="true" inputId="solo-vig" />
                            <label for="solo-vig" class="text-sm">Solo vigentes</label>
                        </div>
                        <Select v-model="filtroTipo" :options="tipoOpciones" optionLabel="label" optionValue="value" placeholder="Tipo" showClear class="w-40" />
                        <InputText v-model="search" placeholder="Buscar trabajador..." />
                    </div>
                </template>
            </Toolbar>

            <DataTable :value="historial.data" striped-rows paginator :rows="20" :total-records="historial.total"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                currentPageReportTemplate="Total: {totalRecords} registros">
                <Column field="trabajador" header="Trabajador" sortable>
                    <template #body="{ data }">
                        <div>
                            <div class="font-medium">{{ data.trabajador }}</div>
                            <div class="text-xs text-gray-500">CI: {{ data.ci }} · Versat: {{ data.versat || '—' }} · Nómina: {{ data.nronomina }}</div>
                        </div>
                    </template>
                </Column>
                <Column field="area" header="Área" />
                <Column field="cargo" header="Cargo" />
                <Column field="plaza" header="Plaza">
                    <template #body="{ data }">
                        <span class="text-xs">#{{ data.plaza || '—' }}</span>
                        <Tag v-if="data.cubreplaza" value="Cubre plaza" severity="info" class="ml-1" />
                    </template>
                </Column>
                <Column field="tipo" header="Movimiento">
                    <template #body="{ data }">
                        <Tag :value="data.tipo" :severity="data.tipo === 'BAJAS' ? 'danger' : data.tipo === 'ALTAS' ? 'success' : 'warn'" />
                    </template>
                </Column>
                <Column field="fecha_alta" header="Alta" />
                <Column field="fecha_baja" header="Baja">
                    <template #body="{ data }">
                        <Tag v-if="data.vigente" value="Vigente" severity="success" />
                        <span v-else>{{ data.fecha_baja }}</span>
                    </template>
                </Column>
                <Column header="Acciones" style="width: 130px">
                    <template #body="{ data }">
                        <div class="flex gap-1" v-if="data.vigente">
                            <Button icon="pi pi-arrow-right-arrow-left" rounded text severity="warn" title="Trasladar" @click="abrirTraslado(data)" />
                            <Button icon="pi pi-user-minus" rounded text severity="danger" title="Dar de baja" @click="darBaja(data)" />
                        </div>
                        <span v-else class="text-xs text-gray-400">—</span>
                    </template>
                </Column>
            </DataTable>
        </div>

        <!-- Diálogo Alta -->
        <Dialog v-model:visible="showAlta" header="Alta de trabajador" modal :style="{ width: '520px' }">
            <form @submit.prevent="submitAlta" class="space-y-4">
                <div>
                    <label class="block mb-1 font-medium">Trabajador</label>
                    <Select v-model="formAlta.id_bolsa" :options="trabajadores" optionLabel="nombre" optionValue="id"
                        placeholder="Seleccione trabajador..." filter class="w-full" required />
                </div>
                <div>
                    <label class="block mb-1 font-medium">Número de nómina</label>
                    <InputText v-model="formAlta.nronomina" type="number" class="w-full" required />
                </div>
                <div>
                    <label class="block mb-1 font-medium">Plaza (cargo + área)</label>
                    <Select v-model="formAlta.id_plantilla" :options="plazas" optionLabel="label" optionValue="id"
                        placeholder="Seleccione plaza..." filter class="w-full" required />
                </div>
                <div class="flex items-center gap-2">
                    <Checkbox v-model="formAlta.cubreplaza" :binary="true" inputId="cubre-alta" />
                    <label for="cubre-alta" class="text-sm">Cubre plaza</label>
                </div>
                <div class="flex justify-end gap-2">
                    <Button label="Cancelar" severity="secondary" type="button" @click="showAlta = false" />
                    <Button label="Registrar alta" type="submit" icon="pi pi-check" />
                </div>
            </form>
        </Dialog>

        <!-- Diálogo Traslado -->
        <Dialog v-model:visible="showTraslado" header="Trasladar trabajador" modal :style="{ width: '520px' }">
            <form @submit.prevent="submitTraslado" class="space-y-4">
                <div>
                    <label class="block mb-1 font-medium">Nueva plaza</label>
                    <Select v-model="formTraslado.id_plantilla" :options="plazas" optionLabel="label" optionValue="id"
                        placeholder="Seleccione nueva plaza..." filter class="w-full" required />
                </div>
                <div>
                    <label class="block mb-1 font-medium">Número de nómina</label>
                    <InputText v-model="formTraslado.nronomina" type="number" class="w-full" />
                </div>
                <div class="flex items-center gap-2">
                    <Checkbox v-model="formTraslado.cubreplaza" :binary="true" inputId="cubre-tras" />
                    <label for="cubre-tras" class="text-sm">Cubre plaza</label>
                </div>
                <div class="flex justify-end gap-2">
                    <Button label="Cancelar" severity="secondary" type="button" @click="showTraslado = false" />
                    <Button label="Trasladar" type="submit" icon="pi pi-check" />
                </div>
            </form>
        </Dialog>
    </AppLayout>
</template>
