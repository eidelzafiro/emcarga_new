<template><AppLayout><Card><template #title>Solicitudes de Servicio</template><template #content>
<DataTable :value="solicitudes.data" paginator :rows="20" stripedRows class="p-datatable-sm" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros">
<Column field="numero" header="N°" sortable />
<Column field="cliente.nombre" header="Cliente" />
<Column field="fecha_solicitud" header="Fecha" />
<Column field="fecha_planificada" header="Planificada" />
<Column field="valor_total" header="Valor Total" />
<Column field="estado" header="Estado"><template #body="{ data }"><Tag :value="data.estado" :severity="data.estado === 'ejecutada' ? 'success' : data.estado === 'cancelada' ? 'danger' : 'info'" /></template></Column>
<Column header="Acciones" style="width:120px"><template #body="{ data }"><Button icon="pi pi-pencil" text rounded severity="info" /><Button icon="pi pi-trash" text rounded severity="danger" /></template></Column>
</DataTable></template></Card></AppLayout></template>
<script setup>import AppLayout from '@/Layouts/AppLayout.vue'; const props = defineProps({ solicitudes: Object });</script>
