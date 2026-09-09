<?php

namespace App\Services\Reports;

use App\Models\Aforo;
use App\Models\CartaPorte;
use App\Models\Cliente;
use App\Models\Entidad;
use App\Models\HojasRuta;
use App\Models\Lugare;
use App\Models\Producto;
use App\Models\Tractivo;
use Illuminate\Support\Facades\Response;

/**
 * Grupo EXPORTAR TABLAS (migrado en R-1): vuelca tablas maestras a CSV
 * descargable (sin dependencia de PhpSpreadsheet). Los ids 1075-1082.
 */
class ExportarTablasReportService
{
    private function entidadIds(): array
    {
        $activa = (int) entidadActivaId();
        if (! $activa) {
            return [23];
        }

        return Entidad::idsPermitidos($activa);
    }

    private function csv(string $filename, array $headers, array $rows): \Illuminate\Http\Response
    {
        $out = fopen('php://temp', 'r+');
        fputcsv($out, $headers);
        foreach ($rows as $r) {
            fputcsv($out, $r);
        }
        rewind($out);
        $content = stream_get_contents($out);
        fclose($out);

        return Response::make($content, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    // 1075 — EXPORTAR TABLA CLIENTES
    public function clientes(array $filtros): \Illuminate\Http\Response
    {
        $ids = $this->entidadIds();
        $rows = Cliente::whereIn('id_entidad', $ids)->orderBy('nombre')
            ->get(['codigo', 'nombre', 'razon_social', 'nit', 'direccion', 'telefono', 'email', 'nrocontrato']);

        return $this->csv('clientes.csv',
            ['Código', 'Nombre', 'Razón Social', 'NIT', 'Dirección', 'Teléfono', 'Email', 'Contrato'],
            $rows->map(fn ($c) => [$c->codigo, $c->nombre, $c->razon_social, $c->nit, $c->direccion, $c->telefono, $c->email, $c->nrocontrato])->all());
    }

    // 1076 — EXPORTAR TABLA ORGANISMOS
    public function organismos(array $filtros): \Illuminate\Http\Response
    {
        $ids = $this->entidadIds();
        $rows = Cliente::whereIn('id_entidad', $ids)->orderBy('nombre')
            ->get(['codigo', 'nombre', 'razon_social', 'nit', 'direccion', 'telefono', 'email']);

        return $this->csv('organismos.csv',
            ['Código', 'Nombre', 'Razón Social', 'NIT', 'Dirección', 'Teléfono', 'Email'],
            $rows->map(fn ($c) => [$c->codigo, $c->nombre, $c->razon_social, $c->nit, $c->direccion, $c->telefono, $c->email])->all());
    }

    // 1077 — EXPORTAR TABLA LUGARES
    public function lugares(array $filtros): \Illuminate\Http\Response
    {
        $rows = Lugare::orderBy('nombre')
            ->get(['codigo', 'nombre', 'provincia', 'municipio', 'direccion', 'personalidad', 'activo']);

        return $this->csv('lugares.csv',
            ['Código', 'Nombre', 'Provincia', 'Municipio', 'Dirección', 'Personalidad', 'Activo'],
            $rows->map(fn ($l) => [$l->codigo, $l->nombre, $l->provincia, $l->municipio, $l->direccion, $l->personalidad, $l->activo ? 'Sí' : 'No'])->all());
    }

    // 1078 — EXPORTAR TABLA PRODUCTOS
    public function productos(array $filtros): \Illuminate\Http\Response
    {
        $rows = Producto::orderBy('id')->get(['id', 'nombre']);

        return $this->csv('productos.csv',
            ['Id', 'Nombre'],
            $rows->map(fn ($p) => [$p->id, $p->nombre])->all());
    }

    // 1079 — EXPORTAR TABLA GIRADO (Cartas de Porte)
    public function girado(array $filtros): \Illuminate\Http\Response
    {
        $rows = CartaPorte::orderBy('fecha_emision')
            ->get(['numero', 'fecha_emision', 'toneladas', 'distancia', 'conduce', 'estado']);

        return $this->csv('girado.csv',
            ['Número', 'Fecha Emisión', 'Toneladas', 'Distancia', 'Conduce', 'Estado'],
            $rows->map(fn ($c) => [$c->numero, optional($c->fecha_emision)?->format('d/m/Y'), $c->toneladas, $c->distancia, $c->conduce, $c->estado])->all());
    }

    // 1080 — EXPORTAR TABLA AFORO
    public function aforo(array $filtros): \Illuminate\Http\Response
    {
        $rows = Aforo::orderBy('fecha_parte')
            ->get(['id', 'fecha_parte', 'flete_mt', 'flete_mlc', 'flete_demora', 'otros_mt', 'ingreso_mt', 'descuento']);

        return $this->csv('aforo.csv',
            ['Id', 'Fecha Parte', 'Flete MN', 'Flete MLC', 'Demora', 'Otros MN', 'Ingreso MN', 'Descuento'],
            $rows->map(fn ($a) => [$a->id, optional($a->fecha_parte)?->format('d/m/Y'), $a->flete_mt, $a->flete_mlc, $a->flete_demora, $a->otros_mt, $a->ingreso_mt, $a->descuento])->all());
    }

    // 1081 — EXPORTAR TABLA HOJA RUTAS
    public function hojaRutas(array $filtros): \Illuminate\Http\Response
    {
        $ids = $this->entidadIds();
        $rows = HojasRuta::with('tractivo:id,placa')->whereIn('id_entidad', $ids)->orderBy('fecha_emision')
            ->get(['numero', 'fecha_emision', 'id_tractivo', 'estado']);

        return $this->csv('hoja_rutas.csv',
            ['Número', 'Fecha Emisión', 'Tractivo', 'Estado'],
            $rows->map(fn ($h) => [$h->numero, optional($h->fecha_emision)?->format('d/m/Y'), optional($h->tractivo)->placa, $h->estado])->all());
    }

    // 1082 — EXPORTAR TABLA TRACTIVOS
    public function tractivos(array $filtros): \Illuminate\Http\Response
    {
        $ids = $this->entidadIds();
        $rows = Tractivo::whereIn('id_entidad', $ids)->orderBy('codigo')
            ->get(['codigo', 'descripcion', 'placa', 'marca', 'modelo', 'anno', 'capacidad_toneladas', 'estado', 'kilometraje_actual']);

        return $this->csv('tractivos.csv',
            ['Código', 'Descripción', 'Placa', 'Marca', 'Modelo', 'Año', 'Cap. Ton', 'Estado', 'Kms'],
            $rows->map(fn ($t) => [$t->codigo, $t->descripcion, $t->placa, $t->marca, $t->modelo, $t->anno, $t->capacidad_toneladas, $t->estado, $t->kilometraje_actual])->all());
    }
}
