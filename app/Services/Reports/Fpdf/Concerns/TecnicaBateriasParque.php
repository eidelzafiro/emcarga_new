<?php

namespace App\Services\Reports\Fpdf\Concerns;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Reportes de baterías y parque de vehículos. Replican el legacy
 * `Reportestec.php` (FPDF) con el mismo layout, anchos, saltos de página,
 * totales y firmas.
 *
 *  - id 145 → pdfControlBateriasActivas()   (legacy pdf_baterias_tractivos_activas)
 *  - id 151 → pdfPlanBajasBaterias($mes)    (legacy pdf_plan_bajabaterias)
 *  - id 152 → pdfInformacionBaterias($mes)  (legacy pdf_resumenbaterias)
 */
trait TecnicaBateriasParque
{
    /** id 145 — CONTROL DE BATERIAS ACTIVAS X TRACTIVOS. */
    public function pdfControlBateriasActivas(): \Illuminate\Http\Response
    {
        $titulo = 'CONTROL DE BATERIAS ACTIVAS X TRACTIVOS';

        $campos1 = [
            ['titulo' => 'CODIGO', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LRBT', 'letra' => 8],
            ['titulo' => 'FECHA ALTA', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LRBT', 'letra' => 8],
            ['titulo' => 'MARCA', 'ancho' => 50, 'direccion' => 'C', 'bordes' => 'LRBT', 'letra' => 8],
            ['titulo' => 'VOLT', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LRBT', 'letra' => 8],
            ['titulo' => 'AMP', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LRBT', 'letra' => 8],
            ['titulo' => 'VIDA UTIL', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LTRB', 'letra' => 8],
        ];

        $data = [];
        foreach ($this->bateriasBase() as $b) {
            if ($b->fecha_retiro !== null) {
                continue;
            }
            $b->codtractivo = $b->codtractivo ?: (string) ($b->destino ?? '');
            $b->duracion = $this->duracionBateria($b, 0);
            $data[] = $b;
        }
        usort($data, fn ($a, $b) => [$a->codtractivo, (string) $a->folio] <=> [$b->codtractivo, (string) $b->folio]);

        if (! $data) {
            $this->inicio($titulo);
            $this->SetFont('Arial', 'B', 25);
            $this->SetFillColor(255, 255, 255);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');

            return $this->salida($titulo.'.pdf');
        }

        $this->inicio($titulo);
        $posY = 30;
        $i = 1;
        $max = 35;
        $flag = null;

        foreach ($data as $b) {
            if ($flag !== $b->codtractivo) {
                $flag = $b->codtractivo;
                $campos = [[
                    'titulo' => 'DESTINO: '.$this->txt((string) $flag),
                    'ancho' => 175, 'direccion' => 'L', 'bordes' => 'LRBT', 'letra' => 8,
                ]];
                $this->titulos(6, 10, $posY, $campos1, $campos);
                $posY += 12;
                $i += 2;
            }

            $this->SetFont('Arial', '', 10);
            $this->SetXY(10, $posY);
            $this->SetFillColor(200, 200, 200);
            $this->Cell(25, 6, $this->txt((string) $b->folio), 1, 0, 'C', 1);
            $this->Cell(25, 6, $this->txt((string) $b->fecha_instalacion), 1, 0, 'C', 1);
            $this->Cell(50, 6, $this->txt((string) $b->marca), 1, 0, 'C', 1);
            $this->Cell(25, 6, $this->txt((string) $b->voltaje), 1, 0, 'C', 1);
            $this->Cell(25, 6, $this->txt((string) $b->amperaje), 1, 0, 'C', 1);
            $this->Cell(25, 6, $this->txt((string) $b->duracion), 1, 0, 'C', 1);
            $posY += 6;
            $i++;

            if ($i >= $max) {
                $campos = [[
                    'titulo' => 'DESTINO: '.$this->txt((string) $flag),
                    'ancho' => 175, 'direccion' => 'L', 'bordes' => 'LRBT', 'letra' => 8,
                ]];
                $this->inicio($titulo);
                $this->titulos(6, 10, 30, $campos1, $campos);
                $posY = 42;
                $i = 1;
            }
        }

        return $this->salida($titulo.'.pdf');
    }

    /** id 151 — PLAN DE BAJAS DE BATERIAS (mes). */
    public function pdfPlanBajasBaterias(?string $mes = null): \Illuminate\Http\Response
    {
        $titulo = 'PLAN DE BAJAS DE BATERIAS';
        [$anio, $mesNum] = $this->anioMes($mes);
        $mesStr = sprintf('%02d', $mesNum);

        $campos1 = [
            ['titulo' => 'No VEHICULO', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LRBT', 'letra' => 8],
            ['titulo' => 'No BATERIA', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRBT', 'letra' => 8],
            ['titulo' => 'F/ INSTALADA', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LRBT', 'letra' => 8],
            ['titulo' => 'MARCA', 'ancho' => 50, 'direccion' => 'C', 'bordes' => 'LRBT', 'letra' => 8],
            ['titulo' => 'VOLTAJE', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRBT', 'letra' => 8],
            ['titulo' => 'CAPACIDAD AMP', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LRBT', 'letra' => 8],
            ['titulo' => 'FECHA PLANEADA', 'ancho' => 35, 'direccion' => 'C', 'bordes' => 'LRBT', 'letra' => 8],
        ];

        $vidas = DB::table('entidades')
            ->whereIn('id', $this->entidadIds)
            ->pluck('vida_bateria', 'id')
            ->all();

        $data = [];
        foreach ($this->bateriasBase() as $b) {
            $b->codtractivo = $b->codtractivo ?: (string) ($b->destino ?? '');
            $vida = (int) ($vidas[$b->id_entidad] ?? 0);
            $b->fecha2 = $b->fecha_instalacion
                ? Carbon::parse($b->fecha_instalacion)->addMonths($vida)->format('Y-m-d')
                : null;

            if ($b->fecha2 === null) {
                continue;
            }
            if ($mesNum === 0) {
                if ((int) substr($b->fecha2, 0, 4) < $anio) {
                    continue;
                }
            } else {
                if ((int) substr($b->fecha2, 0, 4) !== $anio || (int) substr($b->fecha2, 5, 2) !== $mesNum) {
                    continue;
                }
            }
            $data[] = $b;
        }
        usort($data, fn ($a, $b) => [$a->fecha_instalacion, $a->codtractivo] <=> [$b->fecha_instalacion, $b->codtractivo]);

        if (! $data) {
            $this->inicio($titulo, $mesStr);
            $this->SetFont('Arial', 'B', 25);
            $this->SetFillColor(255, 255, 255);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');

            return $this->salida($titulo.'.pdf');
        }

        $this->inicio($titulo, $mesStr);
        $this->titulos(6, 10, 30, $campos1);
        $posY = 42;
        $i = 1;
        $max = 33;
        $color = true;

        foreach ($data as $b) {
            $this->SetFont('Arial', '', 10);
            $this->SetXY(10, $posY);
            $this->SetFillColor($color ? 255 : 200, $color ? 255 : 200, $color ? 255 : 200);
            $this->Cell(25, 6, $this->txt((string) $b->codtractivo), 1, 0, 'L', 1);
            $this->Cell(20, 6, $this->txt((string) $b->folio), 1, 0, 'C', 1);
            $this->Cell(25, 6, $this->txt((string) $b->fecha_instalacion), 1, 0, 'C', 1);
            $this->Cell(50, 6, $this->txt((string) $b->marca), 1, 0, 'C', 1);
            $this->Cell(20, 6, $this->txt((string) $b->voltaje), 1, 0, 'C', 1);
            $this->Cell(25, 6, $this->txt((string) $b->amperaje), 1, 0, 'C', 1);
            $fecha = substr($b->fecha2, 0, 4).'-'.$this->nombreMesCompleto(sprintf('%02d', (int) substr($b->fecha2, 5, 2)));
            $this->Cell(35, 6, $this->txt($fecha), 1, 0, 'C', 1);

            $color = ! $color;
            $posY += 6;
            $i++;

            if ($i >= $max) {
                $this->inicio($titulo, $mesStr);
                $this->titulos(6, 10, 30, $campos1);
                $posY = 42;
                $i = 1;
                $color = true;
            }
        }

        return $this->salida($titulo.'.pdf');
    }

    /** id 152 — INFORMACION GENERAL DE BATERIAS (mes). */
    public function pdfInformacionBaterias(?string $mes = null): \Illuminate\Http\Response
    {
        $titulo = 'INFORMACION GENERAL DE BATERIAS';
        [$anio, $mesNum] = $this->anioMes($mes);
        $mesStr = sprintf('%02d', $mesNum);

        // Tres filas de encabezado (arriba → abajo): marcas, subtítulos y causas.
        $campos = [
            ['titulo' => 'MARCA', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => 8],
            ['titulo' => 'CAP', 'ancho' => 10, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => 8],
            ['titulo' => 'COSTO', 'ancho' => 30, 'direccion' => 'C', 'bordes' => '1', 'letra' => 8],
            ['titulo' => 'INSTA', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => 8],
            ['titulo' => 'CAIDAS', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => 8],
            ['titulo' => 'DURACION', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => 8],
            ['titulo' => 'DURABILIDAD', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => 8],
            ['titulo' => 'COSTO', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => 8],
            ['titulo' => 'CAUSAS DE BAJA ', 'ancho' => 100, 'direccion' => 'C', 'bordes' => 1, 'letra' => 8],
        ];
        $campos1 = [
            ['titulo' => '', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LR', 'letra' => 8],
            ['titulo' => 'AMP', 'ancho' => 10, 'direccion' => 'C', 'bordes' => 'LR', 'letra' => 8],
            ['titulo' => 'MN', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => 8],
            ['titulo' => 'ME', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => 8],
            ['titulo' => 'LADAS', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LR', 'letra' => 8],
            ['titulo' => '', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LR', 'letra' => 8],
            ['titulo' => 'MESES', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LR', 'letra' => 8],
            ['titulo' => 'PROMEDIO', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LR', 'letra' => 8],
            ['titulo' => 'RENDIMIENTO', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LR', 'letra' => 8],
            ['titulo' => 'Accidentes', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => 8],
            ['titulo' => 'Partes', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => 8],
            ['titulo' => 'Vaso', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => 8],
            ['titulo' => 'Vejez', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => 8],
            ['titulo' => 'Baja', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => 8],
        ];
        $campos2 = [
            ['titulo' => '', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => 8],
            ['titulo' => '', 'ancho' => 10, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => 8],
            ['titulo' => '', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => 8],
            ['titulo' => '', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => 8],
            ['titulo' => '', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => 8],
            ['titulo' => '', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => 8],
            ['titulo' => '', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => 8],
            ['titulo' => '', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => 8],
            ['titulo' => '', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => 8],
            ['titulo' => 'Defectuosas', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => 8],
            ['titulo' => 'Defectuoso', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => 8],
            ['titulo' => '', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => 8],
            ['titulo' => '', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => 8],
            ['titulo' => '', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => 8],
        ];

        $this->forzarLandscape();

        // Agrupación marca → amperaje (equivalente a mostrar_marcas/mostrar_amperajes/mostrar_resumen).
        $grupos = [];
        foreach ($this->bateriasBase() as $b) {
            $grupos[$b->id_marca]['nombre'] = (string) $b->marca;
            $grupos[$b->id_marca]['amps'][(string) $b->amperaje][] = $b;
        }

        $motivos = DB::table('catalogo_items')
            ->where('tipo', 'motivos_baja_bateria')
            ->pluck('origen_id', 'id')
            ->all();

        $posY = 48;
        $i = 1;
        $max = 20;
        $cont = 0;
        $totInstaladas = 0;
        $totBaja = 0;
        $totDuracion = 0.0;
        $totCAc = 0;
        $totCPar = 0;
        $totCVaso = 0;
        $totCVejes = 0;
        $totCBaja = 0;
        $totCosto = 0.0;

        $flag = true;
        $marcTemp = '';

        $this->inicio($titulo, $mesStr);
        $this->firmasBaterias();

        foreach ($grupos as $grupo) {
            foreach ($grupo['amps'] as $amperaje => $baterias) {
                $instaladas = 0;
                $caidas = 0;
                $duracion = 0.0;
                $accidente = 0;
                $partes = 0;
                $vasos = 0;
                $vejez = 0;
                $baja = 0;
                $ultima = null;

                foreach ($baterias as $bateria) {
                    $ultima = $bateria;
                    if (! $this->resumenPasa($bateria, $anio, $mesNum)) {
                        continue;
                    }
                    if ($bateria->fecha_retiro !== null && $this->mesDe($bateria->fecha_retiro) === ($mesNum === 0 ? null : $mesNum)) {
                        $duracion += $this->duracionBateria($bateria, 1);
                        switch ((int) ($motivos[$bateria->id_motivo_baja] ?? 0)) {
                            case 2: $partes++; break;
                            case 3: $vasos++; break;
                            case 4: $accidente++; break;
                            case 5: $vejez++; break;
                            case 6: $baja++; break;
                        }
                        $caidas++;
                    } elseif ($bateria->fecha_retiro === null) {
                        $instaladas++;
                    }
                    $flag = false;
                }

                if ($flag || $ultima === null) {
                    continue;
                }

                $precioMn = (float) $ultima->precio_mn;
                $precioMe = (float) $ultima->precio_me;

                $this->SetFont('Arial', '', 12);
                $this->SetXY(10, $posY);
                $this->SetFillColor(200, 200, 200);
                $marca = (string) $grupo['nombre'];
                if ($marcTemp === $marca) {
                    $this->Cell(25, 6, '', 'LR', 0, 'C', 1);
                } else {
                    $this->Cell(25, 6, $this->txt($marca), 'LTR', 0, 'C', 1);
                }
                $marcTemp = $marca;
                $this->Cell(10, 6, $this->txt($amperaje), 1, 0, 'C', 1);
                $this->Cell(15, 6, $this->cambiarVariable($precioMn), 1, 0, 'C', 1);
                $this->Cell(15, 6, $this->cambiarVariable($precioMe), 1, 0, 'C', 1);
                $this->Cell(15, 6, $this->cambiarVariable($instaladas), 1, 0, 'C', 1);
                $this->Cell(15, 6, $this->cambiarVariable($caidas), 1, 0, 'C', 1);
                $this->Cell(20, 6, $this->cambiarVariable($duracion), 1, 0, 'C', 1);
                if ($caidas > 0) {
                    $this->Cell(20, 6, $this->cambiarVariable(round($duracion / $caidas, 2), 2), 1, 0, 'C', 1);
                    $this->Cell(20, 6, $this->cambiarVariable(round(($precioMn + $precioMe) * $caidas / $duracion, 2), 2), 1, 0, 'C', 1);
                } else {
                    $this->Cell(20, 6, '', 1, 0, 'C', 1);
                    $this->Cell(20, 6, '', 1, 0, 'C', 1);
                }
                $this->Cell(20, 6, $this->cambiarVariable($accidente), 1, 0, 'C', 1);
                $this->Cell(20, 6, $this->cambiarVariable($partes), 1, 0, 'C', 1);
                $this->Cell(20, 6, $this->cambiarVariable($vasos), 1, 0, 'C', 1);
                $this->Cell(20, 6, $this->cambiarVariable($vejez), 1, 0, 'C', 1);
                $this->Cell(20, 6, $this->cambiarVariable($baja), 1, 0, 'C', 1);

                $cont++;
                $totInstaladas += $instaladas;
                $totBaja += $caidas;
                $totDuracion += $duracion;
                $totCAc += $accidente;
                $totCPar += $partes;
                $totCVaso += $vasos;
                $totCVejes += $vejez;
                $totCBaja += $baja;
                if ($caidas > 0 && $duracion > 0) {
                    $totCosto += round(($precioMn + $precioMe) * $caidas / $duracion, 2);
                }

                $posY += 6;
                $i++;

                if ($i >= $max) {
                    $this->SetXY(10, $posY);
                    $this->Cell(25, 6, '', 'T', 0, 'C', 1);
                    $this->inicio($titulo, $mesStr);
                    $this->titulos(6, 10, 30, $campos2, $campos, $campos1);
                    $posY = 48;
                    $i = 1;
                }
            }
            if (! $flag) {
                $this->SetXY(10, $posY);
                $this->Cell(25, 6, '', 'T', 0, 'C', 1);
            }
        }

        if ($cont > 0) {
            $this->SetFont('Arial', 'B', 9);
            $this->SetXY(10, $posY);
            $this->SetFillColor(200, 200, 200);
            $this->Cell(65, 6, 'TOTALES', 1, 0, 'C', 1);
            $this->Cell(15, 6, $this->cambiarVariable($totInstaladas), 1, 0, 'C', 1);
            $this->Cell(15, 6, $this->cambiarVariable($totBaja), 1, 0, 'C', 1);
            $this->Cell(20, 6, $this->cambiarVariable($totDuracion, 1), 1, 0, 'C', 1);
            if ($totBaja > 0) {
                $this->Cell(20, 6, $this->cambiarVariable($totDuracion / $totBaja, 2), 1, 0, 'C', 1);
            } else {
                $this->Cell(20, 6, '', 1, 0, 'C', 1);
            }
            $this->Cell(20, 6, $this->cambiarVariable($totCosto, 2), 1, 0, 'C', 1);
            $this->Cell(20, 6, $this->cambiarVariable($totCAc), 1, 0, 'C', 1);
            $this->Cell(20, 6, $this->cambiarVariable($totCPar), 1, 0, 'C', 1);
            $this->Cell(20, 6, $this->cambiarVariable($totCVaso), 1, 0, 'C', 1);
            $this->Cell(20, 6, $this->cambiarVariable($totCVejes), 1, 0, 'C', 1);
            $this->Cell(20, 6, $this->cambiarVariable($totCBaja), 1, 0, 'C', 1);
        }

        if ($flag) {
            $this->SetFont('Arial', 'B', 25);
            $this->SetFillColor(255, 255, 255);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');
        }

        return $this->salida($titulo.'.pdf');
    }

    // =====================================================================
    // Datos y utilidades
    // =====================================================================

    /** Baterías de las entidades del reporte con marca/tractivo/destino resueltos. */
    private function bateriasBase()
    {
        return DB::table('baterias')
            ->leftJoin('tractivos', 'baterias.id_tractivo', '=', 'tractivos.id')
            ->leftJoin('catalogo_items as marcas', 'baterias.id_marca', '=', 'marcas.id')
            ->leftJoin('catalogo_items as destinos', 'baterias.id_destino', '=', 'destinos.id')
            ->whereIn('baterias.id_entidad', $this->entidadIds)
            ->whereNull('baterias.deleted_at')
            ->select([
                'baterias.id', 'baterias.folio', 'baterias.voltaje', 'baterias.amperaje',
                'baterias.precio_mn', 'baterias.precio_me', 'baterias.fecha_instalacion',
                'baterias.fecha_retiro', 'baterias.estado', 'baterias.id_marca',
                'baterias.id_motivo_baja', 'baterias.id_entidad',
                'tractivos.codigo as codtractivo',
                'marcas.nombre as marca',
                'destinos.nombre as destino',
            ])
            ->get();
    }

    /** Duración en meses (legacy: round(días/30, $dec)). */
    private function duracionBateria(object $b, int $dec): float
    {
        $hasta = $b->fecha_retiro ?: $this->fechaOperaciones;

        return round($this->restaFechas($b->fecha_instalacion, $hasta) / 30, $dec);
    }

    /** Mes (int) de una fecha 'YYYY-MM-DD'. */
    private function mesDe(?string $fecha): ?int
    {
        return $fecha ? (int) substr($fecha, 5, 2) : null;
    }

    /** Condición mensual de `mostrar_resumen` del legacy. */
    private function resumenPasa(object $b, int $anio, int $mesNum): bool
    {
        if ($b->fecha_instalacion === null) {
            return false;
        }

        if ($mesNum === 0) {
            $instOk = (int) substr($b->fecha_instalacion, 0, 4) <= $anio;
            if ($b->fecha_retiro === null) {
                return $instOk;
            }

            return (int) substr($b->fecha_retiro, 0, 4) >= $anio && $instOk;
        }

        $limite = sprintf('%04d-%02d-31', $anio, $mesNum);
        $inicio = sprintf('%04d-%02d-01', $anio, $mesNum);
        $instOk = $b->fecha_instalacion <= $limite;

        if ($b->fecha_retiro === null) {
            return $instOk;
        }

        return $b->fecha_retiro >= $inicio && $instOk;
    }

    /** Días absolutos entre dos fechas (paridad `ModBaterias::restaFechas`). */
    private function restaFechas(?string $desde, ?string $hasta): float
    {
        if (! $desde || ! $hasta) {
            return 0.0;
        }

        return (float) abs(round((strtotime($hasta) - strtotime($desde)) / 86400, 2));
    }

    /** Firmas CONF/APROB/ACUSE del reporte de baterías (legacy pdf_salario_firmas, sistema 6). */
    private function firmasBaterias(): void
    {
        $firma = DB::table('firmas')
            ->whereIn('id_entidad', $this->entidadIds)
            ->where('nombre', 'BATERIAS')
            ->orderBy('id')
            ->first()
            ?: DB::table('firmas')->where('id', 6)->first();

        if (! $firma) {
            return;
        }

        $posX = 10;
        $posY = 190;
        $letra1 = 10;
        $letra2 = 8;
        $ancho = 85;

        $bloques = [
            ['CONF: ', $firma->confecciona_nombre ?? '', $firma->confecciona_cargo ?? ''],
            ['APROB : ', $firma->revisa_nombre ?? '', $firma->revisa_cargo ?? ''],
            ['ACUSE RECIBO: ', $firma->aprueba_nombre ?? '', $firma->aprueba_cargo ?? ''],
        ];

        foreach ($bloques as [$prefijo, $nombre, $cargo]) {
            if ((string) $nombre === '') {
                continue;
            }
            $this->SetFont('Arial', 'B', $letra1);
            $this->SetXY($posX, $posY);
            $this->Cell(0, 6, $this->txt($prefijo.$nombre), 0, 1, 'L');
            $this->SetFont('Arial', 'B', $letra2);
            $this->SetXY($posX, $posY + 6);
            $this->Cell($ancho, 6, $this->txt((string) $cargo), 0, 1, 'L');
            $posX += $ancho;
        }
    }

    /** Fuerza la orientación horizontal en la próxima página (reporte legacy en 'L'). */
    private function forzarLandscape(): void
    {
        if ($this->DefOrientation !== 'L') {
            $this->DefOrientation = 'L';
        }
    }
}
