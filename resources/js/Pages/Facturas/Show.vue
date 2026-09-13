<script setup>
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import Card from 'primevue/card'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import { formatDate } from '@/Utils/date'

const props = defineProps({ factura: Object })

const severityMap = { emitida: 'info', firmada: 'warn', cobrada: 'success', cancelada: 'danger', refacturada: 'warn' }

// Impresión del reporte legacy "factura" (id 13) directamente desde la vista.
function imprimir() {
  window.open(route('reportes.generar', 13) + '?filtros[factura]=' + props.factura.id, '_blank')
}
</script>

<template>
    <AppLayout :title="title">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold">Factura No. {{ factura.numero }}</h2>
                    <p class="text-gray-500">{{ formatDate(factura.fecha_emision) }}</p>
                </div>
                <Tag :severity="severityMap[factura.estado] || 'info'" class="text-lg p-2">{{ factura.estado }}</Tag>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <Card>
                    <template #title>Cliente</template>
                    <template #content>
                        <p class="font-medium">{{ factura.cliente?.nombre }}</p>
                    </template>
                </Card>
                <Card>
                    <template #title>Totales</template>
                    <template #content>
                        <p>Flete MN: ${{ Number(factura.flete_mt).toLocaleString() }}</p>
                        <p>Flete MLC: ${{ Number(factura.flete_mlc).toLocaleString() }}</p>
                        <p>Demora: ${{ Number(factura.flete_demora).toLocaleString() }}</p>
                        <p class="font-bold mt-2">Total: ${{ Number(factura.ingreso_mt).toLocaleString() }}</p>
                    </template>
                </Card>
                <Card>
                    <template #title>Fechas</template>
                    <template #content>
                        <p>Firma: {{ factura.fecha_firma ? formatDate(factura.fecha_firma) : 'Pendiente' }}</p>
                        <p>Cobro MN: {{ factura.fecha_cobro_mn ? formatDate(factura.fecha_cobro_mn) : 'Pendiente' }}</p>
                        <p>Cobro MLC: {{ factura.fecha_cobro_mlc ? formatDate(factura.fecha_cobro_mlc) : 'Pendiente' }}</p>
                        <p>Conciliación: {{ factura.fecha_conciliacion ? formatDate(factura.fecha_conciliacion) : 'Pendiente' }}</p>
                    </template>
                </Card>
            </div>

            <Card>
                <template #title>Cartas Porte</template>
                <template #content>
                    <DataTable :value="factura.aforos || []" striped-rows>
                        <Column field="carta_porte.numero" header="CP" />
                        <Column field="fecha_parte" header="Fecha">
                            <template #body="{ data }">{{ formatDate(data.fecha_parte) }}</template>
                        </Column>
                        <Column field="flete_mt" header="Flete MN">
                            <template #body="{ data }">${{ Number(data.flete_mt).toLocaleString() }}</template>
                        </Column>
                        <Column field="flete_demora" header="Demora">
                            <template #body="{ data }">${{ Number(data.flete_demora).toLocaleString() }}</template>
                        </Column>
                        <Column field="ingreso_mt" header="Total">
                            <template #body="{ data }">${{ Number(data.ingreso_mt).toLocaleString() }}</template>
                        </Column>
                    </DataTable>
                    <div class="text-xs text-gray-400 pt-1">Total: {{ (factura.aforos || []).length }} registros</div>
                </template>
            </Card>

            <Card>
                <template #title>Pagos Vinculados</template>
                <template #content>
                    <DataTable :value="factura.pagos || []" striped-rows emptyMessage="No hay pagos registrados">
                        <Column field="fecha_pago" header="Fecha">
                            <template #body="{ data }">{{ formatDate(data.fecha_pago) }}</template>
                        </Column>
                        <Column field="numero_documento" header="Núm. Documento" />
                        <Column header="Moneda">
                            <template #body="{ data }">{{ data.moneda?.codigo }}</template>
                        </Column>
                        <Column field="monto" header="Monto">
                            <template #body="{ data }">${{ Number(data.monto).toLocaleString() }}</template>
                        </Column>
                        <Column field="concepto" header="Concepto" />
                        <Column field="estado" header="Estado">
                            <template #body="{ data }">
                                <Tag :severity="data.estado === 'aprobado' ? 'success' : data.estado === 'rechazado' ? 'danger' : 'warn'" :value="data.estado" />
                            </template>
                        </Column>
                    </DataTable>
                    <div class="text-xs text-gray-400 pt-1">Total pagos: {{ (factura.pagos || []).length }} — Monto total: ${{ Number((factura.pagos || []).reduce((s, p) => s + Number(p.monto), 0)).toLocaleString() }}</div>
                </template>
            </Card>

            <div class="flex gap-2">
                <Button label="Imprimir" icon="pi pi-print" severity="success" @click="imprimir" />
                <Button label="PDF" icon="pi pi-file-pdf" severity="danger" @click="window.open(route('facturas.imprimir', factura.id), '_blank')" />
                <Button label="Volver" icon="pi pi-arrow-left" severity="secondary" @click="router.get(route('facturas.index'))" />
            </div>
        </div>
    </AppLayout>
</template>
