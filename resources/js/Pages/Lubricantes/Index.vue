<template>
  <AppLayout>
    <Card>
      <template #title>Control de Lubricantes</template>
      <template #content>
        <DataTable :value="lubricantes.data" paginator :rows="20" stripedRows class="p-datatable-sm" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros">
          <Column field="folio" header="Folio" sortable />
          <Column field="tractivo.descripcion" header="Tractivo" />
          <Column field="tipoAceite.nombre" header="Tipo Aceite" />
          <Column field="cantidad" header="Cantidad" />
          <Column field="unidad" header="Unidad" />
          <Column field="fecha" header="Fecha" />
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
const props = defineProps({ lubricantes: Object });
</script>
