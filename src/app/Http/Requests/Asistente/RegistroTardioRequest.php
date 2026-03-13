<?php

namespace App\Http\Requests\Asistente;

use Illuminate\Foundation\Http\FormRequest;

class RegistroTardioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'telefono'                     => ['nullable', 'string', 'max:20'],
            'codigo_barras'                => ['nullable', 'integer', 'min:1'],
            'inmuebles'                    => ['required', 'array', 'min:1'],
            'inmuebles.*.inmueble_id'      => ['required', 'exists:inmuebles,id'],
            'inmuebles.*.coeficiente'      => ['nullable', 'numeric', 'min:0', 'max:100'],
            'inmuebles.*.poder_url'        => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $tieneTelefono = filled($this->input('telefono'));
            $tieneBarcode  = filled($this->input('codigo_barras'));

            if (! $tieneTelefono && ! $tieneBarcode) {
                $validator->errors()->add(
                    'identificador',
                    'Debe indicar al menos telefono o codigo_barras para identificar al asistente.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'inmuebles.required'                => 'Debe asociar al menos un inmueble al asistente.',
            'inmuebles.*.inmueble_id.required'  => 'Cada inmueble debe tener un inmueble_id válido.',
            'inmuebles.*.inmueble_id.exists'    => 'El inmueble indicado no existe.',
        ];
    }
}
