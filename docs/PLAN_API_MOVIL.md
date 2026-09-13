# Plan de implementación — API móvil Zafiro

Estado: 2026-09-13. Stack real: **Laravel 13.8 + MariaDB 10.19 + Inertia/Vue**.
Cliente: **Ionic + Vue** (repo separado). Offline-first **parcial**. Push **Expo/FCM** (con fallback in-app).

## Estado de avance (2026-09-13)

- ✅ **Fase 0** rama `feature/api-mobile`.
- ✅ **Fase 1** Sanctum ^4.3 + `personal_access_tokens` + `HasApiTokens` (commit `fd44a9d`).
- ✅ **Fase 2** `/api/v1` registrado en `bootstrap/app.php` → `routes/api.php` → `api_v1.php`.
- ✅ **Fase 3** `AuthController` (login/me/logout/logout-all) + `UserResource` + rate limiter `login` (commit `fd44a9d`).
- ✅ **Fase 4 (piloto)** Flota/Tractivos + `ResolverEntidadApi` + `ScopesEntidadApi` + test de aislamiento (commit `dbcb38b`).
- Suite: **248 fast verdes**. Endpoint probado: `GET /api/v1/ping` → `{"ok":true,"version":"v1"}`.

## Plan para mañana (2026-09-14)

### 1. Completar Fase 4 — resto de módulos (mismo molde)
Patrón por módulo: `Controller` (index/show) + `Resource` + `ScopesEntidadApi` + rutas `auth:sanctum,api.entidad` + test de aislamiento.
- [ ] **RRHH**: `bolsa` (empleados), `cargos`, `salarios` (admin/choferes, lectura del mes de operaciones).
- [ ] **Clientes**: `clientes`, `lugares`, `acuerdos`.
- [ ] **Ingresos/Indicadores**: `aforos` (por mes de operaciones), `facturas`, `indicadores` (resumen).
- [ ] **Combustible**: `combustible-cargas`, `combustible-descargas`, `tarjetas`, `reportes-costos`.
- [ ] **Taller**: `ordenes-taller`, `control-lubricantes`.
- [ ] `contexto/entidad` y `contexto/fecha` (fijar entidad/mes del token; abilities editables).

### 2. Fase 5 — Resources + paginación + filtros
- [ ] `per_page` (≤100), `cursorPaginate` en tablas grandes, `meta`/`links` uniformes.
- [ ] Filtros `search`, `desde`, `hasta`, `mes`, `id_entidad`.

### 3. Fase 6 — Seguridad
- [ ] `throttle:api` (60/min), CORS del origen Ionic, expiración de tokens, `logout-all`.
- [ ] `SecurityHeaders` en API (HSTS).

### 4. Fase 7 — Eficiencia
- [ ] Eager loading + `withCount`, cache de catálogos, índices compuestos.

### Decisiones pendientes
- [ ] Ionic+Vue vs Ionic+React (asumido Vue).
- [ ] Hosting backend de la API.
- [ ] Redis para cache/colas (phpredis ausente) o `database`/`file`.

---

## Decisiones
- [x] Móvil: Ionic + Vue (repo separado)
- [x] Offline-first: parcial (cache lecturas + cola escrituras append)
- [x] Módulos v1: Flota, RRHH, Clientes, Ingresos/Indicadores, Combustible, Taller
- [x] Push: Expo Push / FCM
- [x] Repos separados
- [ ] [DECISIÓN REQUERIDA] Ionic+Vue vs Ionic+React (asumido Vue)
- [ ] [DECISIÓN REQUERIDA] ¿Packagist/internet para `composer require` o Sanctum offline?
- [ ] [DECISIÓN REQUERIDA] Hosting backend (¿mismo VPS Docker?)

## 1. Análisis de brechas

| Área | Ya tienes | Falta |
|---|---|---|
| Framework | Laravel 13.8, PHP 8.3 | — |
| Auth | Sesión web + Spatie + `LegacyDecryptor` | Sanctum, login por token, abilities |
| Rutas | `web.php` (Inertia) | `routes/api.php` + `/api/v1` |
| Serialización | Modelos crudos | API Resources |
| Scoping | Middleware web `EnsureModulePermission` + `EntidadScoping` + `fecha_operaciones` | Equivalentes stateless |
| Colas | `QUEUE_CONNECTION=database` | Redis [DECISIÓN] |
| Push | Reverb (in-app) | FCM/Expo + device tokens |
| Offline | — | Capa local Ionic + cola |

Riesgos: `perfil_activo`/`fecha_operaciones` en sesión; scoping por entidad solo en middleware web;
login debe replicar username en mayúsculas + descifrado legacy; FCM bloqueado en Cuba.

## 2. Arquitectura

```
  Ionic + Vue (Capacitor)
  UI → Pinia → ApiService (Bearer)
   └ SQLite/Preferences (cache + cola escrituras)
              │ HTTPS /api/v1
        Nginx (TLS) → PHP-FPM
              │ routes/api.php
   auth:sanctum → ResolverEntidadApi → ResolverPerfilApi → throttle
              │
   Controllers/Api/V1/*  →  API Resources
              │ Services (reutiliza lógica existente)
        MariaDB emcarga_new
              │ Queue database → jobs (reportes, push)
```

Estructura Laravel nueva:
```
app/Http/Controllers/Api/V1/{AuthController,Flota/*,Rrhh/*,Comercial/*,Ingresos/*,Combustible/*,Taller/*,SyncController}.php
app/Http/Middleware/Api/{ResolverEntidadApi,ResolverPerfilApi,ResolverFechaOperacionesApi}.php
app/Http/Requests/Api/V1/*
app/Http/Resources/Api/V1/*
app/Services/Api/{SyncService,PushService}.php
routes/{api.php,api_v1.php}
```

## 3. Fases

### Fase 0 — Preparación (2 h)
- [ ] Rama `feature/api-mobile`
- [ ] Salva de BD
- [ ] `.env`: `SANCTUM_STATEFUL_DOMAINS`, `API_PREFIX=v1`

### Fase 1 — Sanctum (3 h)
- [ ] `composer require laravel/sanctum` (o empaquetar offline)
- [ ] `config/sanctum.php` + `HasApiTokens` en `User`

### Fase 2 — Versionado (3 h)
- [ ] `withRouting(api:, apiPrefix: 'api')`
- [ ] `routes/api.php` → `api_v1.php` con `prefix('v1')`
- [ ] Middlewares `Api/*`

### Fase 3 — Autenticación (8 h)
- [ ] `AuthController`: login (username mayúsculas + `LegacyDecryptor`), logout, me
- [ ] Endpoints `entidades`, `contexto/entidad`, `contexto/perfil`, `contexto/fecha`
- [ ] Token con abilities `entidad:{id}`, `perfil:{rol}`, `fecha:{Y-m}`

### Fase 4 — Endpoints de negocio (30 h)
- [ ] Flota, RRHH, Clientes, Ingresos/Indicadores, Combustible, Taller (scoping por entidad)

### Fase 5 — Resources + paginación + filtros (10 h)
### Fase 6 — Seguridad (8 h)
### Fase 7 — Eficiencia (10 h)
### Fase 8 — Colas/async (6 h)
### Fase 9 — Testing (12 h)
### Fase 10 — Documentación (Scribe/Scramble) (6 h)
### Fase 11 — Cliente móvil Ionic (25 h)
### Fase 12 — CI/CD (8 h)

Total: ~141 h (~18 días).

## 4. Contratos v1 (resumen)
- `POST /api/v1/auth/login` · `POST /auth/logout` · `GET /auth/me`
- `GET /entidades` · `POST /contexto/entidad` · `POST /contexto/perfil` · `POST /contexto/fecha`
- `GET /tractivos`, `/bolsa`, `/clientes`, `/aforos`, `/indicadores`, `/combustible/cargas`, `/taller/ordenes`
- `POST /sync/pull`

## 5. Datos
- Verificar `personal_access_tokens` (ya existe).
- Nuevas: `device_tokens`, `api_sync_log`.
- Índices: `tractivos(id_entidad,codigo)`, `bolsa(id_entidad,id_area)`, `aforos(id_carta_porte,fecha_parte)`, `combustible_descargas(id_tarjeta,fdescarga)`, `ordenes_taller(id_entidad,estado)`.

## 6. Checklist de seguridad
HTTPS+HSTS · tokens revocables · throttle login/API · CORS Ionic · FormRequests · sin datos sensibles · scoping verificado · `APP_DEBUG=false` · auditoría en API · `composer audit`.

## 7. Checklist de eficiencia
`< 200ms p95` · sin N+1 · cache catálogos >90% · EXPLAIN sin ALL · payload <100KB · reportes por cola.

## 8. Riesgos
| Riesgo | Prob | Impacto | Mitigación |
|---|---|---|---|
| Datos de otra entidad | Media | Alto | Middleware entidad + tests aislamiento |
| Sin Packagist | Alta | Alto | Sanctum offline en imagen base |
| FCM bloqueado | Alta | Medio | Fallback Reverb in-app |
| Redis ausente | Media | Medio | Colas/cache database/file |
| Password legacy | Media | Alto | Tests con LegacyDecryptor |

## 9. Próximos pasos
`git checkout -b feature/api-mobile` · `php artisan zafiro:salva` · instalar/registrar Sanctum.
