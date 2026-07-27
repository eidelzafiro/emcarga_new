<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
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
     * Reglas para crear un usuario. La contraseña indicada es temporal:
     * el usuario deberá cambiarla en su primer acceso.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', 'alpha_dash', 'unique:users,username'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', 'string', 'exists:roles,name'],
            'id_entidad' => ['nullable', 'integer', 'exists:entidades,id'],
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
            'password.required' => 'Debe indicar la contraseña temporal.',
            'password.min' => 'La contraseña temporal debe tener al menos 6 caracteres.',
            'role.required' => 'Debe seleccionar un perfil.',
            'role.exists' => 'El perfil seleccionado no es válido.',
        ];
    }
}
