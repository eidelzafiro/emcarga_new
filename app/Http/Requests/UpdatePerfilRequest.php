<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePerfilRequest extends FormRequest
{
    /**
     * La autorización se verifica vía RolePolicy en el controlador.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normaliza el nombre del perfil a mayúsculas (paridad legacy).
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
        $roleId = $this->route('perfil')?->id;

        return [
            'nombre' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('roles', 'name')->ignore($roleId)],
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
