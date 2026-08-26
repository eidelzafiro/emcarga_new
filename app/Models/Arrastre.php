<?php

namespace App\Models;

/**
 * Representa un arrastre. Físicamente sigue siendo una fila de `tractivos`
 * (los arrastres son tractivos con id_grupo = grupo ARRASTRES), pero se
 * registra en el morph map como 'arrastre' para que las tablas polimórficas
 * (vehiculos_amortizacion, vehiculos_planes, vehiculos_documentacion) puedan
 * identificarlo con vehiculo_type='arrastre' sin necesidad de una tabla propia.
 */
class Arrastre extends Tractivo
{
    protected $table = 'arrastres';
}
