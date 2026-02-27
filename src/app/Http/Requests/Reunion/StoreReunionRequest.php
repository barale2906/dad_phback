<?php

namespace App\Http\Requests\Reunion;

use Illuminate\Foundation\Http\FormRequest;

class StoreReunionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', 'in:ordinaria,extraordinaria'],
            'fecha' => ['required', 'date'],
            'hora' => ['required', 'date_format:H:i'],
            'modalidad' => ['required', 'in:presencial,virtual,mixta'],
            'ente' => ['required', 'in:ASAMBLEA,CONSEJO,ADMINISTRADOR,CONTADOR'],
            'estado' => ['sometimes', 'in:programada,en_curso,finalizada,cancelada'],
            'zona_comun_ids' => ['sometimes', 'array'],
            'zona_comun_ids.*' => ['integer', 'exists:zonas_comunes,id'],
        ];
    }
}
