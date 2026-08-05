<template>
  <AppLayout>
    <Card>
      <template #title>Neumáticos</template>
      <template #content>
        <DataTable :value="neumaticos.data" paginator :rows="20" stripedRows class="p-datatable-sm" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros">
          <Column field="folio" header="Folio" sortable />
          <Column field="marca" header="Marca" />
          <Column field="modelo" header="Modelo" />
          <Column field="medida" header="Medida" />
          <Column field="tractivo.descripcion" header="Tractivo" />
          <Column field="kilometraje" header="KM" />
          <Column field="estado" header="Estado">
            <template #body="{ data }">
              <Tag :value="data.estado" :severity="data.estado === 'activo' ? 'success' : 'warn'" />
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
const props = defineProps({ neumaticos: Object });
</script>
