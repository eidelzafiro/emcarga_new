<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { useToast } from 'primevue/usetoast';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import ProgressSpinner from 'primevue/progressspinner';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    title: String,
    salarios: { type: Array, default: () => [] },
    mes: Number,
    ano: Number,
    filters: Object,
    tasas: { type: Array, default: () => [] },
    choferes: { type: Array, default: () => [] },
    choferesDelMes: { type: Array, default: () => [] },
    paginacion: Object,
});

const toast = useToast();
const loading = ref(false);
const expandedRows = ref({});
const search = ref(props.filters?.search || '');
const selectedMes = ref(props.mes);
const selectedAno = ref(props.ano);
const selectedChofer = ref(props.filters?.chofer_filter || '');
const currentPage = ref(props.paginacion?.current_page || 1);

const meses = [
    { label: 'Enero', value: 1 }, { label: 'Febrero', value: 2 },
    { label: 'Marzo', value: 3 }, { label: 'Abril', value: 4 },
    { label: 'Mayo', value: 5 }, { label: 'Junio', value: 6 },
    { label: 'Julio', value: 7 }, { label: 'Agosto', value: 8 },
    { label: 'Septiembre', value: 9 }, { label: 'Octubre', value: 10 },
    { label: 'Noviembre', value: 11 }, { label: 'Diciembre', value: 12 },
];

const anos = computed(() => {
    const actual = new Date().getFullYear();
    return Array.from({ length: 5 }, (_, i) => ({ label: actual - i, value: actual - i }));
});

const mesLabel = computed(() => meses.find(m => m.value === selectedMes.value)?.label || '');

function aplicarFiltros(page = 1) {
    loading.value = true;
    currentPage.value = page;
    router.get(route('salarios-choferes.index'), {
        mes: selectedMes.value,
        ano: selectedAno.value,
        search: search.value,
        chofer_filter: selectedChofer.value,
        page: page,
    }, {
        preserveState: true,
        onFinish: () => loading.value = false,
    });
}

function onPage(event) {
    aplicarFiltros(event.page + 1);
}

function formatCurrency(val) {
    if (!val && val !== 0) return '—';
    return new Intl.NumberFormat('es-CU', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(val);
}

function formatNum(val) {
    if (!val && val !== 0) return '—';
    return new Intl.NumberFormat('es-CU', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(val);
}

function getTagSeverity(salario) {
    if (!salario || salario <= 0) return 'secondary';
    if (salario < 500) return 'warn';
    return 'success';
}

// --- Modal de edición de carta de porte ---
const showEditDialog = ref(false);
const savingDetalle = ref(false);
const editForm = ref({});

const tiempoTotalCalc = computed(() => {
    const f = editForm.value;
    if (!f) return 0;
    return (Number(f.tiempo_otros) || 0)
        + (Number(f.tiempo_movimiento) || 0)
        + (Number(f.tiempo_carga) || 0)
        + (Number(f.tiempo_descarga) || 0);
});

// Tasa seleccionada (modelo completo del combo).
const tasaSeleccionada = computed(() =>
    props.tasas.find(t => t.id === editForm.value?.id_tasa) || null);

// Importe de la tasa: tasa2 si hay doble chofer, si no tasa (paridad legacy).
const tasaImporte = computed(() => {
    const t = tasaSeleccionada.value;
    if (!t) return null;
    const esDoble = editForm.value?.id_chofer2 && editForm.value.id_chofer2 !== editForm.value?.id_chofer;
    return esDoble && Number(t.tasa2) > 0 ? Number(t.tasa2) : Number(t.tasa);
});

// Salario de la carta de porte en vivo: ingreso × tasa (+ almacenaje se
// recalcula en el backend; aquí la estimación principal para feedback).
const salarioTotalCalc = computed(() => {
    const f = editForm.value;
    if (!f || tasaImporte.value === null) return null;
    return (Number(f.ingreso) || 0) * tasaImporte.value;
});

function abrirEditar(d) {
    editForm.value = {
        id_aforo: d.id_aforo,
        id_chofer: d.id_chofer,
        id_chofer2: d.id_chofer2 || null,
        id_tasa: d.id_tasa || null,
        ingreso: d.ingreso || 0,
        km_total: d.km_total || 0,
        tn_real: d.tn_real || 0,
        tiempo_otros: d.tiempo_otros || 0,
        tiempo_movimiento: d.tiempo_movimiento || 0,
        tiempo_carga: d.tiempo_carga || 0,
        tiempo_descarga: d.tiempo_descarga || 0,
    };
    showEditDialog.value = true;
}

async function guardarDetalle() {
    const f = editForm.value;
    if (!f || !f.id_chofer) return;

    savingDetalle.value = true;
    try {
        const response = await fetch(route('salarios-choferes.editar-detalle'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                ...f,
                tiempo_total: tiempoTotalCalc.value,
            }),
        });

        const data = await response.json();
        if (data.success) {
            showEditDialog.value = false;
            toast.add({ severity: 'success', summary: 'Actualizado', detail: 'Salario recalculado.', life: 2000 });
            aplicarFiltros(currentPage.value);
        } else {
            toast.add({ severity: 'error', summary: 'Error', detail: data.message || 'No se pudo guardar.', life: 3000 });
        }
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo guardar.', life: 3000 });
    } finally {
        savingDetalle.value = false;
    }
}
</script>

<template>
    <AppLayout :title="title">
        <Head :title="title" />

        <div class="py-4">
            <!-- Botones de reporte -->
            <div class="flex items-center justify-between mb-4 max-w-full mx-auto sm:px-6 lg:px-8">
                <h2 class="font-semibold text-xl leading-tight">{{ title }}</h2>
                <div class="flex gap-2">
                    <a :href="route('reportes.prenomina-choferes', { mes: selectedMes, ano: selectedAno })"
                        target="_blank"
                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition">
                        <i class="pi pi-file-pdf"></i> Prenómina PDF
                    </a>
                    <a :href="route('reportes.prenomina-choferes-excel', { mes: selectedMes, ano: selectedAno })"
                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition">
                        <i class="pi pi-file-excel"></i> Prenómina Excel
                    </a>
                    <a :href="route('reportes.modelo1', { mes: selectedMes, ano: selectedAno, ...(selectedChofer ? { id_bolsa: selectedChofer } : {}) })"
                        target="_blank"
                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition">
                        <i class="pi pi-file-pdf"></i> Modelo 1 PDF
                    </a>
                    <a :href="route('reportes.modelo1-excel', { mes: selectedMes, ano: selectedAno, ...(selectedChofer ? { id_bolsa: selectedChofer } : {}) })"
                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-700 text-white text-sm rounded-lg hover:bg-emerald-800 transition">
                        <i class="pi pi-file-excel"></i> Modelo 1 Excel
                    </a>
                </div>
            </div>
            <div class="max-w-full mx-auto sm:px-6 lg:px-8">
                <!-- Filtros -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-4">
                    <div class="flex flex-wrap items-end gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mes</label>
                            <Select v-model="selectedMes" :options="meses" optionLabel="label" optionValue="value"
                                class="w-40" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Año</label>
                            <Select v-model="selectedAno" :options="anos" optionLabel="label" optionValue="value"
                                class="w-32" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Chofer</label>
                            <Select v-model="selectedChofer" :options="choferesDelMes"
                                optionLabel="nombre" optionValue="id"
                                placeholder="Todos los choferes" class="w-64" showClear />
                        </div>
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Buscar</label>
                            <InputText v-model="search" placeholder="Nombre o CI..." class="w-full"
                                @keyup.enter="aplicarFiltros" />
                        </div>
                        <Button label="Buscar" icon="pi pi-search" @click="aplicarFiltros" :loading="loading" />
                    </div>
                </div>

                <!-- Cargando -->
                <div v-if="loading" class="flex justify-center py-12">
                    <ProgressSpinner style="width: 50px; height: 50px" />
                </div>

                <!-- Tabla principal -->
                <div v-else class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                    <DataTable :value="salarios" v-model:expandedRows="expandedRows" dataKey="id_bolsa"
                        stripedRows responsiveLayout="scroll" :rows="20" lazy
                        :totalRecords="paginacion?.total || 0"
                        :first="(currentPage - 1) * 20"
                        @page="onPage"
                        :globalFilterFields="['nombre_completo', 'carnet']"
                        paginator
                        emptyMessage="No hay choferes con datos para este período.">
                        <template #paginatorstart>
                            <span class="text-sm text-gray-500">
                                {{ paginacion?.total || 0 }} choferes · Página {{ paginacion?.current_page || 1 }} de {{ paginacion?.total_pages || 1 }}
                            </span>
                        </template>
                        <template #header>
                            <div class="text-lg font-semibold">
                                {{ mesLabel }} {{ selectedAno }}
                            </div>
                        </template>

                        <Column expander style="width: 3rem" />
                        <Column field="nombre_completo" header="Chofer" sortable style="min-width: 200px">
                            <template #body="{ data }">
                                <div>
                                    <div class="font-semibold">{{ data.nombre_completo }}</div>
                                    <div class="text-xs text-gray-500">{{ data.cargo }} · CI: {{ data.carnet }}</div>
                                </div>
                            </template>
                        </Column>
                        <Column header="Horas" class="text-right">
                            <template #body="{ data }">
                                <span :class="{ 'text-red-600': data.regular > 240 }">
                                    {{ formatNum(data.regular) }}
                                </span>
                                <span v-if="data.irregular > 0" class="text-xs text-orange-500 ml-1">
                                    ({{ formatNum(data.irregular) }} irr)
                                </span>
                            </template>
                        </Column>
                        <Column header="Ingresos MN" field="ingresos" class="text-right" sortable>
                            <template #body="{ data }">{{ formatCurrency(data.ingresos) }}</template>
                        </Column>
                        <Column header="Salario CP" field="salario_cp" class="text-right" sortable>
                            <template #body="{ data }">{{ formatCurrency(data.salario_cp) }}</template>
                        </Column>
                        <Column header="CLA" field="imp_cla" class="text-right">
                            <template #body="{ data }">{{ formatCurrency(data.imp_cla) }}</template>
                        </Column>
                        <Column header="Nocturnidad" class="text-right">
                            <template #body="{ data }">
                                {{ formatCurrency(data.imp_nocturnidad_1 + data.imp_nocturnidad_2) }}
                            </template>
                        </Column>
                        <Column header="Feriados" field="imp_feriados" class="text-right">
                            <template #body="{ data }">{{ formatCurrency(data.imp_feriados) }}</template>
                        </Column>
                        <Column header="TN Real" field="toneladas" class="text-right">
                            <template #body="{ data }">{{ formatNum(data.toneladas) }}</template>
                        </Column>
                        <Column header="KM Total" field="km_total" class="text-right">
                            <template #body="{ data }">{{ formatNum(data.km_total) }}</template>
                        </Column>
                        <Column header="Salario Final" field="salario_final" sortable style="min-width: 120px">
                            <template #body="{ data }">
                                <Tag :value="formatCurrency(data.salario_final)" :severity="getTagSeverity(data.salario_final)" />
                            </template>
                        </Column>

                        <!-- Fila expandida: detalle de cartas de porte -->
                        <template #expansion="{ data }">
                            <div class="p-4 bg-gray-50 dark:bg-gray-900">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="font-semibold text-gray-700 dark:text-gray-300">
                                        Cartas de porte — {{ data.nombre_completo }} ({{ data.detalle?.length || 0 }})
                                    </h4>
                                </div>
                                <DataTable :value="data.detalle || []" size="small" stripedRows
                                    emptyMessage="Sin cartas de porte para este chofer.">
                                    <Column header="CP (Emisión)" style="min-width: 150px">
                                        <template #body="{ data: d }">
                                            <div class="font-semibold">{{ d.numero_cp }}</div>
                                            <div class="text-xs text-gray-500">{{ d.fecha_emision || '—' }}</div>
                                        </template>
                                    </Column>
                                    <Column header="HR (Cierre)" style="min-width: 120px">
                                        <template #body="{ data: d }">
                                            <div>{{ d.numero_hr || '—' }}</div>
                                            <div class="text-xs text-gray-500">{{ d.fecha_cierre || '—' }}</div>
                                        </template>
                                    </Column>
                                    <Column header="Vehículo" style="width: 100px">
                                        <template #body="{ data: d }">{{ d.tractivo || '—' }}</template>
                                    </Column>
                                    <Column header="Tipo Carga" style="min-width: 140px">
                                        <template #body="{ data: d }">{{ d.tipo_carga || '—' }}</template>
                                    </Column>
                                    <Column header="KMS" class="text-right" style="width: 70px">
                                        <template #body="{ data: d }">{{ formatNum(d.km_total) }}</template>
                                    </Column>
                                    <Column header="Ingresos" class="text-right" style="width: 90px">
                                        <template #body="{ data: d }">{{ formatCurrency(d.ingreso) }}</template>
                                    </Column>
                                    <Column header="Tasa" style="min-width: 170px">
                                        <template #body="{ data: d }">
                                            <span class="text-sm">{{ d.tasa_nombre || '—' }}</span>
                                            <span v-if="d.tasa" class="text-xs text-gray-400"> ({{ d.tasa }})</span>
                                        </template>
                                    </Column>
                                    <Column header="Salario x Tasa" class="text-right" style="width: 100px">
                                        <template #body="{ data: d }">
                                            <span class="font-semibold">{{ formatCurrency(d.salario) }}</span>
                                        </template>
                                    </Column>
                                    <Column header="T. Otros" class="text-right" style="width: 70px">
                                        <template #body="{ data: d }">{{ formatNum(d.tiempo_otros) }}</template>
                                    </Column>
                                    <Column header="T. Mov" class="text-right" style="width: 70px">
                                        <template #body="{ data: d }">{{ formatNum(d.tiempo_movimiento) }}</template>
                                    </Column>
                                    <Column header="T. Carga" class="text-right" style="width: 70px">
                                        <template #body="{ data: d }">{{ formatNum(d.tiempo_carga) }}</template>
                                    </Column>
                                    <Column header="T. Desc." class="text-right" style="width: 70px">
                                        <template #body="{ data: d }">{{ formatNum(d.tiempo_descarga) }}</template>
                                    </Column>
                                    <Column header="T. Total" class="text-right" style="width: 80px">
                                        <template #body="{ data: d }">
                                            <span class="font-semibold">{{ formatNum(d.tiempo_total) }}</span>
                                        </template>
                                    </Column>
                                    <Column header="Flags" style="width: 70px">
                                        <template #body="{ data: d }">
                                            <div class="flex gap-1">
                                                <Tag v-if="d.es_feriado" value="F" severity="danger" class="text-xs" />
                                                <Tag v-if="d.doble_chofer" value="D" severity="warn" class="text-xs" />
                                            </div>
                                        </template>
                                    </Column>
                                    <Column header="" style="width: 60px">
                                        <template #body="{ data: d }">
                                            <Button icon="pi pi-pencil" size="small" severity="secondary" text
                                                @click="abrirEditar(d)" title="Editar carta de porte" />
                                        </template>
                                    </Column>
                                </DataTable>
                            </div>
                        </template>
                    </DataTable>
                </div>
            </div>
        </div>

        <!-- Modal de edición de carta de porte -->
        <Dialog v-model:visible="showEditDialog" header="Editar carta de porte" modal
            :style="{ width: '90vw', maxWidth: '720px' }" :closable="!savingDetalle">
            <form v-if="editForm" @submit.prevent="guardarDetalle" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-1 font-medium">Chofer 1</label>
                        <Select v-model="editForm.id_chofer" :options="choferes" optionLabel="nombre" optionValue="id"
                            placeholder="Seleccionar chofer" class="w-full" filter />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Chofer 2</label>
                        <Select v-model="editForm.id_chofer2" :options="choferes" optionLabel="nombre" optionValue="id"
                            placeholder="Sin chofer 2" class="w-full" filter showClear />
                    </div>
                </div>

                <!-- Datos de la carta (solo lectura) -->
                <fieldset class="border rounded-lg p-3 bg-gray-50 dark:bg-gray-900">
                    <legend class="text-sm font-semibold px-1">Datos de la carta de porte</legend>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block mb-1 text-xs text-gray-500">Ingreso total</label>
                            <InputText :modelValue="formatCurrency(editForm.ingreso)" readonly class="w-full font-mono bg-transparent opacity-100" />
                        </div>
                        <div>
                            <label class="block mb-1 text-xs text-gray-500">KMS</label>
                            <InputText :modelValue="formatNum(editForm.km_total)" readonly class="w-full font-mono bg-transparent opacity-100" />
                        </div>
                        <div>
                            <label class="block mb-1 text-xs text-gray-500">TN Real</label>
                            <InputText :modelValue="formatNum(editForm.tn_real)" readonly class="w-full font-mono bg-transparent opacity-100" />
                        </div>
                    </div>
                </fieldset>

                <div>
                    <label class="block mb-1 font-medium">Tasa aplicada</label>
                    <Select v-model="editForm.id_tasa" :options="tasas" optionLabel="nombre" optionValue="id"
                        placeholder="Seleccionar tasa" class="w-full" filter showClear />
                    <div v-if="tasaImporte !== null" class="mt-2 grid grid-cols-2 gap-4">
                        <div class="p-2 rounded bg-blue-50 dark:bg-gray-800 border dark:border-gray-700">
                            <div class="text-xs text-gray-500">Importe de la tasa</div>
                            <div class="font-mono font-semibold">{{ formatNum(tasaImporte) }}</div>
                        </div>
                        <div class="p-2 rounded bg-emerald-50 dark:bg-gray-800 border dark:border-gray-700">
                            <div class="text-xs text-gray-500">Salario total (ingreso × tasa)</div>
                            <div class="font-mono font-semibold">{{ salarioTotalCalc !== null ? formatCurrency(salarioTotalCalc) : '—' }}</div>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block mb-1 font-medium">Tiempos (horas)</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Otros</label>
                            <InputNumber v-model="editForm.tiempo_otros" class="w-full" :minFractionDigits="2" :maxFractionDigits="2" :min="0" mode="decimal" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Movimiento</label>
                            <InputNumber v-model="editForm.tiempo_movimiento" class="w-full" :minFractionDigits="2" :maxFractionDigits="2" :min="0" mode="decimal" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Carga</label>
                            <InputNumber v-model="editForm.tiempo_carga" class="w-full" :minFractionDigits="2" :maxFractionDigits="2" :min="0" mode="decimal" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Descarga</label>
                            <InputNumber v-model="editForm.tiempo_descarga" class="w-full" :minFractionDigits="2" :maxFractionDigits="2" :min="0" mode="decimal" />
                        </div>
                    </div>
                    <div class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                        Tiempo total: <span class="font-semibold font-mono">{{ formatNum(tiempoTotalCalc) }}</span>
                    </div>
                </div>

                <div class="flex gap-2 justify-end">
                    <Button label="Cancelar" severity="secondary" type="button" @click="showEditDialog = false" :disabled="savingDetalle" />
                    <Button label="Guardar y recalcular" type="submit" icon="pi pi-save" :loading="savingDetalle" />
                </div>
            </form>
        </Dialog>
    </AppLayout>
</template>
