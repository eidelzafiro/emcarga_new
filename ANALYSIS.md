# Análisis de Diseño — ZAS Distributor

## 1. Paleta de Colores

Basado en el análisis del sitio ZAS Distributor (Next.js, diseño profesional):

| Rol | Color | Tailwind |
|-----|-------|----------|
| Fondo principal | Blanco `#FFFFFF` | `bg-white` |
| Fondo secundario | Gris muy claro `#F9FAFB` | `bg-gray-50` |
| Texto primario | Gris oscuro `#111827` | `text-gray-900` |
| Texto secundario | Gris medio `#6B7280` | `text-gray-500` |
| **Primario (navbar/cta)** | Azul profundo `#1D4ED8` | `blue-700` |
| **Primario hover** | Azul más oscuro `#1E40AF` | `blue-800` |
| Acento (success) | Verde esmeralda `#059669` | `emerald-600` |
| Acento (warning) | Ámbar `#D97706` | `amber-600` |
| Bordes | Gris claro `#E5E7EB` | `border-gray-200` |
| Sidebar bg | Blanco/gris `#FFFFFF`/`#F9FAFB` | `bg-white` |
| Sidebar texto activo | Azul `#1D4ED8` | `text-blue-700` |
| Header bg | Blanco con blur `#FFFFFF/95` | `bg-white/95 backdrop-blur-sm` |
| Sombra | `0 1px 3px rgba(0,0,0,0.08)` | `shadow-sm` |

## 2. Tipografía

| Elemento | Fuente | Peso | Tamaño |
|----------|--------|------|--------|
| Body | Nunito / Instrument Sans | 400 | 14-16px |
| Títulos (h1) | Nunito / Inter | 700 | 24-30px |
| Títulos (h2) | Nunito / Inter | 600 | 20px |
| Sidebar items | Nunito | 500 | 14px |
| KPIs (valores) | Nunito | 700 | 28px |
| KPIs (etiquetas) | Nunito | 500 | 12px uppercase |
| Tabla header | Nunito | 600 | 12px uppercase |
| Tabla celdas | Nunito | 400 | 13px |

## 3. Layout del Dashboard

```
┌─────────────────────────────────────────────────────────┐
│ ┌──────────┐  ┌──────────────────────────────────────┐  │
│ │ SIDEBAR  │  │  HEADER                              │  │
│ │          │  │  Logo | Búsqueda | Notif | Perfil    │  │
│ │  Fijo    │  ├──────────────────────────────────────┤  │
│ │   w-64   │  │                                      │  │
│ │          │  │  CONTENT                             │  │
│ │  Naveg.  │  │  ┌─────┐ ┌─────┐ ┌─────┐ ┌─────┐   │  │
│ │  principal│  │  │ KPI │ │ KPI │ │ KPI │ │ KPI │   │  │
│ │          │  │  └─────┘ └─────┘ └─────┘ └─────┘   │  │
│ │  Inicio  │  │                                      │  │
│ │  Ventas  │  │  ┌──────────────┐ ┌──────────────┐  │  │
│ │  Flota   │  │  │  Gráfico     │ │  Tabla       │  │  │
│ │  RRHH    │  │  │  (Chart.js)  │ │  Últimos     │  │  │
│ │  Contab  │  │  │              │ │  pedidos     │  │  │
│ │          │  │  └──────────────┘ └──────────────┘  │  │
│ └──────────┘  └──────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
```

### Sidebar
- Fija, ancho 256px (w-64), colapsable a 64px (iconos solamente)
- Fondo blanco con borde derecho sutil
- Logo en la parte superior
- Items de navegación con icono + texto
- Item activo con color de acento y background sutil
- Botón colapsar en la parte inferior

### Header
- Fijo arriba, altura 64px
- Fondo blanco con backdrop-blur
- Logo (o botón hamburguesa) a la izquierda
- Título de página actual
- Barra de búsqueda (opcional)
- Icono de notificaciones con badge
- Avatar + nombre de usuario con dropdown

### Contenido
- Padding: 24px (p-6)
- Cards blancas con sombra sutil (shadow-sm)
- Grid responsivo para KPIs
- Layout de dos columnas para gráfico + tabla

## 4. Componentes UI

### Cards (KPI)
- Fondo blanco, borde sutil (border-gray-200), border-radius 12px (rounded-xl)
- Padding: 20px (p-5)
- Icono colorido a la derecha
- Valor grande y bold
- Etiqueta en uppercase pequeña
- Sombra sutil (shadow-sm)

### Botones
- Primario: bg-blue-700 text-white, rounded-lg, px-4 py-2
- Outline: border-gray-300 text-gray-700, hover:bg-gray-50
- Tamaños: sm (32px), md (40px), lg (48px)

### Tablas
- Header: bg-gray-50, text-gray-500 uppercase text-xs
- Filas: hover:bg-gray-50, border-b border-gray-100
- Padding: px-4 py-3

### Inputs
- Borde: border-gray-300, focus:ring-2 focus:ring-blue-500
- Border-radius: 8px (rounded-lg)
- Padding: 10px 14px

## 5. Responsividad

| Breakpoint | Sidebar | Layout |
|------------|---------|--------|
| < 1024px | Oculto (overlay con backdrop) | Stack vertical |
| ≥ 1024px | Fijo, colapsable | Grid 2 columnas |
| ≥ 1280px | Fijo expandido | Grid 4 columnas KPIs |

## 6. Iconos
- Usar **PrimeIcons** (ya incluido: `primeicons/primeicons.css`)
- El proyecto ya tiene PrimeVue configurado
- Consistencia: todos los iconos del mismo set

## 7. Identidad de Marca ZAS vs EMCARGA
- ZAS usa: Logo circular blanco con "Z" negra, paleta azul/blanco
- EMCARGA usa: Logo cuadrado esmeralda con "E" blanca, paleta esmeralda/gris
- **Se recomienda mantener la identidad EMCARGA** (esmeralda) pero aplicar el layout profesional de ZAS
