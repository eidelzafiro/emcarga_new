# Auditoría de BD y migración legacy — 2026-09-13

Generado con el auditor `database/audit/` (re-ejecutado sobre la BD actual).
Base: **185 tablas** (15 de sistema Laravel), 2357 campos.

## Fase 3 — Normalización, duplicados y objetos sin uso

### Cobertura
| Métrica | Valor |
|---|---|
| Tablas totales | 185 |
| Tablas usadas en código | 129 |
| 🟢 Candidatas a eliminar (bajo riesgo) | 30 |
| 🟡 A revisar (medio riesgo) | 9 |
| 🔴 En uso / sistema | resto |
| Cobertura de tablas | 77.8% |
| Campos usados (estático) | 923 / 2357 |

> La cobertura de campos es un **escaneo estático** (PHP/Vue/Blade). Un campo
> marcado como no usado puede tener uso dinámico (raw SQL, JSON `extra`,
> acceso por nombre de variable) → NO eliminar campos solo por este reporte.

### 🟢 Tablas candidatas a eliminar (vacías, 0 refs, 0 FKs entrantes)
`amortizaciones`, `cierres_cdt`, `competencias_cargo`, `consumo_piezas`,
`costos_taller`, `descuentos_empleados`, `detalle_movimientos_inventario`,
`detalle_prefacturas`, `detalle_vales_inventario`, `equipos_electricos`,
`equipos_garaje`, `funciones_cargo`, `gastos_taller`, `giros`, `importes_gps`,
`importes_multas`, `lineas_bateria`, `lineas_diferencial`, `lineas_lubricante`,
`lineas_neumatico`, `lineas_otro_agregado`, `locales_electricos`,
`mantenimiento_ciclos`, `motivos_espera`, `movimientos_tarjetas`,
`pagos_adicionales_cargo`, `piezas`, `planes`, `planes_mantenimiento`, `vacaciones`.

Scripts de drop/rollback ya generados en `database/audit/drop/` y `database/audit/rollback/`.
**NO ejecutados** (requieren autorización de EIDEL).

### 🟡 Tablas a revisar (medio riesgo)
| Tabla | Filas | Motivo |
|---|---:|---|
| `clientes_seleccion` | 12 | Datos sin refs en código |
| `conceptos_costos` | 0 | FK entrante (1) |
| `movimientos_inventario` | 0 | FK entrante (1) |
| `perfiles_rh` | 0 | FK entrante (1) |
| `reportes_legacy` | 204 | Datos (catálogo de reportes) sin refs estáticas |
| `sub_tipos_roturas` | 59 | Datos sin refs |
| `subsistemas` | 130 | Datos sin refs |
| `tarjetero` | 0 | 8 FKs entrantes |
| `tipos_neumaticos` | 3 | Datos sin refs |

### Duplicados
- **No hay duplicados reales pendientes.** Los grupos detectados en
  `catalogo_items` por `(tipo, nombre)` corresponden a `tipos_modelo` (16 por
  entidad) y `tipos_tasas` (por entidad/rango), que son **legítimos** y ya se
  excluyeron en la limpieza del 2026-09-11.
- Los duplicados reales de catálogo (MARFIL, GAZ/LADA/MAZ, etc.) ya se
  consolidaron en esa fecha.

## Fase 4 — Migración desde legacy y clasificación de tablas

### 4.1. Verificación del proceso de migración (que NO toque lo revisado)
Cruce de las 30 tablas candidatas a eliminar contra `config/etl.php`:

| Tabla candidata | Mapeada en ETL | Riesgo |
|---|---|---|
| `amortizaciones` | Sí | El ETL la repoblaría |
| `cierres_cdt` | Sí | El ETL la repoblaría |
| `equipos_electricos` | Sí | El ETL la repoblaría |
| `equipos_garaje` | Sí | El ETL la repoblaría |
| `locales_electricos` | Sí | El ETL la repoblaría |
| `mantenimiento_ciclos` | Sí | El ETL la repoblaría |
| `motivos_espera` | Sí | El ETL la repoblaría |
| `planes_mantenimiento` | Sí | El ETL la repoblaría |
| `vacaciones` | No (pero 8 refs en código) | Revisar: puede ser variable/relación, no tabla |

**Conclusión**: antes de eliminar cualquiera de esas 8 tablas hay que quitarlas
también de `config/etl.php` (y de `EtlService`/comandos que las usen), o el
próximo ETL las volvería a crear/poblar. Las demás candidatas no tienen mapeo.

### 4.2. Clasificación de tablas (propuesta a validar con EIDEL)

#### A. Funcionamiento de la nueva aplicación (infraestructura Laravel)
`migrations`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`,
`failed_jobs`, `password_reset_tokens`, `personal_access_tokens`,
`notifications`, `roles`, `permissions`, `model_has_roles`,
`model_has_permissions`, `role_has_permissions`, `users`, `password_histories`,
`entidades`, `entidad_user`, `menu_items`, `bitacora`.

#### B. Catálogos / codificadores
`catalogo_items`, `catalogo_tipos`, `consecutivos`, `monedas`, `areas`,
`cargos`, `grupos_escala`, `meses`, `estados_componentes`, `servicentros`,
`configuraciones_modelo`, `configuraciones_tarifa`, `provincias`, `municipios`,
`lugares`, `distancias`, `tarifas`, `tasas`, `licencia_categorias`,
`estados_tarjetas`, `talleres`, `naves`, `vallas`, `calificadores`,
`lubricantes`, `productos`, `contenedores`, `neumaticos_roturas`,
`sub_tipos_roturas`, `subsistemas`, y las tablas `tipos_*`/`tipo_*`
conservadas (`tipos_tractivos`, `tipos_arrastres`, `tipos_equipos`,
`tipos_combustibles`, `tipos_mantenimiento`, `tipos_incidencias`,
`tipos_penalizaciones`, `tipos_cargas`, `tipos_cargas_reporte`, `tipos_modelo`,
`tipos_agregados`, `tipos_neumaticos`, `tipo_ingresos`, `tipo_vehiculos`).

#### C. Datos de explotación (transaccionales)
`tractivos`, `arrastres`, `arrastre_tractivo`, `motores`, `cajas`,
`diferenciales`, `baterias`, `baterias_movimientos`, `neumaticos`,
`neumaticos_movimientos`, `control_lubricantes`, `otros_agregados`,
`ordenes_taller`, `ordenes_operaciones`, `gastos_orden`, `movimientos_taller`,
`hojas_ruta`, `cartas_porte`, `solicitudes_servicio`, `aforos`, `aforo_lineas`,
`aforo_indicadores`, `facturas`, `prefacturas`, `girado`, `clientes`,
`clientes_seleccion`, `combustible_cargas`, `detalles_carga_combustible`,
`combustible_descargas`, `cierre_tarjetas`, `tarjetas`, `tarjetero`, `dietas`,
`pagos`, `devoluciones`, `otros_gastos`, `gasto_material`, `amortizacion_taller`,
`reportes_costos`, `indirectos_mensuales`, `conciliaciones`, `indicadores`,
`incidencias`, `penalizaciones`, `turnos`, `htarjetas`, `etarjetas`, `bolsa`,
`plantilla`, `salarios_administrativos`, `movimientos_rrhh`,
`historial_tractivos`, `historial_movimientos`, `estadisticas_explotacion`,
`demandas`, `acuerdos`, `alertas`, `osdes`, `firmas`, `firmas_autorizadas`,
`cds`, `cds_entidades`, `reembolsos`, `documentos_chofer`, `empleados`,
`fondos_tiempo`, `combustibles_lubricantes`, `consumo_lubricantes`,
`inventario`, `movimientos_inventario`, `detalle_vales_inventario`,
`detalle_movimientos_inventario`, `detalle_prefacturas`, `vales`,
`otros_ingresos_pre`, `registro_ordenes_taller`, `pizarra`,
`vehiculos_amortizacion`, `vehiculos_planes`, `vehiculos_documentacion`,
`reportes_legacy`.

> Las tablas 🟢 candidatas a eliminar pertenecen todas al grupo C (explotación)
> salvo `planes_mantenimiento` y `mantenimiento_ciclos` (configuración técnica).

### 4.3. Recomendaciones
1. **No eliminar** tablas ni campos sin autorización de EIDEL; el escaneo es
   estático y la BD tiene datos en producción.
2. Si se autoriza la limpieza: primero quitar las 8 tablas mapeadas de
   `config/etl.php` y `EtlService`, luego ejecutar los scripts de
   `database/audit/drop/` (con rollback disponible).
3. Regenerar el dump canónico y la salva tras cualquier cambio estructural.
4. Los seeders actuales solo siembran catálogos/roles/menú; ninguno escribe en
   las tablas de explotación revisadas.
