<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionTarifa;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TarifasConfigController extends Controller
{
    public function edit()
    {
        $config = ConfiguracionTarifa::first();
        if (! $config) {
            $config = ConfiguracionTarifa::create([]);
        }

        return Inertia::render('Tarifas/Configuracion', [
            'title' => 'Configuración de Tarifas',
            'config' => $config,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'demora_1' => 'nullable|numeric|min:0',
            'demora_2' => 'nullable|numeric|min:0',
            'kms_vacio_1' => 'nullable|numeric|min:0',
            'kms_vacio_2' => 'nullable|numeric|min:0',
            'tarifa_horaria_1' => 'nullable|numeric|min:0',
            'tarifa_horaria_2' => 'nullable|numeric|min:0',
            'kms_adicionales_1' => 'nullable|numeric|min:0',
            'kms_adicionales_2' => 'nullable|numeric|min:0',
            'almacenaje' => 'nullable|numeric|min:0',
            'recargo_1' => 'nullable|numeric|min:0',
            'recargo_2' => 'nullable|numeric|min:0',
            'recargo_3_1' => 'nullable|numeric|min:0',
            'recargo_3_2' => 'nullable|numeric|min:0',
            'recargo_3_3' => 'nullable|numeric|min:0',
            'recargo_4' => 'nullable|numeric|min:0',
            'recargo_5' => 'nullable|numeric|min:0',
            'hora_1' => 'nullable|integer|min:0',
            'hora_2' => 'nullable|integer|min:0',
            'hora_3' => 'nullable|integer|min:0',
            'izaje_1' => 'nullable|numeric|min:0',
            'izaje_2' => 'nullable|numeric|min:0',
            'valor_izaje_mt' => 'nullable|numeric|min:0',
            'valor_izaje_me' => 'nullable|numeric|min:0',
            'valor_almacenaje' => 'nullable|numeric|min:0',
            'plazo_libre_exp' => 'nullable|integer|min:0',
        ]);

        $config = ConfiguracionTarifa::first();
        $config->update($validated);

        return redirect()->route('tarifas-config.edit')
            ->with('success', 'Configuración de tarifas actualizada correctamente.');
    }
}
