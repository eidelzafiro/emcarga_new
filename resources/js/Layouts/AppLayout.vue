<template>
  <div class="min-h-screen bg-surface-50">
    <Toast position="top-right" />

    <!-- Mobile overlay -->
    <div
      v-if="sidebarOpen && isMobile"
      class="fixed inset-0 z-20 bg-black/50 lg:hidden"
      @click="sidebarOpen = false"
    />

    <!-- Sidebar -->
    <aside
      class="fixed top-0 left-0 z-30 h-full bg-white border-r border-surface-200 shadow-sm sidebar-transition flex flex-col"
      :class="sidebarOpen ? 'w-64' : 'w-0 lg:w-16'"
    >
      <div class="flex items-center h-16 px-4 border-b border-surface-200 shrink-0">
        <div v-if="sidebarOpen || !isMobile" class="flex items-center gap-3 overflow-hidden">
          <div class="w-8 h-8 rounded-lg bg-emerald-600 flex items-center justify-center text-white font-bold text-sm shrink-0">
            E
          </div>
          <span v-show="sidebarOpen" class="font-semibold text-surface-900 whitespace-nowrap">EMCARGA</span>
        </div>
        <button
          v-if="sidebarOpen"
          class="ml-auto p-1.5 rounded-md text-surface-400 hover:text-surface-600 hover:bg-surface-100 lg:hidden"
          @click="sidebarOpen = false"
        >
          <i class="pi pi-times text-lg" />
        </button>
      </div>

      <nav class="flex-1 overflow-y-auto py-2 px-2">
        <PanelMenu :model="menuItems" class="border-0 !bg-transparent" />
      </nav>

      <div class="hidden lg:flex items-center justify-center h-12 border-t border-surface-200 shrink-0">
        <button
          class="p-1.5 rounded-md text-surface-400 hover:text-surface-600 hover:bg-surface-100"
          @click="toggleSidebar"
          v-tooltip.right="sidebarOpen ? 'Colapsar menú' : 'Expandir menú'"
        >
          <i class="pi" :class="sidebarOpen ? 'pi-chevron-left' : 'pi-chevron-right'" />
        </button>
      </div>
    </aside>

    <!-- Main content -->
    <div
      class="main-content-transition flex flex-col min-h-screen"
      :class="sidebarOpen ? 'lg:ml-64' : 'lg:ml-16'"
    >
      <header class="sticky top-0 z-10 bg-white/95 backdrop-blur-sm border-b border-surface-200 shadow-xs">
        <div class="flex items-center justify-between h-16 px-4 lg:px-6">
          <div class="flex items-center gap-3">
            <button
              class="p-1.5 rounded-md text-surface-500 hover:text-surface-700 hover:bg-surface-100"
              @click="sidebarOpen = !sidebarOpen"
            >
              <i class="pi pi-bars text-lg" />
            </button>
            <h1 v-if="pageTitle" class="text-lg font-semibold text-surface-800 hidden sm:block">
              {{ pageTitle }}
            </h1>
          </div>

          <div class="flex items-center gap-2">
            <button
              class="p-2 rounded-md text-surface-400 hover:text-surface-600 hover:bg-surface-100"
              @click="toggleDarkMode"
              v-tooltip.bottom="isDark ? 'Modo claro' : 'Modo oscuro'"
            >
              <i class="pi" :class="isDark ? 'pi-sun' : 'pi-moon'" />
            </button>

            <!-- Notificaciones -->
            <div class="relative" ref="notificacionesRef">
              <button
                class="p-2 rounded-md text-surface-400 hover:text-surface-600 hover:bg-surface-100 relative"
                @click="toggleNotificaciones"
                v-tooltip.bottom="'Notificaciones'"
              >
                <i class="pi pi-bell text-lg" />
                <Badge v-if="pendientes > 0" :value="pendientes" severity="danger" class="absolute -top-1 -right-1" />
              </button>

              <div
                v-if="notificacionesAbiertas"
                class="absolute right-0 top-full mt-1 w-80 sm:w-96 bg-white rounded-lg shadow-lg border border-surface-200 z-50"
              >
                <div class="flex items-center justify-between px-4 py-3 border-b border-surface-100">
                  <h3 class="text-sm font-semibold text-surface-700">Notificaciones</h3>
                  <button
                    v-if="pendientes > 0"
                    class="text-xs text-emerald-600 hover:text-emerald-700 font-medium"
                    @click="marcarTodasLeidas"
                  >
                    Marcar todas leídas
                  </button>
                </div>

                <div class="max-h-80 overflow-y-auto">
                  <div v-if="notificaciones.length === 0" class="p-6 text-center text-sm text-surface-400">
                    <i class="pi pi-inbox text-2xl mb-2 block" />
                    No hay notificaciones
                  </div>

                  <div
                    v-for="notif in notificaciones"
                    :key="notif.id"
                    class="flex gap-3 px-4 py-3 cursor-pointer border-b border-surface-50 last:border-0 hover:bg-surface-50 transition-colors"
                    :class="{ 'bg-emerald-50/50': !notif.leida }"
                    @click="notif.url ? visitar(notif.url) : marcarLeida(notif)"
                  >
                    <div
                      class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 mt-0.5"
                      :class="claseIcono(notif.tipo)"
                    >
                      <i :class="notif.icono" class="text-white text-sm" />
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-medium text-surface-800 truncate">{{ notif.titulo }}</p>
                      <p class="text-xs text-surface-500 truncate">{{ notif.cuerpo }}</p>
                      <p class="text-xs text-surface-400 mt-0.5">{{ notif.creada }}</p>
                    </div>
                    <div v-if="!notif.leida" class="w-2 h-2 rounded-full bg-emerald-500 shrink-0 mt-2" />
                  </div>
                </div>
              </div>
            </div>

            <!-- Usuario -->
            <div class="relative" ref="userMenuRef">
              <button
                class="flex items-center gap-2 p-1.5 rounded-md hover:bg-surface-100"
                @click="userMenuOpen = !userMenuOpen"
              >
                <Avatar :label="iniciales" shape="circle" class="!bg-emerald-600 !text-white" size="small" />
                <span class="hidden sm:block text-sm font-medium text-surface-700">{{ user?.name }}</span>
                <i class="pi pi-chevron-down text-xs text-surface-400" />
              </button>

              <div
                v-if="userMenuOpen"
                class="absolute right-0 top-full mt-1 w-56 bg-white rounded-lg shadow-lg border border-surface-200 py-1 z-50"
              >
                <div class="px-4 py-2 border-b border-surface-100">
                  <p class="text-sm font-medium text-surface-900">{{ user?.name }}</p>
                  <p class="text-xs text-surface-500">{{ user?.email || user?.username }}</p>
                </div>
                <Link
                  :href="route('password.edit')"
                  class="flex items-center gap-2 px-4 py-2 text-sm text-surface-700 hover:bg-surface-50"
                >
                  <i class="pi pi-key text-surface-400" />
                  Cambiar contraseña
                </Link>
                <hr class="border-surface-100 my-1">
                <Link
                  :href="route('logout')"
                  method="post"
                  as="button"
                  class="flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50 w-full text-left"
                >
                  <i class="pi pi-sign-out" />
                  Cerrar sesión
                </Link>
              </div>
            </div>
          </div>
        </div>
      </header>

      <main class="flex-1 p-4 lg:p-6">
        <slot />
      </main>

      <footer class="px-4 lg:px-6 py-3 text-center text-xs text-surface-400 border-t border-surface-100">
        EMCARGA &copy; {{ new Date().getFullYear() }}
      </footer>
    </div>
  </div>
</template>

<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch, onMounted, onUnmounted } from 'vue';
import { route } from 'ziggy-js';
import { useToast } from 'primevue/usetoast';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const menu = computed(() => page.props.menu ?? []);
const flash = computed(() => page.props.flash ?? {});
const toast = useToast();

const sidebarOpen = ref(true);
const userMenuOpen = ref(false);
const userMenuRef = ref(null);
const notificacionesRef = ref(null);
const isDark = ref(false);
const notificaciones = ref([]);
const pendientes = ref(0);
const notificacionesAbiertas = ref(false);

const isMobile = ref(false);
const checkMobile = () => {
  isMobile.value = window.innerWidth < 1024;
  if (isMobile.value) sidebarOpen.value = false;
};

const handleClickOutside = (e) => {
  if (userMenuRef.value && !userMenuRef.value.contains(e.target)) {
    userMenuOpen.value = false;
  }
  if (notificacionesRef.value && !notificacionesRef.value.contains(e.target)) {
    notificacionesAbiertas.value = false;
  }
};

const cargarNotificaciones = async () => {
  if (!user.value) return;
  try {
    const res = await fetch(route('notificaciones.index'));
    const data = await res.json();
    notificaciones.value = data.items;
    pendientes.value = data.pendientes;
  } catch {
    // Silencioso
  }
};

const marcarLeida = async (notif) => {
  if (notif.leida) return;
  try {
    await fetch(route('notificaciones.leer', notif.id), { method: 'POST' });
    notif.leida = true;
    pendientes.value = Math.max(0, pendientes.value - 1);
  } catch {
    // Silencioso
  }
};

const marcarTodasLeidas = async () => {
  try {
    await fetch(route('notificaciones.leer-todas'), { method: 'POST' });
    notificaciones.value.forEach((n) => (n.leida = true));
    pendientes.value = 0;
  } catch {
    // Silencioso
  }
};

const toggleNotificaciones = () => {
  notificacionesAbiertas.value = !notificacionesAbiertas.value;
  if (notificacionesAbiertas.value && notificaciones.value.length === 0) {
    cargarNotificaciones();
  }
};

const visitar = (url) => {
  notificacionesAbiertas.value = false;
  router.visit(url);
};

const claseIcono = (tipo) => {
  return {
    success: 'bg-emerald-500',
    error: 'bg-red-500',
    warning: 'bg-amber-500',
    info: 'bg-blue-500',
  }[tipo] || 'bg-surface-400';
};

onMounted(() => {
  checkMobile();
  window.addEventListener('resize', checkMobile);
  document.addEventListener('click', handleClickOutside);

  const stored = localStorage.getItem('theme');
  if (stored === 'dark' || (!stored && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    isDark.value = true;
    document.documentElement.classList.add('p-dark');
  }

  cargarNotificaciones();

  if (window.Echo && user.value) {
    window.Echo.private(`App.Models.User.${user.value.id}`)
      .notification((notification) => {
        pendientes.value++;
        notificaciones.value.unshift({
          id: notification.id,
          titulo: notification.titulo || 'Notificación',
          cuerpo: notification.cuerpo || '',
          tipo: notification.tipo || 'info',
          url: notification.url || null,
          icono: notification.icono || 'pi pi-info-circle',
          leida: false,
          creada: 'Ahora',
        });
        toast.add({ severity: notification.tipo || 'info', summary: notification.titulo || 'Notificación', detail: notification.cuerpo || '', life: 5000 });
      });
  }
});

onUnmounted(() => {
  window.removeEventListener('resize', checkMobile);
  document.removeEventListener('click', handleClickOutside);

  if (window.Echo && user.value) {
    window.Echo.leaveChannel(`App.Models.User.${user.value.id}`);
  }
});

watch(isDark, (val) => {
  if (val) {
    document.documentElement.classList.add('p-dark');
    localStorage.setItem('theme', 'dark');
  } else {
    document.documentElement.classList.remove('p-dark');
    localStorage.setItem('theme', 'light');
  }
});

watch(flash, (val) => {
  if (val.success) {
    toast.add({ severity: 'success', summary: 'Éxito', detail: val.success, life: 3000 });
  }
  if (val.error) {
    toast.add({ severity: 'error', summary: 'Error', detail: val.error, life: 5000 });
  }
  if (val.warning) {
    toast.add({ severity: 'warn', summary: 'Advertencia', detail: val.warning, life: 4000 });
  }
}, { deep: true, immediate: true });

const toggleDarkMode = () => {
  isDark.value = !isDark.value;
};

const toggleSidebar = () => {
  sidebarOpen.value = !sidebarOpen.value;
};

const iniciales = computed(() => {
  if (!user.value?.name) return 'U';
  return user.value.name.split(' ').map((w) => w[0]).join('').slice(0, 2).toUpperCase();
});

const pageTitle = computed(() => {
  const title = page.props.title;
  return title || '';
});

function transformarMenu(items, parentLabel = '') {
  return items.map((item) => {
    const icono = item.icono || 'pi pi-circle';
    const label = item.label;

    if (item.children && item.children.length > 0) {
      return {
        key: `${parentLabel}/${label}`,
        label,
        icon: icono,
        items: transformarMenu(item.children, `${parentLabel}/${label}`),
      };
    }

    return {
      key: `${parentLabel}/${label}`,
      label,
      icon: icono,
      command: () => {
        if (item.url) {
          router.visit(item.url);
        }
      },
    };
  });
}

const menuItems = computed(() => transformarMenu(menu.value));
</script>
