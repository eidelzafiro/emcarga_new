# Plan pendiente — Reportes agrupados (2026-09-13)

Estado al cierre del 2026-09-12 (commit `ce9537d`, pushed a `origin/main`):

- Páginas agrupadas **Técnica** (33), **Combustibles** (23) y **Facturación** (10) en el menú *Reportes*, con permisos `reportes-tecnico.ver`, `reportes-combustible.ver`, `reportes-facturacion.ver`.
- 66 reportes portados a FPDF exacto (layout + datos del legacy). `npm run build` OK y `php -l` OK.
- Facturas: botón Imprimir (reporte legacy 13), multiselección + exportar `ids[]`, grid agrupado por `fecha_emision`.
- Aforos: vista tabla agrupada por `fecha_parte`.

## Sesión 2026-09-13 — avance

### ✅ Tarea 3 — CT-1 ficha técnica + reporte 361
- **País y colores** viven en el catálogo unificado (`catalogo_items` tipos `paises` y `colores`).
  - Colores ya estaban migrados en `tractivos.id_color_primario/secundario` (758/756 de 949).
  - País NO tenía destino: migración `2026_09_13_100000_add_ficha_ct1_tipos_tractivos` añade
    `tipos_tractivos.id_pais` (FK a `catalogo_items`) y `tipos_tractivos.bat_volt`, y rellena desde el legacy:
    `tipo_vehiculos.fabricacion`, `id_pais`, `bat_volt` y `id_medida_del/tra/res` (→ 172 país, 56 volt, 58 fabricación).
- **Reporte CT-1** (`TecnicaTaller::pdfCt1Expediente`) cableado: TIPO (tipoEquipo), MARCA/MODELO
  (`tipo_vehiculo`, antes leía por error los del motor), PAÍS, DIST/EJES y #EJES, ACUMULADOR
  (cantidad/voltaje/amperaje), NEUMÁTICOS (del/tras/resp), MED del/tras/resp, CAMA (largo/ancho/altura/m³).
  Verificado: PDF 200 con PAIS=CHINA, TIPO=CAMION PLATAFORMA, MARCA=NORTH BENZ, #EJES=3, neum 2/8/1.
- **Reporte 361 (mttos en otras entidades)**: dato muerto en el legacy. `tec_talleres` tiene **0 filas** y
  solo 17 de 2156 `tec_ordentaller` traen `idtalleres` (ids huérfanos). El ETL deja `id_taller` en NULL
  (FK validada contra `talleres`, vacía). El reporte cae a "NO EXISTEN DATOS" en ambos sistemas → no hay
  nada que recuperar. Pendiente decisión de negocio (dejar vacío o cargar talleres a mano).

### ✅ Tarea 4 — confirmada por EIDEL
- "Fecha de facturación" = `facturas.fecha_emision` (no existe `fecha_facturacion`).

### ✅ Tarea 5 — tests en verde
- `test:fast`: **232 passed** (antes 216 + 16 fallos). `test:legacy`: **8 passed** (desde el host).
- Correcciones:
  - `ReportesDispatcher`: se retiraron 30 mapeos de Nómina que apuntaban a métodos inexistentes
    (Fase C/D nunca implementados y con firma `Request` incompatible con el `array` del dispatcher).
    Los reportes de RRHH se sirven por rutas dedicadas (`ReportController`). Ahora devuelven 404 "no migrado".
  - `config/etl.php`: alineado al esquema post-unificación. Se quitó `tipos_operaciones` (consolidada en
    `catalogo_items`) y las columnas muertas de `tipos_tractivos`/`tipos_arrastres` (marca/modelo/país/
    tipo_equipo/fabricación/tipo_mantenimiento); `fk_validar` apunta a tablas existentes.
  - `CatalogoController::update`: eliminada la referencia a la columna `tipos_equipos.imagen_fuente`
    (borrada el 2026-08-24), que rompía la sincronización de la imagen con la tabla legacy.
  - Tests de Flota/Pizarra: crean `TipoVehiculo` (no `TipoTractivo`) — la FK `tractivos.id_tipo_vehiculo`
    apunta a `tipo_vehiculos` desde el refactor.
  - `FacturacionTest`: `tipo_ingresos` pertenece a RRHH → lo prueba RECHUM, no COMERCIAL.
  - `ContabilidadTest`: se excluyen `vales.index`/`inventario.index` (módulos no implementados).
  - `CatalogosTest`: `imagen` de `tipos_estados` es logo (se sube por archivo); sin archivo se preserva.
  - `MenuTest`: ahora usa el mismo fixture que `MenuItemSeeder` (`menu_items_backup_2026-08-17_menuct7.json`)
    y expectativas del menú real.
- ⚠️ `composer test:legacy` dentro del contenedor `app` falla con `mysql: not found` (falta el binario en la
  imagen). El suite legacy pasa corriéndolo desde el host:
  `DB_HOST=127.0.0.1 DB_PORT=3307 DB_DATABASE=emcarga_new_test php artisan test --testsuite=legacy`.
  Tras correr el suite legacy hay que re-ejecutar `test:setup` (deja el baseline en estado parcial).

### ⚠️ Tarea 6 — menú (decisión EIDEL 2026-09-13)
- El snapshot de referencia `database/menu_items_backup_2026-08-06.json` (100 ítems) está **intacto** (git limpio).
- **Discrepancia detectada**: el fixture que usa `MenuItemSeeder` (`menu_items_backup_2026-08-17_menuct7.json`,
  92 ítems) y el snapshot del 2026-08-06 NO reflejan el menú real actual (146 ítems, ids hasta 198) porque
  no incluyen los ítems autorizados por EIDEL (reportes Técnica/Combustibles/Facturación, Productos,
  Operaciones, Documentos, etc.). Además el seeder borra `menu_items` y restaura solo 92, perdiendo en un
  install fresco los ítems insertados por migraciones.
- **DECISIÓN EIDEL**: el menú se queda **como está ahora mismo** (BD real, 146 ítems). NO se regenera el
  fixture/backup ni se toca el menú.

## Pendientes (por orden)
1. Verificación visual de paridad de los 66 reportes FPDF contra los PDF de referencia
   (`zafiro26/reportes/{combustibles,tecnica,facturacion}`).
2. Reporte 59 (IMPRESIÓN DE COMPROBANTES): decidir si se mapea a `contabilidad`/`contabilidad_detalle` o se deja fuera.
3. Reporte 361 (mttos en otras entidades): **decisión EIDEL 2026-09-13 — se deja como está** (sin datos;
   el legacy `tec_talleres` está vacío).

## Referencias
- Clases FPDF: `app/Services/Reports/Fpdf/{CombustibleFpdfReport,TecnicaFpdfReport,FacturacionFpdfReport}.php` y traits en `Fpdf/Concerns/`.
- Servicios: `CombustibleReportService`, `TecnicaAgrupadaService`, `FacturacionAgrupadaService`.
- Página: `resources/js/Pages/Reportes/Generador.vue`.
- Salvas: `database/backups/` (nueva salva 2026-09-13 13:17).
