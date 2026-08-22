<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
$checks = [
  ['entidades','id_municipio','municipios','id'],
  ['entidades','id_provincia','provincias','id'],
  ['osdes','id_organismo','organismos','id'],
  ['tipos_penalizaciones','area_id','areas','id'],
  ['tipos_penalizaciones','tipo_pago_adicional_id','tipos_pagos_adicionales','id'],
];
foreach($checks as [$t,$c,$rt,$rc]){
  $vals = DB::table($t)->whereNotNull($c)->whereNotIn($c, function($q) use ($rt,$rc){ $q->select($rc)->from($rt); })->select($c, DB::raw('COUNT(*) as n'))->groupBy($c)->orderBy($c)->get();
  echo "$t.$c -> valores huerfanos: ".json_encode($vals->map(fn($r)=>['val'=>$r->$c,'n'=>$r->n])) . "\n";
}
