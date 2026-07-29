<?php

namespace App\Console\Commands;

use App\Models\CatalogoItem;
use App\Models\CatalogoTipo;
use Illuminate\Console\Command;

class EliminarTiposCatalogo extends Command
{
    protected $signature = 'zafiro:eliminar-tipos-catalogo {--force : Ejecutar sin confirmación}';

    protected $description = 'Elimina tipos de catálogo obsoletos y sus items';

    private array $aEliminar = [
        'tipos_contratos',
        'tipos_entidad',
        'tipos_conceptos',
        'tipos_calificadores',
        'tipos_causas_laborales',
        'tipos_causas_movimiento',
        'tipos_especialidad',
        'tipos_jefe_grupo',
        'tipos_plantillas',
        'tipos_ramas',
        'tipos_sistemas_cuc',
        'tipos_sistemas_pago',
        'tipos_subcta_unidad',
        'tipos_tallas',
        'tipos_medios_proteccion',
        'tipos_sistemas',
        'tipos_articulos_bolsa',
    ];

    public function handle(): int
    {
        $totalItems = CatalogoItem::whereIn('tipo', $this->aEliminar)->count();
        $totalTipos = CatalogoTipo::whereIn('tipo', $this->aEliminar)->count();

        $this->warn('Se eliminarán:');
        $this->line("  - {$totalTipos} tipos de catálogo");
        $this->line("  - {$totalItems} registros en catalogo_items");

        if (! $this->option('force') && ! $this->confirm('¿Continuar?')) {
            return Command::FAILURE;
        }

        foreach ($this->aEliminar as $tipo) {
            $items = CatalogoItem::where('tipo', $tipo)->count();
            if ($items > 0) {
                CatalogoItem::where('tipo', $tipo)->forceDelete();
                $this->line("  [ELIMINADO] {$tipo} ({$items} items)");
            } else {
                $this->line("  [VACÍO] {$tipo}");
            }

            CatalogoTipo::where('tipo', $tipo)->delete();
        }

        $this->info('=== Eliminación completada ===');

        return Command::SUCCESS;
    }
}
