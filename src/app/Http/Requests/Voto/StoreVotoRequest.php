<?php

namespace App\Http\Requests\Voto;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'pregunta_id' => ['required', 'integer', 'exists:preguntas,id'],
            'opcion_id' => [
                'required',
                'integer',
                Rule::exists('opciones', 'id')->where('pregunta_id', $this->integer('pregunta_id')),
            ],
            'inmueble_id' => ['nullable', 'integer', 'exists:inmuebles,id'],
            'asistente_id' => ['nullable', 'integer', 'exists:asistentes,id'],
            'telefono' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (! $this->has('inmueble_id') && ! $this->has('asistente_id')) {
                $validator->errors()->add('inmueble_id', 'Debe enviar inmueble_id o asistente_id para registrar el voto.');
            }
        });
    }
}

