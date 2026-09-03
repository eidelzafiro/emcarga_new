<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
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

// --- Edición de tasa ---
const editingTasa = ref({});
const savingTasa = ref({});

function getTasasFiltradas(item) {
    if (!item.id_tasa || !props.tasas.length) return props.tasas;
    const tasaActual = props.tasas.find(t => t.id === item.id_tasa);
    if (tasaActual && tasaActual.id_tipo_carga) {
        return props.tasas.filter(t => !t.id_tipo_carga || t.id_tipo_carga === tasaActual.id_tipo_carga);
    }
    return props.tasas;
}

async function guardarTasa(item) {
    const aforoId = item.id_aforo;
    const nuevaTasaId = editingTasa.value[aforoId];
    if (!nuevaTasaId) return;

    savingTasa.value[aforoId] = true;
    try {
        const response = await fetch(route('salarios-choferes.actualizar-tasa'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ id_aforo: aforoId, id_tasa: nuevaTasaId }),
        });

        const data = await response.json();
        if (data.success) {
            item.id_tasa = nuevaTasaId;
            item.tasa = parseFloat(data.tasa);
            item.tasa_nombre = data.tasa_nombre;
            item.salario = parseFloat(data.salario);
            delete editingTasa.value[aforoId];
            toast.add({ severity: 'success', summary: 'Tasa actualizada', detail: `${data.tasa_nombre} → Salario: ${formatCurrency(data.salario)}`, life: 2000 });
        }
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo actualizar la tasa', life: 3000 });
    } finally {
        savingTasa.value[aforoId] = false;
    }
}

// --- Edición de tiempo por CP ---
const editingTiempo = ref({});
const savingTiempo = ref({});

function abrirEditarTiempo(d) {
    editingTiempo.value[d.id_aforo] = {
        tiempo_total: d.tiempo_total || 0,
        km_total: d.km_total || 0,
        tn_real: d.tn_real || 0,
    };
}

async function guardarTiempo(d) {
    const aforoId = d.id_aforo;
    const datos = editingTiempo.value[aforoId];
    if (!datos) return;

    savingTiempo.value[aforoId] = true;
    try {
        const response = await fetch(route('salarios-choferes.guardar-tiempo'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ id_aforo: aforoId, ...datos }),
        });

        const data = await response.json();
        if (data.success) {
            d.tiempo_total = datos.tiempo_total;
            d.km_total = datos.km_total;
            d.tn_real = datos.tn_real;
            delete editingTiempo.value[aforoId];
            toast.add({ severity: 'success', summary: 'Datos actualizados', life: 2000 });
            // Recalcular
            aplicarFiltros(currentPage.value);
        }
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudieron guardar los datos', life: 3000 });
    } finally {
        savingTiempo.value[aforoId] = false;
    }
}
</script>

<template>
    <AppLayout :title="title">
        <Head :title="title" />

        <template #header>
            <div class="flex items-center justify-between">
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
                    <a :href="route('reportes.modelo1', { mes: selectedMes, ano: selectedAno })"
                        target="_blank"
                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition">
                        <i class="pi pi-file-pdf"></i> Modelo 1 PDF
                    </a>
                    <a :href="route('reportes.modelo1-excel', { mes: selectedMes, ano: selectedAno })"
                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-700 text-white text-sm rounded-lg hover:bg-emerald-800 transition">
                        <i class="pi pi-file-excel"></i> Modelo 1 Excel
                    </a>
                </div>
            </div>
        </template>

        <div class="py-4">
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
                                    <div class="flex items-center gap-2 text-xs text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/30 px-3 py-1.5 rounded-lg">
                                        <i class="pi pi-info-circle"></i>
                                        Para editar el chofer, edite la hoja de ruta. Para editar tasa o tiempos, use los botones de abajo.
                                    </div>
                                </div>
                                <DataTable :value="data.detalle || []" size="small" stripedRows
                                    emptyMessage="Sin cartas de porte para este chofer.">
                                    <Column field="numero_cp" header="CP" style="width: 80px" />
                                    <Column field="fecha_parte" header="Fecha" style="width: 90px" />
                                    <Column field="tractivo" header="Equipo" style="width: 90px" />
                                    <Column field="origen" header="Origen" style="min-width: 110px" />
                                    <Column field="destino" header="Destino" style="min-width: 110px" />
                                    <Column header="KM" class="text-right" style="width: 70px">
                                        <template #body="{ data: d }">
                                            <div v-if="editingTiempo[d.id_aforo] !== undefined">
                                                <InputNumber v-model="editingTiempo[d.id_aforo].km_total"
                                                    class="w-16" size="small" :minFractionDigits="1" :maxFractionDigits="1"
                                                    :min="0" mode="decimal" />
                                            </div>
                                            <span v-else class="cursor-pointer hover:text-blue-600" @click="abrirEditarTiempo(d)">
                                                {{ formatNum(d.km_total) }}
                                            </span>
                                        </template>
                                    </Column>
                                    <Column header="Tiempo" class="text-right" style="width: 70px">
                                        <template #body="{ data: d }">
                                            <div v-if="editingTiempo[d.id_aforo] !== undefined">
                                                <InputNumber v-model="editingTiempo[d.id_aforo].tiempo_total"
                                                    class="w-16" size="small" :minFractionDigits="2" :maxFractionDigits="2"
                                                    :min="0" mode="decimal" />
                                            </div>
                                            <span v-else class="cursor-pointer hover:text-blue-600" @click="abrirEditarTiempo(d)">
                                                {{ formatNum(d.tiempo_total) }}
                                            </span>
                                        </template>
                                    </Column>
                                    <Column header="TN" class="text-right" style="width: 60px">
                                        <template #body="{ data: d }">
                                            <div v-if="editingTiempo[d.id_aforo] !== undefined">
                                                <InputNumber v-model="editingTiempo[d.id_aforo].tn_real"
                                                    class="w-16" size="small" :minFractionDigits="1" :maxFractionDigits="1"
                                                    :min="0" mode="decimal" />
                                            </div>
                                            <span v-else class="cursor-pointer hover:text-blue-600" @click="abrirEditarTiempo(d)">
                                                {{ formatNum(d.tn_real) }}
                                            </span>
                                        </template>
                                    </Column>
                                    <Column header="Ingreso" class="text-right" style="width: 90px">
                                        <template #body="{ data: d }">{{ formatCurrency(d.ingreso) }}</template>
                                    </Column>
                                    <Column header="Tasa" style="min-width: 200px">
                                        <template #body="{ data: d }">
                                            <div v-if="editingTasa[d.id_aforo] !== undefined" class="flex items-center gap-1">
                                                <Select v-model="editingTasa[d.id_aforo]"
                                                    :options="getTasasFiltradas(d)" optionLabel="nombre" optionValue="id"
                                                    placeholder="Seleccionar" class="flex-1" size="small"
                                                    :style="{ minWidth: '140px' }" />
                                                <Button icon="pi pi-check" size="small" severity="success" text
                                                    :loading="savingTasa[d.id_aforo]"
                                                    @click="guardarTasa(d)" />
                                                <Button icon="pi pi-times" size="small" severity="secondary" text
                                                    @click="delete editingTasa[d.id_aforo]" />
                                            </div>
                                            <div v-else class="flex items-center gap-2 cursor-pointer group"
                                                @click="editingTasa[d.id_aforo] = d.id_tasa">
                                                <span class="text-sm">{{ d.tasa_nombre || '—' }}</span>
                                                <span class="text-xs text-gray-400">{{ d.tasa ? `(${d.tasa})` : '' }}</span>
                                                <i class="pi pi-pencil text-xs text-gray-300 group-hover:text-blue-500 transition-colors"></i>
                                            </div>
                                        </template>
                                    </Column>
                                    <Column header="Salario" class="text-right" style="width: 90px">
                                        <template #body="{ data: d }">
                                            <span class="font-semibold">{{ formatCurrency(d.salario) }}</span>
                                        </template>
                                    </Column>
                                    <Column header="" style="width: 80px">
                                        <template #body="{ data: d }">
                                            <div v-if="editingTiempo[d.id_aforo] !== undefined" class="flex gap-1">
                                                <Button icon="pi pi-check" size="small" severity="success" text
                                                    :loading="savingTiempo[d.id_aforo]"
                                                    @click="guardarTiempo(d)" title="Guardar" />
                                                <Button icon="pi pi-times" size="small" severity="secondary" text
                                                    @click="delete editingTiempo[d.id_aforo]" title="Cancelar" />
                                            </div>
                                            <Button v-else icon="pi pi-pencil" size="small" severity="secondary" text
                                                @click="abrirEditarTiempo(d)" title="Editar tiempos" />
                                        </template>
                                    </Column>
                                    <Column header="Flags" style="width: 80px">
                                        <template #body="{ data: d }">
                                            <div class="flex gap-1">
                                                <Tag v-if="d.es_feriado" value="F" severity="danger" class="text-xs" />
                                                <Tag v-if="d.doble_chofer" value="D" severity="warn" class="text-xs" />
                                            </div>
                                        </template>
                                    </Column>
                                </DataTable>
                            </div>
                        </template>
                    </DataTable>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
