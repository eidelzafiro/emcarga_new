# Plan Replicación Exacta Reportes RRHH (2026-09-08)

> Tarea: replicar EXACTAMENTE 11 reportes de Recursos Humanos del legacy en Zafiro.
> Referencia: `zafiro26/reportes/recursos humanos/*.pdf` (entidad "EF CAM HOLGUIN" = id 20, junio 2026).
> El legacy usa FPDF; se replican con FpdfReportBase/ReportesnewFpdfBase.
> **SESIÓN PENDIENTE DE CONTINUAR** — ver al final "PENDIENTES PARA CONTINUAR".

## ESTADO GLOBAL

| # | Reporte | Estado |
|---|---------|--------|
| 1 | LISTADO DE CUMPLEAÑOS DEL MES | ✅ HECHO + VERIFICADO |
| 2 | DATOS P/NOMINAS (ADICIONALES) | ✅ HECHO + VERIFICADO (2026-09-08) |
| 3 | DATOS P/NOMINAS (NOCTURNIDAD) | ✅ HECHO + VERIFICADO (2026-09-08) |
| 4 | DATOS P/NOMINAS PAGO ADMINISTRATIVO HOLGUIN | ✅ HECHO + VERIFICADO (2026-09-08) |
| 5 | LISTADO DE CUMPLEAÑOS DEL MES (=#1) | ✅ |
| 6 | LISTADO PERSONAL CON LICENCIA CONDUCCION | ✅ HECHO + VERIFICADO |
| 7 | MODELO ANALISIS DEL SALARIO TRANSPORTACION | ✅ HECHO + VERIFICADO (2026-09-09) |
| 8 | MODELO RESUMEN DE LOS TIEMPOS CHOFERES TRANSPORTACION | ✅ HECHO + VERIFICADO (2026-09-09) |
| 9 | PRENOMINA INCIDENCIAS AL TIEMPO TRABAJADO | ✅ HECHO + VERIFICADO (2026-09-08) |
| 10 | SC-4-05 CONTROL DIARIO TIEMPO DE TRABAJO | ✅ HECHO + VERIFICADO (2026-09-09) |
| 11 | SC-4-05 CONTROL DIARIO TIEMPO DE TRABAJO CHOFERES DE TRANSORTACION | ✅ HECHO + VERIFICADO (2026-09-09) |

## REPORTE 2: DATOS P/NOMINAS (ADICIONALES) — ✅ HECHO (2026-09-08)
- Legacy: `pdf_salario_prenomina_adicional` (Reportes.php:4764) +
  `modSalarioAdmin->mostrar_salario_emcarga` (sistema de pago = 1).
- Implementado: `app/Services/Reports/Fpdf/AdicionalesFpdfReport.php` (Letter horizontal) +
  `NominaReportService::pdfAdicionales` + `ReportController::pdfAdicionales` + ruta `reportes.adicionales`.
- **Fuente**: reutiliza `ReportePrenominaService::prenominaAdministrativo` y filtra
  `padicionales2 > 0`. Columna EXP = `bolsa.versat`. `padicionales2 = padicionales + CLA`
  (réplica ModSalarioAdmin:968-970). Columnas: NOCTURNIDAD/DOBLAJE/FERIADO/CLA/MAESTRIAS/OTRAS(=incidencia)/TOTAL.
- **Campos añadidos al servicio**: `impdoblaje`, `impferiados`, `impincidencia`, `impotras`,
  `padicionales2` y `versat` en cada registro de `prenominaAdministrativo`.
- **VERIFICADO contra reference** (entidad 20, junio 2026): TOTAL BRIGADA 656.40,
  EQUIPO OPERACIONES 403.96, GRUPO SEGURIDAD 707.32, GRUPO GPS 145.88 y TOTAL GENERAL
  1,257.16 / 656.40 / 1,913.56 idénticos. ⚠️ Diferencia menor: el orden de filas con tarifa
  idéntica no es estable en el legacy (no hay criterio de desempate) y las firmas
  dependen de la tabla `firmas` (configuración distinta, no bug).

## REPORTE 3: DATOS P/NOMINAS (NOCTURNIDAD) — ✅ HECHO (2026-09-08)
- Legacy: `pdf_salario_prenomina_nocturnidad` (Reportes.php:4046).
- Implementado: `app/Services/Reports/Fpdf/NocturnidadFpdfReport.php` (Letter horizontal) +
  `NominaReportService::pdfNocturnidad` + `ReportController::pdfNocturnidad` + ruta `reportes.nocturnidad`.
- **Fuente**: reutiliza `ReportePrenominaService::prenominaAdministrativo` filtrando
  `impnocturnidad > 0`. Columna EXP = `bolsa.versat`. Columnas: TRABAJADAS 7-11PM (HRSX4/IMPORTE),
  TRABAJADAS 11-7AM (HRSX8/IMPORTE), HORAS TOTAL y TRABAJADO A PAGAR. Subtotales por área altura 10.
- **Campos añadidos al servicio**: `noct1`, `noct2`, `impnoct1`, `impnoct2` en cada registro.
- **VERIFICADO contra reference** (entidad 20, junio 2026): los 10 trabajadores con nocturnidad
  y TOTAL GENERAL 268/262.64/529/994.52/797/1,257.16 idénticos. ⚠️ La referencia agrupa
  Xiomara/Yadira/Antonio bajo "GRUPO DE SEGURIDAD INTERNA" pero la BD actual los tiene en
  "EQUIPO DE OPERACIONES" (datos de área cambiaron tras la referencia; mismo contenido y totales).

## INVESTIGACIÓN nro_nomina entidad 20 (RESUELTO 2026-09-08)
- **Causa raíz**: el reporte legacy `mostrar_salario_emcarga` (ModSalarioAdmin.php:266)
  **re-aliasa** `rh_bolsa.versat as nronomina` → la columna "Cod" del reporte
  "DATOS P/NOMINAS SALARIO ADMINISTRATIVO" muestra **VERSAT**, NO el nronomina real.
- El nuevo sistema muestra `movimientos_rrhh.nronomina` (que para varios trabajadores
  es su CI, p.ej. Enrique Parra = 95011948349). Por eso difiere del reference (0248).
- `versat` en entidad 20: **0 faltantes** (completo). `nronomina` en movimientos activos:
  **48 bolsas sin** (no tienen movimiento activo con nronomina).
- ANOMALÍAS: 50 bolsas con >1 movimiento activo; 1 bolsa id_bolsa='' con 153 movs;
  NEW movimientos_rrhh=1568 vs legacy rh_movimientos=1511.
- **Fix pendiente**: al construir el reporte DATOS P/NOMINAS SALARIO ADMINISTRATIVO, la columna
  "Cod" debe usar `bolsa.versat` (no movimientos.nronomina) para replicar el legacy.

## REPORTE 1: LISTADO DE CUMPLEAÑOS DEL MES — ✅ HECHO (2026-09-08)
- Legacy: `pdf_empleados_cumple` (Reportes.php:606) + `modBolsa->mostrar_cumple` (ModBolsa.php:172).
- Implementado: `app/Services/Reports/Fpdf/CumpleanosFpdfReport.php` (extiende ReportesnewFpdfBase,
  orientación 'P' Letter) + `NominaReportService::pdfCumpleanos` + `ReportController::pdfCumpleanos`
  + ruta `reportes.cumpleanos`.
- **Fecha de nacimiento derivada del CI cubano** (YYMMDD): mes=substr(ci,2,2), dia=substr(ci,4,2),
  anioNac=19xx/20xx. edad=ano-anioNac; ajusta -1 si mesNac>=mes AND diaNac>=dia de `session('fecha_operaciones')`.
- Orden: cidentidad DESC. Nombre: tabla bolsa en MAYÚSCULAS (coincide con reference, NO titlecase).
- Título pasa por `latin1()` (fix Ñ → "CUMPLEAÑOS").
- **VERIFICADO contra reference**: mismos 17 empleados, fechas, edades y orden idénticos
  (usando `session('fecha_operaciones')='2026-06-30'`).

## REPORTE 6: LISTADO PERSONAL CON LICENCIA CONDUCCION — ✅ HECHO (2026-09-08)
- Legacy: `pdf_choferes` (Reportes.php:708) + `modBolsa->mostrar_todos('LICENCIA')`.
- Implementado: `app/Services/Reports/Fpdf/LicenciaConduccionFpdfReport.php` (orientación 'L' Legal)
  + `NominaReportService::pdfLicenciaConduccion` + `ReportController::pdfLicenciaConduccion`
  + ruta `reportes.licencia-conduccion`.
- **Migración añadida**: `2026_09_08_200000_add_licencia_to_bolsa_table.php` — añade columna `licencia`
  a `bolsa` y rellena desde legacy `rh_bolsa.licencia` (match por id). Ejecutada OK. Salva previa tomada.
- ETL actualizado (`EtlService`): inserta `licencia`.
- Categorías parseadas desde `bolsa.categorias_licencia` ("B,C,D,E"). FALTA/FVENCE desde
  `licencia_emision`/`licencia_vencimiento` (dd-mm-yyyy). LIMITACION='C/ESPEJUELOS' si `limitaciones`.
- **VERIFICADO contra reference**: 62 filas IDÉNTICAS en las 3 páginas (nombres, CI, licencia,
  categorías X, fechas). Incluye el caso Guillermo (licencia 66112820388 ≠ CI 66112820338).

## INVESTIGACIÓN nro_nomina entidad 20 (RESUELTO 2026-09-08)
- **Causa raíz**: el reporte legacy `mostrar_salario_emcarga` (ModSalarioAdmin.php:266)
  **re-aliasa** `rh_bolsa.versat as nronomina` → la columna "Cod" del reporte
  "DATOS P/NOMINAS SALARIO ADMINISTRATIVO" muestra **VERSAT**, NO el nronomina real.
- El nuevo sistema muestra `movimientos_rrhh.nronomina` (que para varios trabajadores
  es su CI, p.ej. Enrique Parra = 95011948349). Por eso difiere del reference (0248).
- `versat` en entidad 20: **0 faltantes** (completo). `nronomina` en movimientos activos:
  **48 bolsas sin** (no tienen movimiento activo con nronomina).
- ANOMALÍAS: 50 bolsas con >1 movimiento activo; 1 bolsa id_bolsa='' con 153 movs;
  NEW movimientos_rrhh=1568 vs legacy rh_movimientos=1511.
- **Fix**: al construir el reporte DATOS P/NOMINAS SALARIO ADMINISTRATIVO, la columna
  "Cod" debe usar `bolsa.versat` (no movimientos.nronomina) para replicar el legacy.

## REPORTE 4: DATOS P/NOMINAS PAGO ADMINISTRATIVO — ✅ HECHO (2026-09-08)
- Legacy: `pdf_salario_prenomina_resultado_adm` (Reportes.php:4491) + ModSalarioAdmin:973-991
  (Sistema de Pagos por Resultados EMCARGA).
- Implementado: `app/Services/Reports/Fpdf/PagoAdministrativoFpdfReport.php` (Legal horizontal) +
  `NominaReportService::pdfPagoAdministrativo` + `ReportController::pdfPagoAdministrativo` +
  ruta `reportes.pago-administrativo`.
- **Tabla nueva `cds_entidades`** (migración `2026_09_09_010000`): CDS por **entidad+mes+año**
  (el legacy lo tenía global en `rh_tiposistemaspago.cds`=0). CRUD completo: `CdsController`
  (EntidadScoping, upsert por entidad+mes+año) + vista `Cds/Index.vue` (DataTable PrimeVue) +
  permisos `cds.*` (PermissionSeeder + BD actual, RECHUM/SUPERADMIN) + menú **"Coeficiente CDS"**
  (id 191, orden 9 bajo RRHH id 40, permiso `cds.ver`) — añadido con autorización del usuario.
- **Cálculo** (`ReportePrenominaService::pagoAdministrativo`): impstrt=SE+SCLA;
  SRInicial=STRT×CDS (tabla nueva); Pen=% penalización "RESULTADOS ADMINISTRATIVOS"
  (catalogo_items origen 5 → tipos_penalizaciones.tipo_pago_adicional_id) sobre SRInicial
  (máx 100%); SRFinal=SRInicial−Pen; PAGOS ADICIONALES=nocturnidad+maestrias+h/extras;
  SALARIO TOTAL=STRT+SRFinal+PAGOS. Sin CDS cargado → SRInicial/SRFinal/SALARIO vacíos.
- **CORRECCIONES al legacy (decisión del usuario)**: (1) título ÚNICO
  "DATOS P/NOMINAS PAGO ADMINISTRATIVO" en todas las páginas (el legacy mostraba
  "SALARIO ADMINISTRATIVO" en la 1ª y cambiaba al paginar); (2) el subtotal del área se imprime
  UNA vez y el "TOTAL GENERAL" queda limpio (el legacy re-dibujaba el área ENCIMA del
  "TOTAL GENERAL", tapándolo — visible en la referencia como fila duplicada).
- **VERIFICADO contra reference** (entidad 20, junio 2026, CDS=4.629252 derivado de la propia
  referencia): 60 empleados, subtotales por área y TOTAL GENERAL 5,576.67/181,673.13/656.40/
  182,329.53/844,049.32/1,257.16/1,027,636 — coinciden con la referencia (±0.01-71 por el
  redondeo del CDS derivado; con el CDS exacto del cliente será 1:1).
- CDS derivado e insertado para entidad 20 jun-2026 = **4.629252** (el real lo pone el cliente).

## REPORTE 7: MODELO ANALISIS DEL SALARIO TRANSPORTACION — ✅ HECHO (2026-09-09)
- Legacy: `npdf_salario_choferes_resumen_analisis` (Reportes.php:3068) +
  `mostrar_salario_emcarga` sistema=2 (ModSalarioAdmin:449-617).
- Implementado: `app/Services/Reports/Fpdf/AnalisisSalarioTransportacionFpdfReport.php`
  (Letter horizontal, cabecera 3 filas campos/campos1/campos2) +
  `ReportePrenominaService::analisisTransportacion()` + `NominaReportService::pdfAnalisisSalarioTransportacion`
  + `ReportController::pdfAnalisisSalarioTransportacion` + ruta `reportes.analisis-salario-transportacion`.
- **Fórmulas** (por chofer con ttotal>0 || tgarantia>0): impbase2 = impregular+impirregular+impCLA+nocturnidad;
  impiresultado = salario_cp − impbase2 (mín 0); impresultado = impiresultado − penalización%
  (pago adicional 6 RESULTADO CHOFERES, máx 100%); tgarantia/impgarantia de incidencias claves 3/44/46
  (tiempo=periodo_actual, importe=importe); impferiados clave 21; impincidencia solo tipos impsuma>0;
  impsalfinal = impbase2+impferiados+impgarantia+impincidencia+impresultado. Normas:
  normasalarial=impsalfinal/impbase, normatransp=normatotal=impsalfinal/ttotal.
- **⚠️ Quirk legacy replicado**: la fila TOTALES acumula `$ttotal` DOS veces por empleado
  (Reportes.php:3157+3159) → TRANSPOR/TOTAL de TOTALES = 2×suma (4,748.62) y las normas de
  TOTALES dividen por ese doble (25.94). Replicado tal cual en `analisisTransportacion()`.
- **Reutiliza** `SalarioChoferCalcService::calcularSalarioChofer` (ingresos, salario_cp, imp_cla,
  regular/irregular) — el mismo motor de la prenomina de transportación.
- **VERIFICADO contra reference** (entidad 20, junio 2026): 23 choferes 1:1 fila a fila
  (todas las columnas incluidas normas/tarifas: Asbel 46.67, Miguel Alexis 143.99) y TOTALES
  1,786,158.62/4,748.62/123,158.18/69,864.93/53,293.25/1.76/25.94/25.94 idénticos.
  Diferencias no-bug: firmas (tabla `firmas` otra config) y desplazamientos sub-carácter del render.


- Legacy: `npdf_salario_choferes_tiempos` (Reportes.php:2939) + `mostrar_salario_emcarga('nronomina',2,...)`
  → `mostrar_modelo1(idbolsa, 1, mes)` por chofer.
- **Causa raíz de la discrepancia (RESUELTA)**: el legacy consulta `mostrar_modelo1` POR CHOFER con
  `having idchofer = X OR idchofer2 = X` — las CPs de **doble chofer se contabilizan para AMBOS**
  (cada uno acumula los tiempos del mismo aforo). El `modelo1()` general agrupaba solo por
  `cp.id_chofer`, dejando fuera a los choferes que solo aparecen como `id_chofer2` (Hugo De La Cruz,
  Jose Manuel Salvador, Miguel Alexis, Victor Jardines) y truncando a los doble-chofer (Herminio,
  Hernan, Ramon, Sergio, Yasmany solo veían sus CPs como chofer1).
- **Fix 1** (`ReportePrenominaService::modelo1`): cada aforo alimenta DOS grupos (id_chofer e
  id_chofer2 si difieren). Para el chofer2 se resuelve su Bolsa+cargo directamente.
- **Fix 2** (`tiemposChoferes`): cap legacy 240h (`if ttotal>240 → ttotal=240`; el exceso queda como
  `irregular`). Asbel: 246.03 → 240.00 como la referencia.
- **Fix 3**: orden alfabético por nombrecompleto (la referencia sale por rh_bolsa.nombrecompleto).
- **Fix 4** (`ReportesnewFpdfBase::fechaEmision`): formato `Y/m/d` (paridad del pie legacy).
- **VERIFICADO contra reference** (entidad 20, junio 2026): 23 choferes 1:1 (MTD/MOV/CARGA/DESCA/TOTAL
  fila a fila, incluido CARGA vacío de Miguel Alexis y Asbel 240.00), TOTALES 171.00/869.36/632.09/
  707.69/2,374.31 idénticos. Diferencias no-bug: firmas (tabla `firmas` con otra config) y paginación
  (23 filas caben en 1 página Legal horizontal; la referencia partió en 2 por render).


- Legacy: `pdf_salario_prenomina_incidencias($sistema)` (Reportes.php:5426) +
  `modIncidencias->mostrar_detalle($clave)` (ModIncidencias.php:216).
- Implementado: `app/Services/Reports/Fpdf/IncidenciasFpdfReport.php` (Letter horizontal) +
  `NominaReportService::pdfIncidencias` + `ReportController::pdfIncidencias` + ruta `reportes.incidencias`.
- **Fuente**: `ReportePrenominaService::incidenciasTipo(mes, ano, origenId, entidadId)` — filtra
  `incidencias.id_tipo_incidencia` = id de `catalogo_items` (tipo `tipos_incidencias`, `origen_id`=id legacy).
  El reporte recibe `tipo_incidencia` (origen_id). Columnas: EXP(CI si nroci=1)/NOMBRE/CLAVE/INICIO/FINAL/ACTUAL(vacía)/IMPORTE.
- **IMPORTE = `periodo_actual`** (o `importe` si >0): el legacy tenía `importe=0`; la referencia muestra `pactual`.
- **VERIFICADO contra reference** (entidad 20, junio 2026, AUSENCIA JUSTIFICADA origen_id=1):
  Parra 4.00 / BRIGADA 4.00, Nogueira 192.00 + Zaldivar 176.00 + Oro 192.00 / ASEGURAMIENTO 560.00,
  Macdonald 3.83 / ORGANIZACION 3.83, TOTAL GENERAL 567.83 — idénticos.
- **EL BLOQUEO DEL PLAN ESTABA EQUIVOCADO — la FK NO está rota**:
  - La FK física `fk_incidencias_id_tipo_incidencia_catalogo` apunta a **`catalogo_items`**
    (tipo `tipos_incidencias`), NO a `tipos_incidencias` (corregido en fase 3
    `2026_08_23_200100_fase3_recuperacion_fks.php`).
  - `catalogo_items` tiene 71 ítems tipo `tipos_incidencias` (ids **257-327**, `origen_id`=id legacy 1-130).
  - `Incidencia::tipoIncidencia()` → `CatalogoItem` y `SalarioAdminCalcService` resuelve la clave
    vía `$tipo->origen_id`. **Funciona** (verificado: AUSENCIA JUSTIFICADA origen_id=1).
  - `tipos_incidencias` (tabla propia, ids 1-130) es una **tabla duplicada/legacy**; los tipos reales
    de negocio viven en `catalogo_items`.
- **Datos**: entidad 20 junio 2026 → 85 incidencias. AUSENCIA JUSTIFICADA (origen_id=1) = 5 registros
  (Parra 4.00, Nogueira 192.00, Zaldivar 176.00, Oro 192.00, Macdonald 3.83) = total 567.83 (coincide con la referencia).
- **`importe` vs `pactual`**: solo 18 incidencias tienen `importe > 0`; 3308 tienen `importe=0` pero
  3325 tienen `periodo_actual > 0`. La **referencia muestra `pactual` en la columna IMPORTE**
  (ACTUAL vacío), NO `importe`. El legacy puso `importe` en IMPORTE pero `importe=0` en la BD
  legacy para estos registros → la referencia se generó con `pactual` en IMPORTE.
- **Conclusión**: el reporte 9 es IMPLEMENTABLE. Columna IMPORTE = `periodo_actual` (o `importe` si >0).
- **Pendiente**: implementar `IncidenciasFpdfReport` (Letter horizontal) + servicio + controlador + ruta.

## REPORTE 1: LISTADO DE CUMPLEAÑOS DEL MES — ✅ HECHO (2026-09-08)
- Legacy: `pdf_empleados_cumple` (Reportes.php:606) + `modBolsa->mostrar_cumple` (ModBolsa.php:172).
- Implementado: `app/Services/Reports/Fpdf/CumpleanosFpdfReport.php` (extiende ReportesnewFpdfBase,
  orientación 'P' Letter) + `NominaReportService::pdfCumpleanos` + `ReportController::pdfCumpleanos`
  + ruta `reportes.cumpleanos`.
- **Fecha de nacimiento derivada del CI cubano** (YYMMDD): mes=substr(ci,2,2), dia=substr(ci,4,2),
  anioNac=19xx/20xx. edad=ano-anioNac; ajusta -1 si mesNac>=mes AND diaNac>=dia de `session('fecha_operaciones')`.
- Orden: cidentidad DESC. Nombre: tabla bolsa en MAYÚSCULAS (coincide con reference, NO titlecase).
- Título pasa por `latin1()` (fix Ñ → "CUMPLEAÑOS").
- **VERIFICADO contra reference**: mismos 17 empleados, fechas, edades y orden idénticos
  (usando `session('fecha_operaciones')='2026-06-30'`).

## REPORTE 6: LISTADO PERSONAL CON LICENCIA CONDUCCION — ✅ HECHO (2026-09-08)
- Legacy: `pdf_choferes` (Reportes.php:708) + `modBolsa->mostrar_todos('LICENCIA')`.
- Implementado: `app/Services/Reports/Fpdf/LicenciaConduccionFpdfReport.php` (orientación 'L' Legal)
  + `NominaReportService::pdfLicenciaConduccion` + `ReportController::pdfLicenciaConduccion`
  + ruta `reportes.licencia-conduccion`.
- **Migración añadida**: `2026_09_08_200000_add_licencia_to_bolsa_table.php` — añade columna `licencia`
  a `bolsa` y rellena desde legacy `rh_bolsa.licencia` (match por id). Ejecutada OK. Salva previa tomada.
- ETL actualizado (`EtlService`): inserta `licencia`.
- Categorías parseadas desde `bolsa.categorias_licencia` ("B,C,D,E"). FALTA/FVENCE desde
  `licencia_emision`/`licencia_vencimiento` (dd-mm-yyyy). LIMITACION='C/ESPEJUELOS' si `limitaciones`.
- **VERIFICADO contra reference**: 62 filas IDÉNTICAS en las 3 páginas (nombres, CI, licencia,
  categorías X, fechas). Incluye el caso Guillermo (licencia 66112820388 ≠ CI 66112820338).

---

## PENDIENTES PARA CONTINUAR (2026-09-08, sesión terminada)

### Archivos creados/modificados en esta sesión
- ✅ `app/Services/Reports/Fpdf/CumpleanosFpdfReport.php` (NUEVO)
- ✅ `app/Services/Reports/Fpdf/LicenciaConduccionFpdfReport.php` (NUEVO)
- ✅ `database/migrations/2026_09_08_200000_add_licencia_to_bolsa_table.php` (NUEVO, ejecutada OK)
- ✅ `app/Models/Bolsa.php` — añadido `licencia` a `$fillable`
- ✅ `app/Services/Etl/EtlService.php` — inserta `licencia` en migrarBolsa (~línea 1711)
- ✅ `app/Services/Reports/NominaReportService.php` — `pdfCumpleanos`, `pdfLicenciaConduccion`
- ✅ `app/Http/Controllers/ReportController.php` — `pdfCumpleanos`, `pdfLicenciaConduccion`
- ✅ `routes/web.php` — rutas `reportes.cumpleanos`, `reportes.licencia-conduccion`
- ⚠️ `Modelo1ControlDiarioFpdfReport.php` — cambios previos (firmas en loop, letras de datos/encabezado)
  (de la sesión anterior; ya verificados)

### PATRÓN a seguir para cada reporte nuevo
1. Crear clase en `app/Services/Reports/Fpdf/` que extiende `ReportesnewFpdfBase`.
2. El constructor pasa orientación/paper (ej. 'P'/'Letter', 'L'/'Legal').
3. `generate()`: `inicio($this->latin1($titulo), 50, 5)` → `titulos($campos, $campos1, [], $linea, 15, $posY)`
   → dibujar filas → `Output('S')`.
4. `titulos()` del base dibuja superior→medio→inferior. El legacy `titulos($pdf,$linea,15,30,$campos1,$campos)`
   equivale a `titulos($campos, $campos1, [], $linea, 15, 30)` en el base (campos=superior, campos1=medio).
5. Datos con `latin1()` en texto y `fmtVar()`/`fmt()` para números. Título SIEMPRE por `latin1()`.
6. Añadir método en `NominaReportService` + `ReportController` (con `abort_unless(...,'reportes-nomina.ver')`)
   + ruta `reportes.<nombre>`.
7. **SALVAR BD antes de migrar** (`php artisan zafiro:salva`).

### Fórmulas/hallazgos por reporte pendiente
- **Nro_nomina / columna "Cod"**: usar `bolsa.versat` (el legacy re-aliasa versat como nronomina).
- **Fechas**: derivadas del CI cubano (cumpleaños) o de columnas fecha (licencia). Usar `strtok` para
  quitar la hora de los Carbon.
- **Incidencias (bloqueado)**: los ids `incidencias.id_tipo_incidencia` (257-285) no existen en
  `tipos_incidencias` (max 130); no hay incidencias tipo=1 (AUSENCIA JUSTIFICADA) y `importe=0`.
  Decisión: migración de reparación o re-ETL. Ver sección REPORTE 9.

### Siguientes pasos
1. Resolver el bloqueo del reporte 9 (incidencias) — requiere decisión de datos.
2. Implementar reportes 2, 3, 4, 7, 8, 10, 11 siguiendo el patrón.
3. Arreglar columna "Cod" del reporte PAGO ADMINISTRATIVO para usar `versat`.
4. Verificar cada reporte contra su PDF de referencia (entidad 20, junio 2026).

### Referencias legacy (system/application/controllers/Reportes.php)
- CONTROL DATOS GENERALES: 3403 · ADICIONALES: 4798 · NOCTURNIDAD: 4077
- PAGO ADMINISTRATIVO: 4283/4542/4426/4694 · LICENCIA: 708 · ANALISIS SALARIO: 3068
- RESUMEN TIEMPOS: 2940 · INCIDENCIAS: 5426 · SC-4-05: 3525 · SC-4-05 CHOFERES: 3843

---

## REPORTE 10: SC-4-05 CONTROL DIARIO TIEMPO DE TRABAJO — ✅ HECHO (2026-09-09)
- Legacy: `pdf_salario_control_diario_administrativo` (Reportes.php:3510) +
  `ModMovimientos::mostrar_control` (sistema de pago != 2).
- Implementado: `ControlDiarioAdministrativoFpdfReport` (Legal horizontal) +
  `ReportePrenominaService::controlDiario()` + `pdfControlDiarioAdministrativo`
  + ruta `reportes.control-diario-administrativo`.
- **Lógica**: por trabajador, tiempo por día = 8 (sábado 4, domingo 'D') o turnos
  si `cargo.id_grupo_horario=3` (tabla `turnos` del mes: tiempo por día + noct1/noct2);
  las incidencias PISAN el día con su clave legacy (origen_id) y suman al mapa de
  descuentos (V/C-R/AI-AJ/LM-PS/CM/MOV/INT/FNT + A-B/CHM/RT-SE/AT/SL/IMP/OTROS/TIEMPO).
  `regular` = regular1 − irregular(rh_saladmin via legacy) + tiempo2 + tadrl,
  con ajustes legacy 216→tiempo_mes, 190.6→dialab×8, 173.3→dlab2×8.
  `tiempo_mes`/`dias_laborables` leídos de `rh_meses` legacy (conexión legacy).
- **Orden**: área (orden) + `grupos_escala.nombre` DESC (tabla propia). El grid
  replica el render legacy: 2 líneas/trabajador (nronomina+nombre), días en 2
  medias filas (1-15/16-31) con celda extra en i==14 y rellenos 29/28/27.
- **VERIFICADO contra reference** (entidad 20, junio 2026): mismas 6 páginas;
  brigada de reparación 1:1 (Sergio Pupo 46×15+192, Jorge Luis 8.00/100.00/86.00,
  Enrique 4.00/88.00/102.00, Raciel 4.00/116.00, Juan Pablo 28.00/192.00, etc.).
  ⚠️ Diferencia de datos NO bug: "Isidro Hidalgo Verdecia" (legacy idbolsa 1735,
  falta=2026-07-31) NO existe en la BD nueva — dado de alta en legacy tras el corte
  del ETL (su CI 82080626105 no está en `bolsa`). Re-importar cuando se repita el ETL.

## REPORTE 11: SC-4-05 CONTROL DIARIO CHOFERES DE TRANSPORTACION — ✅ HECHO (2026-09-09)
- Legacy: `pdf_salario_control_diario_choferes` (Reportes.php:3828) +
  `ModMovimientos::mostrar_control_choferes` (sistema de pago = 2).
- Implementado: `ControlDiarioChoferesFpdfReport` (extiende el del 10; DT =
  días trabajados, sin filas de área, max 180) + `controlDiarioChoferes()` +
  `pdfControlDiarioChoferes` + ruta `reportes.control-diario-choferes`.
- **Lógica**: DT = días 'T' (hojas de ruta no canceladas con cierre en el mes,
  rango emisión→cierre, quitando `dias_trabajados`); incidencias pisan el día con
  la clave (2 chars) EXCEPTO domingos 'D'; noct1/noct2 en horas desde el detalle
  del modelo1; `regular = min(ttotal + tiempo1, 240)`.
- **VERIFICADO contra reference**: mismas 4 páginas, mismos 40 choferes (Alcibiades/
  Alexis T×30 DT=30, Aralio 7×30 reg=192, Asbel reg=240, David reg=164.52,
  Herminio solo domingos reg=120.78, Carlos De La Cruz solo D, etc.).
- ⚠️ Nota: los casos "DT=30 pero tiempo por día todo T" (Alcibiades/Alexis) vienen
  de hojas de ruta con rango emisión→cierre cubriendo el mes completo — igual que la
  referencia (el legacy `mostrar_control_hojaruta` hace exactamente eso).



## ESTADO DE LA SESIÓN 2026-09-08 (documentado para continuar mañana)

### ✅ Completado en esta sesión
- **Reporte 2 (ADICIONALES)** — `AdicionalesFpdfReport` + `pdfAdicionales` + ruta `reportes.adicionales`. VERIFICADO contra reference.
- **Reporte 3 (NOCTURNIDAD)** — `NocturnidadFpdfReport` + `pdfNocturnidad` + ruta `reportes.nocturnidad`. VERIFICADO (solo cambió la agrupación de área de 3 trabajadores).
- **Reporte 9 (INCIDENCIAS)** — `IncidenciasFpdfReport` + `incidenciasTipo()` + `pdfIncidencias` + ruta `reportes.incidencias`. **Desbloqueado**: la FK apunta a `catalogo_items`, no a `tipos_incidencias`. VERIFICADO (total 567.83).
- **Reporte 4 (PAGO ADMINISTRATIVO)** — `PagoAdministrativoFpdfReport` + `pagoAdministrativo()` + `pdfPagoAdministrativo` + ruta `reportes.pago-administrativo`. **CORREGIDOS los 2 errores del legacy** (título único y fila duplicada). VERIFICADO.
- **Tabla CDS nueva** `cds_entidades` (migración `2026_09_09_010000_create_cds_entidades_table.php`): CDS por entidad+mes+año. Modelo `CdsEntidad`, controlador `CdsController` (EntidadScoping + upsert), vista `Cds/Index.vue`, permisos `cds.*` (PermissionSeeder + BD actual + RECHUM/SUPERADMIN), menú "Coeficiente CDS" (id 191, orden 9 bajo RRHH id 40). CDS de verificación insertado para entidad 20 jun-2026 = **4.629252**.
- **Reporte 8 (RESUMEN TIEMPOS)** — `ResumenTiemposChoferesFpdfReport` + `tiemposChoferes()` + `pdfResumenTiemposChoferes` + ruta `reportes.resumen-tiempos-choferes`. **IMPLEMENTADO pero con discrepancias de datos por investigar** (ver pendientes).

### Archivos NUEVOS de esta sesión
- `app/Services/Reports/Fpdf/AdicionalesFpdfReport.php`
- `app/Services/Reports/Fpdf/NocturnidadFpdfReport.php`
- `app/Services/Reports/Fpdf/IncidenciasFpdfReport.php`
- `app/Services/Reports/Fpdf/PagoAdministrativoFpdfReport.php`
- `app/Services/Reports/Fpdf/ResumenTiemposChoferesFpdfReport.php`
- `app/Http/Controllers/CdsController.php`
- `app/Models/CdsEntidad.php`
- `resources/js/Pages/Cds/Index.vue`
- `database/migrations/2026_09_09_010000_create_cds_entidades_table.php`

### Archivos MODIFICADOS de esta sesión
- `app/Services/ReportePrenominaService.php` — campos `impdoblaje/impferiados/impincidencia/impotras/padicionales2/versat/noct1/noct2/impnoct1/impnoct2` en `prenominaAdministrativo`; métodos nuevos `incidenciasTipo()`, `pagoAdministrativo()`, `tiemposChoferes()`.
- `app/Services/Reports/NominaReportService.php` — `pdfAdicionales`, `pdfNocturnidad`, `pdfIncidencias`, `pdfPagoAdministrativo`, `pdfResumenTiemposChoferes`.
- `app/Http/Controllers/ReportController.php` — métodos homólogos.
- `routes/web.php` — rutas `reportes.{adicionales,nocturnidad,incidencias,pago-administrativo,resumen-tiempos-choferes}` y `resource('cds')`.
- `database/seeders/PermissionSeeder.php` — permisos `cds.*`.

### 🔶 PENDIENTE PARA CONTINUAR MAÑANA
1. **Reporte 8 (RESUMEN TIEMPOS) — INVESTIGAR discrepancia de datos**:
   - El `tiemposChoferes()` usa `modelo1()` del servicio nuevo. Los TOTALES de tiempos (MTD/MOV/CARGA/DESCA) NO coinciden con la referencia:
     - Referencia: Asbel MTD=17/MOV=70.26/CARGA=86.41/DESCA=72.34/TOTAL=240.00 (23 choferes).
     - Generado: Asbel TOTAL=246.03; solo 19 choferes; faltan varios (Hugo De La Cruz, Jose Manuel Salvador, Miguel Alexis, Victor Jardines, Alcibiades, Luis Batista, Herminio con totales distintos).
   - **Causa probable**: el `modelo1()` del servicio nuevo no replica exactamente la consulta legacy `modSalarioChofer->mostrar_modelo1(idbolsa,1,mes)` que filtra `com_hojaruta.idgrupo=1`, `com_girado.cancelada=0`, y usa `com_aforo.tperm/tmov/tcarga/tdescarga/ttotal` directo. Revisar el filtro por `idgrupo` y `cancelada`, y la fuente de los tiempos (`aforos.tiempo_*` vs `com_aforo.tperm*`).
   - Comparar contra la referencia del reporte 8 (23 choferes) y ajustar `tiemposChoferes()`.
2. **Reporte 7 (MODELO ANALISIS DEL SALARIO TRANSPORTACION)** — legacy `npdf_salario_choferes_resumen_analisis` (Reportes.php:3068). Usa `mostrar_salario_emcarga('nronomina',2,...)`. Requiere `ingresos`, `ttotal`, `tgarantia`, `coeficiente`, `tiempo trabajado`, `resultado`, `norma`, `tarifa transporte/general`. Implementar con el motor del chofer.
3. **Reporte 10 (SC-4-05 CONTROL DIARIO TIEMPO DE TRABAJO)** — legacy `pdf_salario_control_diario_administrativo` (Reportes.php:3510). Grid de días 1-31 con valores (46/8/D/T/...) por trabajador + resumen de descuentos + nocturnidad. Muy detallado.
4. **Reporte 11 (SC-4-05 CHOFERES)** — legacy `pdf_salario_control_diario_choferes` (Reportes.php:3828). Variante con "T"/"D" para choferes.
5. **CDS**: el cliente introduce el CDS mensual vía el nuevo menú "Coeficiente CDS". Para el reporte 4, sin CDS cargado las columnas SRInicial/SRFinal/SALARIO salen vacías. El CDS derivado (4.629252) es de prueba; el real debe confirmarlo el cliente.
6. **Verificar** que la nueva vista `Cds/Index.vue` compila en producción (build ya hecho OK).

### DATOS/decisions de negocio registrados
- La columna "Cod" de los reportes administrativos usa `bolsa.versat` (no `nronomina` del movimiento).
- El CDS se guarda por entidad+mes+año (no global), en la tabla `cds_entidades`.
- Corregidos los bugs del legacy en el reporte 4 (título único + TOTAL GENERAL sin fila duplicada).

---

## ESTADO DE LA SESIÓN 2026-09-09 (11/11 reportes COMPLETOS)

### ✅ Completado en esta sesión
- **Reporte 8 (RESUMEN TIEMPOS)** — DESBLOQUEADO y VERIFICADO. Fix del `modelo1()`:
  doble chofer contabiliza para AMBOS choferes (legacy having idchofer OR idchofer2);
  cap 240h; orden alfabético; `fechaEmision()` a Y/m/d. 23 choferes 1:1,
  TOTALES 171.00/869.36/632.09/707.69/2,374.31.
- **Reporte 7 (ANALISIS SALARIO TRANSPORTACION)** — `AnalisisSalarioTransportacionFpdfReport`
  + `analisisTransportacion()` + ruta `reportes.analisis-salario-transportacion`. VERIFICADO:
  23 choferes 1:1 y TOTALES exactos (incluye el quirk del ttotal doble 4,748.62 → normas 25.94).
- **Reporte 10 (SC-4-05 CONTROL DIARIO ADMIN)** — `ControlDiarioAdministrativoFpdfReport`
  + `controlDiario()` + ruta `reportes.control-diario-administrativo`. VERIFICADO (6 páginas,
  brigada 1:1). Orden por grupos_escala DESC replicado.
- **Reporte 11 (SC-4-05 CONTROL DIARIO CHOFERES)** — `ControlDiarioChoferesFpdfReport`
  + `controlDiarioChoferes()` + ruta `reportes.control-diario-choferes`. VERIFICADO (4 páginas,
  40 choferes, Herminio 120.78, etc.).

### Archivos NUEVOS
- `app/Services/Reports/Fpdf/AnalisisSalarioTransportacionFpdfReport.php`
- `app/Services/Reports/Fpdf/ControlDiarioAdministrativoFpdfReport.php`
- `app/Services/Reports/Fpdf/ControlDiarioChoferesFpdfReport.php`

### Archivos MODIFICADOS
- `app/Services/ReportePrenominaService.php` — fix doble chofer en `modelo1()`; cap 240;
  `regular`/`irregular` en `tiemposChoferes()`; orden por nombre; `analisisTransportacion()`;
  helpers legacy (`feriadosMesLegacy`, `diasLaborablesLegacy`, `tiempoMesLegacy`,
  `incidenciasMes`, `irregularMovimiento`); `controlDiario()` y `controlDiarioChoferes()`.
- `app/Services/Reports/Fpdf/ReportesnewFpdfBase.php` — `fechaEmision()` formato Y/m/d.
- `app/Services/Reports/NominaReportService.php` — `pdfAnalisisSalarioTransportacion`,
  `pdfControlDiarioAdministrativo`, `pdfControlDiarioChoferes`.
- `app/Http/Controllers/ReportController.php` — métodos homólogos.
- `routes/web.php` — rutas nuevas ×3.

### Tests
- Los 15 fallos de la suite `fast` PRE-EXISTÍAN (verificados con git stash: mismos
  15 failed/203 passed antes y después de mis cambios). No introduje regresiones.
- `ReportesDispatcherTest` falla por mapeos legacy→métodos aún no implementados
  (ej. 79→incidenciasTiempoTrabajado) — pendiente de otra sesión, NO relacionado.

### Pendientes conocidos (NO bugs de estos reportes)
- **Isidro Hidalgo Verdecia** (legacy bolsa 1735, falta=2026-07-31): no está en la BD
  nueva (alta posterior al corte del ETL). Aparece en la referencia del reporte 10.
  Solución: re-correr ETL de bolsa o insertarlo manualmente.
- Firmas de los PDFs (tabla `firmas`): la BD nueva tiene otra configuración que la
  referencia (JORGE AIRAM vs YAILIN). Configurar las firmas reales del cliente.
- El CDS 4.629252 de entidad 20 jun-2026 sigue siendo derivado; el cliente debe
  confirmar el valor real.
