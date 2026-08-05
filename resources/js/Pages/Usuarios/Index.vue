<template>
  <AppLayout>
    <Card>
      <template #title>Gestión de usuarios</template>
      <template #content>
        <!-- Barra de herramientas -->
        <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-3 mb-4 items-center">
          <div class="grid grid-cols-1 sm:grid-cols-4 gap-2">
            <span class="p-input-icon-left">
              <i class="pi pi-search" />
              <InputText
                v-model="search"
                placeholder="Buscar por nombre o usuario…"
                class="w-full"
                @input="buscar"
              />
            </span>
            <Select
              v-model="perfilFiltro"
              :options="['', ...roles]"
              placeholder="Filtrar por perfil"
              @change="aplicarFiltros"
              :showClear="true"
            >
              <template #value="{ value }">
                <span v-if="value" class="text-sm">{{ value }}</span>
                <span v-else class="text-surface-400 text-sm">Filtrar por perfil</span>
              </template>
            </Select>
            <Select
              v-model="entidadFiltro"
              :options="entidades"
              optionLabel="nombre"
              optionValue="id"
              placeholder="Filtrar por entidad"
              @change="aplicarFiltros"
              :showClear="true"
            >
              <template #value="{ value }">
                <span v-if="value" class="text-sm">{{ entidadNombre(value) }}</span>
                <span v-else class="text-surface-400 text-sm">Filtrar por entidad</span>
              </template>
            </Select>
            <Select
              v-model="estadoFiltro"
              :options="[
                { value: '', label: 'Todos los estados' },
                { value: 'activo', label: 'Activo' },
                { value: 'bloqueado', label: 'Bloqueado' },
                { value: 'temporal', label: 'Contraseña temporal' },
              ]"
              optionLabel="label"
              optionValue="value"
              @change="aplicarFiltros"
            >
              <template #value="{ value }">
                <span v-if="value" class="text-sm">{{ value === 'activo' ? 'Activo' : value === 'bloqueado' ? 'Bloqueado' : value === 'temporal' ? 'Contraseña temporal' : 'Todos los estados' }}</span>
                <span v-else class="text-surface-400 text-sm">Todos los estados</span>
              </template>
            </Select>
          </div>
          <Button
            v-if="can('usuarios.crear')"
            icon="pi pi-plus"
            label="Nuevo usuario"
            @click="abrirCrear"
          />
        </div>

        <!-- Tabla -->
        <DataTable
          :value="usuarios.data"
          :rows="15"
          :paginator="true"
          :totalRecords="usuarios.total"
          :lazy="true"
          :first="(usuarios.current_page - 1) * usuarios.per_page"
          @page="onPage"
          sortMode="multiple"
          stripedRows
          size="small"
          class="p-datatable-sm"
         paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros">
          <Column field="username" header="Usuario" sortable />
          <Column header="Entidad">
            <template #body="{ data }">
              <span class="text-sm">{{ data.entidad?.abreviatura ?? '—' }}</span>
            </template>
          </Column>
          <Column header="Perfil">
            <template #body="{ data }">
              <span v-for="rol in data.roles" :key="rol.id">
                <Tag :value="rol.name" severity="info" class="mr-1" />
              </span>
              <span v-if="!data.roles.length" class="text-surface-400 text-sm">—</span>
            </template>
          </Column>
          <Column header="Estado">
            <template #body="{ data }">
              <Tag
                v-if="data.activo === false"
                value="Inactivo"
                severity="secondary"
              />
              <Tag
                v-else-if="data.bloqueado || data.intentos_fallidos >= 5"
                value="Bloqueado"
                severity="danger"
              />
              <Tag
                v-else-if="data.password_temporal"
                value="Contraseña temporal"
                severity="warn"
              />
              <Tag
                v-else
                value="Activo"
                severity="success"
              />
            </template>
          </Column>
          <Column header="Último acceso">
            <template #body="{ data }">
              <span class="text-sm text-surface-500">{{ formatoFecha(data.ultimo_login) }}</span>
            </template>
          </Column>
          <Column header="Acciones" :exportable="false">
            <template #body="{ data }">
              <div class="flex gap-1">
                <Button
                  v-if="can('usuarios.desbloquear')"
                  :icon="data.activo !== false ? 'pi pi-eye' : 'pi pi-eye-slash'"
                  :severity="data.activo !== false ? 'success' : 'secondary'"
                  text
                  rounded
                  size="small"
                  @click="toggleActivo(data)"
                  v-tooltip.left="(data.activo !== false ? 'Desactivar' : 'Activar') + ' usuario'"
                />
                <Button
                  v-if="can('usuarios.desbloquear')"
                  :icon="data.bloqueado ? 'pi pi-lock-open' : 'pi pi-lock'"
                  :severity="data.bloqueado ? 'warn' : 'secondary'"
                  text
                  rounded
                  size="small"
                  @click="toggleBloqueado(data)"
                  v-tooltip.left="data.bloqueado ? 'Desbloquear' : 'Bloquear'"
                />
                <Button
                  v-if="can('usuarios.editar')"
                  icon="pi pi-pencil"
                  severity="secondary"
                  text
                  rounded
                  size="small"
                  @click="abrirEditar(data)"
                  v-tooltip.left="'Editar'"
                />
                <Button
                  v-if="can('usuarios.restablecer')"
                  icon="pi pi-key"
                  severity="info"
                  text
                  rounded
                  size="small"
                  @click="abrirRestablecer(data)"
                  v-tooltip.left="'Restablecer'"
                />
                <Button
                  v-if="can('usuarios.eliminar')"
                  icon="pi pi-trash"
                  severity="danger"
                  text
                  rounded
                  size="small"
                  @click="abrirEliminar(data)"
                  v-tooltip.left="'Eliminar'"
                />
              </div>
            </template>
          </Column>
          <template #empty>
            <div class="text-center py-8 text-surface-400">
              <i class="pi pi-users text-3xl mb-2 block" />
              No se encontraron usuarios.
            </div>
          </template>
        </DataTable>
      </template>
    </Card>

    <!-- Modal crear/editar -->
    <Dialog
      v-model:visible="modalForm"
      :header="editando ? 'Editar usuario' : 'Nuevo usuario'"
      :modal="true"
      class="w-full max-w-lg"
    >
      <form class="space-y-4" @submit.prevent="guardarForm">
        <div>
          <label class="block text-sm font-medium text-surface-700 mb-1">Nombre completo</label>
          <InputText v-model="form.name" class="w-full" :class="{ 'p-invalid': form.errors.name }" />
          <small v-if="form.errors.name" class="text-red-500">{{ form.errors.name }}</small>
        </div>
        <div>
          <label class="block text-sm font-medium text-surface-700 mb-1">Usuario</label>
          <InputText v-model="form.username" class="w-full uppercase" :class="{ 'p-invalid': form.errors.username }" />
          <small v-if="form.errors.username" class="text-red-500">{{ form.errors.username }}</small>
        </div>
        <div>
          <label class="block text-sm font-medium text-surface-700 mb-1">Correo (opcional)</label>
          <InputText v-model="form.email" type="email" class="w-full" :class="{ 'p-invalid': form.errors.email }" />
          <small v-if="form.errors.email" class="text-red-500">{{ form.errors.email }}</small>
        </div>
        <div v-if="!editando">
          <label class="block text-sm font-medium text-surface-700 mb-1">Contraseña temporal</label>
          <Password v-model="form.password" :feedback="false" toggleMask class="w-full" inputClass="w-full" fluid />
          <small class="text-surface-400 block mt-1">El usuario deberá cambiarla en su primer acceso.</small>
          <small v-if="form.errors.password" class="text-red-500 block">{{ form.errors.password }}</small>
        </div>
        <div>
          <label class="block text-sm font-medium text-surface-700 mb-1">Perfil</label>
          <Select
            v-model="form.role"
            :options="roles"
            placeholder="Seleccione un perfil"
            class="w-full"
            :class="{ 'p-invalid': form.errors.role }"
          />
          <small v-if="form.errors.role" class="text-red-500">{{ form.errors.role }}</small>
        </div>
        <div>
          <label class="block text-sm font-medium text-surface-700 mb-1">Entidad</label>
          <Select
            v-model="form.id_entidad"
            :options="entidades"
            optionLabel="nombre"
            optionValue="id"
            placeholder="Seleccione una entidad"
            class="w-full"
            :disabled="!esAdmin"
            :class="{ 'p-invalid': form.errors.id_entidad }"
          />
          <small v-if="form.errors.id_entidad" class="text-red-500">{{ form.errors.id_entidad }}</small>
          <small v-if="!esAdmin && miEntidadId" class="text-surface-400 block mt-1">
            Solo el administrador puede cambiar la entidad.
          </small>
        </div>
        <div v-if="entidadesAccesoOptions.length">
          <label class="block text-sm font-medium text-surface-700 mb-1">Entidades a las que tiene acceso</label>
          <small v-if="entidadesAccesoOptions.length === 1" class="text-surface-400 block mb-1">
            La entidad principal no tiene subordinadas, solo se muestra ella misma.
          </small>
          <div class="grid grid-cols-2 gap-2 max-h-48 overflow-y-auto border border-surface-300 rounded p-2">
            <div v-for="ent in entidadesAccesoOptions" :key="ent.id" class="flex items-center gap-2">
              <Checkbox
                :inputId="'ent_' + ent.id"
                :value="ent.id"
                v-model="form.entidades"
                :binary="false"
              />
              <label :for="'ent_' + ent.id" class="text-sm cursor-pointer">{{ ent.nombre }}</label>
            </div>
          </div>
        </div>
      </form>
      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="cerrarModales" />
        <Button
          :label="form.processing ? 'Guardando…' : 'Guardar'"
          :loading="form.processing"
          @click="guardarForm"
        />
      </template>
    </Dialog>

    <!-- Modal restablecer contraseña -->
    <Dialog
      v-model:visible="modalReset"
      header="Restablecer contraseña"
      :modal="true"
      class="w-full max-w-md"
    >
      <p class="text-sm text-surface-600 mb-4">
        Indique la contraseña temporal para <strong>{{ seleccionado?.username }}</strong>.
        Deberá cambiarla en su próximo acceso. También se desbloqueará la cuenta.
      </p>
      <Password v-model="formReset.password" :feedback="false" toggleMask class="w-full" inputClass="w-full" fluid />
      <small v-if="formReset.errors.password" class="text-red-500 block mt-1">{{ formReset.errors.password }}</small>
      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="cerrarModales" />
        <Button
          label="Restablecer"
          :loading="formReset.processing"
          @click="restablecer"
        />
      </template>
    </Dialog>

    <!-- Modal eliminar -->
    <Dialog
      v-model:visible="modalEliminar"
      header="Eliminar usuario"
      :modal="true"
      class="w-full max-w-sm"
    >
      <p class="text-sm text-surface-600">
        ¿Está seguro de eliminar al usuario <strong>{{ seleccionado?.username }}</strong> ({{ seleccionado?.name }})?
      </p>
      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="cerrarModales" />
        <Button
          label="Eliminar"
          severity="danger"
          :loading="formEliminar.processing"
          @click="eliminar"
        />
      </template>
    </Dialog>
  </AppLayout>
</template>

<script setup>
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  usuarios: Object,
  roles: Array,
  entidades: Array,
  esAdmin: Boolean,
  miEntidadId: Number,
  filters: Object,
});

const page = usePage();
const permissions = computed(() => page.props.auth?.permissions ?? []);
const can = (permiso) => permissions.value.includes(permiso);

const entidadesAccesoOptions = computed(() => {
  if (!form.id_entidad) return [];
  const hijas = props.entidades.filter((e) => e.parent_id === form.id_entidad);
  return hijas.length > 0 ? hijas : props.entidades.filter((e) => e.id === form.id_entidad);
});

const search = ref(props.filters?.search ?? '');
const perfilFiltro = ref(props.filters?.perfil ?? '');
const entidadFiltro = ref(props.filters?.entidad ?? '');
const estadoFiltro = ref(props.filters?.estado ?? '');

const entidadNombre = (id) => {
  const ent = props.entidades.find((e) => e.id === id);
  return ent ? ent.nombre : '';
};

const paramsFiltro = () => {
  const p = { search: search.value };
  if (perfilFiltro.value) p.perfil = perfilFiltro.value;
  if (entidadFiltro.value) p.entidad = entidadFiltro.value;
  if (estadoFiltro.value) p.estado = estadoFiltro.value;
  return p;
};

let timer = null;
const buscar = () => {
  clearTimeout(timer);
  timer = setTimeout(() => {
    router.get(route('usuarios.index'), paramsFiltro(), { preserveState: true, replace: true });
  }, 300);
};

const aplicarFiltros = () => {
  router.get(route('usuarios.index'), paramsFiltro(), { preserveState: true, replace: true });
};

const onPage = (event) => {
  router.get(route('usuarios.index'), { ...paramsFiltro(), page: event.page + 1 }, { preserveState: true, replace: true });
};

const modalForm = ref(false);
const modalReset = ref(false);
const modalEliminar = ref(false);
const editando = ref(false);
const seleccionado = ref(null);

const form = useForm({
  name: '',
  username: '',
  email: '',
  password: '',
  role: '',
  id_entidad: null,
  entidades: [],
  idgrupo: null,
});

const formReset = useForm({ password: '' });
const formEliminar = useForm({});

const abrirCrear = () => {
  editando.value = false;
  seleccionado.value = null;
  form.reset();
  form.clearErrors();
  form.id_entidad = props.miEntidadId;
  modalForm.value = true;
};

const abrirEditar = (usuario) => {
  editando.value = true;
  seleccionado.value = usuario;
  form.name = usuario.name;
  form.username = usuario.username;
  form.email = usuario.email ?? '';
  form.password = '';
  form.role = usuario.roles[0]?.name ?? '';
  form.id_entidad = usuario.id_entidad ?? props.miEntidadId;
  form.entidades = usuario.entidades?.map((e) => e.id) ?? [];
  form.idgrupo = usuario.idgrupo;
  form.clearErrors();
  modalForm.value = true;
};

const guardarForm = () => {
  if (editando.value) {
    form.put(route('usuarios.update', seleccionado.value.id), {
      onSuccess: () => cerrarModales(),
    });
  } else {
    form.post(route('usuarios.store'), {
      onSuccess: () => cerrarModales(),
    });
  }
};

const abrirRestablecer = (usuario) => {
  seleccionado.value = usuario;
  formReset.reset();
  formReset.clearErrors();
  modalReset.value = true;
};

const restablecer = () => {
  formReset.post(route('usuarios.restablecer', seleccionado.value.id), {
    onSuccess: () => cerrarModales(),
  });
};

const abrirEliminar = (usuario) => {
  seleccionado.value = usuario;
  modalEliminar.value = true;
};

const eliminar = () => {
  formEliminar.delete(route('usuarios.destroy', seleccionado.value.id), {
    onSuccess: () => cerrarModales(),
  });
};

const desbloquear = (usuario) => {
  router.post(route('usuarios.desbloquear', usuario.id));
};

const toggleActivo = (usuario) => {
  router.post(route('usuarios.toggle-activo', usuario.id));
};

const toggleBloqueado = (usuario) => {
  router.post(route('usuarios.toggle-bloqueado', usuario.id));
};

const cerrarModales = () => {
  modalForm.value = false;
  modalReset.value = false;
  modalEliminar.value = false;
  seleccionado.value = null;
};

const formatoFecha = (fecha) => {
  if (!fecha) return 'Nunca';
  return new Date(fecha).toLocaleString('es');
};
</script>
