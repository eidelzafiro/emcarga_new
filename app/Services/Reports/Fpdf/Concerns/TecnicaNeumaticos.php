<?php

namespace App\Services\Reports\Fpdf\Concerns;

use App\Support\Catalogos;
use Illuminate\Support\Facades\DB;

/**
 * Reportes de neumáticos. Replican el layout y los datos del legacy
 * `Reportestec.php` (controladores pdf_plan_bajasneumaticos,
 * pdf_resumenneumaticos, pdf_cn2, pdf_cn3, pdf_listado_neumaticosbaja y
 * pdf_neumaticos_tractivos_activas) sobre el esquema nuevo.
 *
 * Equivalencias de esquema:
 *   tec_neumaticos            → neumaticos
 *   tec_neumaticosmov         → neumaticos_movimientos
 *   tec_marca                 → catalogo_items (tipo marcas)
 *   tec_neumaticosmedidas     → neumaticos.medida (texto) / catalogo_items (medidas_neumaticos)
 *   tec_neumaticosposicion    → catalogo_items (posiciones_neumaticos)
 *   tec_tipoestados           → neumaticos.estado (texto)
 *   tec_tractivos             → tractivos
 *   tec_destagregados         → catalogo_items (destinos_agregados)
 */
trait TecnicaNeumaticos
{
    /**
     * 153 · PLAN DE BAJAS Y RECAUCHES DE NEUMATICOS
     * Legacy: pdf_plan_bajasneumaticos($mes, $ano, $unidad, $idmarca, $idmedida, $idestado)
     */
    public function pdfPlanBajasRecauches(?string $mes = null, ?string $ano = null): \Illuminate\Http\Response
    {
        $this->DefOrientation = 'L';
        $titulo = 'PLAN DE BAJAS Y RECAUCHES DE NEUMATICOS';
        [$anio, $mesNum] = $this->anioMes($mes);
        if ($ano !== null && $ano !== '') {
            $anio = (int) $ano;
        }
        $fecha = $mes ?? '';

        $campos1 = [
            ['titulo' => '#', 'ancho' => 7, 'direccion' => 'C', 'bordes' => 'LRBT', 'letra' => '8'],
            ['titulo' => '# VEHICULO', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRBT', 'letra' => '8'],
            ['titulo' => '# NEUM', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LRBT', 'letra' => '8'],
            ['titulo' => 'MARCA', 'ancho' => 35, 'direccion' => 'C', 'bordes' => 'LRBT', 'letra' => '8'],
            ['titulo' => 'RIN/No', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRBT', 'letra' => '8'],
            ['titulo' => 'ESTADO', 'ancho' => 30, 'direccion' => 'C', 'bordes' => 'LRBT', 'letra' => '8'],
            ['titulo' => 'TOTAL', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LRBT', 'letra' => '8'],
            ['titulo' => 'PLAN', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LRBT', 'letra' => '8'],
            ['titulo' => 'RESTANTES', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LRBT', 'letra' => '8'],
            ['titulo' => 'PROMEDIO', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LRBT', 'letra' => '8'],
            ['titulo' => 'ESTIMADO', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'],
        ];
        $campos = [
            ['titulo' => '', 'ancho' => 127, 'direccion' => 'C', 'bordes' => 'LRBT', 'letra' => '8'],
            ['titulo' => 'KILOMETROS', 'ancho' => 100, 'direccion' => 'C', 'bordes' => 'LRBT', 'letra' => '8'],
            ['titulo' => 'FECHA PLAN', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => '8'],
        ];

        $this->inicio($titulo, $fecha);

        $query = DB::table('neumaticos as n')
            ->leftJoin('catalogo_items as ma', 'n.id_marca', '=', 'ma.id')
            ->leftJoin('tractivos as t', 'n.id_tractivo', '=', 't.id')
            ->whereIn('n.id_entidad', $this->entidadIds)
            ->whereNull('n.fecha_retiro')
            ->whereNotNull('n.fecha_plan_retiro')
            ->where('n.kms_promedio', '<>', 0)
            ->whereYear('n.fecha_plan_retiro', '<=', $anio);

        if ($ano !== null && $ano !== '') {
            $query->whereYear('n.fecha_plan_retiro', $anio);
        }
        if ($mes !== null && $mes !== '' && $mes !== '00') {
            $query->whereMonth('n.fecha_plan_retiro', '<=', $mesNum);
        }

        $rows = $query->orderBy('n.fecha_plan_retiro')
            ->orderBy('t.codigo')
            ->orderBy('n.folio')
            ->get([
                'n.id', 'n.folio', 'n.medida', 'n.estado', 'n.kms_promedio',
                'n.fecha_plan_retiro', 'ma.nombre as marca', 't.codigo as codtractivo',
                't.id_grupo',
            ]);

        $vidaNuevo = (float) ($this->entidad->vida_neum_nuevo ?? 0);
        $vidaRec = (float) ($this->entidad->vida_neum_rec ?? 0);
        $vidaAdmin = (float) ($this->entidad->vida_neum_admin ?? 0);
        $grupoArrastres = Catalogos::grupoArrastresId();

        $flag = false;
        $pos_y = 42;
        $i = 1;
        $max = 26;
        $cont = 1;
        $color = true;

        foreach ($rows as $obj) {
            if (! $flag) {
                $this->titulos(6, 10, 30, $campos1, $campos);
                $flag = true;
            }
            if ($i >= $max) {
                $this->inicio($titulo, $fecha);
                $this->titulos(6, 10, 30, $campos1, $campos);
                $pos_y = 42;
                $i = 1;
                $color = true;
            }

            $kms = $this->duracionNeumatico((int) $obj->id);
            $esArrastre = $grupoArrastres && (int) $obj->id_grupo === (int) $grupoArrastres;
            $plan = $esArrastre ? $vidaAdmin : $vidaNuevo;
            if ($obj->estado === 'recauchado') {
                $plan = $vidaRec;
            }
            $restante = $plan - $kms;

            $this->SetFont('Arial', $restante <= 300 ? 'B' : '', 10);
            $this->SetXY(10, $pos_y);
            $this->SetFillColor($color ? 999 : $this->fillColor());
            $this->Cell(7, 6, (string) $cont++, 1, 0, 'C', 1);
            $this->Cell(20, 6, $this->txt((string) $obj->codtractivo), 1, 0, 'C', 1);
            $this->Cell(15, 6, $this->txt((string) $obj->folio), 1, 0, 'C', 1);
            $this->SetFont('Arial', 'U', 10);
            $this->Cell(35, 6, $this->txt((string) $obj->marca), 1, 0, 'C', 1);
            $this->Cell(20, 6, $this->txt((string) $obj->medida), 1, 0, 'C', 1);
            $this->Cell(30, 6, $this->txt((string) $obj->estado), 1, 0, 'C', 1);
            $this->SetFont('Arial', '', 10);
            $this->Cell(25, 6, (string) round($kms, 2), 1, 0, 'C', 1);
            $this->Cell(25, 6, (string) round($plan, 2), 1, 0, 'C', 1);
            $this->Cell(25, 6, (string) round($restante, 2), 1, 0, 'C', 1);
            $this->Cell(25, 6, (string) round((float) $obj->kms_promedio, 2), 1, 0, 'C', 1);
            $this->Cell(25, 6, (string) $obj->fecha_plan_retiro, 1, 0, 'C', 1);

            $color = ! $color;
            $pos_y += 6;
            $i++;
        }

        if (! $flag) {
            $this->noExistenDatos();
        }

        return $this->salida('PlanBajasRecauchesNeumaticos.pdf');
    }

    /**
     * 154 · INFORMACION GENERAL DE NEUMATICOS
     * Legacy: pdf_resumenneumaticos($mes, $unidad)
     */
    public function pdfInformacionNeumaticos(?string $mes = null): \Illuminate\Http\Response
    {
        $this->DefOrientation = 'L';
        [$anio, $mesNum] = $this->anioMes($mes);
        $fecha = $mes ?? '';

        $campos = [
            ['titulo' => 'MARCA', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => '8'],
            ['titulo' => 'MEDIDAS', 'ancho' => 17, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => '8'],
            ['titulo' => 'COSTO', 'ancho' => 24, 'direccion' => 'C', 'bordes' => 'LRTB', 'letra' => '8'],
            ['titulo' => 'INSTA', 'ancho' => 12, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => '8'],
            ['titulo' => 'CAIDAS', 'ancho' => 12, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => '8'],
            ['titulo' => 'TOTAL', 'ancho' => 16, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => '8'],
            ['titulo' => 'DURABI', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => '8'],
            ['titulo' => 'Desgaste', 'ancho' => 14, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => '8'],
            ['titulo' => 'Condicio', 'ancho' => 14, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => '8'],
            ['titulo' => 'Cortadas', 'ancho' => 14, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => '8'],
            ['titulo' => 'Condicio', 'ancho' => 15, 'direccion' => 'L', 'bordes' => 'LRT', 'letra' => '8'],
            ['titulo' => 'Separa', 'ancho' => 14, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => '8'],
            ['titulo' => 'Defecto', 'ancho' => 14, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => '8'],
            ['titulo' => 'Misce', 'ancho' => 14, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => '8'],
            ['titulo' => 'Defecto de', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => '8'],
            ['titulo' => 'Dañadas', 'ancho' => 14, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => '8'],
            ['titulo' => 'Costo', 'ancho' => 16, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => '8'],
        ];
        $campos1 = [
            ['titulo' => '', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LR', 'letra' => '8'],
            ['titulo' => '', 'ancho' => 17, 'direccion' => 'C', 'bordes' => 'LR', 'letra' => '8'],
            ['titulo' => 'CUP', 'ancho' => 12, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => '8'],
            ['titulo' => 'CUC', 'ancho' => 12, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => '8'],
            ['titulo' => 'LADAS', 'ancho' => 12, 'direccion' => 'C', 'bordes' => 'LR', 'letra' => '8'],
            ['titulo' => '', 'ancho' => 12, 'direccion' => 'C', 'bordes' => 'LR', 'letra' => '8'],
            ['titulo' => 'KMS', 'ancho' => 16, 'direccion' => 'C', 'bordes' => 'LR', 'letra' => '8'],
            ['titulo' => 'LIDAD', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LR', 'letra' => '8'],
            ['titulo' => 'de la', 'ancho' => 14, 'direccion' => 'C', 'bordes' => 'LR', 'letra' => '8'],
            ['titulo' => 'nes de la', 'ancho' => 14, 'direccion' => 'C', 'bordes' => 'LR', 'letra' => '8'],
            ['titulo' => '', 'ancho' => 14, 'direccion' => 'C', 'bordes' => 'LR', 'letra' => '8'],
            ['titulo' => 'nes de los', 'ancho' => 15, 'direccion' => 'L', 'bordes' => 'LR', 'letra' => '8'],
            ['titulo' => 'ción', 'ancho' => 14, 'direccion' => 'C', 'bordes' => 'LR', 'letra' => '8'],
            ['titulo' => 'de las', 'ancho' => 14, 'direccion' => 'C', 'bordes' => 'LR', 'letra' => '8'],
            ['titulo' => 'laneas', 'ancho' => 14, 'direccion' => 'C', 'bordes' => 'LR', 'letra' => '8'],
            ['titulo' => 'Repara', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LR', 'letra' => '8'],
            ['titulo' => '', 'ancho' => 14, 'direccion' => 'C', 'bordes' => 'LR', 'letra' => '8'],
            ['titulo' => 'Rendi', 'ancho' => 16, 'direccion' => 'C', 'bordes' => 'LR', 'letra' => '8'],
        ];
        $campos2 = [
            ['titulo' => '', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'],
            ['titulo' => '', 'ancho' => 17, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'],
            ['titulo' => '', 'ancho' => 12, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'],
            ['titulo' => '', 'ancho' => 12, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'],
            ['titulo' => '', 'ancho' => 12, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'],
            ['titulo' => '', 'ancho' => 12, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'],
            ['titulo' => '', 'ancho' => 16, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'],
            ['titulo' => 'PROM', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'],
            ['titulo' => 'banda', 'ancho' => 14, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'],
            ['titulo' => 'banda', 'ancho' => 14, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'],
            ['titulo' => '', 'ancho' => 14, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'],
            ['titulo' => 'cascos', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'],
            ['titulo' => '', 'ancho' => 14, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'],
            ['titulo' => 'Pestañas', 'ancho' => 14, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'],
            ['titulo' => '', 'ancho' => 14, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'],
            ['titulo' => 'ciones', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'],
            ['titulo' => '', 'ancho' => 14, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'],
            ['titulo' => 'miento', 'ancho' => 16, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'],
        ];

        $estados = ['nuevo', 'recauchado', 'regular', 'activo'];

        foreach ($estados as $estado) {
            $titulo = 'INFORMACION GENERAL DE NEUMATICOS ('.strtoupper($estado).')';
            $this->inicio($titulo, $fecha);

            $neumaticos = DB::table('neumaticos as n')
                ->leftJoin('catalogo_items as ma', 'n.id_marca', '=', 'ma.id')
                ->whereIn('n.id_entidad', $this->entidadIds)
                ->where('n.estado', $estado)
                ->orderBy('ma.nombre')
                ->orderBy('n.medida')
                ->get([
                    'n.id', 'n.id_marca', 'ma.nombre as marca', 'n.medida',
                    'n.fecha_retiro', 'n.precio_mn', 'n.precio_me',
                ]);

            $grupos = [];
            foreach ($neumaticos as $x) {
                $key = ((string) $x->id_marca).'|'.((string) $x->marca).'|'.((string) $x->medida);
                if (! isset($grupos[$key])) {
                    $grupos[$key] = ['marca' => $x->marca, 'medida' => $x->medida, 'items' => []];
                }
                $grupos[$key]['items'][] = $x;
            }

            $pos_y = 48;
            $i = 1;
            $max = 25;
            $marcTemp = '';
            $cont = 0;
            $totInst = 0;
            $totBaja = 0;
            $totKms = 0;
            $totDesgaste = 0;
            $totCondBanda = 0;
            $totCortadas = 0;
            $totCondCascos = 0;
            $totSeparacion = 0;
            $totDefPest = 0;
            $totMisc = 0;
            $totDefRep = 0;
            $totDan = 0;
            $proCosto = 0;

            foreach ($grupos as $grupo) {
                $kms = 0.0;
                $instaladas = 0;
                $caidas = 0;
                $costomn = 0.0;
                $costome = 0.0;

                foreach ($grupo['items'] as $neumatico) {
                    $esBaja = $neumatico->fecha_retiro !== null
                        && ($mesNum === 0 || (int) substr((string) $neumatico->fecha_retiro, 5, 2) === $mesNum);
                    if ($esBaja) {
                        $kms += $this->duracionNeumatico((int) $neumatico->id);
                        $caidas++;
                    } else {
                        $instaladas++;
                    }
                    $costomn += (float) ($neumatico->precio_mn ?? 0);
                    $costome += (float) ($neumatico->precio_me ?? 0);
                }

                if (($instaladas + $caidas) === 0) {
                    continue;
                }

                if ($cont === 0) {
                    $this->titulos(6, 8, 30, $campos2, $campos, $campos1);
                }

                $this->SetFont('Arial', '', 9);
                $this->SetXY(8, $pos_y);
                $this->SetFillColor(999);
                $marca = (string) ($grupo['marca'] ?? '');
                if ($marcTemp === $marca) {
                    $this->Cell(25, 6, '', 'LR', 0, 'C', 1);
                } else {
                    $this->Cell(25, 6, $this->txt($marca), 'LTR', 0, 'C', 1);
                }
                $marcTemp = $marca;
                $cont++;
                $this->Cell(17, 6, $this->txt((string) $grupo['medida']), 1, 0, 'C', 1);
                $this->Cell(12, 6, $this->cambiarVariable($costomn / ($instaladas + $caidas), 2), 1, 0, 'C', 1);
                $this->Cell(12, 6, $this->cambiarVariable($costome / ($instaladas + $caidas), 2), 1, 0, 'C', 1);
                $this->Cell(12, 6, $this->cambiarVariable($instaladas), 1, 0, 'C', 1);
                $this->Cell(12, 6, $this->cambiarVariable($caidas), 1, 0, 'C', 1);
                $this->Cell(16, 6, $this->cambiarVariable($kms), 1, 0, 'C', 1);
                $this->Cell(15, 6, $caidas > 0 ? $this->cambiarVariable($kms / $caidas, 1) : '', 1, 0, 'C', 1);
                $this->Cell(14, 6, '', 1, 0, 'C', 1);
                $this->Cell(14, 6, '', 1, 0, 'C', 1);
                $this->Cell(14, 6, '', 1, 0, 'C', 1);
                $this->Cell(15, 6, '', 1, 0, 'C', 1);
                $this->Cell(14, 6, '', 1, 0, 'C', 1);
                $this->Cell(14, 6, '', 1, 0, 'C', 1);
                $this->Cell(14, 6, '', 1, 0, 'C', 1);
                $this->Cell(15, 6, '', 1, 0, 'C', 1);
                $this->Cell(14, 6, '', 1, 0, 'C', 1);
                if ($kms != 0 && $caidas > 0) {
                    $this->Cell(16, 6, $this->cambiarVariable((($costomn + $costome) / $caidas) * $caidas * 10000 / $kms, 2), 1, 0, 'C', 1);
                } else {
                    $this->Cell(16, 6, '', 1, 0, 'C', 1);
                }

                $totInst += $instaladas;
                $totBaja += $caidas;
                $totKms += $kms;
                if ($caidas > 0 && $kms != 0) {
                    $proCosto += (($costomn + $costome) / $caidas) * $caidas * 10000 / $kms;
                }

                $pos_y += 6;
                $i++;
                if ($i >= $max) {
                    $this->inicio($titulo, $fecha);
                    $this->titulos(6, 8, 30, $campos2, $campos, $campos1);
                    $pos_y = 48;
                    $i = 1;
                }
            }

            if ($cont > 0) {
                $this->SetFont('Arial', 'B', 9);
                $this->SetXY(8, $pos_y);
                $this->SetFillColor($this->fillColor());
                $this->Cell(66, 6, 'TOTALES', 1, 0, 'C', 1);
                $this->Cell(12, 6, $this->cambiarVariable($totInst), 1, 0, 'C', 1);
                $this->Cell(12, 6, $this->cambiarVariable($totBaja), 1, 0, 'C', 1);
                $this->Cell(16, 6, $this->cambiarVariable($totKms), 1, 0, 'C', 1);
                $this->Cell(15, 6, $totBaja > 0 ? $this->cambiarVariable($totKms / $totBaja, 1) : '', 1, 0, 'C', 1);
                $this->Cell(14, 6, '', 1, 0, 'C', 1);
                $this->Cell(14, 6, '', 1, 0, 'C', 1);
                $this->Cell(14, 6, '', 1, 0, 'C', 1);
                $this->Cell(15, 6, '', 1, 0, 'C', 1);
                $this->Cell(14, 6, '', 1, 0, 'C', 1);
                $this->Cell(14, 6, '', 1, 0, 'C', 1);
                $this->Cell(14, 6, '', 1, 0, 'C', 1);
                $this->Cell(15, 6, '', 1, 0, 'C', 1);
                $this->Cell(14, 6, '', 1, 0, 'C', 1);
                $this->Cell(16, 6, $this->cambiarVariable($proCosto, 2), 1, 0, 'C', 1);
            } else {
                $this->noExistenDatos();
            }
        }

        return $this->salida('InformacionNeumaticos.pdf');
    }

    /**
     * 156 · CONTROL DE LA VIDA UTIL DEL NEUMATICO (MODELO CN-2)
     * Legacy: pdf_cn2($idneumaticos)
     */
    public function pdfCn2Neumatico($neumatico): \Illuminate\Http\Response
    {
        $this->DefOrientation = 'L';
        $titulo = 'CONTROL DE LA VIDA UTIL DEL NEUMATICO (MODELO CN-2)';
        $id = is_numeric($neumatico) ? (int) $neumatico : 0;

        $campos1 = [
            ['titulo' => 'FECHA', 'ancho' => 18, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => '8'],
            ['titulo' => '# VEHICULO', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => '8'],
            ['titulo' => 'POSICION', 'ancho' => 40, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => '8'],
            ['titulo' => 'KILOMETRAJE DEL VEHICULO', 'ancho' => 60, 'direccion' => 'C', 'bordes' => 'LRBT', 'letra' => '8'],
            ['titulo' => 'FECHA', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => '8'],
            ['titulo' => 'PROF DIBUJO', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => '8'],
            ['titulo' => 'BALANCEADA', 'ancho' => 22, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => '8'],
            ['titulo' => 'OBSERVACIONES', 'ancho' => 60, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => '8'],
        ];
        $campos2 = [
            ['titulo' => 'MONTAR', 'ancho' => 18, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'],
            ['titulo' => '', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'],
            ['titulo' => '', 'ancho' => 40, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'],
            ['titulo' => 'MONTAR', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRBT', 'letra' => '8'],
            ['titulo' => 'RETIRAR', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRBT', 'letra' => '8'],
            ['titulo' => 'TOTAL', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRBT', 'letra' => '8'],
            ['titulo' => 'RETIRAR', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'],
            ['titulo' => 'RETIRAR', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'],
            ['titulo' => '', 'ancho' => 22, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'],
            ['titulo' => '', 'ancho' => 60, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'],
        ];

        $n = DB::table('neumaticos as n')
            ->leftJoin('catalogo_items as ma', 'n.id_marca', '=', 'ma.id')
            ->where('n.id', $id)
            ->first([
                'n.id', 'n.folio', 'n.medida', 'n.profinicial', 'n.explotacion_anterior',
                'n.fecha_fabricacion', 'n.precio_mn', 'n.precio_me', 'n.balanceada',
                'ma.nombre as marca',
            ]);

        $this->inicio($titulo);

        if (! $n) {
            $this->noExistenDatos();

            return $this->salida('CN2Neumatico.pdf');
        }

        $campos = [
            ['titulo' => '# NEUMATICO: '.$n->folio, 'ancho' => 43, 'direccion' => 'L', 'bordes' => 'LTRB', 'letra' => '8'],
            ['titulo' => 'MARCA: '.$n->marca, 'ancho' => 44, 'direccion' => 'L', 'bordes' => 'LTRB', 'letra' => '8'],
            ['titulo' => 'PROF INICIAL: '.$n->profinicial, 'ancho' => 44, 'direccion' => 'L', 'bordes' => 'LTRB', 'letra' => '8'],
            ['titulo' => 'T/ EXPL: '.$n->explotacion_anterior, 'ancho' => 43, 'direccion' => 'L', 'bordes' => 'LTRB', 'letra' => '8'],
            ['titulo' => 'AÑO FAB:'.$n->fecha_fabricacion, 'ancho' => 43, 'direccion' => 'L', 'bordes' => 'LTRB', 'letra' => '8'],
            ['titulo' => 'COSTO: '.$n->precio_mn.'MN-'.$n->precio_me.'ME', 'ancho' => 43, 'direccion' => 'L', 'bordes' => 'LTRB', 'letra' => '8'],
        ];

        $movimientos = DB::table('neumaticos_movimientos as m')
            ->leftJoin('tractivos as t', 'm.id_tractivo', '=', 't.id')
            ->leftJoin('catalogo_items as p', function ($join) {
                $join->on('p.origen_id', '=', 'm.posicion')
                    ->where('p.tipo', '=', 'posiciones_neumaticos');
            })
            ->where('m.id_neumatico', $id)
            ->orderBy('m.fecha_montaje')
            ->orderBy('m.id')
            ->get([
                'm.id', 'm.id_tractivo', 'm.fecha_montaje', 'm.fecha_retiro',
                'm.km_instalado', 'm.km_retirado', 'm.posicion', 'm.id_destino',
                'm.observaciones', 't.codigo as codtractivo', 'p.nombre as nombposicion',
            ]);

        $this->titulos(6, 10, 30, $campos2, $campos, $campos1);
        $pos_y = 48;
        $i = 1;
        $max = 25;
        $kmTotal = 0.0;

        foreach ($movimientos as $mov) {
            $duracion = $this->duracionMovimiento($mov);
            $kmTotal += $duracion;

            $observacion = $this->neumAjustarNotas(trim((string) ($mov->observaciones ?? '')), 30);
            if ($observacion === []) {
                $observacion = [''];
            }
            $total = count($observacion);

            foreach ($observacion as $k => $linea) {
                $borde = $total === 1
                    ? 'LRTB'
                    : ($k === 0 ? 'LRT' : ($k === $total - 1 ? 'LRB' : 'LR'));

                $this->SetFont('Arial', '', 9);
                $this->SetXY(10, $pos_y);
                $this->SetFillColor(999);
                $this->Cell(18, 6, $k === 0 ? $this->txt((string) $mov->fecha_montaje) : '', $borde, 0, 'C', 1);
                $this->Cell(20, 6, $k === 0 ? $this->txt((string) $mov->codtractivo) : '', $borde, 0, 'C', 1);
                $this->Cell(40, 6, $k === 0 ? $this->txt((string) $mov->nombposicion) : '', $borde, 0, 'C', 1);
                $this->Cell(20, 6, $k === 0 ? (string) $mov->km_instalado : '', $borde, 0, 'C', 1);
                $this->Cell(20, 6, $k === 0 ? (string) $mov->km_retirado : '', $borde, 0, 'C', 1);
                $this->Cell(20, 6, $k === 0 ? (string) round($duracion, 2) : '', $borde, 0, 'C', 1);
                $this->Cell(20, 6, $k === 0 ? $this->txt((string) $mov->fecha_retiro) : '', $borde, 0, 'C', 1);
                $this->Cell(20, 6, '', $borde, 0, 'C', 1);
                $this->Cell(22, 6, $k === 0 ? $this->txt((string) $n->balanceada) : '', $borde, 0, 'C', 1);
                $this->Cell(60, 6, $this->txt((string) $linea), $borde, 0, 'L', 1);

                $pos_y += 6;
                $i++;
                if ($i >= $max) {
                    $this->SetFont('Arial', '', 10);
                    $this->SetXY(10, $pos_y);
                    $this->SetFillColor(999);
                    $this->Cell(251, 6, '', 'T', 0, 'C', 1);
                    $this->inicio($titulo);
                    $this->titulos(6, 10, 30, $campos2, $campos, $campos1);
                    $pos_y = 42;
                    $i = 1;
                    $max = 26;
                }
            }
        }

        $this->SetFont('Arial', 'B', 10);
        $this->SetXY(10, $pos_y);
        $this->SetFillColor($this->fillColor());
        $this->Cell(118, 6, 'TOTAL DE KILOMETROS RECORRIDOS', 1, 0, 'C', 1);
        $this->SetFillColor(999);
        $this->Cell(20, 6, (string) round($kmTotal, 2), 1, 0, 'C', 1);
        $this->SetFillColor($this->fillColor());
        $this->Cell(122, 6, '', 1, 0, 'C', 1);

        return $this->salida('CN2Neumatico.pdf');
    }

    /**
     * 162 · ANALISIS DE NEUMATICOS QUE HAN CAUSADO BAJA (MODELO CN-3)
     * Legacy: pdf_cn3($mes, $unidad)
     */
    public function pdfCn3AnalisisBaja(?string $mes = null): \Illuminate\Http\Response
    {
        $this->DefOrientation = 'L';
        [$anio, $mesNum] = $this->anioMes($mes);
        $fecha = $mes ?? '';

        $campos = [
            ['titulo' => 'MARCA', 'ancho' => 30, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => '9'],
            ['titulo' => 'MEDIDAS', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => '9'],
            ['titulo' => 'CAUSA O MOTIVO', 'ancho' => 80, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => '9'],
            ['titulo' => 'CODIGO', 'ancho' => 16, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => '9'],
            ['titulo' => 'CANTIDAD', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => '9'],
            ['titulo' => 'KMS', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => '9'],
            ['titulo' => 'PROMEDIO KMS', 'ancho' => 30, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => '9'],
            ['titulo' => 'PROMEDIO KMS', 'ancho' => 30, 'direccion' => 'C', 'bordes' => 'LRT', 'letra' => '9'],
        ];
        $campos1 = [
            ['titulo' => '', 'ancho' => 30, 'direccion' => 'C', 'bordes' => 'LR', 'letra' => '9'],
            ['titulo' => '', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LR', 'letra' => '9'],
            ['titulo' => '', 'ancho' => 80, 'direccion' => 'C', 'bordes' => 'LR', 'letra' => '9'],
            ['titulo' => '', 'ancho' => 16, 'direccion' => 'C', 'bordes' => 'LR', 'letra' => '9'],
            ['titulo' => '', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LR', 'letra' => '9'],
            ['titulo' => 'RECORRIDOS', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LR', 'letra' => '9'],
            ['titulo' => 'RECORRIDOS', 'ancho' => 30, 'direccion' => 'C', 'bordes' => 'LR', 'letra' => '9'],
            ['titulo' => 'RECORRIDOS', 'ancho' => 30, 'direccion' => 'C', 'bordes' => 'LR', 'letra' => '9'],
        ];
        $campos2 = [
            ['titulo' => '', 'ancho' => 30, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '9'],
            ['titulo' => '', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '9'],
            ['titulo' => '', 'ancho' => 80, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '9'],
            ['titulo' => '', 'ancho' => 16, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '9'],
            ['titulo' => '', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '9'],
            ['titulo' => '', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '9'],
            ['titulo' => '', 'ancho' => 30, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '9'],
            ['titulo' => 'POR MARCAS', 'ancho' => 30, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '9'],
        ];

        $estados = ['nuevo', 'recauchado', 'regular', 'activo'];

        foreach ($estados as $estado) {
            $titulo = 'ANALISIS DE NEUMATICOS QUE HAN CAUSADO BAJA (MODELO CN-3) '.strtoupper($estado);
            $this->inicio($titulo, $fecha);

            $neumaticos = DB::table('neumaticos as n')
                ->leftJoin('catalogo_items as ma', 'n.id_marca', '=', 'ma.id')
                ->whereIn('n.id_entidad', $this->entidadIds)
                ->where('n.estado', $estado)
                ->whereNotNull('n.fecha_retiro')
                ->whereYear('n.fecha_retiro', $anio)
                ->whereMonth('n.fecha_retiro', $mesNum)
                ->orderBy('ma.nombre')
                ->orderBy('n.medida')
                ->get(['n.id', 'n.id_marca', 'ma.nombre as marca', 'n.medida']);

            $grupos = [];
            foreach ($neumaticos as $x) {
                $key = ((string) $x->id_marca).'|'.((string) $x->marca).'|'.((string) $x->medida);
                if (! isset($grupos[$key])) {
                    $grupos[$key] = ['marca' => $x->marca, 'medida' => $x->medida, 'items' => []];
                }
                $grupos[$key]['items'][] = $x;
            }

            $pos_y = 48;
            $i = 1;
            $max = 25;
            $marcTemp = '';
            $medTemp = '';
            $filas = 0;
            $cant_neum = 0;
            $kms_total = 0.0;

            foreach ($grupos as $grupo) {
                $duracion = 0.0;
                foreach ($grupo['items'] as $x) {
                    $duracion += $this->duracionNeumatico((int) $x->id);
                }
                $cantidad = count($grupo['items']);

                if ($filas === 0) {
                    $this->titulos(6, 8, 30, $campos2, $campos, $campos1);
                    $this->SetFont('Arial', '', 9);
                }

                $marca = (string) ($grupo['marca'] ?? '');
                $medida = (string) ($grupo['medida'] ?? '');
                $promedio = $cantidad > 0 ? round($duracion / $cantidad, 2) : 0;

                $this->SetXY(8, $pos_y);
                $this->SetFillColor(999);
                if ($marcTemp === $marca) {
                    $this->Cell(30, 6, '', 'LR', 0, 'C', 1);
                } else {
                    $this->Cell(30, 6, $this->txt($marca), 'LTR', 0, 'C', 1);
                    $medTemp = '';
                }
                $marcTemp = $marca;
                if ($medTemp === $medida) {
                    $this->Cell(20, 6, '', 'LR', 0, 'C', 1);
                } else {
                    $this->Cell(20, 6, $this->txt($medida), 'LTR', 0, 'C', 1);
                }
                $medTemp = $medida;
                $this->Cell(80, 6, '', 'LTR', 0, 'C', 1);
                $this->Cell(16, 6, '', 'LTR', 0, 'C', 1);
                $this->Cell(20, 6, (string) $cantidad, 1, 0, 'C', 1);
                $this->Cell(25, 6, (string) $duracion, 1, 0, 'C', 1);
                $this->Cell(30, 6, (string) $promedio, 1, 0, 'C', 1);
                $this->Cell(30, 6, (string) round($promedio, 2), 1, 0, 'C', 1);

                $filas++;
                $cant_neum += $cantidad;
                $kms_total += $duracion;

                $pos_y += 6;
                $i++;
                if ($i >= $max) {
                    $this->inicio($titulo, $fecha);
                    $this->titulos(6, 8, 30, $campos2, $campos, $campos1);
                    $pos_y = 48;
                    $i = 1;
                }
            }

            if ($filas !== 0) {
                $this->SetXY(8, $pos_y);
                $this->SetFont('Arial', 'B', 9);
                $this->SetFillColor($this->fillColor());
                $this->Cell(146, 6, 'TOTAL', 1, 0, 'R', 1);
                $this->SetFillColor(999);
                $this->Cell(20, 6, (string) $cant_neum, 1, 0, 'C', 1);
                $this->Cell(55, 6, 'PROMEDIO DE KMS RECORRIDOS ', 1, 0, 'C', 1);
                $this->Cell(30, 6, (string) round($cant_neum > 0 ? $kms_total / $cant_neum : 0, 2), 1, 0, 'C', 1);
            } else {
                $this->noExistenDatos();
            }
        }

        return $this->salida('CN3AnalisisBaja.pdf');
    }

    /**
     * 365 · LISTADO DE NEUMATICOS QUE HAN CAUSADO BAJA
     * Legacy: pdf_listado_neumaticosbaja($mes, $unidad)
     */
    public function pdfListadoNeumaticosBaja(?string $mes = null): \Illuminate\Http\Response
    {
        $this->DefOrientation = 'P';
        [$anio, $mesNum] = $this->anioMes($mes);

        $campos1 = [
            ['titulo' => 'No', 'ancho' => 15, 'direccion' => 'C'],
            ['titulo' => '# NEUMATICO', 'ancho' => 30, 'direccion' => 'C'],
            ['titulo' => 'MARCA', 'ancho' => 30, 'direccion' => 'C'],
            ['titulo' => 'MEDIDA', 'ancho' => 30, 'direccion' => 'C'],
            ['titulo' => 'DURACION', 'ancho' => 30, 'direccion' => 'C'],
            ['titulo' => 'VEHICULO', 'ancho' => 30, 'direccion' => 'C'],
            ['titulo' => 'FECHA BAJA', 'ancho' => 30, 'direccion' => 'C'],
        ];

        $estados = ['nuevo', 'recauchado', 'regular', 'activo'];

        foreach ($estados as $estado) {
            $titulo = 'LISTADO DE NEUMATICOS QUE HAN CAUSADO BAJA '.strtoupper($estado);
            $this->inicio($titulo);

            $neumaticos = DB::table('neumaticos as n')
                ->leftJoin('catalogo_items as ma', 'n.id_marca', '=', 'ma.id')
                ->leftJoin('tractivos as t', 'n.id_tractivo', '=', 't.id')
                ->whereIn('n.id_entidad', $this->entidadIds)
                ->where('n.estado', $estado)
                ->whereNotNull('n.fecha_retiro')
                ->whereYear('n.fecha_retiro', $anio)
                ->whereMonth('n.fecha_retiro', $mesNum)
                ->orderBy('n.folio')
                ->get([
                    'n.id', 'n.folio', 'n.medida', 'n.fecha_retiro',
                    'ma.nombre as marca', 't.codigo as codtractivo',
                ]);

            if ($neumaticos->isEmpty()) {
                $this->noExistenDatos();

                continue;
            }

            $this->titulos(6, 10, 35, $campos1);
            $pos_y = 41;
            $i = 1;
            $nro = 1;
            $max = 30;
            $linea = 7;

            foreach ($neumaticos as $arr) {
                $this->SetFont('Arial', '', 10);
                $this->SetXY(10, $pos_y);
                $this->SetFillColor(999);
                $this->Cell(15, $linea, (string) $nro, 1, 0, 'C', 1);
                $this->Cell(30, $linea, $this->txt((string) $arr->folio), 1, 0, 'C', 1);
                $this->Cell(30, $linea, $this->txt((string) $arr->marca), 1, 0, 'L', 1);
                $this->Cell(30, $linea, $this->txt((string) $arr->medida), 1, 0, 'L', 1);
                $this->Cell(30, $linea, (string) $this->duracionNeumatico((int) $arr->id), 1, 0, 'L', 1);
                $this->Cell(30, $linea, $this->txt((string) $arr->codtractivo), 1, 0, 'L', 1);
                $this->Cell(30, $linea, $this->txt((string) $arr->fecha_retiro), 1, 0, 'L', 1);

                $pos_y += $linea;
                $i++;
                $nro++;
                if ($i === $max) {
                    $this->inicio($titulo);
                    $this->titulos(6, 10, 35, $campos1);
                    $pos_y = 41;
                    $i = 0;
                }
            }
        }

        return $this->salida('ListadoNeumaticosBaja.pdf');
    }

    /**
     * 1000 · CONTROL DE NEUMATICOS ACTIVOS X TRACTIVOS
     * Legacy: pdf_neumaticos_tractivos_activas()
     */
    public function pdfNeumaticosActivosXTractivos(): \Illuminate\Http\Response
    {
        $this->DefOrientation = 'P';
        $titulo = 'CONTROL DE NEUMATICOS ACTIVAS X TRACTIVOS';

        $campos1 = [
            ['titulo' => 'CODIGO', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LRBT', 'letra' => '8'],
            ['titulo' => 'MARCA', 'ancho' => 35, 'direccion' => 'C', 'bordes' => 'LRBT', 'letra' => '8'],
            ['titulo' => 'MEDIDA', 'ancho' => 35, 'direccion' => 'C', 'bordes' => 'LRBT', 'letra' => '8'],
            ['titulo' => 'ESTADO', 'ancho' => 35, 'direccion' => 'C', 'bordes' => 'LRBT', 'letra' => '8'],
            ['titulo' => 'FECHA', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LRBT', 'letra' => '8'],
            ['titulo' => 'KMS', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LRBT', 'letra' => '8'],
        ];

        $this->inicio($titulo);

        $data = DB::table('neumaticos as n')
            ->leftJoin('catalogo_items as ma', 'n.id_marca', '=', 'ma.id')
            ->leftJoin('tractivos as t', 'n.id_tractivo', '=', 't.id')
            ->whereIn('n.id_entidad', $this->entidadIds)
            ->whereNull('n.fecha_retiro')
            ->orderBy('t.codigo')
            ->orderBy('n.kilometraje')
            ->get([
                'n.id', 'n.folio', 'n.medida', 'n.estado', 'n.fecha_instalacion',
                'n.kilometraje', 'ma.nombre as marca', 't.codigo as codtractivo',
            ]);

        if ($data->isEmpty()) {
            $this->noExistenDatos();

            return $this->salida('NeumaticosActivosXTractivos.pdf');
        }

        $pos_y = 30;
        $i = 1;
        $max = 35;
        $flag = '';

        foreach ($data as $obj) {
            if ($flag !== $obj->codtractivo) {
                $flag = (string) $obj->codtractivo;
                $campos = [
                    ['titulo' => 'DESTINO: '.$flag, 'ancho' => 180, 'direccion' => 'L', 'bordes' => 'LRBT', 'letra' => '8'],
                ];
                $this->titulos(6, 10, $pos_y, $campos1, $campos);
                $pos_y += 12;
                $i += 2;
            }

            if ($i >= $max) {
                $campos = [
                    ['titulo' => 'DESTINO: '.$flag, 'ancho' => 180, 'direccion' => 'L', 'bordes' => 'LRBT', 'letra' => '8'],
                ];
                $this->inicio($titulo);
                $this->titulos(6, 10, 30, $campos1, $campos);
                $pos_y = 42;
                $i = 1;
            }

            $this->SetFont('Arial', '', 10);
            $this->SetXY(10, $pos_y);
            $this->SetFillColor(999);
            $this->Cell(25, 6, $this->txt((string) $obj->folio), 1, 0, 'C', 1);
            $this->Cell(35, 6, $this->txt((string) $obj->marca), 1, 0, 'C', 1);
            $this->Cell(35, 6, $this->txt((string) $obj->medida), 1, 0, 'C', 1);
            $this->Cell(35, 6, $this->txt((string) $obj->estado), 1, 0, 'C', 1);
            $this->Cell(25, 6, $this->txt((string) $obj->fecha_instalacion), 1, 0, 'C', 1);
            $this->Cell(25, 6, (string) $obj->kilometraje, 1, 0, 'C', 1);

            $pos_y += 6;
            $i++;
        }

        return $this->salida('NeumaticosActivosXTractivos.pdf');
    }

    /**
     * Duración acumulada de un neumático (equivalente a
     * modNeumaticos::mostrar_duracionneumatico).
     */
    protected function duracionNeumatico(int $idNeumatico): float
    {
        $suma = (float) DB::table('neumaticos_movimientos')
            ->where('id_neumatico', $idNeumatico)
            ->whereNotNull('km_retirado')
            ->where('km_retirado', '<>', 0)
            ->sum(DB::raw('km_retirado - km_instalado'));

        $ultimo = DB::table('neumaticos_movimientos')
            ->where('id_neumatico', $idNeumatico)
            ->orderByDesc('id')
            ->first();

        if ($ultimo && ($ultimo->km_retirado === null || (float) $ultimo->km_retirado == 0.0)) {
            $kmActual = 0.0;
            $vehiculo = Catalogos::idDe('destinos_agregados', 1);
            if ($vehiculo !== null && (int) $ultimo->id_destino === (int) $vehiculo && (int) $ultimo->posicion !== 5) {
                $kmActual = (float) (DB::table('tractivos')->where('id', $ultimo->id_tractivo)->value('kilometraje_actual') ?? 0);
            }
            $suma += $kmActual - (float) $ultimo->km_instalado;
        }

        return round($suma, 2);
    }

    /**
     * Duración de un movimiento individual (km retirado - instalado o
     * kilometraje actual del tractivo si sigue montado).
     */
    protected function duracionMovimiento(object $mov): float
    {
        if ($mov->km_retirado !== null && (float) $mov->km_retirado != 0.0) {
            return round((float) $mov->km_retirado - (float) $mov->km_instalado, 2);
        }

        $vehiculo = Catalogos::idDe('destinos_agregados', 1);
        if ((int) $mov->posicion === 5 || ($vehiculo !== null && (int) $mov->id_destino !== (int) $vehiculo)) {
            return 0.0;
        }

        $kmActual = (float) (DB::table('tractivos')->where('id', $mov->id_tractivo)->value('kilometraje_actual') ?? 0);

        return round($kmActual - (float) $mov->km_instalado, 2);
    }

    /**
     * Replica Reportestec::ajustar_notas(): parte un texto en líneas.
     *
     * @return array<int, string>
     */
    protected function neumAjustarNotas(string $string, int $largo): array
    {
        $palabras = explode(' ', $string);
        $arrpalabras = [];
        $linea = '';
        foreach ($palabras as $palabra) {
            if (strlen($linea) + strlen($palabra) <= $largo) {
                $linea .= ' '.$palabra;
            } else {
                $arrpalabras[] = $linea;
                $linea = $palabra;
            }
        }
        $arrpalabras[] = $linea;

        return $arrpalabras;
    }

    /** Mensaje legacy "NO EXISTEN DATOS PARA MOSTRAR" (tras `inicio`). */
    protected function noExistenDatos(): void
    {
        $this->SetFont('Arial', 'B', 25);
        $this->SetFillColor(999);
        $this->SetXY(10, 65);
        $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');
    }

    /** Color de fondo legacy (session FillColor); fallback gris claro. */
    protected function fillColor(): int
    {
        $color = session('FillColor');

        return is_numeric($color) ? (int) $color : 220;
    }
}
