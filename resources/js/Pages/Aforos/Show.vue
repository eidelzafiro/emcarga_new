<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import Card from 'primevue/card'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import { formatDate } from '@/Utils/date'

const props = defineProps({ aforo: Object })

const estado = computed(() => {
    if (props.aforo.id_factura) return { label: 'Facturado', severity: 'success' }
    if (props.aforo.id_prefactura) return { label: 'Prefacturado', severity: 'warn' }
    return { label: 'Pendiente', severity: 'info' }
})

const cp = computed(() => props.aforo.carta_porte || {})
</script>

<template>
    <AppLayout :title="title">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold">Aforo CP No. {{ cp.numero }}</h2>
                    <p class="text-surface-500">Fecha de parte: {{ formatDate(aforo.fecha_parte) }}</p>
                </div>
                <Tag :severity="estado.severity" class="text-lg p-2">{{ estado.label }}</Tag>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <Card>
                    <template #title>Cliente</template>
                    <template #content>
                        <p class="font-medium">{{ cp.cliente?.nombre }}</p>
                        <p class="text-sm text-surface-500">{{ cp.cliente?.codigo }}</p>
                    </template>
                </Card>
                <Card>
                    <template #title>Tractivo</template>
                    <template #content>
                        <p class="font-medium">{{ cp.tractivo?.codigo }}</p>
                        <p class="text-sm text-surface-500">Placa: {{ cp.tractivo?.placa }}</p>
                    </template>
                </Card>
                <Card>
                    <template #title>Facturación</template>
                    <template #content>
                        <p v-if="aforo.factura">Factura: <span class="font-medium">{{ aforo.factura.numero }}</span> ({{ aforo.factura.estado }})</p>
                        <p v-if="aforo.prefactura">Prefactura: <span class="font-medium">{{ aforo.prefactura.numero }}</span> ({{ aforo.prefactura.estado }})</p>
                        <p v-if="aforo.refactura" class="text-warn-600 font-medium">Marcado para refacturación</p>
                        <p v-if="!aforo.factura && !aforo.prefactura && !aforo.refactura">Pendiente de facturar</p>
                    </template>
                </Card>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <Card>
                    <template #title>Datos de la Carta Porte</template>
                    <template #content>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <p class="text-surface-500">Orig</p><p>{{ cp.lugar_origen?.nombre || '—' }}</p>
                            <p class="text-surface-500">Destino</p><p>{{ cp.lugar_destino?.nombre || '—' }}</p>
                            <p class="text-surface-500">Producto</p><p>{{ cp.producto?.nombre || '—' }}</p>
                            <p class="text-surface-500">Tipo de carga</p><p>{{ cp.tipo_carga?.nombre || '—' }}</p>
                            <p class="text-surface-500">Chofer</p><p>{{ cp.chofer?.nombrecompleto || '—' }}</p>
                            <p class="text-surface-500">Chofer 2</p><p>{{ cp.chofer2?.nombrecompleto || '—' }}</p>
                            <p class="text-surface-500">Hoja de Ruta</p><p>{{ cp.hoja_ruta?.numero || '—' }}</p>
                        </div>
                    </template>
                </Card>
                <Card>
                    <template #title>Totales del Aforo</template>
                    <template #content>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <p class="text-surface-500">Flete MN</p><p>${{ Number(aforo.flete_mt).toLocaleString() }}</p>
                            <p class="text-surface-500">Flete MLC</p><p>${{ Number(aforo.flete_mlc).toLocaleString() }}</p>
                            <p class="text-surface-500">Demora</p><p>${{ Number(aforo.flete_demora).toLocaleString() }}</p>
                            <p class="text-surface-500">Otros MN</p><p>${{ Number(aforo.otros_mt).toLocaleString() }}</p>
                            <p class="text-surface-500 font-bold">Ingreso MN</p><p class="font-bold">${{ Number(aforo.ingreso_mt).toLocaleString() }}</p>
                            <p class="text-surface-500">Coeficiente</p><p>{{ aforo.tasa }}</p>
                            <p class="text-surface-500 font-bold">Salario</p><p class="font-bold">${{ Number(aforo.salario).toLocaleString() }}</p>
                        </div>
                    </template>
                </Card>
            </div>

            <Card>
                <template #title>Desglose del Cálculo</template>
                <template #content>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <h4 class="font-semibold mb-2">Tarifas por línea</h4>
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-left text-surface-500">
                                        <th class="py-1">#</th><th>Tarifa</th><th>Flete MN</th><th>Flete MLC</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="n in 5" :key="n">
                                        <td class="py-1">{{ n }}</td>
                                        <td>{{ Number(aforo['tarifa_mt_' + n] || 0).toLocaleString() }}</td>
                                        <td>{{ Number(aforo['flete_mt_' + n] || 0).toLocaleString() }}</td>
                                        <td>{{ Number(aforo['flete_mlc_' + n] || 0).toLocaleString() }}</td>
                                    </tr>
                                </tbody>
                            </table>
                            <p class="mt-2"><span class="text-surface-500">Almacenaje:</span> ${{ Number(aforo.almacenaje_flete || 0).toLocaleString() }}</p>
                            <p><span class="text-surface-500">Recargos:</span> ${{ Number(aforo.otros_mt).toLocaleString() }}</p>
                        </div>
                        <div>
                            <h4 class="font-semibold mb-2">Demora</h4>
                            <p><span class="text-surface-500">Carga:</span> {{ aforo.dem_carga }}h / ${{ Number(aforo.flete_dem_1 || 0).toLocaleString() }}</p>
                            <p><span class="text-surface-500">Descarga:</span> {{ aforo.dem_descarga }}h / ${{ Number(aforo.flete_dem_2 || 0).toLocaleString() }}</p>
                            <p><span class="text-surface-500">Total:</span> ${{ Number(aforo.flete_demora).toLocaleString() }}</p>
                            <h4 class="font-semibold mt-3 mb-1">Indicadores</h4>
                            <p><span class="text-surface-500">Tráf. pos:</span> {{ aforo.traf_pos_total }}</p>
                            <p><span class="text-surface-500">Tráf. real:</span> {{ aforo.traf_real_total }}</p>
                            <p><span class="text-surface-500">Km carga:</span> {{ aforo.km_carga_total }}</p>
                        </div>
                    </div>
                </template>
            </Card>

            <div class="flex gap-2">
                <Button label="Volver" icon="pi pi-arrow-left" severity="secondary" @click="router.get(route('aforos.index'))" />
            </div>
        </div>
    </AppLayout>
</template>