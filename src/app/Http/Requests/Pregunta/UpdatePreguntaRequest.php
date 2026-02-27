<?php

namespace App\Http\Requests\Pregunta;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePreguntaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'pregunta' => ['required', 'string', 'max:1000'],
            'estado' => ['required', 'in:inactiva,abierta,cerrada,cancelada'],
            'orden' => ['required', 'integer', 'min:1'],
        ];
    }
}
