# Plan de implementación — API móvil Zafiro

Estado: 2026-09-13. Stack real: **Laravel 13.8 + MariaDB 10.19 + Inertia/Vue**.
Cliente: **Ionic + Vue** (repo separado). Offline-first **parcial**. Push **Expo/FCM** (con fallback in-app).

## Estado de avance (2026-09-14)

- ✅ **Fase 0** rama `feature/api-mobile`.
- ✅ **Fase 1** Sanctum ^4.3 + `personal_access_tokens` + `HasApiTokens` (commit `fd44a9d`).
- ✅ **Fase 2** `/api/v1` registrado en `bootstrap/app.php` → `routes/api.php` → `api_v1.php`.
- ✅ **Fase 3** `AuthController` (login/me/logout/logout-all) + `UserResource` + rate limiter `login` (commit `fd44a9d`).
- ✅ **Fase 4** Flota/Tractivos + `ResolverEntidadApi` + `ScopesEntidadApi` (commit `dbcb38b`).
- ✅ **Fase 4 completa (2026-09-14)**: 17 endpoints de negocio + `ResolverFechaOperacionesApi` (ability `fecha:{Y-m}`) + `ContextoController` (`contexto/entidad`, `contexto/fecha` editan las abilities del token). Módulos: RRHH (bolsa, cargos, salarios), Comercial (clientes, lugares, acuerdos), Ingresos (aforos, facturas, indicadores), Combustible (cargas, descargas, tarjetas, reportes-costos), Taller (órdenes, control-lubricantes).
- Suite: **290 fast verdes** (42 nuevos: smoke de endpoints + aislamiento por entidad). Endpoint probado: `GET /api/v1/ping` → `{"ok":true,"version":"v1"}`.

## Plan pendiente (2026-09-14 en adelante)

### 1. Fase 5 — Resources + paginación + filtros
- [x] `per_page` (≤100) en todos los índices, `meta`/`links` uniformes (JsonResource).
- [x] Filtros `search`, `desde`, `hasta`, `mes`, `id_entidad` donde aplica.
- [x] `cursorPaginate` en tablas grandes: `aforos` y `combustible/descargas` (avance con `?cursor=`).
- [x] Filtros por fecha de operaciones expuestos en `me` para el cliente (`entidad_activa`, `fecha_operaciones`).

### 2. Fase 6 — Seguridad
- [x] `throttle:api` (60/min por usuario/IP) en el grupo autenticado; `throttle:login` (5/min) en login.
- [x] CORS del cliente Ionic (`config/cors.php`, orígenes vía `MOBILE_ORIGINS`, sin `*`).
- [x] Expiración de tokens: `SANCTUM_EXPIRATION` (por defecto 43200 min = 30 días).
- [x] `SecurityHeaders` aplicado al grupo `api` (nosniff, HSTS si HTTPS).
- [x] `logout-all` (revoca todos los tokens del usuario).

### 3. Fase 7 — Eficiencia
- [x] Eager loading en todos los índices/show (relaciones cargadas con columnas acotadas).
- [x] Cache de catálogos: `App\Support\Catalogos` (Cache::remember con TTL) ya cubre los catálogos unificados.
- [x] Índices compuestos `api_*` (migración `2026_09_14_120000`): tractivos(id_entidad,codigo), bolsa(id_entidad,id_area), clientes(id_entidad,nombre), tarjetas(id_entidad,numero), combustible_descargas(id_entidad,fdescarga), ordenes_taller(id_entidad,estado), salarios(id_entidad,mes,ano).

### 4. Fase 8 — Colas/async + push
- [x] Tablas `device_tokens` y `api_sync_log` (migración `2026_09_14_130000`).
- [x] `PushSender` (contrato) + `ExpoPushSender` + `NullPushSender` (fallback offline, Cuba).
- [x] Job `EnviarPush` (cola `database`, 3 intentos) y listener `EnviarPushNotificacion` sobre `NotificationSent`.
- [x] Endpoints: `POST/DELETE /dispositivos`, `GET /notificaciones`, `POST /notificaciones/{id}/leer`, `POST /notificaciones/leer-todas`, `POST /sync/pull`.
- [x] Push desactivado por defecto (`EXPO_PUSH_ENABLED=false`); el cliente recibe la notificación in-app.
- [ ] Push real cuando haya salida a internet (activar `EXPO_PUSH_ENABLED` + token).

### 5. Fase 9+ — Testing, Documentación, Cliente Ionic, CI/CD
- [x] Fase 10: documentación OpenAPI con **Scramble** (`dedoc/scramble` ^0.13). UI en `/docs/api`, JSON en `/docs/api.json`; spec exportado en `docs/openapi-v1.json` (`php artisan scramble:export`). Acceso restringido a local o SUPERADMIN (gate `viewApiDocs`). Bearer documentado automáticamente.
- [ ] Fase 11: cliente móvil Ionic (repo separado).
- [ ] Fase 12: CI/CD.

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
- [x] Flota, RRHH, Clientes, Ingresos/Indicadores, Combustible, Taller (scoping por entidad)

### Fase 5 — Resources + paginación + filtros (10 h)
### Fase 6 — Seguridad (8 h)
### Fase 7 — Eficiencia (10 h)
### Fase 8 — Colas/async (6 h)
- [x] Tablas `device_tokens` + `api_sync_log`; job `EnviarPush` en cola `database`; listener de `NotificationSent`.
### Fase 9 — Testing (12 h)
### Fase 10 — Documentación (Scribe/Scramble) (6 h)
- [x] Scramble: OpenAPI 3.1 + UI `/docs/api`; spec `docs/openapi-v1.json`; acceso SUPERADMIN/local.
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
- Nuevas: `device_tokens`, `api_sync_log` (migración `2026_09_14_130000`, aplicada).
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
