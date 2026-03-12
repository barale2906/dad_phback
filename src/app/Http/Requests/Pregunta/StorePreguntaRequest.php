<?php

namespace App\Http\Requests\Pregunta;

use Illuminate\Foundation\Http\FormRequest;

class StorePreguntaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'reunion_id' => ['required', 'exists:reuniones,id'],
            'pregunta' => ['required', 'string', 'max:1000'],
            'tipo' => ['sometimes', 'in:VOTACION,QUORUM_CHECK'],
            'estado' => ['sometimes', 'in:inactiva,abierta,cerrada,cancelada'],
            'orden' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ];
    }
}
