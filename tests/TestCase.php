<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    private const BDS_PROHIBIDAS = ['emcarga_new', 'emcarga'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->verificarBlindajeBd();
    }

    /**
     * Blindaje: los tests NUNCA deben tocar las BD de trabajo reales
     * (emcarga_new / emcarga). Solo se permite:
     *  - sqlite (in-memory o archivo), o
     *  - mysql/otra conexion cuya base de datos NO este en la lista
     *    BDS_PROHIBIDAS (p.ej. emcarga_new_test).
     */
    private function verificarBlindajeBd(): void
    {
        $driver = config('database.default');
        $bd = config('database.connections.'.$driver.'.database');

        if (in_array($bd, self::BDS_PROHIBIDAS, true)) {
            throw new \LogicException(
                'Blindaje de tests: default="'.$driver.'" con BD="'.$bd.'" '
                .'(prohibida). Los tests van a tocar la BD real. '
                .'Revise .env.testing/phpunit.xml.'
            );
        }
    }

    protected function beforeRefreshingDatabase()
    {
        if (config('database.default') === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }
    }

    protected function afterRefreshingDatabase()
    {
        if (config('database.default') === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }
}
