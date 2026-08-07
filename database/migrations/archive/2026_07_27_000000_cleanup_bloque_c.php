<?php

use App\Models\MenuItem;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('unidades');

        $routes = [
            'pizarra.index',
            'hotkeys.index',
            'acciones-hotkeys.index',
            'lineas-bateria.index',
            'lineas-diferencial.index',
            'lineas-lubricante.index',
            'lineas-neumatico.index',
            'lineas-otro-agregado.index',
            'detalle-movimientos-inventario.index',
            'detalle-vales-inventario.index',
            'causas-gps.index',
            'causas-multas.index',
            'importes-gps.index',
            'importes-multas.index',
            'unidades.index',
            'tipos-subcta-unidad.index',
            'clientes-mm.index',
            'detalle-prefacturas.index',
            'perfiles-rh.index',
            'competencias-cargo.index',
            'funciones-cargo.index',
            'tipos-ramas.index',
            'tipos-sistemas-cuc.index',
            'buques.index',
        ];

        MenuItem::whereIn('route', $routes)->delete();
    }

    public function down(): void {}
};
