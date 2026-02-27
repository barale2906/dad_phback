<?php

namespace App\Http\Requests\UserInmueble;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserInmuebleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'inmueble_id' => ['required', 'exists:inmuebles,id'],
            'relacion' => ['required', 'in:PROPIETARIO,RESIDENTE,ARRENDATARIO,APODERADO'],
            'es_principal' => ['sometimes', 'boolean'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
        ];
    }
}
