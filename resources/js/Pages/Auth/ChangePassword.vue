<template>
  <AppLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Cambiar contraseña
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
        <div
          v-if="$page.props.flash.warning"
          class="mb-4 rounded-lg bg-yellow-50 border border-yellow-200 p-4 text-sm text-yellow-800"
        >
          {{ $page.props.flash.warning }}
        </div>

        <div class="bg-white shadow rounded-lg p-6">
          <ul class="mb-6 text-sm text-gray-600 list-disc pl-5 space-y-1">
            <li>Mínimo 6 caracteres</li>
            <li>Al menos una mayúscula, una minúscula, un número y un carácter especial (!@#.$%?&amp;*()_-+=)</li>
            <li>No puede coincidir con contraseñas utilizadas anteriormente</li>
          </ul>

          <form @submit.prevent="submit">
            <div class="mb-5">
              <label for="password_actual" class="block text-sm font-medium text-gray-700 mb-1">
                Contraseña actual
              </label>
              <input
                id="password_actual"
                v-model="form.password_actual"
                type="password"
                required
                autocomplete="current-password"
                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                :class="{ 'border-red-500': form.errors.password_actual }"
              />
              <p v-if="form.errors.password_actual" class="mt-1 text-sm text-red-600">
                {{ form.errors.password_actual }}
              </p>
            </div>

            <div class="mb-5">
              <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                Nueva contraseña
              </label>
              <input
                id="password"
                v-model="form.password"
                type="password"
                required
                autocomplete="new-password"
                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                :class="{ 'border-red-500': form.errors.password }"
              />
              <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">
                {{ form.errors.password }}
              </p>
            </div>

            <div class="mb-6">
              <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                Confirmar nueva contraseña
              </label>
              <input
                id="password_confirmation"
                v-model="form.password_confirmation"
                type="password"
                required
                autocomplete="new-password"
                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>

            <button
              type="submit"
              :disabled="form.processing"
              class="w-full py-2.5 px-4 rounded-lg bg-indigo-600 text-white font-semibold hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 transition"
            >
              {{ form.processing ? 'Actualizando…' : 'Actualizar contraseña' }}
            </button>
          </form>
        </div>
      </div>
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
