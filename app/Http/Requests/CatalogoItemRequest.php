<?php

namespace App\Http\Requests;

use App\Support\CatalogoSchema;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación y autorización para crear/editar ítems del catálogo
 * unificado (rutas catalogo.store / catalogo.update).
 *
 * El frontend envía los campos extra PLANOS (p.ej. `descripcion`,
 * `siglas`); aquí se validan planos y se empaquetan en la columna
 * JSON `extra` en itemData(), cerrando el desfase front/back que
 * hacía que los extras se perdieran silenciosamente.
 */
class CatalogoItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permiso = $this->isMethod('post') ? 'catalogo.crear' : 'catalogo.editar';

        return $this->user()?->can($permiso) ?? false;
    }

    public function rules(): array
    {
        return CatalogoSchema::validationRules($this->route('tipo'));
    }

    public function attributes(): array
    {
        $attributes = [
            'nombre' => 'nombre',
            'codigo' => 'código',
            'activo' => 'activo',
        ];

        foreach (CatalogoSchema::extraFields($this->route('tipo')) as $key => $cfg) {
            $attributes[$key] = mb_strtolower($cfg['label'] ?? $key);
        }

        return $attributes;
    }

    /**
     * Datos listos para CatalogoItem::create()/update():
     * columnas reales del modelo + extras empaquetados en el JSON.
     */
    public function itemData(): array
    {
        $tipo = $this->route('tipo');
        $validados = $this->validated();

        $data = [
            'nombre' => $validados['nombre'],
            // Paridad con el comportamiento anterior: si no viene, queda activo.
            'activo' => $this->has('activo') ? $this->boolean('activo') : true,
        ];

        // El código solo se persiste si vino en el request; cuando es
        // automático lo genera el controlador (store) o no se toca (update).
        if (array_key_exists('codigo', $validados) && $validados['codigo'] !== null) {
            $data['codigo'] = $validados['codigo'];
        }

        // Solo se toca la columna `extra` si el tipo tiene campos extra
        // configurados; así un update no borra extras migrados de tipos
        // aún sin configuración.
        $camposExtra = array_keys(CatalogoSchema::extraFields($tipo));
        if ($camposExtra !== []) {
            $extra = [];
            foreach ($camposExtra as $key) {
                if (array_key_exists($key, $validados) && $validados[$key] !== null && $validados[$key] !== '') {
                    $extra[$key] = $validados[$key];
                }
            }
            $data['extra'] = $extra ?: null;
        }

        return $data;
    }
}
