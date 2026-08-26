<script setup>
import { reactive, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';

const props = defineProps({
    title: String,
    tipoVehiculo: Object,
    options: Object,
});

const isEdit = !!props.tipoVehiculo;

// Inicializa el objeto de ficha solo con las claves definidas,
// precargando desde la relación existente (edición).
const iniciarFicha = (tipo) => {
    const defs = props.options['ficha_' + tipo] ?? {};
    const rel = tipo === 'tractivo'
        ? props.tipoVehiculo?.tipoTractivo
        : props.tipoVehiculo?.tipoArrastre;
    const obj = {};
    Object.keys(defs).forEach((k) => { obj[k] = rel?.[k] ?? null; });
    return obj;
};

const form = reactive({
    clase: props.tipoVehiculo?.clase ?? 'tractivo',
    id_tipo_equipo: props.tipoVehiculo?.id_tipo_equipo ?? null,
    id_marca: props.tipoVehiculo?.id_marca ?? null,
    id_modelo: props.tipoVehiculo?.id_modelo ?? null,
    id_tipo_mantenimiento: props.tipoVehiculo?.id_tipo_mantenimiento ?? null,
    fabricacion: props.tipoVehiculo?.fabricacion ?? null,
    id_tipo_tractivo: props.tipoVehiculo?.id_tipo_tractivo ?? null,
    id_tipo_arrastre: props.tipoVehiculo?.id_tipo_arrastre ?? null,
    ficha_tractivo: iniciarFicha('tractivo'),
    ficha_arrastre: iniciarFicha('arrastre'),
    activo: props.tipoVehiculo?.activo ?? true,
});

// Al cambiar la clase, limpia el subtipo contrario.
watch(() => form.clase, (nuevo) => {
    if (nuevo === 'tractivo') form.id_tipo_arrastre = null;
    else form.id_tipo_tractivo = null;
});

const submit = () => {
    const payload = { ...form };
    if (isEdit) {
        router.put(route('tipo-vehiculos.update', props.tipoVehiculo.id), payload, { preserveScroll: true });
    } else {
        router.post(route('tipo-vehiculos.store'), payload, { preserveScroll: true });
    }
};

const sel = (lista) => [{ value: null, label: '—' }, ...lista];
</script>

<template>
    <AppLayout>
        <Head :title="title" />
        <Card>
            <template #title>{{ title }}</template>
            <template #content>
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Datos comunes -->
                    <fieldset class="border rounded p-4 dark:border-gray-700">
                        <legend class="px-2 font-medium">Datos generales</legend>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Clase</label>
                                <Select v-model="form.clase"
                                        :options="[{ value: 'tractivo', label: 'Tractivo' }, { value: 'arrastre', label: 'Arrastre' }]"
                                        optionLabel="label" optionValue="value" class="w-full" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Año fabricación</label>
                                <InputNumber v-model="form.fabricacion" mode="decimal" class="w-full" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de equipo</label>
                                <Select v-model="form.id_tipo_equipo" :options="sel(options.tipo_equipo)" optionLabel="label" optionValue="value" class="w-full" showClear />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Marca</label>
                                <Select v-model="form.id_marca" :options="sel(options.marcas)" optionLabel="label" optionValue="value" class="w-full" showClear />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Modelo</label>
                                <Select v-model="form.id_modelo" :options="sel(options.modelos)" optionLabel="label" optionValue="value" class="w-full" showClear />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de mantenimiento</label>
                                <Select v-model="form.id_tipo_mantenimiento" :options="sel(options.tipo_mantenimiento)" optionLabel="label" optionValue="value" class="w-full" showClear />
                            </div>
                            <div class="flex items-center gap-2 pt-6">
                                <Checkbox v-model="form.activo" :binary="true" inputId="activo" />
                                <label for="activo" class="text-sm">Activo</label>
                            </div>
                        </div>
                    </fieldset>

                    <!-- Sección Tractivo -->
                    <fieldset v-if="form.clase === 'tractivo'"
                              class="border rounded p-4 border-sky-300 dark:border-sky-700">
                        <legend class="px-2 font-medium text-sky-700 dark:text-sky-300">Datos de Tractivo</legend>
                        <p class="text-xs text-gray-500 mb-3">
                            La ficha técnica se crea al guardar este tipo o se actualiza si ya existe
                            una asociada. Complete los campos que apliquen.
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div v-for="(cfg, key) in options.ficha_tractivo" :key="key">
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ cfg.label }}</label>
                                <Select v-if="cfg.type === 'select'" v-model="form.ficha_tractivo[key]"
                                        :options="cfg.options" optionLabel="label" optionValue="value" class="w-full" showClear />
                                <InputNumber v-else-if="cfg.type === 'number'" v-model="form.ficha_tractivo[key]" mode="decimal" class="w-full" />
                                <div v-else-if="cfg.type === 'boolean'" class="flex items-center gap-2 pt-2">
                                    <Checkbox v-model="form.ficha_tractivo[key]" :binary="true" />
                                </div>
                                <InputText v-else v-model="form.ficha_tractivo[key]" class="w-full" />
                            </div>
                        </div>
                    </fieldset>

                    <!-- Sección Arrastre -->
                    <fieldset v-else
                              class="border rounded p-4 border-amber-300 dark:border-amber-700">
                        <legend class="px-2 font-medium text-amber-700 dark:text-amber-300">Datos de Arrastre</legend>
                        <p class="text-xs text-gray-500 mb-3">
                            La ficha técnica se crea al guardar este tipo o se actualiza si ya existe
                            una asociada. Complete los campos que apliquen.
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div v-for="(cfg, key) in options.ficha_arrastre" :key="key">
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ cfg.label }}</label>
                                <Select v-if="cfg.type === 'select'" v-model="form.ficha_arrastre[key]"
                                        :options="cfg.options" optionLabel="label" optionValue="value" class="w-full" showClear />
                                <InputNumber v-else-if="cfg.type === 'number'" v-model="form.ficha_arrastre[key]" mode="decimal" class="w-full" />
                                <div v-else-if="cfg.type === 'boolean'" class="flex items-center gap-2 pt-2">
                                    <Checkbox v-model="form.ficha_arrastre[key]" :binary="true" />
                                </div>
                                <InputText v-else v-model="form.ficha_arrastre[key]" class="w-full" />
                            </div>
                        </div>
                    </fieldset>

                    <div class="flex gap-3">
                        <Button type="submit" :label="isEdit ? 'Actualizar' : 'Crear'" />
                        <Button label="Cancelar" severity="secondary" @click="router.get(route('tipo-vehiculos.index'))" />
                    </div>
                </form>
            </template>
        </Card>
    </AppLayout>
</template>
