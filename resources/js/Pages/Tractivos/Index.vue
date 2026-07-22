<template>
  <AppLayout>
    <Card>
      <template #title>Flota de Vehículos</template>
      <template #subtitle>Gestión de tractivos y vehículos de la flota</template>
      <template #content>
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-4">
          <div class="relative w-full sm:w-72">
            <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-surface-400 text-sm" />
            <InputText
              v-model="search"
              placeholder="Buscar por descripción o placa…"
              class="w-full pl-9"
              @input="debouncedSearch"
            />
          </div>
          <Button icon="pi pi-plus" label="Nuevo vehículo" @click="showModal = true" />
        </div>

        <DataTable :value="tractivos.data" stripedRows size="small" :rows="10" :paginator="true" :totalRecords="tractivos.total" :lazy="true" :first="(tractivos.current_page - 1) * tractivos.per_page" @page="onPage">
          <Column field="descripcion" header="Descripción" sortable />
          <Column field="placa" header="Placa" sortable />
          <Column field="marca" header="Marca" sortable />
          <Column header="Estado">
            <template #body="{ data }">
              <Tag
                :value="data.estado || 'activo'"
                :severity="(data.estado || 'activo') === 'activo' ? 'success' : 'danger'"
              />
            </template>
          </Column>
          <Column header="Acciones" :exportable="false">
            <template #body="{ data }">
              <div class="flex gap-1">
                <Button icon="pi pi-pencil" severity="secondary" text rounded size="small" @click="edit(data)" v-tooltip.left="'Editar'" />
                <Button icon="pi pi-trash" severity="danger" text rounded size="small" @click="confirmDelete(data)" v-tooltip.left="'Eliminar'" />
              </div>
            </template>
          </Column>
          <template #empty>
            <div class="text-center py-8 text-surface-400">
              <i class="pi pi-truck text-3xl mb-2 block" />
              No se encontraron vehículos.
            </div>
          </template>
        </DataTable>
      </template>
    </Card>

    <Dialog v-model:visible="showModal" :header="editing ? 'Editar Vehículo' : 'Nuevo Vehículo'" :modal="true" class="w-full max-w-lg">
      <form @submit.prevent="submit">
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Descripción *</label>
            <InputText v-model="form.descripcion" required class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Placa *</label>
            <InputText v-model="form.placa" required class="w-full" />
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-surface-700 mb-1">Marca</label>
              <InputText v-model="form.marca" class="w-full" />
            </div>
            <div>
              <label class="block text-sm font-medium text-surface-700 mb-1">Modelo</label>
              <InputText v-model="form.modelo" class="w-full" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Año</label>
            <InputNumber v-model="form.anno" class="w-full" />
          </div>
        </div>
      </form>
      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="showModal = false" />
        <Button :label="editing ? 'Actualizar' : 'Crear'" @click="submit" />
      </template>
    </Dialog>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { debounce } from 'lodash';

const props = defineProps({
  tractivos: Object,
  filters: Object,
});

const search = ref(props.filters?.search || '');
const showModal = ref(false);
const editing = ref(false);
const form = ref({
  id: null,
  descripcion: '',
  placa: '',
  marca: '',
  modelo: '',
  anno: null,
});

const debouncedSearch = debounce(() => {
  router.get(route('tractivos.index'), { search: search.value }, {
    preserveState: true,
    replace: true,
  });
}, 300);

const onPage = (event) => {
  router.get(route('tractivos.index'), { page: event.page + 1, search: search.value }, { preserveState: true, replace: true });
};

const edit = (tractivo) => {
  editing.value = true;
  form.value = { ...tractivo };
  showModal.value = true;
};

const submit = () => {
  if (editing.value) {
    router.put(route('tractivos.update', form.value.id), form.value, {
      onSuccess: () => {
        showModal.value = false;
        editing.value = false;
        resetForm();
      },
    });
  } else {
    router.post(route('tractivos.store'), form.value, {
      onSuccess: () => {
        showModal.value = false;
        resetForm();
      },
    });
  }
};

const confirmDelete = (tractivo) => {
  if (confirm('¿Está seguro de eliminar este vehículo?')) {
    router.delete(route('tractivos.destroy', tractivo.id));
  }
};

const resetForm = () => {
  form.value = { id: null, descripcion: '', placa: '', marca: '', modelo: '', anno: null };
};
</script>
