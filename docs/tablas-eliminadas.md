# Tablas eliminadas

Migración: `2026_07_31_010000_drop_unused_legacy_catalog_tables`

Las siguientes tablas catálogo legacy se eliminaron porque no se usan en Zafiro:

| Tabla | Uso anterior | Reemplazo |
|---|---|---|
| `plantilla` | Plantilla de RRHH | `bolsa` / `historial_movimientos` |
| `tipos_plantillas` | Tipos de plantilla | — |
| `tipos_calificadores` | Tipos de calificadores | — |
| `tipos_articulos_bolsa` | Tipos de artículos de bolsa | — |
| `tipos_entidad` | Tipos de entidad | `entidades` |
| `tipos_especialidad` | Tipos de especialidad | — |
| `tipos_tallas` | Tipos de tallas | — |
| `tipos_causas_baja` | Causas de baja | — |
| `tipos_causas_laborales` | Causas laborales | — |
| `tipos_causas_movimiento` | Causas de movimiento | — |
| `tipos_jefe_grupo` | Tipos de jefe de grupo | — |

## Acciones de limpieza

1. **Migración** — soltó FK `historial_movimientos.id_plantilla` y las FKs cruzadas
   `tipos_causas_baja`/`tipos_causas_movimiento → tipos_causas_laborales`, luego
   eliminó las 11 tablas.
2. **`catalogo_items`** — se borraron los items con `tipo` en las tablas eliminadas.
3. **`catalogo_tipos`** — se borraron los tipos de catálogo correspondientes.
4. **Modelos y controladores** — se eliminaron los 11 modelos y 11 controladores
   (`PlantillaController`, `TiposCalificadoresController`, etc.).
5. **Rutas** — se quitaron los `Route::resource` de `web.php`.
6. **Seeders** — se limpiaron `MenuSeeder`, `MenuItemSeeder`, `PermissionSeeder`
   y `CatalogoTipoSeeder`.
7. **UI** — se eliminaron las páginas Vue y el test `RrhhTest` referente a `plantilla.index`.

## Notas

- La columna `historial_movimientos.id_plantilla` ya no existe.
- `CatalogoSchema::tipos_causas_baja` quedó como entrada vacía para compatibilidad.
- `EliminarTiposCatalogo.php` conserva la lista de tipos (incluye los eliminados)
  por si hay datos huérfanos en `catalogo_items`.
