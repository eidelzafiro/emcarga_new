<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TiposMantenimiento extends Model
{
    use SoftDeletes;

    protected $table = 'tipos_mantenimiento';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'activo',
        'kms_max',
        'frecuencia',
        'mtto_base',
        'holgura',
        'mttos',
    ];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function setMttosAttribute($value): void
    {
        $this->attributes['mttos'] = str_replace("\n", ' ', (string) $value);
    }

    public function lineas()
    {
        return $this->hasMany(LineasMantenimiento::class, 'id_tipo_mantenimiento');
    }

    /**
     * Replica del legacy `crear_planmtto()` (Taller.php): borra las líneas del
     * tipo y regenera el plan iterando desde $frecuencia hasta $kms_max con
     * paso $frecuencia. En los km donde el resto de dividir por $mtto_base es 0
     * asigna el siguiente mantenimiento de la lista $mttos (rotando); en el
     * resto asigna 'Rev'.
     */
    public function regenerarLineas(): void
    {
        $frecuencia = (int) $this->frecuencia;
        $kmsMax = (int) $this->kms_max;
        $mttoBase = (int) $this->mtto_base;

        if ($frecuencia <= 0 || $kmsMax <= 0) {
            return;
        }

        LineasMantenimiento::where('id_tipo_mantenimiento', $this->getKey())->delete();

        $mantenimientos = [];
        foreach (explode(' ', (string) $this->mttos) as $valor) {
            $valor = trim($valor);
            if ($valor !== '' && $valor !== ' ') {
                $mantenimientos[] = $valor;
            }
        }

        $cont = 0;
        for ($index = $frecuencia; $index <= $kmsMax; $index += $frecuencia) {
            $tipo = 'Rev';

            if ($mttoBase > 0 && $index % $mttoBase === 0) {
                $tipo = $mantenimientos[$cont++] ?? 'Rev';
            }

            if ($cont === count($mantenimientos)) {
                $cont = 0;
            }

            LineasMantenimiento::create([
                'id_tipo_mantenimiento' => $this->getKey(),
                'kilometraje' => $index,
                'descripcion' => $tipo,
            ]);
        }
    }
}
