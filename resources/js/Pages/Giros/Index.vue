<template>
  <AppLayout>
    <div class="card">
      <div class="flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
        <h2 class="text-2xl font-semibold m-0">Cartas Porte</h2>
        <div class="flex gap-2">
          <InputText v-model="filters.search" placeholder="Buscar por número..." @keyup.enter="buscar" class="w-56" />
          <Button icon="pi pi-search" severity="secondary" @click="buscar" />
        </div>
      </div>

      <DataTable :value="cartas.data" paginator :rows="20" stripedRows
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
        currentPageReportTemplate="Total: {totalRecords} registros" v-model:first="first">
        <Column field="numero" header="Carta Porte" sortable />
        <Column field="cliente.nombre" header="Cliente" />
        <Column field="fecha_emision" header="Fecha Emisión" />
        <Column field="ingreso_mt" header="Ingreso MT" />
        <Column field="flete_mt" header="Flete MT" />
        <Column field="estado" header="Estado">
          <template #body="{ data }">
            <Tag :value="data.estado" :severity="severityEstado(data.estado)" />
          </template>
        </Column>
      </DataTable>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ cartas: Object, filters: Object });

const first = ref(0);
const filters = ref({ search: props.filters?.search ?? '' });

function severityEstado(estado) {
  return { emitida: 'info', recepcionada: 'success', cancelada: 'danger' }[estado] ?? 'secondary';
}

function buscar() {
  router.get('/giros', { search: filters.value.search }, { preserveState: true, preserveScroll: true });
}
</script>