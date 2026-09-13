# Auditoría de paridad de reportes (Zafiro vs legacy)

Fecha: 2026-09-13

## Metodología
1. Cada reporte implementado en Zafiro declara en su clase FPDF el método legacy
   que replica (docblock). Se listan abajo.
2. La paridad **real** se confirma comparando el PDF generado por Zafiro contra
   el PDF de referencia del legacy (`zafiro26/reportes/{combustibles,tecnica,facturacion}`
   y las referencias de RRHH), hoja por hoja: columnas, anchos, subtotales y saltos.
3. Un reporte con "réplica declarada" puede tener diferencias no verificadas
   (datos, totales, formato). Los marcados "SIN referencia" no declaran su
   método legacy y hay que revisarlos.

## Reportes FPDF y método legacy declarado

| Clase Zafiro | Método legacy |
|---|---|
| AdicionalesFpdfReport | pdf_salario_prenomina_adicional |
| AnalisisSalarioTransportacionFpdfReport | npdf_salario_choferes_resumen_analisis |
| CombustibleFpdfReport | pdf_combustible_tanque, pdf_combustible_tarjetas |
| ControlDiarioAdministrativoFpdfReport | pdf_salario_control_diario_administrativo |
| ControlDiarioChoferesFpdfReport | pdf_salario_control_diario_choferes |
| CumpleanosFpdfReport | pdf_empleados_cumple |
| DocumentosFpdfBase | pdf_codificadores |
| DocumentosFpdfReport | pdf_cp_estado |
| IncidenciasFpdfReport | pdf_salario_prenomina_incidencias |
| IndicadoresResumenFpdfReport | pdf_indicadores_resumen, pdf_indicadores_organismo_producto |
| IngresosChoferesFpdfReport | pdf_ingresos_choferes |
| IngresosResumenFpdfReport | pdf_ingresos_resumen_otros, pdf_ingresos_resumen_tractivo, pdf_ingresos_organismo_producto |
| LicenciaConduccionFpdfReport | pdf_choferes |
| Modelo1ControlDiarioFpdfReport | npdf_salario_choferes_modelo1_transcar |
| NocturnidadFpdfReport | pdf_salario_prenomina_nocturnidad |
| PagoAdministrativoFpdfReport | pdf_salario_prenomina_resultado_adm |
| PrenominaTransportacionFpdfReport | npdf_salario_choferes_resumen |
| ResumenTiemposChoferesFpdfReport | npdf_salario_choferes_tiempos |
| FacturacionFpdfReport | (usa traits; verificar) |
| PrenominaAdministrativoFpdfReport | (verificar) |
| TecnicaFpdfReport | (usa traits; verificar) |

## Grupos con traits (Técnica / Combustibles / Facturación)
- Técnica: `TecnicaGeneral`, `TecnicaTaller`, `TecnicaNeumaticos`, `TecnicaBateriasParque`.
- Combustible: `Combustible126` + `CombustibleFpdfReport`.
- Facturación: `FacturacionFpdfReport`.

Estos grupos se portaron en los commits `ce9537d` (66 reportes) y anteriores.
La paridad se valida contra los PDF de referencia de `zafiro26/reportes/`.

## Candidatos a revisar/reescribir
- **Ingresos e Indicadores** (`ResumenExplotacionService` + `IngresosResumenFpdfReport`
  + `IndicadoresResumenFpdfReport`): declaran réplica de `pdf_ingresos_resumen_*`
  y `pdf_indicadores_resumen`, pero el usuario reporta que la salida no coincide
  con el formato legacy. **Prioridad 1 de reescritura.**
- Reportes sin referencia legacy declarada en docblock (`FacturacionFpdfReport`,
  `PrenominaAdministrativoFpdfReport`, `TecnicaFpdfReport`): verificar si su
  diseño coincide con el legacy.
- Reportes nuevos sin equivalente legacy (p. ej. impresión por coordenadas
  `ImpresionCoordenadasService`, `AforoReportService`): no aplica paridad.

## Recomendación
1. Rehacer **Ingresos/Indicadores** con paridad exacta a `pdf_ingresos_resumen_*`
   y `pdf_indicadores_resumen`.
2. Verificación visual sistemática de Técnica (33), Combustibles (23) y
   Facturación (10) contra los PDF de referencia.
3. Implementar los 21 reportes legacy faltantes (ver `REPORTES_FALTANTES.md`).
