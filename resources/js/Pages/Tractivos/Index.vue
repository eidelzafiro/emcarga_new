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
          <div class="flex gap-2">
            <Select v-model="grupo" :options="clasificaciones" optionLabel="label" optionValue="value" placeholder="Clasificación" class="w-52" showClear @change="cambiarGrupo" />
            <Button icon="pi pi-plus" label="Nuevo vehículo" @click="openCreate" />
          </div>
        </div>

        <DataTable :value="tractivos.data" stripedRows size="small" :rows="10" :paginator="true" :totalRecords="tractivos.total" :lazy="true" :first="(tractivos.current_page - 1) * tractivos.per_page" @page="onPage" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros">
          <Column field="codigo" header="Código" sortable />
          <Column field="descripcion" header="Descripción" sortable />
          <Column field="placa" header="Chapa" sortable />
          <Column header="Tipo equipo">
            <template #body="{ data }">
              <span>{{ data.tipo_equipo_label || '—' }}</span>
            </template>
          </Column>
          <Column header="Tipo de vehículo">
            <template #body="{ data }">
              <span>{{ data.tipo_vehiculo_label || '—' }}</span>
            </template>
          </Column>
          <Column field="capacidad_toneladas" header="Capacidad" sortable />
          <Column field="indice_consumo" header="Índice" sortable />
          <Column header="Tipo mtto">
            <template #body="{ data }">
              <span>{{ data.tipo_mtto_label || '—' }}</span>
            </template>
          </Column>
          <Column header="Acciones" :exportable="false">
            <template #body="{ data }">
              <div class="flex gap-1">
                <Button icon="pi pi-pencil" severity="secondary" text rounded size="small" @click="openEdit(data)" v-tooltip.left="'Editar'" />
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

    <Dialog v-model:visible="showModal" :header="editing ? 'Editar Vehículo' : 'Nuevo Vehículo'" :modal="true" :style="{ width: '820px' }">
      <form @submit.prevent="submit">
        <div class="grid grid-cols-3 gap-4">
          <div class="col-span-3 border-b border-surface-200 pb-2 mb-1 text-sm font-semibold text-surface-600">Identificación</div>
          <div class="col-span-2">
            <label class="block text-sm font-medium text-surface-700 mb-1">Descripción *</label>
            <InputText v-model="form.descripcion" required class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Chapa *</label>
            <InputText v-model="form.placa" required class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Tipo de Vehículo *</label>
            <Select v-if="esArrastre" v-model="form.id_tipo_vehiculo" :options="catalogos.tiposArrastre" optionLabel="label" optionValue="value" class="w-full" showClear required @change="aplicarFichaTipo" />
            <Select v-else v-model="form.id_tipo_vehiculo" :options="catalogos.tiposTractivo" optionLabel="label" optionValue="value" class="w-full" showClear required @change="aplicarFichaTipo" />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Motor</label>
            <Select v-model="form.id_motor" :options="catalogos.motores" optionLabel="label" optionValue="value" class="w-full" showClear :disabled="esArrastre" />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Caja</label>
            <Select v-model="form.id_caja" :options="catalogos.cajas" optionLabel="label" optionValue="value" class="w-full" showClear :disabled="esArrastre" />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Diferencial</label>
            <Select v-model="form.id_diferencial" :options="catalogos.diferenciales" optionLabel="label" optionValue="value" class="w-full" showClear />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Color primario</label>
            <Select v-model="form.id_color_primario" :options="catalogos.colores" optionLabel="label" optionValue="value" class="w-full" showClear />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Color secundario</label>
            <Select v-model="form.id_color_secundario" :options="catalogos.colores" optionLabel="label" optionValue="value" class="w-full" showClear />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Grupo *</label>
            <Select v-model="form.id_grupo" :options="catalogos.grupos" optionLabel="label" optionValue="value" class="w-full" required />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Tipo servicio</label>
            <Select v-model="form.id_tipo_servicio" :options="catalogos.tiposServicio" optionLabel="label" optionValue="value" class="w-full" showClear />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Tipo estado</label>
            <Select v-model="form.id_tipo_estado" :options="catalogos.estados" optionLabel="label" optionValue="value" class="w-full" showClear />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Lubricante hidráulico</label>
            <Select v-model="form.id_lubricante_hidraulico" :options="catalogos.lubricantes" optionLabel="label" optionValue="value" class="w-full" showClear />
          </div>

          <div class="col-span-3 border-b border-surface-200 pb-1 mb-1 text-sm font-semibold text-surface-600">Números / Físico</div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">No. Motor</label>
            <InputText v-model="form.numero_motor" class="w-full" :disabled="esArrastre" />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">No. Chasis</label>
            <InputText v-model="form.numero_chasis" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">No. Caja</label>
            <InputText v-model="form.numero_caja" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">VIN</label>
            <InputText v-model="form.vin" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">No. Carrocería</label>
            <InputText v-model="form.nro_carroceria" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Registro</label>
            <InputText v-model="form.nro_registro" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Resolución</label>
            <InputText v-model="form.nro_resolucion" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Capacidad (ton) *</label>
            <InputNumber v-model="form.capacidad_toneladas" mode="decimal" class="w-full" required />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Tara</label>
            <InputNumber v-model="form.tara" mode="decimal" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Cap. depósito</label>
            <InputNumber v-model="form.cap_deposito" mode="decimal" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Cap. hidráulico</label>
            <InputNumber v-model="form.cap_hidraulico" mode="decimal" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Cuenta combustible</label>
            <InputText v-model="form.cta_combustible" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Índice consumo *</label>
            <InputNumber v-model="form.indice_consumo" mode="decimal" class="w-full" required />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Índice aceite</label>
            <InputNumber v-model="form.indice_aceite" mode="decimal" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">GPS</label>
            <div class="flex items-center gap-2 pt-2">
              <ToggleSwitch v-model="form.gps" />
              <span class="text-sm text-surface-500">{{ form.gps ? 'Con GPS' : 'Sin GPS' }}</span>
            </div>
          </div>

          <div class="col-span-3 border-b border-surface-200 pb-1 mb-1 text-sm font-semibold text-surface-600">Kilometrajes / Planes</div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Kilometraje actual</label>
            <InputNumber v-model="form.kilometraje_actual" mode="decimal" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Kms disponibilidad</label>
            <InputNumber v-model="form.kms_disp" mode="decimal" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">KMS plan MTTO</label>
            <InputNumber v-model="form.kms_plan_mtto" mode="decimal" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Plan combustible</label>
            <InputNumber v-model="form.plan_comb" mode="decimal" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Plan TN</label>
            <InputNumber v-model="form.plan_tn" mode="decimal" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Plan viajes</label>
            <InputNumber v-model="form.plan_viajes" mode="decimal" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Plan gastos</label>
            <InputNumber v-model="form.plan_gastos" mode="decimal" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Plan CDT</label>
            <InputNumber v-model="form.plan_cdt" mode="decimal" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Plan diario</label>
            <InputNumber v-model="form.plan_diario" mode="decimal" class="w-full" />
          </div>

          <div class="col-span-3 border-b border-surface-200 pb-1 mb-1 text-sm font-semibold text-surface-600">Vencimientos / Fechas</div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Fecha alta</label>
            <DatePicker v-model="form.fecha_alta" dateFormat="yy-mm-dd" showIcon class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Fecha baja</label>
            <DatePicker v-model="form.fecha_baja" dateFormat="yy-mm-dd" showIcon class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Fecha reconstrucción</label>
            <DatePicker v-model="form.f_reconstruccion" dateFormat="yy-mm-dd" showIcon class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">FICAV</label>
            <InputText v-model="form.ficav" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Emisión FICAV</label>
            <DatePicker v-model="form.femision_ficav" dateFormat="yy-mm-dd" showIcon class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Vence FICAV</label>
            <DatePicker v-model="form.fvence_ficav" dateFormat="yy-mm-dd" showIcon class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">LOT</label>
            <InputText v-model="form.lot" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Emisión LOT</label>
            <DatePicker v-model="form.femision_lot" dateFormat="yy-mm-dd" showIcon class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Vence LOT</label>
            <DatePicker v-model="form.fvence_lot" dateFormat="yy-mm-dd" showIcon class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Circulación</label>
            <InputText v-model="form.circulacion" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Emisión circulación</label>
            <DatePicker v-model="form.femision_circ" dateFormat="yy-mm-dd" showIcon class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 mb-1">Vence circulación</label>
            <DatePicker v-model="form.fvence_circ" dateFormat="yy-mm-dd" showIcon class="w-full" />
          </div>
        </div>
      </form>
      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="closeModal" />
        <Button :label="editing ? 'Actualizar' : 'Crear'" @click="submit" />
      </template>
    </Dialog>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/Layouts/AppLayout.vue';
import { debounce } from 'lodash';

const props = defineProps({
  tractivos: Object,
  filters: Object,
  catalogos: Object,
});

const clasificaciones = [
  { label: 'Tractivos', value: 1 },
  { label: 'Administrativos', value: 7 },
  { label: 'Arrastres', value: 8 },
  { label: 'Vehículos / Todos', value: '' },
];

const search = ref(props.filters?.search || '');
const grupo = ref(props.filters?.grupo || '');
const showModal = ref(false);
const editing = ref(false);
const form = ref(baseForm());

const esArrastre = computed(() => Number(form.value.id_grupo) === 8);

// Aplicar al form la ficha (marca/modelo/año) heredada del tipo seleccionado.
// (Ya no se muestran en el formulario; se conserva el payar de control.)
function aplicarFichaTipo() {
  const id = form.value.id_tipo_vehiculo;
  form.value.marca = '';
  form.value.modelo = '';
  form.value.anno = null;
}

function baseForm() {
  return {
    id: null,
    descripcion: '', placa: '',
    id_tipo_vehiculo: null, id_motor: null, id_caja: null, id_diferencial: null,
    id_grupo: null, id_tipo_servicio: null, id_color_primario: null, id_color_secundario: null,
    id_tipo_estado: null, id_lubricante_hidraulico: null,
    numero_motor: '', numero_chasis: '', numero_caja: '', vin: '', nro_carroceria: '', nro_registro: '', nro_resolucion: '',
    capacidad_toneladas: null, tara: null, cap_deposito: null, cap_hidraulico: null, cta_combustible: '',
    indice_consumo: null, indice_aceite: null, gps: false,
    kilometraje_actual: null, kms_disp: null, kms_plan_mtto: null,
    plan_comb: null, plan_tn: null, plan_viajes: null, plan_gastos: null, plan_cdt: null, plan_diario: null,
    fecha_alta: null, fecha_baja: null, f_reconstruccion: null,
    ficav: '', femision_ficav: null, fvence_ficav: null,
    lot: '', femision_lot: null, fvence_lot: null,
    circulacion: '', femision_circ: null, fvence_circ: null,
  };
}

const debouncedSearch = debounce(() => {
  router.get(route('tractivos.index'), { search: search.value, grupo: grupo.value }, {
    preserveState: true,
    replace: true,
  });
}, 300);

function cambiarGrupo() {
  router.get(route('tractivos.index'), { search: search.value, grupo: grupo.value }, {
    preserveState: true,
    replace: true,
  });
}

const onPage = (event) => {
  router.get(route('tractivos.index'), { page: event.page + 1, search: search.value, grupo: grupo.value }, { preserveState: true, replace: true });
};

const openCreate = () => {
  editing.value = false;
  form.value = baseForm();
  if (grupo.value) {
    form.value.id_grupo = grupo.value;
  }
  showModal.value = true;
};

const openEdit = (tractivo) => {
  editing.value = true;
  form.value = { ...baseForm(), ...tractivo };
  form.value.id = tractivo.id;
  form.value.gps = !!tractivo.gps;
  showModal.value = true;
};

const closeModal = () => { showModal.value = false; editing.value = false; };

const submit = () => {
  const payload = { ...form.value, gps: form.value.gps ? '1' : '0' };
  if (editing.value) {
    router.put(route('tractivos.update', payload.id), payload, {
      onSuccess: closeModal,
    });
  } else {
    router.post(route('tractivos.store'), payload, {
      onSuccess: closeModal,
    });
  }
};

const confirmDelete = (tractivo) => {
  if (confirm('¿Está seguro de eliminar este vehículo?')) {
    router.delete(route('tractivos.destroy', tractivo.id));
  }
};
</script>