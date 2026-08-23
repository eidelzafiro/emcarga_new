<template>
  <AppLayout>
    <div class="max-w-xl mx-auto">
      <Card>
        <template #title>Mi perfil</template>
        <template #content>
          <!-- Avatar -->
          <div class="flex items-center gap-5 mb-8">
            <div class="relative shrink-0">
              <img
                v-if="user.url_avatar"
                :src="user.url_avatar"
                :alt="user.nombre_completo"
                class="w-24 h-24 rounded-full object-cover border border-surface-200 dark:border-surface-700"
              />
              <Avatar
                v-else
                :label="iniciales"
                shape="circle"
                size="xlarge"
                class="!bg-blue-600 !text-white !text-2xl"
              />
            </div>

            <div class="flex flex-col gap-2 items-start">
              <label class="cursor-pointer inline-flex items-center gap-2 px-3 py-2 rounded-md bg-surface-100 dark:bg-surface-800 text-sm font-medium text-surface-700 dark:text-surface-200 hover:bg-surface-200 dark:hover:bg-surface-700 transition-colors">
                <i class="pi pi-camera" />
                {{ user.url_avatar ? 'Cambiar avatar' : 'Subir avatar' }}
                <input
                  type="file"
                  accept="image/jpeg,image/png,image/webp"
                  class="hidden"
                  @change="subirAvatar"
                />
              </label>
              <Button
                v-if="user.url_avatar"
                label="Eliminar avatar"
                icon="pi pi-trash"
                text
                severity="danger"
                size="small"
                :loading="eliminando"
                @click="eliminarAvatar"
              />
              <small v-if="form.errors.avatar" class="text-red-500">{{ form.errors.avatar }}</small>
              <small class="text-xs text-surface-400">JPG, PNG o WEBP · máx. 2 MB · 1024×1024</small>
            </div>
          </div>

          <!-- Datos personales -->
          <form @submit.prevent="submit" class="space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label for="name" class="block text-sm font-medium text-surface-700 mb-1">Nombre</label>
                <InputText
                  id="name"
                  v-model="form.name"
                  maxlength="100"
                  fluid
                  :class="{ 'p-invalid': form.errors.name }"
                />
                <small v-if="form.errors.name" class="text-red-500">{{ form.errors.name }}</small>
              </div>

              <div>
                <label for="apellidos" class="block text-sm font-medium text-surface-700 mb-1">Apellidos</label>
                <InputText
                  id="apellidos"
                  v-model="form.apellidos"
                  maxlength="190"
                  fluid
                  :class="{ 'p-invalid': form.errors.apellidos }"
                />
                <small v-if="form.errors.apellidos" class="text-red-500">{{ form.errors.apellidos }}</small>
              </div>
            </div>

            <div>
              <label for="email" class="block text-sm font-medium text-surface-700 mb-1">Correo electrónico</label>
              <InputText
                id="email"
                v-model="form.email"
                type="email"
                maxlength="190"
                fluid
                :class="{ 'p-invalid': form.errors.email }"
              />
              <small v-if="form.errors.email" class="text-red-500">{{ form.errors.email }}</small>
              <small v-else class="block text-xs text-surface-400 mt-1">
                Se usa para la recuperación de contraseña (.cu → correo nacional, resto → Gmail).
              </small>
            </div>

            <div>
              <label for="username" class="block text-sm font-medium text-surface-400 mb-1">Usuario (no editable)</label>
              <InputText id="username" :model-value="user.username" disabled fluid />
            </div>

            <Button type="submit" label="Guardar cambios" :loading="form.processing" class="w-full" />
          </form>
        </template>
      </Card>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Avatar from 'primevue/avatar';

const user = computed(() => usePage().props.auth.user);

const form = useForm({
  name: user.value.name ?? '',
  apellidos: user.value.apellidos ?? '',
  email: user.value.email ?? '',
});

const eliminando = ref(false);

const iniciales = computed(() => {
  const fuente = user.value.nombre_completo || user.value.name || '';
  return fuente.split(' ').map((w) => w[0]).join('').slice(0, 2).toUpperCase() || 'U';
});

const submit = () => {
  form.put(route('profile.update'), {
    preserveScroll: true,
    onSuccess: () => router.reload({ only: ['auth'] }),
  });
};

function subirAvatar(evento) {
  const archivo = evento.target.files?.[0];
  if (!archivo) return;
  router.post(route('profile.avatar'), { _method: 'post', avatar: archivo }, {
    preserveScroll: true,
    onFinish: () => { evento.target.value = ''; },
    onSuccess: () => router.reload({ only: ['auth', 'flash'] }),
  });
}

async function eliminarAvatar() {
  eliminando.value = true;
  router.delete(route('profile.avatar.delete'), {
    preserveScroll: true,
    onFinish: () => { eliminando.value = false; },
    onSuccess: () => router.reload({ only: ['auth', 'flash'] }),
  });
}
</script>
