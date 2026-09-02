<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'

const props = defineProps({ resumen: Object, totales: Object, fechaOperaciones: String })

function formatNumber(val) {
    return Number(val || 0).toLocaleString('es-CU', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function formatNumber4(val) {
    return Number(val || 0).toLocaleString('es-CU', { minimumFractionDigits: 4, maximumFractionDigits: 4 })
}

const title = 'Cuadre Contabilidad'
</script>

<template>
    <AppLayout :title="title">
        <div class="card">
            <div class="mb-4">
                <h3 class="text-lg font-bold">Resumen Mensual</h3>
                <p class="text-sm text-gray-500">Mes de operaciones: {{ fechaOperaciones }}</p>
            </div>

            <DataTable :value="resumen" striped-rows showGridlines>
                <Column header="Tractivo">
                    <template #body="{ data }">{{ data.tractivo?.codigo }}</template>
                </Column>
                <Column field="combustible_mn" header="Combustible" style="width: 110px">
                    <template #body="{ data }">{{ formatNumber(data.combustible_mn) }}</template>
                </Column>
                <Column field="lubricante_mn" header="Lubricante" style="width: 110px">
                    <template #body="{ data }">{{ formatNumber(data.lubricante_mn) }}</template>
                </Column>
                <Column field="piezas_mn" header="Piezas" style="width: 110px">
                    <template #body="{ data }">{{ formatNumber(data.piezas_mn) }}</template>
                </Column>
                <Column field="salario_total" header="Salario Total" style="width: 110px">
                    <template #body="{ data }">{{ formatNumber(data.salario_total) }}</template>
                </Column>
                <Column field="dietas" header="Dietas" style="width: 110px">
                    <template #body="{ data }">{{ formatNumber(data.dietas) }}</template>
                </Column>
                <Column field="amortizacion_mn" header="Amort." style="width: 110px">
                    <template #body="{ data }">{{ formatNumber(data.amortizacion_mn) }}</template>
                </Column>
                <Column field="chapa" header="Chapa" style="width: 100px">
                    <template #body="{ data }">{{ formatNumber(data.chapa) }}</template>
                </Column>
                <Column field="otros_gastos_mn" header="Otros Gastos" style="width: 110px">
                    <template #body="{ data }">{{ formatNumber(data.otros_gastos_mn) }}</template>
                </Column>
                <Column field="gastos_mn" header="Gastos" style="width: 110px">
                    <template #body="{ data }">{{ formatNumber(data.gastos_mn) }}</template>
                </Column>
                <Column field="ingresos_mn" header="Ingresos" style="width: 110px">
                    <template #body="{ data }">{{ formatNumber(data.ingresos_mn) }}</template>
                </Column>
                <Column field="kms_total" header="KMS" style="width: 90px">
                    <template #body="{ data }">{{ formatNumber(data.kms_total) }}</template>
                </Column>
                <Column field="toneladas" header="Tonel." style="width: 90px">
                    <template #body="{ data }">{{ formatNumber(data.toneladas) }}</template>
                </Column>
                <Column field="utilidad_mn" header="Utilidad" style="width: 110px">
                    <template #body="{ data }">
                        <Tag :severity="data.utilidad_mn >= 0 ? 'success' : 'danger'" :value="formatNumber(data.utilidad_mn)" />
                    </template>
                </Column>
                <Column field="costo_mn" header="Costo/TnKm" style="width: 110px">
                    <template #body="{ data }">{{ formatNumber4(data.costo_tn_kms) }}</template>
                </Column>
            </DataTable>

            <!-- Fila de totales -->
            <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <h4 class="font-bold mb-3">Totales</h4>
                <div class="grid grid-cols-4 md:grid-cols-6 gap-3 text-sm">
                    <div><span class="font-medium">Combustible:</span> {{ formatNumber(totales.combustible_mn) }}</div>
                    <div><span class="font-medium">Lubricante:</span> {{ formatNumber(totales.lubricante_mn) }}</div>
                    <div><span class="font-medium">Piezas:</span> {{ formatNumber(totales.piezas_mn) }}</div>
                    <div><span class="font-medium">Salario Total:</span> {{ formatNumber(totales.salario_total) }}</div>
                    <div><span class="font-medium">Dietas:</span> {{ formatNumber(totales.dietas) }}</div>
                    <div><span class="font-medium">Amortización:</span> {{ formatNumber(totales.amortizacion_mn) }}</div>
                    <div><span class="font-medium">Chapa:</span> {{ formatNumber(totales.chapa) }}</div>
                    <div><span class="font-medium">Otros Gastos:</span> {{ formatNumber(totales.otros_gastos_mn) }}</div>
                    <div><span class="font-medium">Gastos:</span> {{ formatNumber(totales.gastos_mn) }}</div>
                    <div><span class="font-medium">Ingresos:</span> {{ formatNumber(totales.ingresos_mn) }}</div>
                    <div><span class="font-medium">KMS Total:</span> {{ formatNumber(totales.kms_total) }}</div>
                    <div><span class="font-medium">Toneladas:</span> {{ formatNumber(totales.toneladas) }}</div>
                    <div class="col-span-2"><span class="font-medium">Utilidad:</span>
                        <Tag :severity="totales.utilidad_mn >= 0 ? 'success' : 'danger'" :value="formatNumber(totales.utilidad_mn)" />
                    </div>
                    <div><span class="font-medium">Costo MN:</span> {{ formatNumber(totales.costo_mn) }}</div>
                    <div><span class="font-medium">Costo MLC:</span> {{ formatNumber(totales.costo_mlc) }}</div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
