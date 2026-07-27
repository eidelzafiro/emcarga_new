<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('menus.crear');
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:100'],
            'parent_id' => ['nullable', 'integer', 'exists:menu_items,id'],
            'icon' => ['nullable', 'string', 'max:50'],
            'route' => ['nullable', 'string', 'max:150'],
            'permission' => ['nullable', 'string', 'max:150'],
            'orden' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'label.required' => 'La etiqueta del menú es obligatoria.',
            'parent_id.exists' => 'El padre seleccionado no es válido.',
        ];
    }
}
