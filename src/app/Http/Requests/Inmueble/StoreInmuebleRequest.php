<?php

namespace App\Http\Requests\Inmueble;

use Illuminate\Foundation\Http\FormRequest;

class StoreInmuebleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'nomenclatura' => ['required', 'string', 'max:50', 'unique:inmuebles,nomenclatura'],
            'coeficiente' => ['required', 'numeric', 'min:0', 'max:100'],
            'tipo' => ['required', 'string', 'max:50'],
            'propietario_documento' => ['nullable', 'string', 'max:50'],
            'propietario_nombre' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }
}
