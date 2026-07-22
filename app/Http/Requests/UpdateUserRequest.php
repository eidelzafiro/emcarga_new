<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * La autorización se verifica vía UserPolicy en el controlador.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normaliza el username a mayúsculas antes de validar
     * (paridad con el legacy y consistencia con la BD).
     */
    protected function prepareForValidation(): void
    {
        if ($this->username) {
            $this->merge(['username' => strtoupper($this->username)]);
        }
    }

    /**
     * Reglas para editar un usuario (la contraseña se gestiona aparte,
     * en la acción de restablecer).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('users', 'username')->ignore($userId)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'role' => ['required', 'string', 'exists:roles,name'],
            'idunidad' => ['nullable', 'integer'],
            'idgrupo' => ['nullable', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'username.required' => 'El usuario es obligatorio.',
            'username.alpha_dash' => 'El usuario solo puede contener letras, números, guiones y guiones bajos.',
            'username.unique' => 'El usuario ya existe.',
            'email.email' => 'El correo no tiene un formato válido.',
            'email.unique' => 'El correo ya está registrado.',
            'role.required' => 'Debe seleccionar un perfil.',
            'role.exists' => 'El perfil seleccionado no es válido.',
        ];
    }
}
