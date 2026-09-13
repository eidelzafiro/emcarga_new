# Reportes legacy: inventario y faltantes por elaborar

Fecha: 2026-09-13
Fuente: tabla `reportes` de la BD legacy `emcarga` (204 filas) cruzada con
`App\Services\Reports\ReportesDispatcher::MAPA` y las rutas dedicadas
`ReportController` (`reportes.*`).

## Resumen

| Métrica | Total |
|---|---|
| Reportes en `reportes` (legacy) | 204 |
| Marcados como usados (algún perfil = 1) | 187 |
| Enrutados por `ReportesDispatcher::MAPA` | 173 |
| Servidos por rutas dedicadas de RRHH (no pasan por el dispatcher) | ~10 |
| **Usados sin implementación real** | **21** |

Los reportes de RRHH/Nómina no usan el dispatcher (sus métodos esperan
`Request`); se sirven por las rutas `reportes.*` de `ReportController` y la
página `Reportes/Salarios.vue`.

## Faltantes reales por elaborar

### Nómina administrativa (grupo `1.DATOS P/NOMINAS(A CALCULAR)`)
| ID | Reporte | Controlador legacy |
|---|---|---|
| 80 | 04. PENALIZACIONES X PAGO ADICIONAL | `reportes/pdf_salario_penalizacion/` |
| 87 | 01. DATOS DE LOS TRABAJADORES | `reportes/pdf_salario_datos_trabajadores/` |
| 91 | 05. SALARIOS X SISTEMA DE PAGO | `reportes/pdf_salario_prenomina_sistema/` |
| 1066 | 11. RESUMEN DE SALARIOS X CONCEPTOS E INCIDENCIAS | `reportes/pdf_salario_resumen_concepto_admin/` |
| 4067 | 06. SALARIOS X SISTEMA DE PAGO CON RESULTADOS (HOLGUÍN) | `reportes/pdf_salario_prenomina_resultado_adm/1` |
| 4068 | 06. SALARIOS X SISTEMA DE PAGO CON RESULTADOS PAQUETERÍA (HOLGUÍN) | `reportes/pdf_salario_prenomina_resultado_adm/3` |

### Nómina choferes (grupo `2.DATOS P/NOMINAS(CHOFERES)`)
| ID | Reporte | Controlador legacy |
|---|---|---|
| 122 | 03. MODELO 1 CONTROL TRANSPORTACIONES (DETALLE) | `reportes/npdf_salario_modelo1/` |
| 123 | 12. LISTADO GARANTÍA SALARIAL | `reportes/npdf_listado_choferes_garantia/` |
| 124 | 09. PAGO X GARANTÍA SALARIAL | `reportes/npdf_salario_choferes_garantia/` |
| 125 | 08. PAGO DE SALARIOS | `reportes/npdf_salario_choferes_resumen/` |

### Certificaciones (grupo `CERTIFICOS`)
| ID | Reporte | Controlador legacy |
|---|---|---|
| 1048 | CERTIFICACIÓN CHOFERES ÁREA COMERCIAL | `reportes2/pdf_chofer_certificacion/` |
| 1055 | RESUMEN GASTOS DIETAS | `reportes2/pdf_contabilidad_dietas_resumen/` |
| 1056 | RESUMEN INDICADORES EXPLOTACIÓN X CHOFERES | `reportes2/pdf_indicadores_resumen/` |
| 1057 | RESUMEN INGRESOS X CHOFERES | `reportesnew/pdf_ingresos_resumen/` |
| 1060 | CERTIFICACIÓN CHOFERES GASTOS X EQUIPO | `reportes2/pdf_certifico_gastos/` |
| 1062 | CERTIFICACIÓN CHOFERES CUMPLIMIENTO DEL CDT X EQUIPO | `reportestec/pdf_certifico_cdt_tractivos/` |
| 1064 | CERTIFICACIÓN CHOFERES INGRESO POR ALMACENAMIENTO | `reportes2/pdf_certifico_almacenamiento/` |
| 1068 | CERTIFICACIÓN CHOFERES ÁREA COMERCIAL (EXCEL) | `reportes2/pdf_chofer_certificacion_excel/` |
| 1072 | CERTIFICACIÓN CHOFERES ÁREA COMERCIAL TONELADAS-KM | `reportes2/pdf_chofer_certificacion_tnskms/` |
| 1073 | CERTIFICACIÓN CHOFERES ÁREA COMERCIAL TONELADAS-KM (EXCEL) | `reportes2/pdf_chofer_certificacion_tnskms_excel/` |
| 4060 | REPORTE DE OPERACIONES EMCARGA | `reportes2/excel_resumen_emcarga/` |

## Faltantes de MAPA que SÍ están implementados por ruta dedicada
Se retiraron del `MAPA` (no son del dispatcher) pero tienen ruta y página:

| ID | Reporte | Ruta Zafiro |
|---|---|---|
| 79 | INCIDENCIAS EN EL TIEMPO TRABAJADO | `reportes.incidencias` |
| 88 | PAGOS ADICIONALES | `reportes.adicionales` |
| 90 | PAGOS X NOCTURNIDAD | `reportes.nocturnidad` |
| 92 | CONTROL DIARIO DEL TRABAJO (ADMIN.) | `reportes.control-diario-administrativo` |
| 1046 / 1074 | EXPORTAR PRENÓMINA A EXCEL | `reportes.prenomina-*-excel` / `reportes.exportar-versat` |
| 119 | ANÁLISIS INDICADORES SALARIOS | `reportes.analisis-salario-transportacion` |
| 120 | ANÁLISIS DE LOS TIEMPOS | `reportes.resumen-tiempos-choferes` |
| 121 | MODELO 1 CONTROL TRANSPORTACIONES | `reportes.modelo1` |
| 1047 | CONTROL DIARIO DEL TRABAJO DE LOS CHOFERES | `reportes.control-diario-choferes` |

## Notas
- Los 17 reportes legacy restantes (204 − 187) no están marcados como usados por
  ningún perfil y quedan fuera del alcance.
- `reportes.resumen` (Resumen de Ingresos e Indicadores de Explotación) es una
  implementación nueva que cubre parcialmente #1056/#1057, pero no está mapeada a
  los ids legacy.
- El inventario se genera con: `docker compose exec -T app php /tmp/reportes_audit.php`.
