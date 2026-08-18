<?php

namespace App\Support;

use App\Models\CatalogoTipo;
use Illuminate\Validation\Rule;

/**
 * Fuente única de verdad para la configuración de los catálogos
 * unificados (catalogo_items + catalogo_tipos).
 *
 * Schema-driven: los campos extra por tipo se leen de la columna JSON
 * `fields` de catalogo_tipos (migración 2026_08_18_100000). Editar ese
 * JSON cambia el formulario sin tocar código. El array hardcodeado de
 * antaño vive solo en esa migración como valor inicial.
 *
 * Sigue en PHP (pequeñas y difíciles de administrar por el cliente):
 * WITH_SOFT_DELETE (mostrar borrados), usaCodigoManual y searchFields.
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
     * Cache por request de los campos extra leídos de la BD.
     *
     * @var array<string, array|null>
     */
    private static array $cache = [];

    /**
     * Valores por defecto de los campos extra por tipo (bootstrap/seed).
     * NO es la fuente de verdad en runtime: eso es `catalogo_tipos.fields`
     * (BD). Este array solo se usa para sembrar un tipo nuevo o cuando la
     * columna `fields` aún no existe (degradación previa a la migración).
     */
    private const DEFAULT_FIELDS = [
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
        'tipos_penalizaciones' => [
            'id_tipo_pago_adicional' => ['label' => 'Pago Adicional', 'type' => 'number'],
            'id_area' => ['label' => 'Área Penalizada', 'type' => 'number'],
            'importe' => ['label' => '% Penalización', 'type' => 'number'],
        ],
        'tipos_roturas' => [],
        'tipos_servicios' => [],
    ];

    /**
     * Campos extra por defecto de un tipo (valores de bootstrap). Usado por
     * el seeder y por extraFields() como degradación.
     */
    public static function defaultFields(string $tipo): array
    {
        return self::DEFAULT_FIELDS[$tipo] ?? [];
    }

    /**
     * Limpia la cache de campos por request. Útil en tests para que un cambio
     * en `catalogo_tipos.fields` se refleje sin reiniciar el proceso.
     */
    public static function flushCache(): void
    {
        self::$cache = [];
    }

    public static function extraFields(string $tipo): array
    {
        if (array_key_exists($tipo, self::$cache)) {
            return self::$cache[$tipo] ?? [];
        }

        $campos = self::defaultFields($tipo);

        try {
            $raw = CatalogoTipo::where('tipo', $tipo)->value('fields');
            if (is_array($raw)) {
                // `value()` aplica el cast `array` del modelo → ya es array.
                $campos = $raw;
            } elseif (is_string($raw)) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $campos = $decoded;
                }
            }
        } catch (\Throwable $e) {
            // Antes de la migración 2026_08_18_100000 la columna `fields` no
            // existe: se degrada a los valores por defecto embebidos.
            $campos = self::defaultFields($tipo);
        }

        self::$cache[$tipo] = $campos;

        return $campos;
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
