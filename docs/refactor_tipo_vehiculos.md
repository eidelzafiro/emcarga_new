# Refactor tipo_vehiculos — estado (Fase A/B/C completas, D pendiente)

Fecha: 2026-08-25. Proyecto Zafiro (Laravel 13) en `/home/eidel/zafiro26/emcarga-new`.
BD `emcarga_new` (mysql contenedor, root/secret). Cliente `mysql` solo en contenedor `mysql`.

## Objetivo
Unificar `tipos_tractivos` + `tipos_arrastres` en `tipo_vehiculos`, repuntar `tractivos`,
y extraer fichas comunes a tablas polimórficas para preparar el split físico de `arrastres` (Fase D).

## Reglas de oro (AGENTS)
- Antes de mutar BD real: `php artisan zafiro:salva`.
- Nunca `migrate:fresh` / `emcarga:etl` sin `--no-fresh` en la BD de trabajo.
- "Arrastre" = `tractivos` con `id_grupo IN (SELECT id FROM catalogo_items WHERE origen_id=8)` = 646 filas.
- Discriminador `tipo_vehiculos`: arrastre si `id_tipo_equipo` ∈ {21 SEMI-REMOLQUES, 26 REMOLQUES}, sino tractivo.
- `Tractivo::marca`/`modelo`/`anno`/`color` son columnas escalares del propio `tractivos` (NO relaciones).

## Fase A — tipo_vehiculos (COMPLETA, ambas BD)
- `database/migrations/2026_08_25_200000_crear_tipo_vehiculos.php`: tabla `tipo_vehiculos`
  (marca/modelo/tipo_equipo/tipo_mantenimiento + discriminador clase + FKs a subtablas).
  Poblada desde `tipos_tractivos`(202) + `tipos_arrastres`(83) = 285.
- `tractivos.id_tipo_vehiculo` repuntado (1579 válidos; 5 null por tipos huérfanos legacy).
- `app/Models/TipoVehiculo.php` + `Tractivo::tipoVehiculo()`.
- `TractivosController::combosTipoVehiculo()` (lee `tipo_vehiculos`), validación
  `id_tipo_vehiculo => exists:tipo_vehiculos,id`, `Index.vue` Select único `catalogos.tiposVehiculo`.
- Previa Fase A (ya commiteada): normalización tipo_equipo/combustible (`2026_08_25_100000`),
  CUÑA TRACTORA (`2026_08_25_110000`), limpieza columnas muertas `tipos_equipos`
  (`2026_08_24_100000`), logo en `catalogo_items` (`2026_08_24_140000`).

## Fase B — limpieza de subtablas (COMPLETA, ambas BD)
- `database/migrations/2026_08_25_210000_limpiar_subtablas_tipo_vehiculo.php`:
  suelta 4 FKs y elimina `id_marca`,`id_modelo`,`id_tipo_equipo`,`id_tipo_mantenimiento`
  de `tipos_tractivos` y `tipos_arrastres`. Conserva `fabricacion` (año) y
  `id_tipo_combustible` (solo tractivos).
- `TipoTractivo`/`TipoArrastre`: quitan esas columnas/relaciones; añaden
  `tipoVehiculo()` HasOne + `tractivos()` HasManyThrough vía `tipo_vehiculos`
  (conteo correcto de vehículos). `TractivosController` index join `tipo_vehiculos as tv`.
- `TiposTractivosController`/`TiposArrastresController`: grid/filtros desde `tv`; formulario
  sin `id_marca/id_modelo/id_tipo_mantenimiento`.
- `ArrastresController::tiposTipoArrastre()` lee `TipoVehiculo` (donde `id_tipo_arrastre`
  no es nulo), `value = tipo_vehiculos.id`; reglas `exists:tipo_vehiculos,id`.

## Fase C — fichas polimórficas (COMPLETA, ambas BD)
- `database/migrations/2026_08_25_220000_crear_tablas_polimorficas_vehiculo.php`:
  crea `vehiculos_amortizacion`, `vehiculos_planes`, `vehiculos_documentacion`
  (clave `(vehiculo_type, vehiculo_id)`). Puebla desde `tractivos` (1584 filas cada una)
  y **elimina las 19 columnas** de `tractivos` (amortmn/vchapa, plan_*, ficav/lot/circulacion y fechas).
- Modelos `VehiculoAmortizacion`/`VehiculoPlan`/`VehiculoDocumentacion` (morphTo).
- `Tractivo`: `morphOne` amortizacion/planes/documentacion + **accesores virtuales**
  (`plan_comb`, `amortmn`, `circulacion`, etc.) + `syncVehiculoExtra()` (create/update hijas
  en transacción; borra el hijo si todos los campos vienen nulos).
- `TractivosController`: eager-load de fichas en index, las hidrata el formulario vía transform,
  y persiste con `syncVehiculoExtra` en store/update.
- `CostoCalculoService` usa los accesores (sin cambios); `TallerReportService::crtCirculacion`
  hace `LEFT JOIN vehiculos_documentacion`; `EtlService::migrarTractivos` escribe las fichas en
  las nuevas tablas.
- `AppServiceProvider`: `Relation::morphMap(['tractivo' => Tractivo::class])` — **imprescindible**
  para que las relaciones coincidan con el valor `'tractivo'` insertado manualmente.

## Verificación realizada
- Migraciones DONE en BD real y test (`emcarga_new_test`).
- `php -l` limpio en todos los archivos tocados.
- Conteos: `tipo_vehiculos`=285; `tipos_tractivos`=202; `tipos_arrastres`=83;
  `vehiculos_amortizacion/planes/documentacion`=1584 (no-nulos: 1584/1190/1253).
- Script runtime: el accessor de `Tractivo` devuelve `amortmn`/`plan_comb`/`circulacion`
  correctos vía la relación polimórfica (morph map confirmado).
- `npm run build` no requerido (sin cambios en Vue: el form sigue ligado a los campos,
  ahora hidratados por el transform del controlador).

## Salvas
- Pre-refactor: `emcarga_new_2026-08-25_15-32-52.sql`
- Pre-Fase B: `emcarga_new_2026-08-25_16-33-14.sql`
- Pre-Fase C: `emcarga_new_2026-08-25_17-31-38.sql`

## Fase D (identificación de arrastres) — COMPLETADA 2026-08-25 (versión segura)

El usuario reportó que `vehiculos_amortizacion`, `vehiculos_planes` y
`vehiculos_documentacion` marcaron todos los vehículos como `tractivo`, sin identificar
los arrastres. Se implementó la **identificación** sin el split físico destructivo:

- `app/Models/Arrastre.php` (nuevo): extiende `Tractivo`, misma tabla `tractivos`.
- `AppServiceProvider` morphMap: añadido `'arrastre' => Arrastre::class`.
- `Tractivo`: las 3 relaciones `amortizacion/planes/documentacion` pasaron de `morphOne`
  a `hasOne` **type-agnostic** (`whereIn vehiculo_type IN ('tractivo','arrastre')`) para que
  carguen igual desde `Tractivo` o `Arrastre`. `syncVehiculoExtra()` escribe `vehiculo_type`
  según la instancia.
- Migración `2026_08_25_270000_identificar_arrastres_polimorficos.php` (idempotente): marca
  `vehiculo_type='arrastre'` para las 646 filas cuyo tractivo está en el grupo ARRASTRES
  (catalogo_items origen_id=8) en las 3 tablas. Aplicado en BD en vivo: 646/646/646.
- Verificado con tinker: `VehiculoAmortizacion::where('arrastre')->vehiculo` → `Arrastre`;
  `Tractivo::find(arrastre)->amortizacion` y `Arrastre::find(arrastre)->amortizacion` cargan;
  tractores siguen `OK:tractivo`. Salva: `emcarga_new_2026-08-25_20-50-30.sql`.

### Split físico — DISEÑO APROBADO POR EL USUARIO 2026-08-25 (espejo conservado → luego split limpio)

El usuario decidió primero el enfoque no destructivo (espejo conservado, solo 3 tablas con
`id_arrastre`). Posteriormente pidió **eliminar el espejo** y ejecutar el **split limpio** (ver abajo).

**Hecho (base reversible, no destructiva):**
- `database/migrations/2026_08_25_280000_crear_tabla_arrastres.php`: crea `arrastres` (LIKE
  tractivos) y replica los 646 arrastres (grupo ARRASTRES, catalogo_items origen_id=8)
  **preservando su id original** → las FKs existentes siguen válidas.
- `app/Models/Arrastre.php`: `protected $table = 'arrastres'`.
- `Tractivo`: relaciones de ficha revertidas a `morphOne`; `Arrastre` resuelve
  `vehiculo_type='arrastre'`.
- Verificado: `Arrastre::count()=646`, `Tractivo::count()=1584`; morph resuelve
  `arrastre`→Arrastre(tabla arrastres) y `tractivo`→Tractivo(tabla tractivos).
- Salva: `emcarga_new_2026-08-25_21-00-01.sql`.

**Hecho (repunteo de FKs — migración `2026_08_25_290000_arrastres_split_id_arrastre.php):**
- `hojas_ruta.id_arrastre`: FK repuntada de `tractivos` → `arrastres` (ya tenía la columna).
- `neumaticos` y `ordenes_taller`: nueva columna `id_arrastre` (FK→arrastres), poblada para
  las filas que referenciaban un arrastre (252 y 415 filas respectivamente).
- Las 27+1 tablas que solo tenían `id_tractivo`/`tractivo_id` se **repuntaron al TRACTOR**
  asociado vía el pivote `arrastre_tractivo` (donde existía la asociación). Las filas de
  arrastre sin pivote quedan apuntando al espejo en `tractivos` (válido por el espejo).
- Verificado: 0 FKs huérfanos en neumaticos/ordenes_taller/hojas_ruta tras el repunteo.
  Salva: `emcarga_new_2026-08-25_21-12-21.sql`.

**Split limpio — EJECUTADO 2026-08-25 (espejo eliminado):**

El usuario pidió eliminar el espejo y mantener `arrastres` como tabla única y canónica. Campos
de `arrastres` (subconjunto definido por el usuario): `codigo`, `placa` (chapa), `id_tipo_vehiculo`,
`indice_aceite`, `tara`, `id_color_primario`, `id_color_secundario`, `estado`, `fecha_alta`,
`fecha_baja` (fbaja), `id_entidad`. (`fecha_reconstruccion` no existe en BD → omitida.)

- `2026_08_25_300000_arrastres_split_limpio.php`: pivote `arrastre_tractivo.id_arrastre` repuntado
  tractivos→arrastres; **todas** las tablas vehículo que referencian un arrastre ganan `id_arrastre`
  (FK→arrastres), poblada desde `id_tractivo`/`tractivo_id`; `id_tractivo` puesto NULL para esas
  filas (nullable en las NOT NULL: neumaticos/historial_tractivos). Eliminadas las fichas que los
  arrastres NO tienen: `vehiculos_planes` (646) y `motores` (505) de arrastres.
- `2026_08_25_310000_arrastres_slim.php`: recorte de columnas de `arrastres` al subconjunto.
- `2026_08_25_320000_borrar_espejo_tractivos.php`: borra los 646 arrastres del espejo `tractivos`
  (con guarda de 0 FKs colgando). Salva previa: `emcarga_new_2026-08-25_21-28-59.sql`.
- Resultado: `tractivos`=938 (tractores), `arrastres`=646; **0 FKs huérfanos** a arrastres vía
  `id_tractivo`. Pivote `arrastre_tractivo.id_arrastre` → `arrastres`.

**Código auditado:**
- `ArrastresController`: reescrito para usar modelo `Arrastre` y los campos delgados (ruta
  `arrastres/{arrastre}` → `Arrastre $arrastre`). `reglas()` valida solo los campos delgados.
- `EtlService::migrarArrastres`: ahora CREA filas en `arrastres` (no en `tractivos`); escribe
  amortizacion/documentacion con `vehiculo_type='arrastre'. `migrarTractivos` excluye `idgrupo=8`.
  `migrarAsociaciones` lee arrastres desde `arrastres`. `migrarHojasRuta`/`validar()` cuentan
  arrastres desde `arrastres`.
- Lectura de arrastres en dropdowns pasada a `Arrastre`: `AforosController`, `HojasRutaController`,
  `CartaPorteController`, `SolicitudesController`. (`ControlLubricanteController` ya no necesita
  cambio: `tractivos` ya no contiene arrastres.)

**Notas de diseño (estado final):**
- Arrastres = tabla `arrastres` (modelo `Arrastre`, morphMap `'arrastre'`). Tractores = `tractivos`.
- Una fila referencia un arrastre vía `id_arrastre` (→arrastres) o, en su defecto, el pivote
  `arrastre_tractivo` por `id_tractivo`.
- **Pendiente frontend**: `Arrastres/Index.vue` (y formularios que usaban `descripcion`/`marca`/
  `modelo`) deben migrarse a los campos delgados de `arrastres` (`codigo`, `placa`, `tara`,
  `id_tipo_vehiculo`, colores). No bloquea el backend.

## Aprendizajes críticos
- **DDL no hace rollback en migraciones fallidas** → dropear manualmente antes de re-ejecutar.
- `information_schema.table_constraints` NO tiene `referenced_table_name`; usar `REFERENTIAL_CONSTRAINTS`.
- MemPalace MCP inoperativo (timeouts) durante esta sesión → diary no escrito; este respaldo
  local sustituye al palacio hasta que recupere el servicio.
- `php artisan tinker` falla en el contenedor `app` (psysh no puede escribir `.config/psysh`);
  para pruebas runtime usar script temporal con `bootstrap/app.php`.
