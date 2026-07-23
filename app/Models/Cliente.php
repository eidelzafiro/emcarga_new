<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Cliente extends Model {
    use SoftDeletes;
    protected $table = 'clientes';
    protected $fillable = ['codigo', 'nombre', 'razon_social', 'nit', 'direccion', 'telefono', 'email', 'contacto', 'activo'];
    protected function casts(): array { return ['activo' => 'boolean']; }
}
