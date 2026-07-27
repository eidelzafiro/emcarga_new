# Guía de Integración — Diseño ZAS para EMCARGA

## Archivos modificados/creados

### 1. CSS y Tema
| Archivo | Acción |
|---------|--------|
| `resources/css/app.css` | **Actualizado** — se añadieron colores `zas-*`, clases utilitarias (`.kpi-card`, `.table-header`, `.status-badge-*`), y fuente Nunito como predeterminada |

### 2. Vue SFC (Inertia) — Principales
| Archivo | Acción |
|---------|--------|
| `resources/js/Pages/Auth/Login.vue` | **Reemplazado** — Nuevo diseño de dos columnas con fondo degrade y panel de login minimalista |
| `resources/js/Pages/Dashboard.vue` | **Reemplazado** — Dashboard completo con KPIs, gráfico Chart.js, tabla de movimientos y actividad reciente |
| `resources/js/Layouts/AppLayout.vue` | **Reemplazado** — Layout refinado con colores grises en lugar de surface, transiciones sutiles |

### 3. Controladores y Servicios
| Archivo | Acción |
|---------|--------|
| `app/Http/Controllers/DashboardController.php` | **Actualizado** — Se añadieron datos mock de `movimientos` y `actividadReciente` |
| `app/Services/KpiService.php` | **Actualizado** — Se redujo a 4 KPIs principales y se añadió campo `subtexto` |

### 4. Blade (Alternativa sin Inertia)
| Archivo | Acción |
|---------|--------|
| `resources/views/layouts/auth.blade.php` | **Nuevo** — Layout para páginas de autenticación |
| `resources/views/layouts/dashboard.blade.php` | **Nuevo** — Layout con sidebar y header para dashboard |
| `resources/views/auth/login.blade.php` | **Nuevo** — Login en Blade (mismo diseño que Vue) |
| `resources/views/dashboard/index.blade.php` | **Nuevo** — Dashboard en Blade con Chart.js vía CDN |

### 5. Documentación
| Archivo | Acción |
|---------|--------|
| `ANALYSIS.md` | **Nuevo** — Análisis detallado del diseño ZAS |

---

## Instrucciones de integración

### Paso 1: Instalar dependencias
```bash
npm install chart.js
```

### Paso 2: Compilar assets
```bash
npm run build
```

### Paso 3: Verificar rutas
Las rutas ya existen en `routes/web.php`:
- `GET /login` → `LoginController@create` → `Auth/Login.vue`
- `GET /dashboard` → `DashboardController@index` → `Dashboard.vue`

### Paso 4: Modo oscuro
El modo oscuro usa PrimeVue (`p-dark`). Se activa desde el header con el icono de luna/sol.

### Paso 5: WebSockets (KPIs en vivo)
El Dashboard escucha el canal `kpis` para actualizaciones en tiempo real vía Laravel Echo.

---

## Personalización

### Cambiar colores principales
En `resources/css/app.css`, modificar los valores de `--color-zas-*` o ajustar directamente las clases en los templates.

### Añadir más KPIs
Editar `app/Services/KpiService.php` — cada KPI sigue la estructura:
```php
[
    'label' => 'Etiqueta',
    'valor' => 'Valor',
    'subtexto' => 'Texto secundario',
    'icono' => 'pi pi-icono',
    'color' => 'bg-emerald-500',
]
```

### Añadir más movimientos o actividad reciente
Editar `app/Http/Controllers/DashboardController.php` en el método `index()`.

### Usar datos reales
Reemplazar el array `$movimientos` y `$actividadReciente` por consultas a la base de datos usando los modelos existentes.

---

## Estructura de componentes

```
resources/js/
├── Layouts/
│   └── AppLayout.vue          # Layout principal (sidebar + header + content)
├── Pages/
│   ├── Auth/
│   │   └── Login.vue          # Página de inicio de sesión
│   └── Dashboard.vue          # Página de dashboard
```

```
resources/views/
├── layouts/
│   ├── auth.blade.php         # Layout auth (Blade)
│   └── dashboard.blade.php    # Layout dashboard (Blade)
├── auth/
│   └── login.blade.php        # Login (Blade)
├── dashboard/
│   └── index.blade.php        # Dashboard (Blade)
└── app.blade.php              # Root layout Inertia (sin cambios)
```

---

## Notas técnicas

- **Tailwind CSS v4**: usa `@import 'tailwindcss'` en lugar de directives.
- **Chart.js**: se cargó como dependencia npm. Para Blade se usa CDN como alternativa.
- **PrimeVue**: los componentes como `PanelMenu`, `Avatar`, `Badge`, `Toast` se usan en el layout.
- **Iconos**: PrimeIcons vía `primeicons/primeicons.css`.
- **Tipografía**: Nunito cargada desde Google Fonts (definida como `--font-sans`).
