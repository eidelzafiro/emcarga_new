<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import Toolbar from 'primevue/toolbar'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ historial: Object, filters: Object, bolsa: Array })
const toast = useToast()
const search = ref(props.filters?.search || '')
const title = 'Historial de Movimientos'

watch(search, () => {
    router.get(route('historial-movimientos.index'), { search: search.value }, { preserveState: true, replace: true })
})
</script>

<template>
    <AppLayout :title="title">
        <div class="card">
            <Toolbar class="mb-4">
                <template #end>
                    <InputText v-model="search" placeholder="Buscar..." />
                </template>
            </Toolbar>

            <DataTable :value="historial.data" striped-rows paginator :rows="20" :total-records="historial.total">
                <Column field="id" header="#" sortable />
                <Column field="fecha" header="Fecha" sortable />
                <Column field="tipo_movimiento" header="Tipo de Movimiento" sortable />
                <Column header="Empleado">
                    <template #body="{ data }">
                        {{ data.bolsa_nombre || data.ci || '—' }}
                    </template>
                </Column>
                <Column field="descripcion" header="Descripción" />
            </DataTable>
        </div>
    </AppLayout>
</template>
