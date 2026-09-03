<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
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
    resumen: Object,
});

const toast = useToast();
const loading = ref(false);
const expandedRows = ref({});
const search = ref(props.filters?.search || '');
const selectedMes = ref(props.mes);
const selectedAno = ref(props.ano);

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

function aplicarFiltros() {
    loading.value = true;
    router.get(route('salarios-choferes.index'), {
        mes: selectedMes.value,
        ano: selectedAno.value,
        search: search.value,
    }, {
        preserveState: true,
        onFinish: () => loading.value = false,
    });
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
</script>

<template>
    <AppLayout :title="title">
        <Head :title="title" />

        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl leading-tight">{{ title }}</h2>
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
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Buscar chofer</label>
                            <InputText v-model="search" placeholder="Nombre del chofer..." class="w-full"
                                @keyup.enter="aplicarFiltros" />
                        </div>
                        <Button label="Buscar" icon="pi pi-search" @click="aplicarFiltros" :loading="loading" />
                    </div>
                </div>

                <!-- Resumen -->
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-4" v-if="resumen">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 text-center">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Choferes</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ resumen.total_choferes }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 text-center">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Salario Total</div>
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ formatCurrency(resumen.total_salario) }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 text-center">
                        <div class="text-sm text-gray-500 dark:text-gray-400">CLA Total</div>
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ formatCurrency(resumen.total_cla) }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 text-center">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Nocturnidad</div>
                        <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ formatCurrency(resumen.total_nocturnidad) }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 text-center">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Feriados</div>
                        <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ formatCurrency(resumen.total_feriados) }}</div>
                    </div>
                </div>

                <!-- Cargando -->
                <div v-if="loading" class="flex justify-center py-12">
                    <ProgressSpinner style="width: 50px; height: 50px" />
                </div>

                <!-- Tabla principal -->
                <div v-else class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                    <DataTable :value="salarios" v-model:expandedRows="expandedRows" dataKey="id_bolsa"
                        stripedRows responsiveLayout="scroll" :rows="50"
                        :globalFilterFields="['nombre_completo', 'carnet']"
                        emptyMessage="No hay choferes con datos para este período.">
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
                                    <div class="text-xs text-gray-500">{{ data.cargo }}</div>
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
                                <h4 class="font-semibold mb-3 text-gray-700 dark:text-gray-300">
                                    Cartas de porte — {{ data.nombre_completo }} ({{ data.detalle?.length || 0 }})
                                </h4>
                                <DataTable :value="data.detalle || []" size="small" stripedRows
                                    emptyMessage="Sin cartas de porte para este chofer.">
                                    <Column field="numero_cp" header="CP" />
                                    <Column field="fecha_parte" header="Fecha" />
                                    <Column field="tractivo" header="Equipo" />
                                    <Column field="origen" header="Origen" style="min-width: 120px" />
                                    <Column field="destino" header="Destino" style="min-width: 120px" />
                                    <Column field="km_total" header="KM" class="text-right">
                                        <template #body="{ data: d }">{{ formatNum(d.km_total) }}</template>
                                    </Column>
                                    <Column field="tiempo_total" header="Tiempo" class="text-right">
                                        <template #body="{ data: d }">{{ formatNum(d.tiempo_total) }}</template>
                                    </Column>
                                    <Column field="tn_real" header="TN" class="text-right">
                                        <template #body="{ data: d }">{{ formatNum(d.tn_real) }}</template>
                                    </Column>
                                    <Column field="ingreso" header="Ingreso" class="text-right">
                                        <template #body="{ data: d }">{{ formatCurrency(d.ingreso) }}</template>
                                    </Column>
                                    <Column field="tasa" header="Tasa" class="text-right">
                                        <template #body="{ data: d }">{{ data.tasa }}</template>
                                    </Column>
                                    <Column field="salario" header="Salario" class="text-right">
                                        <template #body="{ data: d }">
                                            <span class="font-semibold">{{ formatCurrency(d.salario) }}</span>
                                        </template>
                                    </Column>
                                    <Column header="Flags">
                                        <template #body="{ data: d }">
                                            <div class="flex gap-1">
                                                <Tag v-if="d.es_feriado" value="Feriado" severity="danger" class="text-xs" />
                                                <Tag v-if="d.doble_chofer" value="Doble" severity="warn" class="text-xs" />
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
