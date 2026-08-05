<template>
  <AppLayout>
    <Card>
      <template #title>Cajas de Transmisión</template>
      <template #content>
        <DataTable :value="cajas.data" paginator :rows="20" stripedRows class="p-datatable-sm" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros">
          <Column field="codigo" header="Código" sortable />
          <Column field="descripcion" header="Descripción" sortable />
          <Column field="marca" header="Marca" />
          <Column field="modelo" header="Modelo" />
          <Column field="numero_serie" header="N° Serie" />
          <Column field="tractivo.descripcion" header="Tractivo" />
          <Column field="estado" header="Estado">
            <template #body="{ data }">
              <Tag :value="data.estado || 'disponible'" :severity="data.estado === 'disponible' ? 'success' : 'warn'" />
            </template>
          </Column>
          <Column header="Acciones" style="width:120px">
            <template #body="{ data }">
              <Button icon="pi pi-pencil" text rounded severity="info" />
              <Button icon="pi pi-trash" text rounded severity="danger" />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </AppLayout>
</template>
<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
const props = defineProps({ cajas: Object });
</script>
