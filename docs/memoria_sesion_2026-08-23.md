# Memoria de sesión — 2026-08-23 (Zafiro / emcarga-new)

Resumen de bugs corregidos y decisiones técnicas. Respaldo local porque las
herramientas de escritura de MemPalace no estaban disponibles en la sesión.

## 1. Reportes PDF se mostraban como texto crudo (`%PDF-1.7...`)

**Causa:** el botón "Generar" del catálogo de reportes (`resources/js/Pages/Reportes/Index.vue`)
usaba `router.post(...)` (XHR Inertia). El servidor devuelve el binario PDF vía
`->stream()`, pero Inertia no puede procesar una respuesta binaria en XHR, así
que el navegador pintaba los bytes como texto plano en la página.

**Fix:**
- `Reportes/Index.vue`: el "Generar" usa navegación real del navegador
  (`window.open(route('reportes.generar', id) + '?filtros=' + JSON, '_blank')`)
  para PDF. Las **exportaciones en cola** conservan `router.post` (flujo Inertia
  con feedback en la página), ramificando por `rep.es_exportacion`.
- `routes/web.php`: `reportes.generar` ahora es `Route::match(['get','post'], ...)`.
- `ReportesController::generar`: decodifica `filtros` con `json_decode` cuando
  llega como string (GET).
- `ReporteCatalogoService::agrupar`: añade `es_exportacion`
  (`ReportesDispatcher::esExportacion($id)`) a cada reporte del catálogo.

**Regla general (Inertia + descargas):** cualquier PDF/binario debe servirse con
una petición REAL (GET + `window.open`/`target=_blank` o `<a download>`), nunca
con `router.get/post`. Las rutas de impresión específicas (factura, carta-porte,
hoja-ruta, aforo, prefactura) ya usaban `window.open(...imprimir, '_blank')` y
funcionaban; el único roto era el catálogo.

## 2. `zafiro:salva` estaba roto

**Causa:** `DatabaseBackupService::salvar()` comprobaba `command -v mysqldump`
(sí existe en el contenedor `app`) y siempre usaba `dumpConBin()`. Pero
`mysqldump` fallaba con `TLS/SSL error: self-signed certificate in certificate
chain` (error 2026) y el código **nunca** caía al dump PDO de respaldo.

**Fix (`app/Services/DatabaseBackupService.php`):**
- `dumpConBin`: añadido `--skip-ssl` al comando mysqldump (el
  `mysqldump --skip-ssl` manual sí funcionaba). También `--events` por paridad.
- `salvar`: envuelve `dumpConBin` en try/catch; si falla, elimina el archivo
  parcial y degrada a `salvarConPdo()`.

Verificado: `php artisan zafiro:salva` genera ~20.66 MB válidos.

**Nota importante:** la base de datos es en realidad **MariaDB** (no MySQL
estricto), pese a lo que dice AGENTS.md. Los dumps se generan con
`MariaDB dump 10.19`.

## 3. Mojibake sistémico en la BD (raíz de "entidades con caracteres raros")

**Causa:** no era un problema de visualización, sino de la **migración/ETL**. El
legacy `emcarga` era latin1/CP1252 y el ETL insertó esos bytes en columnas
`utf8mb4` sin transcodificar → UTF-8 doblemente codificado (p.ej. "Pinar del
Río" → "Pinar del RÃ­o").

**Reparación:** se aplicó doble decode CP1252→UTF-8 con guarda a **3328 filas en
61 columnas** (clientes, bolsa, cargos, catalogo_items, lugares, firmas, tasas,
motores, cajas, facturas, entidades, etc.). Re-scan confirmó **0 filas** con
mojibake.

- Idiom de reparación (idempotente/seguro):
  ```php
  mb_convert_encoding(mb_convert_encoding($s, 'CP1252', 'UTF-8'), 'CP1252', 'UTF-8')
  ```
  con guarda `preg_match('/[ÃÂƒâ€]/u', $s)` (solo decodificar strings marcados;
  el UTF-8 ya correcto queda intacto).
- Para casos profundos (4+ pasadas) iterar hasta 5 decodes y elegir la versión
  válida UTF-8 sin marcadores.
- **Exclusión:** columnas tipo JSON/extra (`json|extra|fields|config|datos|
  metadata|parametros|props|settings|permisos`) para no corromper datos
  estructurados.

Backup de rollback antes del masivo update:
`database/backups/emcarga_new_2026-08-23_00-55_pre_moji.sql` (21 MB).

## 4. Entorno / build

- `npm run build` **debe correr en el HOST** (`/home/eidel/.npm-global/bin/npm`),
  NO en el contenedor `app` (no tiene node/npm en PATH).
- Comandos Docker se ejecutan desde `emcarga-new/` con
  `docker compose exec -T app php artisan ...`.

## 5. KPIs y dashboard

- `KpiService` ahora recibe `fecha_operaciones` (helper `fechaOperaciones()` en
  `DashboardController`) y filtra periodo/ingresos por esa fecha en vez de hoy.
- `AppLayout.vue`: los selectores de entidad/perfil/fecha de operaciones son
  `reactive` y se sincronizan vía `watch(contexto)`; cada cambio dispara
  `router.reload({ preserveScroll: true })` para recalcular KPIs.
