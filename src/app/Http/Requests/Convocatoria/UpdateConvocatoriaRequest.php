<?php

namespace App\Http\Requests\Convocatoria;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConvocatoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'fecha_convocatoria' => ['required', 'date'],
            'medio' => ['required', 'in:email,fisico,whatsapp,mixto'],
            'contenido' => ['required', 'string'],
            'orden_dia_snapshot' => ['nullable', 'string'],
            'fecha_limite_legal' => ['nullable', 'date'],
            'estado' => ['required', 'in:borrador,enviada,publicada,cerrada'],
        ];
    }
}
