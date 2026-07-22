<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePerfilRequest extends FormRequest
{
    /**
     * La autorización se verifica vía RolePolicy en el controlador.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normaliza el nombre del perfil a mayúsculas (paridad con los
     * perfiles legacy: RECHUM, TECNICA, etc.).
     */
    protected function prepareForValidation(): void
    {
        if ($this->nombre) {
            $this->merge(['nombre' => strtoupper($this->nombre)]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100', 'alpha_dash', 'unique:roles,name'],
            'permisos' => ['nullable', 'array'],
            'permisos.*' => ['string', 'exists:permissions,name'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del perfil es obligatorio.',
            'nombre.alpha_dash' => 'El nombre solo puede contener letras, números, guiones y guiones bajos.',
            'nombre.unique' => 'Ya existe un perfil con ese nombre.',
            'permisos.*.exists' => 'Uno de los permisos seleccionados no es válido.',
        ];
    }
}
