<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Subsistema extends Model {
    use SoftDeletes;
    protected $table = 'subsistemas';
    protected $fillable = ['codigo', 'nombre', 'descripcion', 'activo'];
    protected function casts(): array { return ['activo' => 'boolean']; }
}
