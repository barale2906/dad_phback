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
            'estado' => ['sometimes', 'in:inactiva,abierta,cerrada,cancelada'],
            'orden' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
