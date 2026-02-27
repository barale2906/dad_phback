<?php

namespace App\Http\Requests\Timer;

use Illuminate\Foundation\Http\FormRequest;

class StoreTimerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'reunion_id' => ['required', 'exists:reuniones,id'],
            'tipo' => ['required', 'in:INTERVENCION,VOTACION'],
            'duracion_segundos' => ['required', 'integer', 'min:1', 'max:3600'],
            'estado' => ['sometimes', 'in:inactivo,activo,pausado,finalizado'],
            'interviniente_nombre' => ['nullable', 'string', 'max:255'],
            'interviniente_asistente_id' => ['nullable', 'exists:asistentes,id'],
        ];
    }
}
