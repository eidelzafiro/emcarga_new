<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Ajuste extends Model {
    protected $table = 'ajustes';
    protected $fillable = ['id_giro', 'concepto', 'monto', 'tipo'];
}
