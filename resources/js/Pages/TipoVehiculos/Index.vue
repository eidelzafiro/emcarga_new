<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { useConfirm } from 'primevue/useconfirm';
import ConfirmDialog from 'primevue/confirmdialog';
import { debounce } from 'lodash';

const props = defineProps({
    title: String,
    grupos: Array,
    filters: Object,
    clases: Array,
    options: Object,
});

const confirm = useConfirm();
const search = ref(props.filters?.search ?? '');
const clase = ref(props.filters?.clase ?? '');
const equipoF = ref(props.filters?.id_tipo_equipo ? Number(props.filters.id_tipo_equipo) : null);
const marcaF = ref(props.filters?.id_marca ? Number(props.filters.id_marca) : null);
const modeloF = ref(props.filters?.id_modelo ? Number(props.filters.id_modelo) : null);

const aplicarFiltros = () => {
    router.get(route('tipo-vehiculos.index'), {
        search: search.value,
        clase: clase.value,
        id_tipo_equipo: equipoF.value,
        id_marca: marcaF.value,
        id_modelo: modeloF.value,
    }, { preserveState: true, replace: true });
};

const debouncedSearch = debounce(() => aplicarFiltros(), 300);
const onClase = () => aplicarFiltros();
const onFiltro = () => aplicarFiltros();

const badgeSeverity = (clase) => (clase === 'arrastre' ? 'warn' : 'info');

const totalTipos = () =>
    (props.grupos ?? []).reduce(
        (acc, g) => acc + g.marcas.reduce((a, m) => a + m.modelos.length, 0),
        0,
    );

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
        <Card>
            <template #title>Tipos de Vehículo</template>
            <template #subtitle>
                {{ totalTipos() }} tipos · agrupados por tipo de equipo
            </template>
            <template #content>
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-5">
                    <div class="relative w-full sm:w-72">
                        <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm" />
                        <InputText v-model="search" placeholder="Buscar marca/modelo/equipo/año…" class="w-full pl-9" @input="debouncedSearch" />
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Select v-model="equipoF" :options="options?.tipo_equipo ?? []" optionLabel="label" optionValue="value" placeholder="Tipo de equipo" showClear class="w-52" @change="onFiltro" />
                        <Select v-model="marcaF" :options="options?.marcas ?? []" optionLabel="label" optionValue="value" placeholder="Marca" showClear class="w-44" @change="onFiltro" />
                        <Select v-model="modeloF" :options="options?.modelos ?? []" optionLabel="label" optionValue="value" placeholder="Modelo" showClear class="w-44" @change="onFiltro" />
                        <Select v-model="clase" :options="clases" optionLabel="label" optionValue="value" placeholder="Clase" showClear class="w-44" @change="onClase" />
                        <Button icon="pi pi-plus" label="Nuevo" @click="router.get(route('tipo-vehiculos.create'))" />
                    </div>
                </div>

                <div v-if="!grupos || grupos.length === 0" class="text-center py-12 text-gray-400">
                    <i class="pi pi-car text-4xl mb-3 block" />
                    No se encontraron tipos de vehículo.
                </div>

                <div v-for="grupo in grupos" :key="grupo.equipo_id" class="mb-8">
                    <div class="flex items-center gap-3 mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">
                        <img v-if="grupo.equipo_imagen" :src="grupo.equipo_imagen" class="w-10 h-10 object-contain rounded bg-white p-1" alt="" />
                        <i v-else class="pi pi-car text-2xl text-primary" />
                        <h2 class="text-lg font-semibold">{{ grupo.equipo_nombre }}</h2>
                        <Tag :value="grupo.marcas.length + ' marca(s)'" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        <div v-for="marca in grupo.marcas" :key="marca.marca_id"
                            class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden bg-white dark:bg-gray-800 shadow-sm hover:shadow-md transition flex flex-col">
                            <div class="h-16 bg-gray-100 dark:bg-gray-700 flex items-center justify-center relative">
                                <img v-if="marca.marca_imagen" :src="marca.marca_imagen" class="max-h-12 max-w-full object-contain p-1" alt="" />
                                <img v-else-if="grupo.equipo_imagen" :src="grupo.equipo_imagen" class="max-h-12 max-w-full object-contain p-1" :alt="grupo.equipo_nombre" />
                                <i v-else class="pi pi-car text-2xl text-gray-400" />
                                <Tag :value="marca.vehiculos_count + ' vehículo(s)'" class="absolute top-1 left-1" />
                            </div>
                            <div class="p-3 flex-1">
                                <div class="font-bold text-xl leading-tight mb-2 text-gray-900 dark:text-gray-100">{{ marca.marca_nombre }}</div>
                                <ul class="text-sm divide-y divide-gray-100 dark:divide-gray-700">
                                    <li v-for="mdl in marca.modelos" :key="mdl.id" class="py-1.5 flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <div class="font-semibold text-base truncate text-gray-800 dark:text-gray-200">
                                                {{ mdl.modelo ?? '—' }}
                                                <Tag v-if="mdl.clase" :value="mdl.clase" :severity="badgeSeverity(mdl.clase)" class="ml-1 align-middle" />
                                            </div>
                                            <div class="text-xs text-gray-500 flex flex-wrap gap-x-2 gap-y-0.5 mt-0.5">
                                                <span v-if="mdl.fabricacion"><i class="pi pi-calendar mr-1" />{{ mdl.fabricacion }}</span>
                                                <span v-if="mdl.mantenimiento"><i class="pi pi-wrench mr-1" />{{ mdl.mantenimiento }}</span>
                                                <span><i class="pi pi-truck mr-1" />{{ mdl.vehiculos_count }}</span>
                                                <span :class="mdl.activo ? 'text-green-600' : 'text-red-500'">{{ mdl.activo ? 'Activo' : 'Inactivo' }}</span>
                                            </div>
                                        </div>
                                        <div class="flex gap-1 shrink-0">
                                            <Button icon="pi pi-pencil" severity="secondary" text rounded size="small"
                                                @click="router.get(route('tipo-vehiculos.edit', mdl.id))" v-tooltip.left="'Editar'" />
                                            <Button icon="pi pi-trash" severity="danger" text rounded size="small"
                                                @click="eliminar(mdl)" v-tooltip.left="'Eliminar'" />
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </Card>
    </AppLayout>
</template>
