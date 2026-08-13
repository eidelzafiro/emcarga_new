<?php

namespace Tests\Unit;

use App\Models\ConfiguracionTarifa;
use App\Models\Tarifa;
use App\Models\TipoCarga;
use App\Services\AforoCotizadorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 1 — Motor de cálculo de aforo (AforoCotizadorService).
 * Réplica 1:1 de los endpoints legacy (`Aforo.php` + `modAforo.php`).
 */
class AforoCotizadorServiceTest extends TestCase
{
    use RefreshDatabase;

    private AforoCotizadorService $servicio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->servicio = new AforoCotizadorService;
    }

    private function tipoCarga(int $id, string $nombre): void
    {
        TipoCarga::query()->forceCreate(['id' => $id, 'codigo' => (string) $id, 'nombre' => $nombre]);
    }

    private function tarifa(int $tipo, int $kms, float $tarmt, string $version = '46'): void
    {
        Tarifa::create([
            'id_tipo_carga' => $tipo,
            'kms' => $kms,
            'tarifa_mt' => $tarmt,
            'version' => $version,
        ]);
    }

    private function config(array $overrides = []): ConfiguracionTarifa
    {
        return ConfiguracionTarifa::create(array_merge([
            'demora_1' => 350,
            'demora_2' => 400,
            'kms_vacio_1' => 5,
            'kms_vacio_2' => 8,
            'tarifa_horaria_1' => 100,
            'tarifa_horaria_2' => 150,
            'kms_adicionales_1' => 20,
            'kms_adicionales_2' => 30,
            'almacenaje' => 10,
            'hora_1' => 2,
            'hora_2' => 6,
            'hora_3' => 12,
        ], $overrides));
    }

    public function test_redondeado_aplica_la_misma_semantica_del_legacy(): void
    {
        $this->assertSame(12.35, $this->servicio->redondeado(12.3456, 2));
        $this->assertSame(10.0, $this->servicio->redondeado(10.0, 2));
        $this->assertSame(0.0, $this->servicio->redondeado(0.0, 2));
    }

    public function test_tarifa_mn_puro_usa_tar_por_peso(): void
    {
        $this->tipoCarga(3, 'Contenedor');
        $this->tarifa(3, 100, 50);

        $resultado = $this->servicio->tarifa(moneda: 1, tipocarga: 3, distancia: 100, peso: 10);

        $this->assertSame(50.0, $resultado['tarmt']);
        $this->assertSame(500.0, $resultado['fletemt']); // 50 * 10
        $this->assertSame('', $resultado['fletemlc']);
    }

    public function test_tarifa_mn_puro_aplica_descuento(): void
    {
        $this->tipoCarga(3, 'Contenedor');
        $this->tarifa(3, 100, 50);

        $resultado = $this->servicio->tarifa(moneda: 1, tipocarga: 3, distancia: 100, peso: 10, descuento: 10);

        $this->assertSame(450.0, $resultado['fletemt']); // 500 - 10%
    }

    public function test_tarifa_componente_mlc_calcula_fletemlc(): void
    {
        $this->tipoCarga(3, 'Contenedor');
        $this->tarifa(3, 100, 50);

        $resultado = $this->servicio->tarifa(moneda: 2, tipocarga: 3, distancia: 100, peso: 10, mlc: 50);

        $this->assertSame(500.0, $resultado['fletemt']);
        // (500/24) * (50/100) = 10.4166...
        $this->assertSame(round((500 / 24) * 0.5, 2), $resultado['fletemlc']);
    }

    public function test_tarifa_cereales_es_tar_por_km(): void
    {
        $this->tipoCarga(18, 'Cereales');
        $this->tarifa(18, 100, 3000); // tarifa total del recorrido

        $resultado = $this->servicio->tarifa(moneda: 1, tipocarga: 18, distancia: 100, peso: 10);

        $this->assertSame(30.0, $resultado['tarmt']);  // 3000/100
        $this->assertSame(3000.0, $resultado['fletemt']); // total
    }

    public function test_tarifa_117_y_118_usan_tar_por_distancia(): void
    {
        $this->tipoCarga(117, 'Multimodal 117');
        $this->tarifa(117, 50, 40, 'normal'); // 117/118 viven en com_tarifas → versión normal

        $resultado = $this->servicio->tarifa(moneda: 1, tipocarga: 117, distancia: 50, peso: 10);

        $this->assertSame(40.0, $resultado['tarmt']);
        $this->assertSame(2000.0, $resultado['fletemt']); // 40 * 50
    }

    public function test_tarifa_contenedores_normaliza_a_3_y_4(): void
    {
        $this->tipoCarga(3, 'Contenedor');
        $this->tarifa(3, 80, 25);

        $r4 = $this->servicio->tarifa(moneda: 1, tipocarga: 4, distancia: 80, peso: 5);
        $r3 = $this->servicio->tarifa(moneda: 1, tipocarga: 3, distancia: 80, peso: 5);

        $this->assertSame($r3['fletemt'], $r4['fletemt']);
        $this->assertSame(125.0, $r4['fletemt']);
    }

    public function test_mapeo_tipos_distribucion_104_108(): void
    {
        $this->assertSame(2, $this->servicio->mapearTipoCarga(104));
        $this->assertSame(6, $this->servicio->mapearTipoCarga(105));
        $this->assertSame(1, $this->servicio->mapearTipoCarga(106));
        $this->assertSame(12, $this->servicio->mapearTipoCarga(107));
        $this->assertSame(11, $this->servicio->mapearTipoCarga(108));
        $this->assertSame(3, $this->servicio->mapearTipoCarga(3));
    }

    public function test_dif_horas_redondea_up_y_descuenta_libres(): void
    {
        $this->config();
        // 05:20 - 00:00 → 5 horas + 20 min → redondea a 6; peso 8 (hora_1=2, hora_2=6 → libre 2) → 4
        $this->assertSame(4, $this->servicio->difHoras('00:00', '05:20', 8));
    }

    public function test_calcular_th_usa_tarifa_horaria_por_capacidad(): void
    {
        $this->config(['tarifa_horaria_1' => 100, 'tarifa_horaria_2' => 150]);

        $pequeno = $this->servicio->calcularTh(tipocarga: 10, capacidad: 10, horas: 3);
        $grande = $this->servicio->calcularTh(tipocarga: 10, capacidad: 20, horas: 3);

        $this->assertSame(100.0, $pequeno['tarmt']);
        $this->assertSame(300.0, $pequeno['fth']);
        $this->assertSame(150.0, $grande['tarmt']);
        $this->assertSame(450.0, $grande['fth']);
    }

    public function test_calcular_th_114_usa_tarifas_fijas(): void
    {
        $this->config();

        $pequeno = $this->servicio->calcularTh(tipocarga: 114, capacidad: 10, horas: 2);
        $grande = $this->servicio->calcularTh(tipocarga: 114, capacidad: 20, horas: 2);

        $this->assertSame(1646.40, $pequeno['tarmt']);
        $this->assertSame(2471.60, $grande['tarmt']);
    }

    public function test_calcular_kms_costo_tarifa_fija_70(): void
    {
        $resultado = $this->servicio->calcularKmsCosto(kms: 10);
        $this->assertSame(70.0, $resultado['tarmt']);
        $this->assertSame(700.0, $resultado['fletemt']);
    }

    public function test_calcular_kms_res207_por_combustible(): void
    {
        $con_comb = $this->servicio->calcularKmsRes207(kms: 10, tons: 5, comb: true);
        $sin_comb = $this->servicio->calcularKmsRes207(kms: 10, tons: 5, comb: false);

        $this->assertSame(5.0, $con_comb['tarmt']);
        $this->assertSame(250.0, $con_comb['fletemt']); // 5*10*5
        $this->assertSame(6.30, $sin_comb['tarmt']);
    }

    public function test_calcular_kms_adicionales_por_capacidad(): void
    {
        $this->config(['kms_adicionales_1' => 20, 'kms_adicionales_2' => 30]);

        $pequeno = $this->servicio->calcularKmsAdicionales(tipocarga: 10, kms: 10, capacidad: 10, mlc: 50);
        $grande = $this->servicio->calcularKmsAdicionales(tipocarga: 10, kms: 10, capacidad: 20, mlc: 50);

        $this->assertSame(20.0, $pequeno['tarmt']);
        $this->assertSame(200.0, $pequeno['fletemt']);
        // (200/24)*0.5 = 4.17
        $this->assertSame(round((200 / 24) * 0.5, 2), $pequeno['fletemlc']);
        $this->assertSame(30.0, $grande['tarmt']);
    }

    public function test_calcular_th_efectos_usa_40_o_45(): void
    {
        $pequeno = $this->servicio->calcularThEfectos(tipocarga1: 1, capacidad: 10, horas: 4);
        $grande = $this->servicio->calcularThEfectos(tipocarga1: 1, capacidad: 20, horas: 4);

        $this->assertSame(40.0, $pequeno['tarmt']);
        $this->assertSame(160.0, $pequeno['fletemt']);
        $this->assertSame(45.0, $grande['tarmt']);
    }

    public function test_calcular_estibadores_por_piso(): void
    {
        $this->assertSame(0.05, $this->servicio->calcularEstibadores(kms: 1, flete: 1000)['tarmt']);
        $this->assertSame(0.10, $this->servicio->calcularEstibadores(kms: 2, flete: 1000)['tarmt']);
        $this->assertSame(0.15, $this->servicio->calcularEstibadores(kms: 3, flete: 1000)['tarmt']);
        $this->assertSame(0.20, $this->servicio->calcularEstibadores(kms: 4, flete: 1000)['tarmt']);
        $this->assertSame(200.0, $this->servicio->calcularEstibadores(kms: 4, flete: 1000)['fletemt']);
    }

    public function test_calcular_almacenaje_contenedor_usa_175_o_210(): void
    {
        $cont = $this->servicio->calcularAlmacenaje(alm_peso: 10, tipocarga: 3, tipocont: 1);
        $otro = $this->servicio->calcularAlmacenaje(alm_peso: 10, tipocarga: 3, tipocont: 2);

        $this->assertSame(175.0, $cont['alm_tarifa']);
        $this->assertSame(1750.0, $cont['alm_flete']);
        $this->assertSame(210.0, $otro['alm_tarifa']);
    }

    public function test_calcular_almacenaje_escalones_72_144_216(): void
    {
        $this->config(['almacenaje' => 10]);

        $a72 = $this->servicio->calcularAlmacenaje(alm_peso: 5, alm_horas: 72, tipocarga: 1);
        $a144 = $this->servicio->calcularAlmacenaje(alm_peso: 5, alm_horas: 144, tipocarga: 1);
        $a216 = $this->servicio->calcularAlmacenaje(alm_peso: 5, alm_horas: 216, tipocarga: 1);

        $this->assertSame(10.0, $a72['alm_tarifa']);
        // 72h → 10; 5*72*10
        $this->assertSame(3600.0, $a72['alm_flete']);
        // 144h → 15 (10+50%)
        $this->assertSame(15.0, $a144['alm_tarifa']);
        // 216h → round(15+7.5)=round(22.5)=23 (paridad legacy, PHP redondea .5 hacia arriba)
        $this->assertSame(23.0, $a216['alm_tarifa']);
        $this->assertSame(round(5 * 216 * 23, 2), $a216['alm_flete']);
    }

    public function test_calcular_kms_vacios_con_tarifa_anterior_menor_la_usa(): void
    {
        $this->config(['kms_vacio_1' => 5, 'kms_vacio_2' => 8]);

        $resultado = $this->servicio->calcularKmsVacios(tipocarga: 7, kms: 100, peso: 10, tarkvaciosmn1: 3);
        // peso 10 ≤15 → tarmt=5, tarifa previa 3 < 5 → usa 3
        $this->assertSame(3.0, $resultado['tarmt']);
        $this->assertSame(300.0, $resultado['fletemt']);
    }

    public function test_calcular_demora_por_tipo_carga_y_descuentos(): void
    {
        $this->config(['demora_1' => 350, 'demora_2' => 400]);

        $resultado = $this->servicio->calcularDemora(
            tipocarga1: 1,
            capacidad: 10,
            demcarga: 2,
            demdescarga: 3,
            descuento1: 10,
            descuento2: 0,
            horas: 1,
        );

        $this->assertSame(350.0, $resultado['tardem1']);
        $this->assertSame(350.0, $resultado['tardem2']);
        // fdemcarga = 350*2=700 → -10% = 630
        $this->assertSame(630.0, $resultado['fdemcarga']);
        // fdemdescarga = 350*3 = 1050
        $this->assertSame(1050.0, $resultado['fdemdescarga']);
        $this->assertSame(1680.0, $resultado['fletedemt']);
    }

    public function test_calcular_demora_117_y_118_usa_280_315(): void
    {
        $this->config();

        $pequeno = $this->servicio->calcularDemora(tipocarga1: 117, capacidad: 10, demcarga: 1, demdescarga: 0, horas: 1);
        $grande = $this->servicio->calcularDemora(tipocarga1: 118, capacidad: 20, demcarga: 1, demdescarga: 0, horas: 1);

        $this->assertSame(280.0, $pequeno['tardem1']);
        $this->assertSame(315.0, $grande['tardem1']);
    }

    public function test_calcular_tiempos_suma_hh_mm(): void
    {
        $resultado = $this->servicio->calcularTiempos(movimiento: '01:00', carga: '00:30', descarga: '00:30', otros: '00:00');
        $this->assertSame('02:00', $resultado);

        $resultado2 = $this->servicio->calcularTiempos(movimiento: '01:45', carga: '00:20');
        $this->assertSame('02:05', $resultado2);
    }
}
