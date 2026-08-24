<?php

namespace App\Services;

use App\Models\Caja;
use App\Models\Diferenciale;
use App\Models\Motore;
use App\Models\Tractivo;
use Illuminate\Support\Facades\DB;

/**
 * Regla de negocio del módulo técnico: todo tractivo debe tener SIEMPRE un
 * motor, una caja y un diferencial. Los movimientos (cambios) de estos
 * agregados se realizan por la Orden de Taller, nunca desde el codificador
 * de vehículos (allí las asociaciones son solo informativas).
 *
 * - Al crear/migrar un tractivo sin alguno de los tres, se le crean tomando
 *   marca y modelo del propio tractivo. Códigos: M-{código}, C-{código},
 *   D-{código}. Si ya existe uno creado para el tractivo, se usa ese valor.
 * - Un agregado creado sin tractivo queda obligatoriamente en estado
 *   "disponible"; asignado a un tractivo pasa a "trabajando".
 * - Dar de baja un agregado SIN hacer el cambio deja el tractivo INACTIVO.
 */
class AgregadosTractivoService
{
    public const ESTADO_DISPONIBLE = 'disponible';

    public const ESTADO_TRABAJANDO = 'trabajando';

    public const ESTADO_BAJA = 'baja';

    /**
     * Garantiza que el tractivo tenga motor, caja y diferencial. Idempotente:
     * los componentes existentes no se tocan. Devuelve los creados.
     */
    public function asegurar(Tractivo $tractivo): array
    {
        $creados = [];

        if (! $tractivo->id_motor) {
            $motor = $this->crearMotor($tractivo);
            $creados['motor'] = $motor;
        }

        if (! $tractivo->id_caja) {
            $caja = $this->crearCaja($tractivo);
            $creados['caja'] = $caja;
        }

        if (! $tractivo->id_diferencial) {
            $diferencial = $this->crearDiferencial($tractivo);
            $creados['diferencial'] = $diferencial;
        }

        if ($creados !== []) {
            $tractivo->refresh();
        }

        return $creados;
    }

    /**
     * Versión masiva para ETL/backfill: asegura motor/caja/diferencial a todos
     * los tractivos que les falte alguno. Devuelve [motores, cajas, diferenciales].
     */
    public function asegurarTodos(?int $esperado = null, ?array &$avisos = null): array
    {
        $conteo = ['motor' => 0, 'caja' => 0, 'diferencial' => 0];

        Tractivo::query()
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNull('id_motor')->orWhereNull('id_caja')->orWhereNull('id_diferencial');
            })
            ->orderBy('id')
            ->chunk(500, function ($tractivos) use (&$conteo, &$avisos) {
                foreach ($tractivos as $tractivo) {
                    $creados = $this->asegurar($tractivo);
                    foreach ($creados as $tipo => $componente) {
                        $conteo[$tipo]++;
                        if (is_array($avisos)) {
                            $avisos[] = "tractivo#{$tractivo->id}: {$tipo} auto-generado {$componente->codigo}";
                        }
                    }
                }
            });

        return $conteo;
    }

    public function crearMotor(Tractivo $tractivo): Motore
    {
        return DB::transaction(function () use ($tractivo) {
            // Reusar el componente ya creado para este tractivo si existe.
            $existente = Motore::where('id_tractivo', $tractivo->id)->first();
            if (! $existente) {
                $existente = Motore::create([
                    'codigo' => 'M-'.$this->sufijo($tractivo),
                    'descripcion' => $this->descripcion('Motor', $tractivo),
                    'marca' => $tractivo->marca,
                    'modelo' => $tractivo->modelo,
                    'numero_serie' => $tractivo->numero_motor ?: ($tractivo->codigo ?: (string) $tractivo->id),
                    'id_entidad' => $tractivo->id_entidad,
                    'id_tractivo' => $tractivo->id,
                    'estado' => self::ESTADO_TRABAJANDO,
                ]);
            }

            $tractivo->forceFill(['id_motor' => $existente->id])->save();

            return $existente;
        });
    }

    public function crearCaja(Tractivo $tractivo): Caja
    {
        return DB::transaction(function () use ($tractivo) {
            $existente = Caja::where('id_tractivo', $tractivo->id)->first();
            if (! $existente) {
                $existente = Caja::create([
                    'codigo' => 'C-'.$this->sufijo($tractivo),
                    'descripcion' => $this->descripcion('Caja', $tractivo),
                    'marca' => $tractivo->marca,
                    'modelo' => $tractivo->modelo,
                    'numero_serie' => $tractivo->numero_caja ?: ($tractivo->codigo ?: (string) $tractivo->id),
                    'id_entidad' => $tractivo->id_entidad,
                    'id_tractivo' => $tractivo->id,
                    'estado' => self::ESTADO_TRABAJANDO,
                ]);
            }

            $tractivo->forceFill(['id_caja' => $existente->id])->save();

            return $existente;
        });
    }

    public function crearDiferencial(Tractivo $tractivo): Diferenciale
    {
        return DB::transaction(function () use ($tractivo) {
            $existente = Diferenciale::where('id_tractivo', $tractivo->id)->first();
            if (! $existente) {
                $existente = Diferenciale::create([
                    'codigo' => 'D-'.$this->sufijo($tractivo),
                    'descripcion' => $this->descripcion('Diferencial', $tractivo),
                    'marca' => $tractivo->marca,
                    'modelo' => $tractivo->modelo,
                    'numero_serie' => $tractivo->codigo ?: (string) $tractivo->id,
                    'id_entidad' => $tractivo->id_entidad,
                    'id_tractivo' => $tractivo->id,
                    'estado' => self::ESTADO_TRABAJANDO,
                ]);
            }

            $tractivo->forceFill(['id_diferencial' => $existente->id])->save();

            return $existente;
        });
    }

    /**
     * Baja de un agregado SIN cambio: queda en "baja" con fecha, se libera del
     * tractivo y este queda INACTIVO (no puede operar sin sus 3 agregados).
     */
    public function darBaja(Motore|Caja|Diferenciale $agregado): void
    {
        DB::transaction(function () use ($agregado) {
            $tractivoId = $agregado->id_tractivo;

            $agregado->estado = self::ESTADO_BAJA;
            $agregado->fecha_baja = now()->toDateString();
            $agregado->id_tractivo = null;
            $agregado->save();

            if ($tractivoId && ($tractivo = Tractivo::find($tractivoId))) {
                $campo = match (true) {
                    $agregado instanceof Motore => 'id_motor',
                    $agregado instanceof Caja => 'id_caja',
                    default => 'id_diferencial',
                };
                $tractivo->forceFill([$campo => null])->save();

                // Sin motor, caja o diferencial el tractivo queda inactivo.
                $tractivo->estado = 'inactivo';
                $tractivo->save();
            }
        });
    }

    private function sufijo(Tractivo $tractivo): string
    {
        return $tractivo->codigo !== '' && $tractivo->codigo !== null ? $tractivo->codigo : (string) $tractivo->id;
    }

    private function descripcion(string $tipo, Tractivo $tractivo): string
    {
        $partes = array_filter([$tipo, $tractivo->marca, $tractivo->modelo]);

        return implode(' ', $partes).' ('.$this->sufijo($tractivo).')';
    }
}
