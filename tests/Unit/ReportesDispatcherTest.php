<?php

namespace Tests\Unit;

use App\Services\Reports\ReportesDispatcher;
use ReflectionClass;
use Tests\TestCase;

class ReportesDispatcherTest extends TestCase
{
    private function mapa(): array
    {
        return (new ReflectionClass(ReportesDispatcher::class))->getConstant('MAPA');
    }

    public function test_todos_los_mapeos_resuelven_a_clase_y_metodo_validos(): void
    {
        foreach ($this->mapa() as $id => [$clase, $metodo]) {
            $this->assertTrue(
                class_exists($clase),
                "Reporte #{$id}: la clase {$clase} no existe."
            );

            $ref = new ReflectionClass($clase);
            $this->assertTrue(
                $ref->hasMethod($metodo) && $ref->getMethod($metodo)->isPublic(),
                "Reporte #{$id}: el método {$clase}::{$metodo} no existe o no es público."
            );
        }

        $this->assertNotEmpty($this->mapa(), 'El MAPA de reportes no debe estar vacío.');
    }

    public function test_las_clases_son_resolvibles_por_el_contenedor(): void
    {
        $vistos = [];
        foreach ($this->mapa() as $id => [$clase, $metodo]) {
            if (isset($vistos[$clase])) {
                continue;
            }
            $vistos[$clase] = true;

            $instancia = app($clase);
            $this->assertIsObject($instancia);
            $this->assertTrue(method_exists($instancia, $metodo));
        }
    }
}
