# Plan de Migración de Reportes (los que se usan)

> Propósito: migrar a Zafiro los reportes marcados como **USADOS** en
> `INVENTARIO_REPORTES.xlsx` (196 de 204; 8 sin marcar). Fuente de verdad: tabla
> legacy `reportes` (BD `emcarga`).
> Generado: 2026-08-21.
>
> **Principio rector:** el legacy tiene ~427 métodos en 7 controladores, pero la
> mayoría son **funciones de encabezado/formateo que se repiten** (dibujar tabla,
> formato moneda, fecha, pie de empresa, número a letras). Se extraen UNA vez a una
> base común; cada reporte queda como una acción de controlador delgada + una vista
> Blade dompdf que reusa la base. No se reescribe la lógica de formato por reporte.

---

## 0. Base común (hacer PRIMERO, una sola vez)

Crear infraestructura reutilizable en `app/` (Zafiro ya usa dompdf + Blade):

1. **`app/Services/Reportes/ReportesBaseService.php`** (o trait)
   - `encabezadoEmpresa($pdf)` — logo "Z" + razón social + fecha (paridad legacy).
   - `piePagina($pdf, $usuario, $pagina)` — pie con usuario/fecha.
   - `formatoMoneda($valor, $moneda)` — MN / ME / MT con separador decimal legacy.
   - `formatoNumero($valor, $decimales)` / `formatoPorcentaje()`.
   - `formatoFecha($fecha)` (d/m/Y) y `formatoMesAnio()`.
   - `dibujarTabla($cabeceras, $filas, $anchos, $totales)` — tabla tabular dompdf.
   - `numeroALetras($valor)` — si el legacy lo usa (facturas/certificados).
2. **Layout + componentes Blade** (`resources/views/reports/`):
   - `layouts/pdf.blade.php` (ya existe para factura/CP/HR/aforo) → estandarizar.
   - Componentes: `<x-reporte.encabezado>`, `<x-reporte.tabla>`, `<x-reporte.pie>`.
3. **Filtros reutilizables** según la columna `variable` de la tabla `reportes`
   (el parámetro que configura cada reporte). Tipos de filtro a implementar una vez:
   - `mes` → selector mes/año (DatePickerMes ya existe en frontend).
   - `fecha` → rango de fechas.
   - `consecutivo` → número de consecutivo.
   - `tractivo` → selector de equipo.
   - `cliente` / `organismo` / `abreviatura` → selector de cliente.
   - `cargas` / `devoluciones` / `ingresos` / `indicadores` / `variable` / `variable2`
     → parámetro de agrupación (dropdown de dimensión).
   - sin parámetro (`todos`, `base`, `1`, `0`, `CAJA`, `EMISION`) → reporte directo.
4. **`ReporteController`** (o resource por módulo) que recibe el filtro y renderiza
   la vista correspondiente, pinchando los modelos/servicios ya existentes en Zafiro.

> La base absorbe las funciones repetidas. Estimación: 1–2 días. Sin esto, cada
> reporte duplica ~200–400 líneas de formato.

---

## 1. Agrupaciones (variantes de `tipo`) — 196 reportes usados

| # | Agrupación (`tipo`) | Usados | Fuente legacy | Estado en Zafiro |
|---|---------------------|-------:|---------------|------------------|
| 1 | COMBUSTIBLE | 21 | Reportes2 | Pendiente (tablas `combustible_*`, `tarjetas` ya migradas) |
| 2 | ADMINISTRACION | 21 | Reportes2 | Pendiente |
| 3 | 1.DATOS P/NOMINAS (A CALCULAR) | 11 | Reportes/Reportesh | `NominaReportService` esqueleto (incompleto) |
| 4 | COSTOS | 12 | Reportes2 + CostoReporte | Pendiente (usa `reportes_costos` + `CostoCalculoService`) |
| 5 | DOCUMENTOS | 13 | Reportes2 | **Cubierto** (CartaPorte/HojaRuta ReportService) — reconciliar |
| 6 | FACTURACION | 10 | Reportes2 | **Parcial** (Factura/Prefactura ReportService) — completar |
| 7 | CERTIFICOS | 13 | Reportes | Pendiente |
| 8 | INDICADORES | 14 | Reportes2 | Pendiente |
| 9 | 2.DATOS P/NOMINAS (CHOFERES) | 9 | Reportes/Reportesh | `NominaReportService` esqueleto |
| 10 | TECNICA | 10 | Reportestec | Parcial (parque/flota ya tiene servicios) |
| 11 | CONTROL TALLER | 12 | Reportestec | **Cubierto** (OrdenTaller ReportService) — reconciliar |
| 12 | INGRESOS | 10 | Reportesnew/Reportes2 | Pendiente |
| 13 | PIZARRA | 6 | Reportes2 | Pendiente |
| 14 | OTROS | 6 | varios | Pendiente (revisar uno a uno) |
| 15 | NEUMATICOS | 6 | Reportestec | **Cubierto** (PlanBajasNeumatico) — reconciliar |
| 16 | GPS | 5 | Reportes2 | Pendiente |
| 17 | ENERGIA | 5 | Reportestec | Pendiente |
| 18 | EMCARGA | 5 | Reportesemcarga | Pendiente |
| 19 | TIEMPOS | 3 | Reportes2 | **Cubierto** (Aforo ReportService) — reconciliar |
| 20 | BATERIAS | 3 | Reportestec | Pendiente |

> Nota: la columna **Prioridad** del Excel viene vacía. La secuencia siguiente usa
> afinidad de fuente de datos; reordenar según Prioridad cuando se llene.

---

## 2. Secuencia de fases (por afinidad de datos)

### Fase A — Base + Comercial/Facturación/Documentos/GPS/Ingresos/Pizarra/Indicadores
- Hacer paso 0 (base común).
- Estos leen `aforos`, `facturas`, `cartas_porte`, `hojas_ruta`, `tarjetas`,
  `ingresos` — todas ya en Zafiro. Documentos/Tiempos/Facturación ya tienen
  servicios: **reconciliar** (confirmar que el xlsx coincide, no rehacer).
- Nuevos: GPS (5), Ingresos (10), Pizarra (6), Indicadores (14), ADMINISTRACION (21).
- Entrega: formularios de filtro reutilizables operativos.

### Fase B — Combustible + Costos + Contabilidad
- Combustible (21) y Costos (12) leen `combustible_cargas/descargas`, `tarjetas`,
  `reportes_costos` (ya calculado por `CostoCalculoService`). ADMINISTRACION (21)
  y COSTOS comparten submayor/contabilidad.
- Reusar base + filtros `mes`/`fecha`/`consecutivo`/`tarjeta`.

### Fase C — Nómina (Reportes.php, Reportesh.php, Reportesnew.php)
- 1.DATOS P/NOMINAS (11) + 2.DATOS P/NOMINAS CHOFERES (9) + CERTIFICOS (13).
- Completar `NominaReportService` (hoy esqueleto) usando tablas migradas
  (`incidencias`, `tasas`, `bolsa`, `turnos`, `penalizaciones`).
- Validar con usuario qué modelo de prenómina/divisa se usa HOY (EMCARGA/Transcar/Condor/Nelson) — son excluyentes.

### Fase D — Técnica (Reportestec.php)
- TECNICA (10), CONTROL TALLER (12, ya cubierto), NEUMATICOS (6, ya cubierto),
  BATERIAS (3), ENERGIA (5), EXISTENCIA (1), CERTIFICOS (en Fase C).
- Flota/parque/motores/cajas/diferenciales ya tienen datos + servicios; los
  reportes son vistas sobre ellos.

### Fase E — EMCARGA (Reportesemcarga.php)
- 5 indicadores agregados (distancia media, toneladas reales, tráfico, kms).
- Confirmar si se calculan en otra herramienta (Excel/PowerBI) y el reporte es volcado.

---

## 3. Criterios de aceptación por reporte
- Misma agrupación (`tipo`) y mismos perfiles que la tabla `reportes`.
- Mismo parámetro de configuración (`variable`).
- Salida PDF con dompdf, formato paritario al legacy (no idéntico pixel a pixel,
  pero mismos datos y columnas).
- Reusa la base común (sin duplicar funciones de formato).

## 4. No hacer
- No migrar los 8 reportes no marcados como usados.
- No reescribir las funciones de encabezado/formato por reporte (van a la base).
- No migrar lo que ya tiene `*ReportService` cubierto (reconciliar, no duplicar).
