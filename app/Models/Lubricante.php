<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Lubricante extends Model {
    use SoftDeletes;
    protected $table = 'lubricantes';
    protected $fillable = ['codigo', 'nombre', 'tipo', 'viscosidad', 'costo_litro', 'activo'];
    protected function casts(): array { return ['activo' => 'boolean', 'costo_litro' => 'decimal:2']; }
}
