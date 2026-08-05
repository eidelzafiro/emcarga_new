<template>
  <AppLayout>
    <Card>
      <template #title>Control de Energía</template>
      <template #content>
        <DataTable :value="medidores.data" paginator :rows="20" stripedRows class="p-datatable-sm" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros">
          <Column field="codigo" header="Código" sortable />
          <Column field="metro" header="Medidor" />
          <Column field="tipo" header="Tipo" />
          <Column field="lectura_actual" header="Lectura Actual" />
          <Column field="prepago" header="Prepago">
            <template #body="{ data }">
              <i :class="data.prepago ? 'pi pi-check text-green-500' : 'pi pi-times text-surface-400'" />
            </template>
          </Column>
          <Column field="activo" header="Activo">
            <template #body="{ data }">
              <Tag :value="data.activo ? 'Sí' : 'No'" :severity="data.activo ? 'success' : 'danger'" />
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
const props = defineProps({ medidores: Object });
</script>
