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

            <button
              class="p-2 rounded-md text-surface-400 hover:text-surface-600 hover:bg-surface-100 relative"
              @click="abrirNotificaciones"
              v-tooltip.bottom="'Notificaciones'"
            >
              <i class="pi pi-bell text-lg" />
              <Badge v-if="notificacionesPendientes > 0" :value="notificacionesPendientes" severity="danger" class="absolute -top-1 -right-1" />
            </button>

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
const isDark = ref(false);

const isMobile = ref(false);
const checkMobile = () => {
  isMobile.value = window.innerWidth < 1024;
  if (isMobile.value) sidebarOpen.value = false;
};

const handleClickOutside = (e) => {
  if (userMenuRef.value && !userMenuRef.value.contains(e.target)) {
    userMenuOpen.value = false;
  }
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

  // Conectar Echo para tiempo real (Fase 4.7+)
  if (window.Echo && user.value) {
    window.Echo.private('test')
      .listen('.TestBroadcast', (e) => {
        toast.add({ severity: 'info', summary: 'Tiempo real', detail: e.message, life: 4000 });
      });

    window.Echo.private(`App.Models.User.${user.value.id}`)
      .notification((notification) => {
        notificacionesPendientes.value++;
        toast.add({ severity: 'info', summary: notification.title || 'Notificación', detail: notification.body || '', life: 5000 });
      });
  }
});

onUnmounted(() => {
  window.removeEventListener('resize', checkMobile);
  document.removeEventListener('click', handleClickOutside);

  if (window.Echo) {
    window.Echo.leaveChannel('test');
    if (user.value) {
      window.Echo.leaveChannel(`App.Models.User.${user.value.id}`);
    }
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

const notificacionesPendientes = ref(0);

const abrirNotificaciones = () => {
  // TODO: Fase 4.8
};

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
