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
        $isPatch = $this->isMethod('PATCH');

        return [
            'pregunta' => [$isPatch ? 'sometimes' : 'required', 'string', 'max:1000'],
            'tipo'     => ['sometimes', 'in:VOTACION,QUORUM_CHECK'],
            'estado'   => [$isPatch ? 'sometimes' : 'required', 'in:inactiva,abierta,cerrada,cancelada'],
            'orden'    => [$isPatch ? 'sometimes' : 'required', 'integer', 'min:1'],
        ];
    }
}
