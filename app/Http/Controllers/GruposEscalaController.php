<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\GrupoEscala;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class GruposEscalaController extends Controller
{
    use ManagesCatalog;

    const MULTIPLICADOR_SALARIO = 190.6;

    protected function getModelClass(): string
    {
        return GrupoEscala::class;
    }

    protected function getRouteName(): string
    {
        return 'grupos-escala';
    }

    protected function getTitle(): string
    {
        return 'Grupos Escala';
    }

    protected function isEntityScoped(): bool
    {
        return true;
    }

    protected function getExtraFields(): array
    {
        return [
            'tarifa' => ['label' => 'Tarifa', 'type' => 'number'],
            'salario' => ['label' => 'Salario', 'type' => 'number'],
        ];
    }

    protected function getValidationRules($id = null): array
    {
        $rules = [
            'nombre' => 'required|string|max:255',
            'activo' => 'boolean',
            'tarifa' => 'required|numeric|min:0',
            'salario' => 'required|numeric|min:0',
        ];

        return $rules;
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->getValidationRules());

        // Calcular salario desde tarifa o tarifa desde salario
        $data = $this->calcularSalarioTarifa($data);

        $data['id_entidad'] = (int) entidadActivaId();

        $model = $this->getModelClass();
        $model::create($data);

        if ($request->boolean('_continuar')) {
            return redirect()->back()->with('success', 'Creado correctamente. Puede continuar añadiendo.');
        }

        return redirect()->back()->with('success', 'Creado correctamente');
    }

    public function update(Request $request, $id)
    {
        $model = $this->getModelClass();
        $item = $model::findOrFail($id);

        $this->autorizarEntidad($item->id_entidad ?? null);

        $data = $request->validate($this->getValidationRules($id));

        // Calcular salario desde tarifa o tarifa desde salario
        $data = $this->calcularSalarioTarifa($data);

        $item->update($data);

        return redirect()->back()->with('success', 'Actualizado correctamente');
    }

    /**
     * Calcula salario desde tarifa (tarifa × 190.6) o tarifa desde salario.
     * Si ambos vienen, prevalece tarifa → salario.
     */
    private function calcularSalarioTarifa(array $data): array
    {
        $tarifa = isset($data['tarifa']) ? (float) $data['tarifa'] : null;
        $salario = isset($data['salario']) ? (float) $data['salario'] : null;

        if ($tarifa !== null && ($salario === null || $salario == 0)) {
            // Tarifa → salario
            $data['salario'] = round($tarifa * self::MULTIPLICADOR_SALARIO, 2);
        } elseif ($salario !== null && ($tarifa === null || $tarifa == 0)) {
            // Salario → tarifa
            $data['tarifa'] = round($salario / self::MULTIPLICADOR_SALARIO, 4);
        } elseif ($tarifa !== null && $salario !== null) {
            // Ambos presentes: recalcular salario desde tarifa
            $data['salario'] = round($tarifa * self::MULTIPLICADOR_SALARIO, 2);
        }

        return $data;
    }
}
