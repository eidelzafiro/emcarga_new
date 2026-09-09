<?php

namespace App\Services;

use App\Http\Controllers\Traits\EntidadScoping;
use App\Models\Bolsa;
use App\Models\Cargo;
use App\Models\Area;
use App\Models\Entidad;
use App\Models\GrupoEscala;
use App\Models\Incidencia;
use App\Models\Penalizacion;
use App\Models\Plantilla;
use App\Models\Salario;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardRecursosHumanosService
{
    use EntidadScoping;

    public function datos(): array
    {
        $entidadId = (int) entidadActivaId();
        $idsEntidades = $entidadId ? Entidad::idsPermitidos($entidadId) : [];
        $entidadNombre = $entidadId ? (Entidad::find($entidadId)?->nombre ?? '—') : '—';

        // ═══ SECCIÓN 1: EMPRESA ACTIVA ═══

        $entidad = $entidadId ? Entidad::find($entidadId) : null;

        $totalAreas = Area::whereIn('id_entidad', $idsEntidades)
            ->where('activo', true)
            ->count();

        $totalCargos = Cargo::whereIn('id_entidad', $idsEntidades)
            ->where('activo', true)
            ->count();

        $cargosEnUso = Bolsa::whereIn('id_entidad', $idsEntidades)
            ->where('activo', true)
            ->whereNotNull('id_cargo')
            ->distinct('id_cargo')
            ->count('id_cargo');

        $totalTrabajadores = Bolsa::whereIn('id_entidad', $idsEntidades)
            ->where('activo', true)
            ->count();

        $trabajadoresPorArea = Bolsa::whereIn('bolsa.id_entidad', $idsEntidades)
            ->where('bolsa.activo', true)
            ->join('areas', 'bolsa.id_area', '=', 'areas.id')
            ->select('areas.nombre as area', DB::raw('COUNT(*) as total'))
            ->groupBy('areas.nombre')
            ->orderByDesc('total')
            ->get()
            ->all();

        // Cobertura de plantilla por área
        $plantillaPorArea = Plantilla::whereIn('plantilla.id_entidad', $idsEntidades)
            ->join('areas', 'plantilla.id_area', '=', 'areas.id')
            ->select(
                'areas.nombre as area',
                DB::raw('SUM(plantilla.propuesta) as plazas_propuesta'),
                DB::raw('SUM(plantilla.aprobada) as plazas_aprobadas'),
                DB::raw('SUM(plantilla.cubierta) as plazas_cubiertas')
            )
            ->groupBy('areas.nombre')
            ->orderByDesc('plazas_propuesta')
            ->get()
            ->all();

        // Calcular trabajadores reales por cargo/area para cubierta_real
        $trabajadoresPorCargoArea = Bolsa::whereIn('bolsa.id_entidad', $idsEntidades)
            ->where('bolsa.activo', true)
            ->whereNotNull('bolsa.id_cargo')
            ->select('id_cargo', 'id_area', DB::raw('COUNT(*) as total_reales'))
            ->groupBy('id_cargo', 'id_area')
            ->get()
            ->keyBy(fn ($r) => $r->id_cargo . '_' . $r->id_area);

        $totalPlazasPropuesta = 0;
        $totalPlazasAprobadas = 0;
        $totalTrabajadoresReales = 0;
        foreach ($plantillaPorArea as &$p) {
            $totalPlazasPropuesta += $p->plazas_propuesta;
            $totalPlazasAprobadas += $p->plazas_aprobadas;
        }
        unset($p);

        $totalTrabajadoresReales = Bolsa::whereIn('id_entidad', $idsEntidades)
            ->where('activo', true)
            ->count();

        $salariosPorGrupoEscala = Cargo::whereIn('cargos.id_entidad', $idsEntidades)
            ->where('cargos.activo', true)
            ->join('bolsa', 'cargos.id', '=', 'bolsa.id_cargo')
            ->leftJoin('grupos_escala', 'cargos.id_grupo_escala', '=', 'grupos_escala.id')
            ->select(
                DB::raw('COALESCE(grupos_escala.nombre, "Sin escala") as grupo_escala'),
                DB::raw('COUNT(DISTINCT bolsa.id) as trabajadores'),
                DB::raw('COALESCE(SUM(DISTINCT grupos_escala.salario), 0) as salario_total')
            )
            ->groupBy('grupos_escala.nombre')
            ->orderByDesc('trabajadores')
            ->get()
            ->all();

        // ═══ SECCIÓN 2: FUERZA DE TRABAJO ═══

        $bolsaQuery = Bolsa::whereIn('bolsa.id_entidad', $idsEntidades)
            ->where('bolsa.activo', true);

        // Sexo (id → catalogo_items tipos_sexo)
        $porSexo = (clone $bolsaQuery)
            ->leftJoin('catalogo_items as cat_sexo', 'bolsa.sexo', '=', 'cat_sexo.id')
            ->select(
                DB::raw('COALESCE(cat_sexo.nombre, "No especificado") as etiqueta'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('cat_sexo.nombre')
            ->get()
            ->all();

        // Color de piel (id → catalogo_items tipos_color_piel)
        $porColorPiel = (clone $bolsaQuery)
            ->leftJoin('catalogo_items as cat_piel', 'bolsa.color_piel', '=', 'cat_piel.id')
            ->select(
                DB::raw('COALESCE(cat_piel.nombre, "No especificado") as etiqueta'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('cat_piel.nombre')
            ->orderByDesc('total')
            ->get()
            ->all();

        // Nivel educacional (id → catalogo_items tipos_nivel_educacion)
        $porNivelEducacional = (clone $bolsaQuery)
            ->leftJoin('catalogo_items as cat_nivel', 'bolsa.nivel_educacional', '=', 'cat_nivel.id')
            ->select(
                DB::raw('COALESCE(cat_nivel.nombre, "No especificado") as etiqueta'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('cat_nivel.nombre')
            ->orderByDesc('total')
            ->get()
            ->all();

        // Rango de edades (usando fecha_nacimiento)
        $hoy = now();
        $todosTrabajadores = (clone $bolsaQuery)
            ->select('id', 'fecha_nacimiento', 'ci')
            ->get();

        $rangosEdad = [
            '18-25' => 0, '26-35' => 0, '36-45' => 0,
            '46-55' => 0, '56-65' => 0, '65+' => 0, 'Sin dato' => 0,
        ];

        foreach ($todosTrabajadores as $t) {
            $edad = null;

            if ($t->fecha_nacimiento) {
                $edad = Carbon::parse($t->fecha_nacimiento)->age;
            } elseif ($t->ci && strlen($t->ci) >= 6) {
                // Los 6 primeros dígitos del CI cubano son YYMMDD (fecha de nacimiento)
                $anio = (int) substr($t->ci, 0, 2);
                $mes = (int) substr($t->ci, 2, 2);
                $dia = (int) substr($t->ci, 4, 2);

                if ($mes >= 1 && $mes <= 12 && $dia >= 1 && $dia <= 31) {
                    $anioCompleto = $anio + ($anio > 30 ? 1900 : 2000);
                    $fechaNac = Carbon::createFromDate($anioCompleto, $mes, $dia);
                    if ($fechaNac->isValid()) {
                        $edad = $fechaNac->age;
                    }
                }
            }

            if ($edad === null) {
                $rangosEdad['Sin dato']++;
            } elseif ($edad <= 25) {
                $rangosEdad['18-25']++;
            } elseif ($edad <= 35) {
                $rangosEdad['26-35']++;
            } elseif ($edad <= 45) {
                $rangosEdad['36-45']++;
            } elseif ($edad <= 55) {
                $rangosEdad['46-55']++;
            } elseif ($edad <= 65) {
                $rangosEdad['56-65']++;
            } else {
                $rangosEdad['65+']++;
            }
        }

        $porEdad = collect($rangosEdad)
            ->map(fn ($total, $rango) => ['etiqueta' => $rango, 'total' => $total])
            ->values()
            ->all();

        // ═══ SECCIÓN 3: DOCUMENTACIÓN CHOFERES ═══

        $choferesQuery = Bolsa::whereIn('bolsa.id_entidad', $idsEntidades)
            ->where('bolsa.activo', true)
            ->join('cargos', 'bolsa.id_cargo', '=', 'cargos.id')
            ->where('cargos.nombre', 'LIKE', '%CHOFER%');

        $totalChoferes = (clone $choferesQuery)->count();

        // Categorías de licencia (pivote licencia_categorias).
        $categoriasLicencia = Bolsa::whereIn('bolsa.id_entidad', $idsEntidades)
            ->where('bolsa.activo', true)
            ->join('cargos', 'bolsa.id_cargo', '=', 'cargos.id')
            ->where('cargos.nombre', 'LIKE', '%CHOFER%')
            ->join('licencia_categorias as lc', 'lc.id_bolsa', '=', 'bolsa.id')
            ->select('lc.categoria as etiqueta', DB::raw('COUNT(*) as total'))
            ->groupBy('lc.categoria')
            ->orderByDesc('total')
            ->get()
            ->all();

        // Estado de documentos (tabla documentos_chofer, un documento por tipo).
        $estadoDocumento = function (string $tipo, bool $requerirDoc = true) use ($idsEntidades, $hoy, $choferesQuery): array {
            $limite = $hoy->copy()->addDays(90)->toDateString();

            $vencidos = (clone $choferesQuery)
                ->join('documentos_chofer as d', function ($j) use ($tipo) {
                    $j->on('d.id_bolsa', '=', 'bolsa.id')->where('d.tipo', $tipo);
                })
                ->whereNotNull('d.vencimiento')
                ->where('d.vencimiento', '<', $hoy->toDateString())
                ->count();

            $porVencer = (clone $choferesQuery)
                ->join('documentos_chofer as d', function ($j) use ($tipo) {
                    $j->on('d.id_bolsa', '=', 'bolsa.id')->where('d.tipo', $tipo);
                })
                ->whereNotNull('d.vencimiento')
                ->where('d.vencimiento', '>=', $hoy->toDateString())
                ->where('d.vencimiento', '<=', $limite)
                ->count();

            $sinVencer = (clone $choferesQuery)
                ->join('documentos_chofer as d', function ($j) use ($tipo) {
                    $j->on('d.id_bolsa', '=', 'bolsa.id')->where('d.tipo', $tipo);
                })
                ->where(function ($q) use ($limite) {
                    $q->where('d.vencimiento', '>', $limite)
                        ->orWhereNull('d.vencimiento');
                })
                ->count();

            return [
                'vencidos' => $vencidos,
                'por_vencer' => $porVencer,
                'vigentes' => $sinVencer,
            ];
        };

        $licencias = $estadoDocumento('LICENCIA');
        $licenciasVencidas = $licencias['vencidos'];
        $licenciasPorVencer = $licencias['por_vencer'];
        $licenciasVigentes = $licencias['vigentes'];

        $psicometrico = $estadoDocumento('PSICOMETRICO');
        $psicometricoVencido = $psicometrico['vencidos'];
        $psicometricoPorVencer = $psicometrico['por_vencer'];
        $psicometricoVigentes = $psicometrico['vigentes'];

        $recalificacion = $estadoDocumento('RECALIFICACION');
        $recalificacionVencida = $recalificacion['vencidos'];
        $recalificacionPorVencer = $recalificacion['por_vencer'];
        $recalificacionVigentes = $recalificacion['vigentes'];

        $chequeo = $estadoDocumento('CHEQUEO_MEDICO');
        $chequeoMedicoVencido = $chequeo['vencidos'];
        $chequeoMedicoPorVencer = $chequeo['por_vencer'];
        $chequeoMedicoVigentes = $chequeo['vigentes'];

        // ═══ SECCIÓN 4: SALARIO ═══

        // Usar fecha_operaciones de la sesión (mes/año del módulo) como hace todo el proyecto
        $fechaOps = session('fecha_operaciones');
        if ($fechaOps) {
            $mesActual = Carbon::parse($fechaOps)->startOfMonth()->toDateString();
            $finMes = Carbon::parse($fechaOps)->endOfMonth()->toDateString();
        } else {
            $mesActual = $hoy->copy()->startOfMonth()->toDateString();
            $finMes = $hoy->copy()->endOfMonth()->toDateString();
        }

        $incidenciasMes = Incidencia::whereIn('incidencias.id_bolsa', function ($q) use ($idsEntidades) {
            $q->select('id')->from('bolsa')->whereIn('id_entidad', $idsEntidades)->where('activo', true);
        })
            ->whereBetween('fecha_inicio', [$mesActual, $finMes])
            ->join('catalogo_items as ci', 'incidencias.id_tipo_incidencia', '=', 'ci.id')
            ->select(
                'ci.nombre as tipo',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(importe) as total_importe'),
                DB::raw('SUM(periodo_actual) as total_periodo')
            )
            ->groupBy('ci.nombre')
            ->orderByDesc('total')
            ->get()
            ->all();

        $totalIncidencias = array_sum(array_column($incidenciasMes, 'total'));
        $importeIncidencias = array_sum(array_column($incidenciasMes, 'total_importe'));

        // Penalizaciones del mes actual
        $penalizacionesMes = Penalizacion::whereIn('penalizaciones.id_bolsa', function ($q) use ($idsEntidades) {
            $q->select('id')->from('bolsa')->whereIn('id_entidad', $idsEntidades)->where('activo', true);
        })
            ->whereBetween('fecha', [$mesActual, $finMes])
            ->join('catalogo_items as ci', 'penalizaciones.id_tipo_penalizacion', '=', 'ci.id')
            ->select(
                'ci.nombre as tipo',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(importe) as total_importe')
            )
            ->groupBy('ci.nombre')
            ->orderByDesc('total')
            ->get()
            ->all();

        $totalPenalizaciones = array_sum(array_column($penalizacionesMes, 'total'));
        $importePenalizaciones = array_sum(array_column($penalizacionesMes, 'total_importe'));

        // Salarios por áreas (si hay datos)
        $salariosPorArea = Salario::whereIn('salarios.id_entidad', $idsEntidades)
            ->join('areas', 'salarios.id_area', '=', 'areas.id')
            ->select(
                'areas.nombre as area',
                DB::raw('COUNT(DISTINCT salarios.id_bolsa) as trabajadores'),
                DB::raw('SUM(imp_salario_final) as total_salario')
            )
            ->groupBy('areas.nombre')
            ->orderByDesc('total_salario')
            ->get()
            ->all();

        // Salarios de choferes por tarifa (columna salarios.tarifa)
        $salariosChoferesPorTasas = Salario::whereIn('salarios.id_entidad', $idsEntidades)
            ->join('bolsa', 'salarios.id_bolsa', '=', 'bolsa.id')
            ->join('cargos', 'bolsa.id_cargo', '=', 'cargos.id')
            ->where('cargos.nombre', 'LIKE', '%CHOFER%')
            ->select(
                'salarios.tarifa as tasa',
                DB::raw('COUNT(DISTINCT salarios.id_bolsa) as choferes'),
                DB::raw('SUM(imp_regular) as total_regular'),
                DB::raw('SUM(imp_reservas_alm) as total_almacenaje'),
                DB::raw('SUM(t_total) as total_tiempo_trabajado'),
                DB::raw('SUM(imp_salario_final) as total_salario')
            )
            ->groupBy('salarios.tarifa')
            ->orderByDesc('total_salario')
            ->get()
            ->all();

        $totalSalarioFinal = array_sum(array_column($salariosPorArea, 'total_salario'));
        $totalTrabajadoresSalario = array_sum(array_column($salariosPorArea, 'trabajadores'));

        return [
            'entidadNombre' => $entidadNombre,
            'entidad' => $entidad ? [
                'nombre' => $entidad->nombre,
                'abreviatura' => $entidad->abreviatura,
                'direccion' => $entidad->direccion,
            ] : null,

            // Sección 1: Empresa activa
            'totalAreas' => $totalAreas,
            'totalCargos' => $totalCargos,
            'cargosEnUso' => $cargosEnUso,
            'totalTrabajadores' => $totalTrabajadores,
            'trabajadoresPorArea' => $trabajadoresPorArea,
            'salariosPorGrupoEscala' => $salariosPorGrupoEscala,
            'plantillaPorArea' => $plantillaPorArea,
            'totalPlazasPropuesta' => $totalPlazasPropuesta,
            'totalPlazasAprobadas' => $totalPlazasAprobadas,
            'totalTrabajadoresReales' => $totalTrabajadoresReales,

            // Sección 2: Fuerza de trabajo
            'porSexo' => $porSexo,
            'porColorPiel' => $porColorPiel,
            'porNivelEducacional' => $porNivelEducacional,
            'porEdad' => $porEdad,

            // Sección 3: Documentación choferes
            'totalChoferes' => $totalChoferes,
            'categoriasLicencia' => $categoriasLicencia,
            'documentacion' => [
                'licencia' => [
                    'vigentes' => max(0, $licenciasVigentes),
                    'vencidas' => $licenciasVencidas,
                    'por_vencer' => $licenciasPorVencer,
                ],
                'psicometrico' => [
                    'vigentes' => max(0, $psicometricoVigentes),
                    'vencidos' => $psicometricoVencido,
                    'por_vencer' => $psicometricoPorVencer,
                ],
                'recalificacion' => [
                    'vigentes' => max(0, $recalificacionVigentes),
                    'vencidas' => $recalificacionVencida,
                    'por_vencer' => $recalificacionPorVencer,
                ],
                'chequeo_medico' => [
                    'vigentes' => max(0, $chequeoMedicoVigentes),
                    'vencidos' => $chequeoMedicoVencido,
                    'por_vencer' => $chequeoMedicoPorVencer,
                ],
            ],

            // Sección 4: Salario
            'incidenciasMes' => $incidenciasMes,
            'totalIncidencias' => $totalIncidencias,
            'importeIncidencias' => $importeIncidencias,
            'penalizacionesMes' => $penalizacionesMes,
            'totalPenalizaciones' => $totalPenalizaciones,
            'importePenalizaciones' => $importePenalizaciones,
            'salariosPorArea' => $salariosPorArea,
            'salariosChoferesPorTasas' => $salariosChoferesPorTasas,
            'totalSalarioFinal' => $totalSalarioFinal,
            'totalTrabajadoresSalario' => $totalTrabajadoresSalario,
        ];
    }
}
