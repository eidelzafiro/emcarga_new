<?php

namespace App\Support;

use Illuminate\Validation\Rule;

/**
 * Fuente única de verdad para la configuración de los catálogos
 * unificados (catalogo_items + catalogo_tipos).
 *
 * Centraliza lo que antes vivía hardcodeado en CatalogoController:
 * campos extra por tipo, código manual, campos de búsqueda y tipos
 * con soft-deletes migrados. Compartido por el controlador y el
 * FormRequest para que no vuelvan a desincronizarse.
 *
 * Paso futuro (pendiente): mover `$extraFields` a la columna JSON
 * `fields` de catalogo_tipos y leerlo desde BD (schema-driven).
 */
class CatalogoSchema
{
    /**
     * Tipos cuyos ítems migrados incluyen soft-deletes legacy.
     * El listado los muestra con withTrashed().
     */
    private const WITH_SOFT_DELETE = [
        'tipos_operaciones',
        'tipos_mantenimiento',
    ];

    /**
     * Campos extra (se persisten en el JSON `extra` de catalogo_items).
     * type soportados: text, textarea, number, boolean, select, email.
     */
    private const EXTRA_FIELDS = [
        'tipos_operaciones' => ['descripcion' => ['label' => 'Descripción', 'type' => 'textarea']],
        'tipos_mantenimiento' => ['descripcion' => ['label' => 'Descripción', 'type' => 'textarea']],
        'tipos_gastos' => ['tipo' => ['label' => 'Tipo', 'type' => 'text']],
        'tipos_causas' => ['tipo' => ['label' => 'Tipo', 'type' => 'text']],
        'tipos_estados' => [
            'imagen' => ['label' => 'Imagen', 'type' => 'text'],
            'siglas' => ['label' => 'Siglas', 'type' => 'text'],
        ],
        'tipo_ingresos' => ['siglas' => ['label' => 'Siglas', 'type' => 'text']],
        'tipos_vehiculos' => ['descripcion' => ['label' => 'Descripción', 'type' => 'textarea']],
        'tipos_deducciones' => [
            'descripcion' => ['label' => 'Descripción', 'type' => 'textarea'],
            'clave' => ['label' => 'Clave', 'type' => 'number'],
        ],
        'tipos_color_piel' => ['descripcion' => ['label' => 'Descripción', 'type' => 'text']],
        'tipos_integracion_politica' => [
            'descripcion' => ['label' => 'Descripción', 'type' => 'text'],
            'politica' => ['label' => 'Política', 'type' => 'text'],
            'abreviatura' => ['label' => 'Abreviatura', 'type' => 'text'],
        ],
        'tipos_nivel_educacion' => [
            'descripcion' => ['label' => 'Descripción', 'type' => 'text'],
            'abreviatura' => ['label' => 'Abreviatura', 'type' => 'text'],
        ],
        'tipos_sexo' => ['descripcion' => ['label' => 'Descripción', 'type' => 'text']],
        'tipos_ubicacion_defensa' => ['descripcion' => ['label' => 'Descripción', 'type' => 'text']],
        'tipos_indicadores' => [
            'descripcion' => ['label' => 'Descripción', 'type' => 'textarea'],
            'unidad' => ['label' => 'Unidad', 'type' => 'text'],
        ],
        'tipos_suspension' => ['descripcion' => ['label' => 'Descripción', 'type' => 'text']],
        'tipos_arrastres' => [
            'descripcion' => ['label' => 'Descripción', 'type' => 'textarea'],
            'capacidad_toneladas' => ['label' => 'Capacidad (ton)', 'type' => 'number'],
        ],
        'tipos_causas_baja' => [], // Tabla eliminada — se mantiene la entrada vacía para compatibilidad
        'tipos_sistemas' => [],
        'tipos_sistemas_pago' => [],
        'tipos_aceites' => [],
        'tipos_agregados' => [],
        'tipos_cargas' => [],
        'tipos_combustibles' => [],
        'tipos_equipos' => [],
        'tipos_incidencias' => [
            'id_tipo_deducciones' => ['label' => 'Tipo de Deducción', 'type' => 'number'],
            'tsuma' => ['label' => 'Suma Tiempo', 'type' => 'boolean'],
            'impsuma' => ['label' => 'Suma Importe', 'type' => 'boolean'],
        ],
        'tipos_neumaticos' => [],
        'tipos_modelo' => [
            'ancho' => ['label' => 'Ancho (mm)', 'type' => 'number'],
            'alto' => ['label' => 'Alto (mm)', 'type' => 'number'],
        ],
        'tipos_estado_civil' => [],
        'tipos_grupo_horario' => [],
        'tipos_lubricantes' => [],
        'tipos_pagos_adicionales' => [],
        'tipos_penalizaciones' => [],
        'tipos_roturas' => [],
        'tipos_servicios' => [],
    ];

    public static function extraFields(string $tipo): array
    {
        return self::EXTRA_FIELDS[$tipo] ?? [];
    }

    /**
     * En el catálogo unificado el código siempre es automático.
     * Se mantiene el hook por si un tipo futuro lo requiere manual.
     */
    public static function usaCodigoManual(string $tipo): bool
    {
        return false;
    }

    public static function searchFields(string $tipo): array
    {
        return ['codigo', 'nombre'];
    }

    public static function usaSoftDeletes(string $tipo): bool
    {
        return in_array($tipo, self::WITH_SOFT_DELETE, true);
    }

    /**
     * Reglas de validación con los extras PLANOS (tal como los envía
     * el frontend). La conversión a la columna JSON `extra` se hace
     * en CatalogoItemRequest::itemData().
     */
    public static function validationRules(string $tipo): array
    {
        $rules = [
            'nombre' => ['required', 'string', 'max:255'],
            'activo' => ['sometimes', 'boolean'],
            'codigo' => self::usaCodigoManual($tipo)
                ? ['required', 'string', 'max:50']
                : ['nullable', 'string', 'max:50'],
        ];

        foreach (self::extraFields($tipo) as $key => $cfg) {
            $rules[$key] = match ($cfg['type'] ?? 'text') {
                'number' => ['nullable', 'numeric'],
                'textarea' => ['nullable', 'string', 'max:2000'],
                'boolean' => ['nullable', 'boolean'],
                'email' => ['nullable', 'email', 'max:255'],
                'select' => isset($cfg['options'])
                    ? ['nullable', Rule::in(array_column($cfg['options'], 'value'))]
                    : ['nullable'],
                default => ['nullable', 'string', 'max:255'],
            };
        }

        return $rules;
    }
}
