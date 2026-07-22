<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-indigo-900 via-indigo-800 to-gray-900 px-4">
    <div class="w-full max-w-md">
      <div class="bg-white rounded-2xl shadow-2xl p-8">
        <div class="text-center mb-8">
          <h1 class="text-3xl font-bold text-indigo-600">EMCARGA</h1>
          <p class="text-gray-500 mt-2">Sistema de Gestión Empresarial</p>
        </div>

        <form @submit.prevent="submit">
          <div class="mb-5">
            <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
              Usuario
            </label>
            <input
              id="username"
              v-model="form.username"
              type="text"
              required
              autofocus
              autocomplete="username"
              class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              :class="{ 'border-red-500': form.errors.username }"
            />
            <p v-if="form.errors.username" class="mt-1 text-sm text-red-600">
              {{ form.errors.username }}
            </p>
          </div>

          <div class="mb-6">
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
              Contraseña
            </label>
            <input
              id="password"
              v-model="form.password"
              type="password"
              required
              autocomplete="current-password"
              class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              :class="{ 'border-red-500': form.errors.password }"
            />
            <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">
              {{ form.errors.password }}
            </p>
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="w-full py-2.5 px-4 rounded-lg bg-indigo-600 text-white font-semibold hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 transition"
          >
            {{ form.processing ? 'Entrando…' : 'Entrar' }}
          </button>
        </form>
      </div>

      <p class="text-center text-indigo-200 text-sm mt-6">EMCARGA &copy; 2026</p>
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
