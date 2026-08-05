<template>
  <AppLayout>
    <Card>
      <template #title>Motores</template>
      <template #content>
        <DataTable :value="motores.data" paginator :rows="20" :totalRecords="motores.total" :lazy="false"
          stripedRows class="p-datatable-sm" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros">
          <Column field="codigo" header="Código" sortable />
          <Column field="descripcion" header="Descripción" sortable />
          <Column field="marca" header="Marca" sortable />
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
              <Button icon="pi pi-pencil" text rounded severity="info" @click="editar(data)" />
              <Button icon="pi pi-trash" text rounded severity="danger" @click="eliminar(data)" />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </AppLayout>
</template>
<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
const props = defineProps({ motores: Object });
const editar = (m) => { };
const eliminar = (m) => { };
</script>
