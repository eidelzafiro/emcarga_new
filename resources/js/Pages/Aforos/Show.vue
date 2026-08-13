<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Card from 'primevue/card'
import Tag from 'primevue/tag'
import Button from 'primevue/button'

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
                    <p class="text-surface-500">Fecha de parte: {{ aforo.fecha_parte }}</p>
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
                            <p class="text-surface-500">Descuento</p><p>${{ Number(aforo.descuento).toLocaleString() }}</p>
                            <p class="text-surface-500 font-bold">Ingreso MN</p><p class="font-bold">${{ Number(aforo.ingreso_mt).toLocaleString() }}</p>
                        </div>
                    </template>
                </Card>
            </div>

            <div class="flex gap-2">
                <Button label="Volver" icon="pi pi-arrow-left" severity="secondary" @click="router.get(route('aforos.index'))" />
            </div>
        </div>
    </AppLayout>
</template>