<?php

namespace App\Http\Requests\ZonaComun;

use Illuminate\Foundation\Http\FormRequest;

class StoreZonaComunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:120'],
            'descripcion' => ['nullable', 'string'],
            'capacidad' => ['nullable', 'integer', 'min:1'],
            'tipo' => ['required', 'string', 'max:50'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }
}
