<?php

namespace App\Http\Requests\Inmueble;

use App\Models\Inmueble;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInmuebleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $inmueble = $this->route('inmueble');
        $inmuebleId = $inmueble instanceof Inmueble ? $inmueble->id : null;

        return [
            'nomenclatura' => ['required', 'string', 'max:50', Rule::unique('inmuebles', 'nomenclatura')->ignore($inmuebleId)],
            'coeficiente' => ['required', 'numeric', 'min:0', 'max:100'],
            'tipo' => ['required', 'string', 'max:50'],
            'propietario_documento' => ['nullable', 'string', 'max:50'],
            'propietario_nombre' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'activo' => ['required', 'boolean'],
        ];
    }
}
