<template><AppLayout><Card><template #title>Lugares</template><template #content>
<DataTable :value="lugares.data" paginator :rows="20" stripedRows class="p-datatable-sm">
<Column field="codigo" header="Código" sortable />
<Column field="nombre" header="Nombre" sortable />
<Column field="provincia" header="Provincia" />
<Column field="municipio" header="Municipio" />
<Column field="activo" header="Activo"><template #body="{ data }"><Tag :value="data.activo ? 'Sí' : 'No'" :severity="data.activo ? 'success' : 'danger'" /></template></Column>
<Column header="Acciones" style="width:120px"><template #body="{ data }"><Button icon="pi pi-pencil" text rounded severity="info" /><Button icon="pi pi-trash" text rounded severity="danger" /></template></Column>
</DataTable></template></Card></AppLayout></template>
<script setup>import AppLayout from '@/Layouts/AppLayout.vue'; const props = defineProps({ lugares: Object });</script>
