<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-emerald-900 via-emerald-800 to-gray-900 px-4">
    <div class="w-full max-w-md">
      <div class="bg-white rounded-2xl shadow-2xl p-8">
        <div class="text-center mb-8">
          <div class="w-14 h-14 rounded-xl bg-emerald-600 flex items-center justify-center text-white font-bold text-2xl mx-auto mb-4">
            E
          </div>
          <h1 class="text-3xl font-bold text-surface-900">EMCARGA</h1>
          <p class="text-surface-500 mt-1">Sistema de Gestión Empresarial</p>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
          <div>
            <label for="username" class="block text-sm font-medium text-surface-700 mb-1">
              Usuario
            </label>
            <InputText
              id="username"
              v-model="form.username"
              type="text"
              required
              autofocus
              autocomplete="username"
              class="w-full"
              :class="{ 'p-invalid': form.errors.username }"
            />
            <small v-if="form.errors.username" class="text-red-500">{{ form.errors.username }}</small>
          </div>

          <div>
            <label for="password" class="block text-sm font-medium text-surface-700 mb-1">
              Contraseña
            </label>
            <Password
              id="password"
              v-model="form.password"
              :feedback="false"
              toggleMask
              required
              autocomplete="current-password"
              class="w-full"
              :class="{ 'p-invalid': form.errors.password }"
              inputClass="w-full"
              fluid
            />
            <small v-if="form.errors.password" class="text-red-500">{{ form.errors.password }}</small>
          </div>

          <Button
            type="submit"
            :loading="form.processing"
            :label="form.processing ? 'Entrando…' : 'Entrar'"
            class="w-full"
          />
        </form>
      </div>

      <p class="text-center text-emerald-200/60 text-sm mt-6">EMCARGA &copy; 2026</p>
    </div>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

const form = useForm({
  username: '',
  password: '',
});

const submit = () => {
  form.post(route('login.store'), {
    onFinish: () => form.reset('password'),
  });
};
</script>
