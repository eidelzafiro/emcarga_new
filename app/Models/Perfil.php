<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Perfil extends Model
{
    protected $table = 'perfiles';

    protected $fillable = ['nombre', 'descripcion'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'idperfil');
    }
}
