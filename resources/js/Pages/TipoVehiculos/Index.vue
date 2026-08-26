<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from 'primevue/card';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { useConfirm } from 'primevue/useconfirm';
import ConfirmDialog from 'primevue/confirmdialog';
import { debounce } from 'lodash';

const props = defineProps({
    title: String,
    items: Object,
    filters: Object,
    clases: Array,
    options: Object,
});

const confirm = useConfirm();
const search = ref(props.filters?.search ?? '');
const clase = ref(props.filters?.clase ?? '');

const debouncedSearch = debounce(() => {
    router.get(route('tipo-vehiculos.index'), { search: search.value, clase: clase.value }, { preserveState: true, replace: true });
}, 300);

const onClase = () => {
    router.get(route('tipo-vehiculos.index'), { search: search.value, clase: clase.value }, { preserveState: true, replace: true });
};

const onPage = (event) => {
    router.get(route('tipo-vehiculos.index'), { page: event.page + 1, search: search.value, clase: clase.value }, { preserveState: true, replace: true });
};

const badgeSeverity = (clase) => (clase === 'arrastre' ? 'warn' : 'info');

const eliminar = (item) => {
    confirm.require({
        message: `¿Eliminar el tipo de vehículo #${item.id}?`,
        header: 'Eliminar',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Eliminar',
        rejectLabel: 'Volver',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(route('tipo-vehiculos.destroy', item.id), { preserveScroll: true }),
    });
};
</script>

<template>
    <AppLayout>
        <Head :title="title" />
        <ConfirmDialog />
        <Card>
            <template #title>Tipos de Vehículo</template>
            <template #subtitle>Gestión de tipos de tractivos y arrastres</template>
            <template #content>
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-4">
                    <div class="relative w-full sm:w-72">
                        <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm" />
                        <InputText v-model="search" placeholder="Buscar marca/modelo/equipo/año…" class="w-full pl-9" @input="debouncedSearch" />
                    </div>
                    <div class="flex gap-2">
                        <Select v-model="clase" :options="clases" optionLabel="label" optionValue="value" placeholder="Clase" showClear class="w-44" @change="onClase" />
                        <Button icon="pi pi-plus" label="Nuevo" @click="router.get(route('tipo-vehiculos.create'))" />
                    </div>
                </div>

                <DataTable :value="items.data" stripedRows size="small" :rows="items.per_page" :paginator="true"
                    :totalRecords="items.total" :first="(items.current_page - 1) * items.per_page" @page="onPage"
                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                    currentPageReportTemplate="Total: {totalRecords} registros">
                    <Column field="id" header="ID" sortable />
                    <Column field="clase" header="Clase">
                        <template #body="{ data }">
                            <Tag :value="data.clase" :severity="badgeSeverity(data.clase)" />
                        </template>
                    </Column>
                    <Column header="Equipo">
                        <template #body="{ data }">{{ data.tipo_equipo?.nombre ?? '—' }}</template>
                    </Column>
                    <Column header="Marca">
                        <template #body="{ data }">{{ data.marca?.nombre ?? '—' }}</template>
                    </Column>
                    <Column header="Modelo">
                        <template #body="{ data }">{{ data.modelo?.nombre ?? '—' }}</template>
                    </Column>
                    <Column field="fabricacion" header="Año" sortable />
                    <Column header="Mantenimiento">
                        <template #body="{ data }">{{ data.tipo_mantenimiento?.nombre ?? '—' }}</template>
                    </Column>
                    <Column field="vehiculos_count" header="Vehículos" sortable />
                    <Column header="Activo">
                        <template #body="{ data }">
                            <Tag :value="data.activo ? 'Sí' : 'No'" :severity="data.activo ? 'success' : 'danger'" />
                        </template>
                    </Column>
                    <Column header="Acciones" :exportable="false">
                        <template #body="{ data }">
                            <div class="flex gap-1">
                                <Button icon="pi pi-pencil" severity="secondary" text rounded size="small"
                                    @click="router.get(route('tipo-vehiculos.edit', data.id))" v-tooltip.left="'Editar'" />
                                <Button icon="pi pi-trash" severity="danger" text rounded size="small"
                                    @click="eliminar(data)" v-tooltip.left="'Eliminar'" />
                            </div>
                        </template>
                    </Column>
                    <template #empty>
                        <div class="text-center py-8 text-gray-400">
                            <i class="pi pi-car text-3xl mb-2 block" />
                            No se encontraron tipos de vehículo.
                        </div>
                    </template>
                </DataTable>
            </template>
        </Card>
    </AppLayout>
</template>
