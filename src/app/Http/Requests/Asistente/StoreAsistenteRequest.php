<?php

namespace App\Http\Requests\Asistente;

use Illuminate\Foundation\Http\FormRequest;

class StoreAsistenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'usuario_id' => ['nullable', 'exists:users,id'],
            'nombre' => ['required', 'string', 'max:255'],
            'documento' => ['nullable', 'string', 'max:50'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'barcode_numero' => ['nullable', 'integer', 'min:1'],
            'tipo_asistente' => ['required', 'in:PROPIETARIO,RESIDENTE,APODERADO,INVITADO'],
            'inmuebles' => ['required', 'array', 'min:1'],
            'inmuebles.*.inmueble_id' => ['required', 'exists:inmuebles,id'],
            'inmuebles.*.coeficiente' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'inmuebles.*.poder_url' => ['nullable', 'string', 'max:255'],
        ];
    }
}
