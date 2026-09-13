<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Usuario expuesto a la API móvil (sin datos sensibles).
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'nombre_completo' => $this->nombre_completo ?? trim(($this->name ?? '').' '.($this->apellidos ?? '')),
            'email' => $this->email,
            'id_entidad' => $this->id_entidad,
            'roles' => $this->getRoleNames(),
            'password_temporal' => (bool) $this->password_temporal,
        ];
    }
}
