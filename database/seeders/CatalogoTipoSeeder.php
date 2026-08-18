<?php

namespace Database\Seeders;

use App\Models\CatalogoTipo;
use App\Support\CatalogoSchema;
use Illuminate\Database\Seeder;

class CatalogoTipoSeeder extends Seeder
{
    private array $titles = [
        'tipos_sistemas' => 'Tipos de Sistemas',
        'tipos_aceites' => 'Tipos de Aceites',
        'tipos_agregados' => 'Tipos de Agregados',
        'tipos_arrastres' => 'Tipos de Arrastres',
        'tipos_cargas' => 'Tipos de Cargas',
        'tipos_causas' => 'Causas',
        'tipos_color_piel' => 'Color de Piel',
        'tipos_combustibles' => 'Tipos de Combustibles',
        'tipos_deducciones' => 'Deducciones',
        'tipos_modelo' => 'Tipos de Modelo',
        'tipos_equipos' => 'Tipos de Equipos',
        'tipos_estado_civil' => 'Estado Civil',
        'tipos_estados' => 'Estados',
        'tipos_gastos' => 'Tipos de Gastos',
        'tipos_grupo_horario' => 'Grupo Horario',
        'tipos_incidencias' => 'Incidencias',
        'tipos_indicadores' => 'Indicadores',
        'tipos_integracion_politica' => 'Integración Política',
        'tipos_lubricantes' => 'Tipos de Lubricantes',
        'tipos_mantenimiento' => 'Tipos de Mantenimiento',
        'tipos_neumaticos' => 'Tipos de Neumáticos',
        'tipos_nivel_educacion' => 'Nivel de Educación',
        'tipos_operaciones' => 'Tipos de Operaciones',
        'tipos_pagos_adicionales' => 'Pagos Adicionales',
        'tipos_penalizaciones' => 'Penalizaciones',
        'tipos_roturas' => 'Tipos de Roturas',
        'tipos_servicios' => 'Tipos de Servicios',
        'tipos_sexo' => 'Sexo',
        'tipos_suspension' => 'Suspensión',
        'tipos_ubicacion_defensa' => 'Ubicación Defensa',
        'tipos_vehiculos' => 'Tipos de Vehículos',
        'tipos_sistemas_pago' => 'Tipos de Sistemas de Pago',
        'tipo_ingresos' => 'Tipos de Ingresos',
        'tipos_tractivos_alternativo' => 'Tipos de Tractivos Alternativo',
    ];

    public function run(): void
    {
        $grupos = [
            'Técnica' => ['tipos_aceites', 'tipos_agregados', 'tipos_arrastres', 'tipos_combustibles', 'tipos_equipos', 'tipos_lubricantes', 'tipos_neumaticos', 'tipos_roturas', 'tipos_tractivos_alternativo', 'tipos_vehiculos'],
            'Comercial' => ['tipos_cargas', 'tipos_servicios', 'tipos_gastos'],
            'Técnica' => ['tipos_aceites', 'tipos_agregados', 'tipos_arrastres', 'tipos_combustibles', 'tipos_equipos', 'tipos_lubricantes', 'tipos_neumaticos', 'tipos_roturas', 'tipos_sistemas', 'tipos_tractivos_alternativo', 'tipos_vehiculos'],
            'RRHH' => ['tipos_causas', 'tipos_color_piel', 'tipos_deducciones', 'tipos_modelo', 'tipos_estado_civil', 'tipos_estados', 'tipos_grupo_horario', 'tipos_incidencias', 'tipos_indicadores', 'tipos_integracion_politica', 'tipos_nivel_educacion', 'tipos_pagos_adicionales', 'tipos_penalizaciones', 'tipos_sistemas_pago', 'tipos_sexo', 'tipos_suspension', 'tipos_ubicacion_defensa', 'tipo_ingresos', 'tipos_mantenimiento', 'tipos_operaciones'],
        ];

        $all = [];
        foreach ($grupos as $agrupacion => $tipos) {
            foreach ($tipos as $tipo) {
                $all[] = ['tipo' => $tipo, 'agrupacion' => $agrupacion];
            }
        }

        $orden = 0;
        foreach ($all as $entry) {
            $orden++;
            $tipo = $entry['tipo'];
            CatalogoTipo::updateOrCreate(
                ['tipo' => $tipo],
                [
                    'titulo' => $this->titles[$tipo] ?? $tipo,
                    'agrupacion' => $entry['agrupacion'],
                    'activo' => true,
                    'orden' => $orden,
                    'tabla_legacy' => $tipo,
                    'fields' => CatalogoSchema::defaultFields($tipo) !== []
                        ? json_encode(CatalogoSchema::defaultFields($tipo), JSON_UNESCAPED_UNICODE)
                        : null,
                ]
            );
        }
    }
}
