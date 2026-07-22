<template>
  <AppLayout>
    <div class="max-w-xl mx-auto">
      <Card>
        <template #title>Cambiar contraseña</template>
        <template #content>
          <div class="mb-6 p-3 bg-surface-50 rounded-lg border border-surface-200">
            <p class="text-sm font-medium text-surface-700 mb-2">Requisitos:</p>
            <ul class="text-sm text-surface-500 space-y-1 list-disc pl-5">
              <li>Mínimo 6 caracteres</li>
              <li>Al menos una mayúscula, una minúscula, un número y un carácter especial (!@#.$%?&amp;*()_-+=)</li>
              <li>No puede coincidir con contraseñas utilizadas anteriormente</li>
            </ul>
          </div>

          <form @submit.prevent="submit" class="space-y-5">
            <div>
              <label for="password_actual" class="block text-sm font-medium text-surface-700 mb-1">Contraseña actual</label>
              <Password
                id="password_actual"
                v-model="form.password_actual"
                :feedback="false"
                toggleMask
                class="w-full"
                :class="{ 'p-invalid': form.errors.password_actual }"
                inputClass="w-full"
                fluid
              />
              <small v-if="form.errors.password_actual" class="text-red-500">{{ form.errors.password_actual }}</small>
            </div>

            <div>
              <label for="password" class="block text-sm font-medium text-surface-700 mb-1">Nueva contraseña</label>
              <Password
                id="password"
                v-model="form.password"
                toggleMask
                class="w-full"
                :class="{ 'p-invalid': form.errors.password }"
                inputClass="w-full"
                fluid
              />
              <small v-if="form.errors.password" class="text-red-500">{{ form.errors.password }}</small>
            </div>

            <div>
              <label for="password_confirmation" class="block text-sm font-medium text-surface-700 mb-1">Confirmar nueva contraseña</label>
              <Password
                id="password_confirmation"
                v-model="form.password_confirmation"
                :feedback="false"
                toggleMask
                class="w-full"
                inputClass="w-full"
                fluid
              />
            </div>

            <Button
              type="submit"
              :loading="form.processing"
              label="Actualizar contraseña"
              class="w-full"
            />
          </form>
        </template>
      </Card>
    </div>
  </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/Layouts/AppLayout.vue';

const form = useForm({
  password_actual: '',
  password: '',
  password_confirmation: '',
});

const submit = () => {
  form.put(route('password.update'), {
    onFinish: () => form.reset(),
  });
};
</script>
